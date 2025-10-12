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
            'status_filter' => $this->status,
            'limit' => $this->limit,
            'progress_key' => $this->progressKey
        ]);

        try {
            // Initialize progress in cache
            $this->updateProgress(0, 0, 0, count($this->ids));

            // Execute bulk refresh operation
            $result = $bulkService->bulkRefreshFromTMDB($this->type, $this->ids, $this->progressKey);

            Log::info("✅ RefreshAllTmdbJob COMPLETED", [
                'type' => $this->type,
                'result' => $result
            ]);

            // Mark as completed
            $this->updateProgress(
                $result['total'] ?? count($this->ids),
                $result['success'] ?? 0,
                $result['failed'] ?? 0,
                count($this->ids),
                true
            );

        } catch (\Exception $e) {
            Log::error("❌ RefreshAllTmdbJob FAILED", [
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
                true,
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
        $this->updateProgress(
            0,
            0,
            count($this->ids),
            count($this->ids),
            true,
            'Job permanently failed: ' . $exception->getMessage()
        );
    }

    /**
     * Update progress in cache
     */
    protected function updateProgress(
        int $total,
        int $success,
        int $failed,
        int $totalItems,
        bool $completed = false,
        ?string $error = null
    ): void {
        $progress = [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'total_items' => $totalItems,
            'completed' => $completed,
            'error' => $error,
            'percentage' => $totalItems > 0 ? round(($total / $totalItems) * 100, 1) : 0,
            'updated_at' => now()->toISOString()
        ];

        Cache::put($this->progressKey, $progress, now()->addHours(2));

        Log::debug("📊 Progress updated", [
            'key' => $this->progressKey,
            'progress' => $progress
        ]);
    }
}
