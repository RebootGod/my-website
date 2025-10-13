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
 * Queue Job: Refresh TMDB Data for Movies Only
 * 
 * Dedicated job for refreshing movie data from TMDB
 * Independent from series and bulk action operations
 * 
 * File naming: RefreshAllTmdb_Movies_Job.php (per workinginstruction.md)
 * Max lines: 350 (per workinginstruction.md)
 * 
 * Security: Database queue only, validated inputs, transaction-based
 * 
 * @package App\Jobs
 */
class RefreshAllTmdb_Movies_Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour for large datasets
    public $tries = 1; // No retry - manual re-trigger if needed
    public $backoff = 0;

    protected array $movieIds;
    protected string $progressKey;
    protected int $batchSize = 5; // Process 5 movies at a time

    /**
     * Create a new job instance.
     *
     * @param array $movieIds Array of movie IDs to refresh
     * @param string $progressKey Cache key for progress tracking
     */
    public function __construct(
        array $movieIds,
        string $progressKey
    ) {
        $this->movieIds = $movieIds;
        $this->progressKey = $progressKey;
        $this->onConnection('database'); // Force database queue
    }

    /**
     * Execute the job.
     */
    public function handle(ContentBulkOperationService $bulkService): void
    {
        Log::info("🎬 RefreshAllTmdb_Movies_Job STARTED", [
            'total_movies' => count($this->movieIds),
            'batch_size' => $this->batchSize,
            'progress_key' => $this->progressKey
        ]);

        try {
            $totalMovies = count($this->movieIds);
            $successCount = 0;
            $failedCount = 0;
            $processedCount = 0;
            $errors = [];

            // Split movie IDs into batches
            $batches = array_chunk($this->movieIds, $this->batchSize);
            $totalBatches = count($batches);

            Log::info("📦 Processing movies in {$totalBatches} batches", [
                'batch_size' => $this->batchSize
            ]);

            // Initialize progress
            $this->updateProgress(
                $processedCount,
                $successCount,
                $failedCount,
                $totalMovies,
                0,
                $totalBatches,
                [],
                false
            );

            // Process each batch
            foreach ($batches as $batchIndex => $batchMovieIds) {
                $currentBatch = $batchIndex + 1;
                
                Log::info("🎬 Processing movie batch {$currentBatch}/{$totalBatches}", [
                    'movie_ids' => $batchMovieIds,
                    'batch_size' => count($batchMovieIds)
                ]);

                // Update progress: Currently processing this batch
                $this->updateProgress(
                    $processedCount,
                    $successCount,
                    $failedCount,
                    $totalMovies,
                    $currentBatch,
                    $totalBatches,
                    $errors,
                    false,
                    $batchMovieIds
                );

                // Process batch movies
                $result = $bulkService->bulkRefreshFromTMDB('movie', $batchMovieIds);

                // Update counters
                $successCount += $result['success'];
                $failedCount += $result['failed'];
                $processedCount += count($batchMovieIds);

                // Collect errors
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }

                Log::info("✅ Movie batch {$currentBatch}/{$totalBatches} completed", [
                    'batch_success' => $result['success'],
                    'batch_failed' => $result['failed'],
                    'total_success' => $successCount,
                    'total_failed' => $failedCount,
                    'progress' => round(($processedCount / $totalMovies) * 100, 1) . '%'
                ]);

                // Update progress after batch
                $this->updateProgress(
                    $processedCount,
                    $successCount,
                    $failedCount,
                    $totalMovies,
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

            Log::info("✅ RefreshAllTmdb_Movies_Job COMPLETED", [
                'total_processed' => $processedCount,
                'success' => $successCount,
                'failed' => $failedCount
            ]);

            // Mark as completed
            $this->updateProgress(
                $processedCount,
                $successCount,
                $failedCount,
                $totalMovies,
                $totalBatches,
                $totalBatches,
                $errors,
                true
            );

        } catch (\Exception $e) {
            Log::error("❌ RefreshAllTmdb_Movies_Job FAILED", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update progress with error
            $this->updateProgress(
                0,
                0,
                count($this->movieIds),
                count($this->movieIds),
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
        Log::error("💀 RefreshAllTmdb_Movies_Job PERMANENTLY FAILED", [
            'progress_key' => $this->progressKey,
            'error' => $exception->getMessage()
        ]);

        // Mark progress as failed
        $this->updateProgress(
            0,
            0,
            count($this->movieIds),
            count($this->movieIds),
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
        int $totalMovies,
        int $currentBatch,
        int $totalBatches,
        array $errors,
        bool $completed = false,
        array $currentProcessing = [],
        ?string $errorMessage = null
    ): void {
        $waiting = $totalMovies - $processed;
        
        $progress = [
            // Overall progress
            'total' => $totalMovies,
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
            'percentage' => $totalMovies > 0 ? round(($processed / $totalMovies) * 100, 1) : 0,
            
            // Type indicator
            'type' => 'movie',
            
            // Errors
            'error' => $errorMessage,
            'errors' => array_slice($errors, -10), // Last 10 errors only
            'total_errors' => count($errors),
            
            // Timestamps
            'updated_at' => now()->toISOString()
        ];

        Cache::put($this->progressKey, $progress, now()->addHours(2));

        Log::debug("📊 Movies progress updated", [
            'key' => $this->progressKey,
            'batch' => "{$currentBatch}/{$totalBatches}",
            'processed' => "{$processed}/{$totalMovies}",
            'percentage' => $progress['percentage'] . '%'
        ]);
    }
}
