<?php

namespace App\Jobs;

use App\Models\Series;
use App\Models\SeriesSeason;
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
 * Queue Job: Process Season Upload from Bot
 * 
 * Handles season creation from TMDB data
 * Creates season WITHOUT episodes (per user requirement)
 * 
 * Security: Transaction-based, validated inputs
 * 
 * @package App\Jobs
 */
class ProcessSeasonUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = 30;

    protected int $tmdbId;
    protected int $seasonNumber;
    protected ?int $telegramUserId;
    protected ?string $telegramUsername;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $tmdbId,
        int $seasonNumber,
        ?int $telegramUserId = null,
        ?string $telegramUsername = null
    ) {
        $this->tmdbId = $tmdbId;
        $this->seasonNumber = $seasonNumber;
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
        Log::info('Processing season upload job', [
            'tmdb_id' => $this->tmdbId,
            'season_number' => $this->seasonNumber,
            'telegram_user_id' => $this->telegramUserId
        ]);

        DB::beginTransaction();
        
        try {
            // Find series by TMDB ID
            $series = Series::where('tmdb_id', $this->tmdbId)->first();
            
            if (!$series) {
                throw new \Exception("Series not found with TMDB ID: {$this->tmdbId}. Create series first using /uploadseries");
            }

            // Check if season already exists
            $existingCheck = $uploadService->checkSeasonExists($series->id, $this->seasonNumber);
            
            if ($existingCheck['exists']) {
                Log::info('Season already exists, skipping', [
                    'series_id' => $series->id,
                    'season_number' => $this->seasonNumber,
                    'season_id' => $existingCheck['season']->id
                ]);
                
                DB::rollBack();
                return;
            }

            // Fetch season data from TMDB
            $tmdbData = $tmdbService->fetchSeason($this->tmdbId, $this->seasonNumber);
            
            if (!$tmdbData) {
                throw new \Exception("Failed to fetch season data from TMDB for series {$this->tmdbId} season {$this->seasonNumber}");
            }

            // Prepare season data
            $seasonData = $uploadService->prepareSeasonData($tmdbData, $series->id);

            // Create season (NO episodes - per user requirement)
            $season = SeriesSeason::create($seasonData);

            DB::commit();

            Log::info('Season created successfully via bot', [
                'season_id' => $season->id,
                'series_id' => $series->id,
                'series_title' => $series->title,
                'season_number' => $this->seasonNumber,
                'telegram_user_id' => $this->telegramUserId,
                'telegram_username' => $this->telegramUsername,
                'note' => 'Episodes NOT created - use /uploadepisode to add episodes manually'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to process season upload', [
                'tmdb_id' => $this->tmdbId,
                'season_number' => $this->seasonNumber,
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
        Log::error('Season upload job failed permanently', [
            'tmdb_id' => $this->tmdbId,
            'season_number' => $this->seasonNumber,
            'error' => $exception->getMessage(),
            'telegram_user_id' => $this->telegramUserId
        ]);
    }
}
