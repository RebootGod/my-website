<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Models\MovieSource;
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
 * Queue Job: Process Movie Upload from Bot
 * 
 * Handles movie creation from TMDB data
 * Creates movie and movie source entries
 * 
 * Security: Transaction-based, validated inputs
 * 
 * @package App\Jobs
 */
class ProcessMovieUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = 30;

    protected int $tmdbId;
    protected string $embedUrl;
    protected ?string $downloadUrl;
    protected ?int $telegramUserId;
    protected ?string $telegramUsername;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $tmdbId,
        string $embedUrl,
        ?string $downloadUrl = null,
        ?int $telegramUserId = null,
        ?string $telegramUsername = null
    ) {
        $this->tmdbId = $tmdbId;
        $this->embedUrl = $embedUrl;
        $this->downloadUrl = $downloadUrl;
        $this->telegramUserId = $telegramUserId;
        $this->telegramUsername = $telegramUsername;
        
        // Use default queue connection instead of hardcoded Redis
        // $this->onQueue('bot-uploads');
    }

    /**
     * Execute the job.
     */
    public function handle(
        TmdbDataService $tmdbService,
        ContentUploadService $uploadService
    ): void {
        Log::info('Processing movie upload job', [
            'tmdb_id' => $this->tmdbId,
            'telegram_user_id' => $this->telegramUserId
        ]);

        DB::beginTransaction();
        
        try {
            // Check if movie already exists
            $existingCheck = $uploadService->checkMovieExists($this->tmdbId);
            
            if ($existingCheck['exists']) {
                Log::info('Movie already exists, skipping', [
                    'tmdb_id' => $this->tmdbId,
                    'movie_id' => $existingCheck['movie']->id
                ]);
                
                DB::rollBack();
                return;
            }

            // Fetch movie data from TMDB
            $tmdbData = $tmdbService->fetchMovie($this->tmdbId);
            
            if (!$tmdbData) {
                throw new \Exception("Failed to fetch movie data from TMDB for ID: {$this->tmdbId}");
            }

            // Prepare movie data
            $movieData = $uploadService->prepareMovieData(
                $tmdbData,
                $this->embedUrl,
                $this->downloadUrl
            );

            // Create movie
            $movie = Movie::create($movieData);

            DB::commit();

            // Dispatch image download jobs (after commit)
            if (!empty($movieData['poster_path'])) {
                DownloadTmdbImageJob::dispatch(
                    'movie',
                    $movie->id,
                    'poster',
                    $movieData['poster_path']
                );
            }

            if (!empty($movieData['backdrop_path'])) {
                DownloadTmdbImageJob::dispatch(
                    'movie',
                    $movie->id,
                    'backdrop',
                    $movieData['backdrop_path']
                );
            }

            Log::info('Movie created successfully via bot', [
                'movie_id' => $movie->id,
                'tmdb_id' => $this->tmdbId,
                'title' => $movie->title,
                'telegram_user_id' => $this->telegramUserId,
                'telegram_username' => $this->telegramUsername
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to process movie upload', [
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
        Log::error('Movie upload job failed permanently', [
            'tmdb_id' => $this->tmdbId,
            'error' => $exception->getMessage(),
            'telegram_user_id' => $this->telegramUserId
        ]);
    }
}
