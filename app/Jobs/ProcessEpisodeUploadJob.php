<?php

namespace App\Jobs;

use App\Models\Series;
use App\Models\SeriesSeason;
use App\Models\SeriesEpisode;
use App\Services\TmdbDataService;
use App\Services\ContentUploadService;
use App\Jobs\DownloadTmdbImageJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Queue Job: Process Episode Upload from Bot
 * 
 * Handles episode creation from TMDB data
 * Creates episode with embed and download URLs
 * 
 * Security: Transaction-based, validated inputs
 * 
 * @package App\Jobs
 */
class ProcessEpisodeUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = 30;

    protected int $tmdbId;
    protected int $seasonNumber;
    protected int $episodeNumber;
    protected string $embedUrl;
    protected ?string $downloadUrl;
    protected ?int $telegramUserId;
    protected ?string $telegramUsername;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $tmdbId,
        int $seasonNumber,
        int $episodeNumber,
        string $embedUrl,
        ?string $downloadUrl = null,
        ?int $telegramUserId = null,
        ?string $telegramUsername = null
    ) {
        $this->tmdbId = $tmdbId;
        $this->seasonNumber = $seasonNumber;
        $this->episodeNumber = $episodeNumber;
        $this->embedUrl = $embedUrl;
        $this->downloadUrl = $downloadUrl;
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
        Log::info('Processing episode upload job', [
            'tmdb_id' => $this->tmdbId,
            'season_number' => $this->seasonNumber,
            'episode_number' => $this->episodeNumber,
            'telegram_user_id' => $this->telegramUserId
        ]);

        DB::beginTransaction();
        
        try {
            // Find series by TMDB ID
            $series = Series::where('tmdb_id', $this->tmdbId)->first();
            
            if (!$series) {
                throw new \Exception("Series not found with TMDB ID: {$this->tmdbId}. Create series first.");
            }

            // Find season
            $season = SeriesSeason::where('series_id', $series->id)
                ->where('season_number', $this->seasonNumber)
                ->first();
            
            if (!$season) {
                throw new \Exception("Season {$this->seasonNumber} not found for series {$series->title}. Create season first.");
            }

            // Check if episode already exists
            $existingCheck = $uploadService->checkEpisodeExists($season->id, $this->episodeNumber);
            
            if ($existingCheck['exists']) {
                Log::info('Episode already exists, skipping', [
                    'series_id' => $series->id,
                    'season_id' => $season->id,
                    'episode_number' => $this->episodeNumber,
                    'episode_id' => $existingCheck['episode']->id
                ]);
                
                DB::rollBack();
                return;
            }

            // Fetch episode data from TMDB
            $tmdbData = $tmdbService->fetchEpisode($this->tmdbId, $this->seasonNumber, $this->episodeNumber);
            
            if (!$tmdbData) {
                throw new \Exception("Failed to fetch episode data from TMDB");
            }

            // Prepare episode data
            $episodeData = $uploadService->prepareEpisodeData(
                $tmdbData,
                $series->id,
                $season->id,
                $this->embedUrl,
                $this->downloadUrl
            );

            // Create episode
            $episode = SeriesEpisode::create($episodeData);

            DB::commit();

            // Dispatch image download job (after commit)
            if (!empty($episodeData['still_path'])) {
                DownloadTmdbImageJob::dispatch(
                    'episode',
                    $episode->id,
                    'still',
                    $episodeData['still_path'],
                    $season->season_number,
                    $episode->episode_number
                );
            }

            Log::info('Episode created successfully via bot', [
                'episode_id' => $episode->id,
                'series_id' => $series->id,
                'series_title' => $series->title,
                'season_number' => $this->seasonNumber,
                'episode_number' => $this->episodeNumber,
                'telegram_user_id' => $this->telegramUserId,
                'telegram_username' => $this->telegramUsername
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to process episode upload', [
                'tmdb_id' => $this->tmdbId,
                'season_number' => $this->seasonNumber,
                'episode_number' => $this->episodeNumber,
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
        Log::error('Episode upload job failed permanently', [
            'tmdb_id' => $this->tmdbId,
            'season_number' => $this->seasonNumber,
            'episode_number' => $this->episodeNumber,
            'error' => $exception->getMessage(),
            'telegram_user_id' => $this->telegramUserId
        ]);
    }
}
