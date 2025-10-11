<?php

namespace App\Jobs;

use App\Models\Series;
use App\Services\TmdbDataService;
use App\Services\ContentUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Queue Job: Process Series Upload from Bot
 * 
 * Handles series creation from TMDB data
 * Creates series WITHOUT seasons/episodes
 * 
 * Security: Transaction-based, validated inputs
 * 
 * @package App\Jobs
 */
class ProcessSeriesUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = 30;

    protected int $tmdbId;
    protected ?int $telegramUserId;
    protected ?string $telegramUsername;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $tmdbId,
        ?int $telegramUserId = null,
        ?string $telegramUsername = null
    ) {
        $this->tmdbId = $tmdbId;
        $this->telegramUserId = $telegramUserId;
        $this->telegramUsername = $telegramUsername;
        
        $this->onQueue('bot-uploads');
    }

    /**
     * Execute the job.
     */
    public function handle(
        TmdbDataService $tmdbService,
        ContentUploadService $uploadService
    ): void {
        Log::info('Processing series upload job', [
            'tmdb_id' => $this->tmdbId,
            'telegram_user_id' => $this->telegramUserId
        ]);

        DB::beginTransaction();
        
        try {
            // Check if series already exists
            $existingCheck = $uploadService->checkSeriesExists($this->tmdbId);
            
            if ($existingCheck['exists']) {
                Log::info('Series already exists, skipping', [
                    'tmdb_id' => $this->tmdbId,
                    'series_id' => $existingCheck['series']->id
                ]);
                
                DB::rollBack();
                return;
            }

            // Fetch series data from TMDB
            $tmdbData = $tmdbService->fetchSeries($this->tmdbId);
            
            if (!$tmdbData) {
                throw new \Exception("Failed to fetch series data from TMDB for ID: {$this->tmdbId}");
            }

            // Prepare series data
            $seriesData = $uploadService->prepareSeriesData($tmdbData);

            // Create series (NO seasons, NO episodes)
            $series = Series::create($seriesData);

            DB::commit();

            Log::info('Series created successfully via bot', [
                'series_id' => $series->id,
                'tmdb_id' => $this->tmdbId,
                'title' => $series->title,
                'telegram_user_id' => $this->telegramUserId,
                'telegram_username' => $this->telegramUsername,
                'note' => 'Seasons and episodes NOT created - manual upload required'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to process series upload', [
                'tmdb_id' => $this->tmdbId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Series upload job failed permanently', [
            'tmdb_id' => $this->tmdbId,
            'error' => $exception->getMessage(),
            'telegram_user_id' => $this->telegramUserId
        ]);
    }
}
