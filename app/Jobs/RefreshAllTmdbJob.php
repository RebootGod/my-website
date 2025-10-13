<?php

namespace App\Jobs;

use App\Services\ContentBulkOperationService;
use App\Models\Movie;
use App\Models\Series;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Queue Job: Refresh All TMDB Data in Background
 * 
 * Processes large TMDB refresh operations without blocking HTTP request
 * Updates progress in cache for real-time tracking
 * 
 * Security: Transaction-based, validated inputs
 * 
 * @package App\Jobs
 */
class RefreshAllTmdbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour for large datasets
    public $tries = 1; // No retry - manual re-trigger if needed
    public $backoff = 0;

    protected string $type;
    protected ?string $status;
    protected ?int $limit;
    protected string $progressKey;
    protected array $ids;
    protected int $batchSize = 5; // Process 5 items at a time

    /**
     * Create a new job instance.
     *
     * @param string $type Content type (movie/series)
     * @param array $ids Array of content IDs to refresh
     * @param string $progressKey Cache key for progress tracking
     * @param string|null $status Filter by status (published/unpublished)
     * @param int|null $limit Limit number of items
     */
    public function __construct(
        string $type,
        array $ids,
        string $progressKey,
        ?string $status = null,
        ?int $limit = null
    ) {
        $this->type = $type;
        $this->ids = $ids;
        $this->progressKey = $progressKey;
        $this->status = $status;
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(ContentBulkOperationService $bulkService): void
    {
        Log::info("🚀 RefreshAllTmdbJob STARTED", [
            'type' => $this->type,
            'total_ids' => count($this->ids),
            'batch_size' => $this->batchSize,
            'status_filter' => $this->status,
            'limit' => $this->limit,
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

            Log::info("📦 Processing in batches", [
                'total_batches' => $totalBatches,
                'batch_size' => $this->batchSize
            ]);

            // Initialize progress
            $this->updateBatchProgress(
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
                
                Log::info("📦 Processing batch {$currentBatch}/{$totalBatches}", [
                    'batch_ids' => $batchIds,
                    'batch_size' => count($batchIds)
                ]);

                // Update progress: Currently processing this batch
                $this->updateBatchProgress(
                    $processedCount,
                    $successCount,
                    $failedCount,
                    $totalItems,
                    $currentBatch,
                    $totalBatches,
                    $errors,
                    false,
                    $batchIds // Current batch being processed
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

                Log::info("✅ Batch {$currentBatch}/{$totalBatches} completed", [
                    'batch_success' => $result['success'],
                    'batch_failed' => $result['failed'],
                    'total_success' => $successCount,
                    'total_failed' => $failedCount,
                    'progress' => round(($processedCount / $totalItems) * 100, 1) . '%'
                ]);

                // Update progress after batch
                $this->updateBatchProgress(
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
                    usleep(100000); // 0.1 second delay
                }
            }

            Log::info("✅ RefreshAllTmdbJob COMPLETED", [
                'type' => $this->type,
                'total_processed' => $processedCount,
                'success' => $successCount,
                'failed' => $failedCount
            ]);

            // Mark as completed
            $this->updateBatchProgress(
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
            Log::error("❌ RefreshAllTmdbJob FAILED", [
                'type' => $this->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update progress with error
            $this->updateBatchProgress(
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
        Log::error("💀 RefreshAllTmdbJob PERMANENTLY FAILED", [
            'type' => $this->type,
            'progress_key' => $this->progressKey,
            'error' => $exception->getMessage()
        ]);

        // Mark progress as failed
        $this->updateBatchProgress(
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
     * Update batch progress in cache with detailed tracking
     */
    protected function updateBatchProgress(
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
            
            // Errors
            'error' => $errorMessage,
            'errors' => array_slice($errors, -10), // Last 10 errors only
            'total_errors' => count($errors),
            
            // Timestamps
            'updated_at' => now()->toISOString()
        ];

        Cache::put($this->progressKey, $progress, now()->addHours(2));

        Log::debug("📊 Batch progress updated", [
            'key' => $this->progressKey,
            'batch' => "{$currentBatch}/{$totalBatches}",
            'processed' => "{$processed}/{$totalItems}",
            'success' => $success,
            'failed' => $failed,
            'waiting' => $waiting,
            'percentage' => $progress['percentage'] . '%'
        ]);
    }
}
