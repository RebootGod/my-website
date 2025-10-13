<?php

namespace App\Jobs;

use App\Services\ContentBulkOperationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Queue Job: Refresh TMDB Data via Bulk Action
 * 
 * Dedicated job for bulk action Refresh TMDB (selected items)
 * Independent from "Refresh All TMDB" button operations
 * Handles both movies and series based on type parameter
 * 
 * File naming: RefreshAllTmdb_BulkAction_Job.php (per workinginstruction.md)
 * Max lines: 350 (per workinginstruction.md)
 * 
 * Security: Database queue only, validated inputs, transaction-based
 * 
 * @package App\Jobs
 */
class RefreshAllTmdb_BulkAction_Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour for large datasets
    public $tries = 1; // No retry - manual re-trigger if needed
    public $backoff = 0;
    public $queue = 'default'; // Database queue only

    protected string $type; // 'movie' or 'series'
    protected array $ids;
    protected string $progressKey;
    protected int $batchSize = 5; // Process 5 items at a time

    /**
     * Create a new job instance.
     *
     * @param string $type Content type (movie or series)
     * @param array $ids Array of content IDs to refresh
     * @param string $progressKey Cache key for progress tracking
     */
    public function __construct(
        string $type,
        array $ids,
        string $progressKey
    ) {
        $this->type = $type;
        $this->ids = $ids;
        $this->progressKey = $progressKey;
        $this->onConnection('database'); // Force database queue
    }

    /**
     * Execute the job.
     */
    public function handle(ContentBulkOperationService $bulkService): void
    {
        $emoji = $this->type === 'movie' ? '🎬' : '📺';
        
        Log::info("{$emoji} RefreshAllTmdb_BulkAction_Job STARTED", [
            'type' => $this->type,
            'total_items' => count($this->ids),
            'batch_size' => $this->batchSize,
            'progress_key' => $this->progressKey
        ]);

        try {
            $totalItems = count($this->ids);
            $successCount = 0;
            $failedCount = 0;
            $processedCount = 0;
            $errors = [];

            // Split IDs into batches
            $batches = array_chunk($this->ids, $this->batchSize);
            $totalBatches = count($batches);

            Log::info("📦 Processing {$this->type} bulk action in {$totalBatches} batches", [
                'batch_size' => $this->batchSize
            ]);

            // Initialize progress
            $this->updateProgress(
                $processedCount,
                $successCount,
                $failedCount,
                $totalItems,
                0,
                $totalBatches,
                [],
                false
            );

            // Process each batch
            foreach ($batches as $batchIndex => $batchIds) {
                $currentBatch = $batchIndex + 1;
                
                Log::info("{$emoji} Processing bulk action batch {$currentBatch}/{$totalBatches}", [
                    'type' => $this->type,
                    'ids' => $batchIds,
                    'batch_size' => count($batchIds)
                ]);

                // Update progress: Currently processing this batch
                $this->updateProgress(
                    $processedCount,
                    $successCount,
                    $failedCount,
                    $totalItems,
                    $currentBatch,
                    $totalBatches,
                    $errors,
                    false,
                    $batchIds
                );

                // Process batch items
                $result = $bulkService->bulkRefreshFromTMDB($this->type, $batchIds);

                // Update counters
                $successCount += $result['success'];
                $failedCount += $result['failed'];
                $processedCount += count($batchIds);

                // Collect errors
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }

                Log::info("✅ Bulk action batch {$currentBatch}/{$totalBatches} completed", [
                    'type' => $this->type,
                    'batch_success' => $result['success'],
                    'batch_failed' => $result['failed'],
                    'total_success' => $successCount,
                    'total_failed' => $failedCount,
                    'progress' => round(($processedCount / $totalItems) * 100, 1) . '%'
                ]);

                // Update progress after batch
                $this->updateProgress(
                    $processedCount,
                    $successCount,
                    $failedCount,
                    $totalItems,
                    $currentBatch,
                    $totalBatches,
                    $errors,
                    false
                );

                // Small delay between batches to prevent overwhelming TMDB API
                if ($currentBatch < $totalBatches) {
                    sleep(1); // 1 second delay between batches
                }
            }

            Log::info("✅ RefreshAllTmdb_BulkAction_Job COMPLETED", [
                'type' => $this->type,
                'total_processed' => $processedCount,
                'success' => $successCount,
                'failed' => $failedCount
            ]);

            // Mark as completed
            $this->updateProgress(
                $processedCount,
                $successCount,
                $failedCount,
                $totalItems,
                $totalBatches,
                $totalBatches,
                $errors,
                true
            );

        } catch (\Exception $e) {
            Log::error("❌ RefreshAllTmdb_BulkAction_Job FAILED", [
                'type' => $this->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update progress with error
            $this->updateProgress(
                0,
                0,
                count($this->ids),
                count($this->ids),
                0,
                0,
                [['error' => $e->getMessage()]],
                true,
                [],
                'Job failed: ' . $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("💀 RefreshAllTmdb_BulkAction_Job PERMANENTLY FAILED", [
            'type' => $this->type,
            'progress_key' => $this->progressKey,
            'error' => $exception->getMessage()
        ]);

        // Mark progress as failed
        $this->updateProgress(
            0,
            0,
            count($this->ids),
            count($this->ids),
            0,
            0,
            [['error' => 'Job permanently failed: ' . $exception->getMessage()]],
            true,
            [],
            'Job permanently failed: ' . $exception->getMessage()
        );
    }

    /**
     * Update progress in cache with detailed tracking
     */
    protected function updateProgress(
        int $processed,
        int $success,
        int $failed,
        int $totalItems,
        int $currentBatch,
        int $totalBatches,
        array $errors,
        bool $completed = false,
        array $currentProcessing = [],
        ?string $errorMessage = null
    ): void {
        $waiting = $totalItems - $processed;
        
        $progress = [
            // Overall progress
            'total' => $totalItems,
            'processed' => $processed,
            'success' => $success,
            'failed' => $failed,
            'waiting' => $waiting,
            
            // Batch progress
            'current_batch' => $currentBatch,
            'total_batches' => $totalBatches,
            'batch_size' => $this->batchSize,
            'current_processing' => $currentProcessing,
            'current_processing_count' => count($currentProcessing),
            
            // Status
            'completed' => $completed,
            'status' => $completed ? 'completed' : 'processing',
            'percentage' => $totalItems > 0 ? round(($processed / $totalItems) * 100, 1) : 0,
            
            // Type indicator
            'type' => $this->type,
            'source' => 'bulk_action', // Identifier for bulk action source
            
            // Errors
            'error' => $errorMessage,
            'errors' => array_slice($errors, -10), // Last 10 errors only
            'total_errors' => count($errors),
            
            // Timestamps
            'updated_at' => now()->toISOString()
        ];

        Cache::put($this->progressKey, $progress, now()->addHours(2));

        Log::debug("📊 Bulk action progress updated", [
            'type' => $this->type,
            'key' => $this->progressKey,
            'batch' => "{$currentBatch}/{$totalBatches}",
            'processed' => "{$processed}/{$totalItems}",
            'percentage' => $progress['percentage'] . '%'
        ]);
    }
}
