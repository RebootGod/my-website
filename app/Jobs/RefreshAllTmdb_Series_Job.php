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
 * Queue Job: Refresh TMDB Data for Series Only
 * 
 * Dedicated job for refreshing series data from TMDB
 * Independent from movies and bulk action operations
 * 
 * File naming: RefreshAllTmdb_Series_Job.php (per workinginstruction.md)
 * Max lines: 350 (per workinginstruction.md)
 * 
 * Security: Database queue only, validated inputs, transaction-based
 * 
 * @package App\Jobs
 */
class RefreshAllTmdb_Series_Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour for large datasets
    public $tries = 1; // No retry - manual re-trigger if needed
    public $backoff = 0;

    protected array $seriesIds;
    protected string $progressKey;
    protected int $batchSize = 5; // Process 5 series at a time

    /**
     * Create a new job instance.
     *
     * @param array $seriesIds Array of series IDs to refresh
     * @param string $progressKey Cache key for progress tracking
     */
    public function __construct(
        array $seriesIds,
        string $progressKey
    ) {
        $this->seriesIds = $seriesIds;
        $this->progressKey = $progressKey;
        $this->onConnection('database'); // Force database queue
    }

    /**
     * Execute the job.
     */
    public function handle(ContentBulkOperationService $bulkService): void
    {
        Log::info("📺 RefreshAllTmdb_Series_Job STARTED", [
            'total_series' => count($this->seriesIds),
            'batch_size' => $this->batchSize,
            'progress_key' => $this->progressKey
        ]);

        try {
            $totalSeries = count($this->seriesIds);
            $successCount = 0;
            $failedCount = 0;
            $processedCount = 0;
            $errors = [];

            // Split series IDs into batches
            $batches = array_chunk($this->seriesIds, $this->batchSize);
            $totalBatches = count($batches);

            Log::info("📦 Processing series in {$totalBatches} batches", [
                'batch_size' => $this->batchSize
            ]);

            // Initialize progress
            $this->updateProgress(
                $processedCount,
                $successCount,
                $failedCount,
                $totalSeries,
                0,
                $totalBatches,
                [],
                false
            );

            // Process each batch
            foreach ($batches as $batchIndex => $batchSeriesIds) {
                $currentBatch = $batchIndex + 1;
                
                Log::info("📺 Processing series batch {$currentBatch}/{$totalBatches}", [
                    'series_ids' => $batchSeriesIds,
                    'batch_size' => count($batchSeriesIds)
                ]);

                // Update progress: Currently processing this batch
                $this->updateProgress(
                    $processedCount,
                    $successCount,
                    $failedCount,
                    $totalSeries,
                    $currentBatch,
                    $totalBatches,
                    $errors,
                    false,
                    $batchSeriesIds
                );

                // Process batch series
                $result = $bulkService->bulkRefreshFromTMDB('series', $batchSeriesIds);

                // Update counters
                $successCount += $result['success'];
                $failedCount += $result['failed'];
                $processedCount += count($batchSeriesIds);

                // Collect errors
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }

                Log::info("✅ Series batch {$currentBatch}/{$totalBatches} completed", [
                    'batch_success' => $result['success'],
                    'batch_failed' => $result['failed'],
                    'total_success' => $successCount,
                    'total_failed' => $failedCount,
                    'progress' => round(($processedCount / $totalSeries) * 100, 1) . '%'
                ]);

                // Update progress after batch
                $this->updateProgress(
                    $processedCount,
                    $successCount,
                    $failedCount,
                    $totalSeries,
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

            Log::info("✅ RefreshAllTmdb_Series_Job COMPLETED", [
                'total_processed' => $processedCount,
                'success' => $successCount,
                'failed' => $failedCount
            ]);

            // Mark as completed
            $this->updateProgress(
                $processedCount,
                $successCount,
                $failedCount,
                $totalSeries,
                $totalBatches,
                $totalBatches,
                $errors,
                true
            );

        } catch (\Exception $e) {
            Log::error("❌ RefreshAllTmdb_Series_Job FAILED", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update progress with error
            $this->updateProgress(
                0,
                0,
                count($this->seriesIds),
                count($this->seriesIds),
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
        Log::error("💀 RefreshAllTmdb_Series_Job PERMANENTLY FAILED", [
            'progress_key' => $this->progressKey,
            'error' => $exception->getMessage()
        ]);

        // Mark progress as failed
        $this->updateProgress(
            0,
            0,
            count($this->seriesIds),
            count($this->seriesIds),
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
        int $totalSeries,
        int $currentBatch,
        int $totalBatches,
        array $errors,
        bool $completed = false,
        array $currentProcessing = [],
        ?string $errorMessage = null
    ): void {
        $waiting = $totalSeries - $processed;
        
        $progress = [
            // Overall progress
            'total' => $totalSeries,
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
            'percentage' => $totalSeries > 0 ? round(($processed / $totalSeries) * 100, 1) : 0,
            
            // Type indicator
            'type' => 'series',
            
            // Errors
            'error' => $errorMessage,
            'errors' => array_slice($errors, -10), // Last 10 errors only
            'total_errors' => count($errors),
            
            // Timestamps
            'updated_at' => now()->toISOString()
        ];

        Cache::put($this->progressKey, $progress, now()->addHours(2));

        Log::debug("📊 Series progress updated", [
            'key' => $this->progressKey,
            'batch' => "{$currentBatch}/{$totalBatches}",
            'processed' => "{$processed}/{$totalSeries}",
            'percentage' => $progress['percentage'] . '%'
        ]);
    }
}
