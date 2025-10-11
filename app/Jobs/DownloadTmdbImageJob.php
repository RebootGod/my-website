<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Models\Series;
use App\Models\SeriesSeason;
use App\Models\SeriesEpisode;
use App\Services\TmdbImageDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: Download TMDB Image
 * 
 * Downloads TMDB image and stores locally
 * Updates model with local path
 * 
 * @package App\Jobs
 */
class DownloadTmdbImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $backoff = 10;

    protected string $modelType;
    protected int $modelId;
    protected string $imageType; // 'poster', 'backdrop', 'still'
    protected string $tmdbPath;
    protected ?int $seasonNumber;
    protected ?int $episodeNumber;

    /**
     * Create a new job instance.
     *
     * @param string $modelType 'movie', 'series', 'season', 'episode'
     * @param int $modelId Model ID
     * @param string $imageType 'poster', 'backdrop', 'still'
     * @param string $tmdbPath TMDB image path
     * @param int|null $seasonNumber For seasons/episodes
     * @param int|null $episodeNumber For episodes
     */
    public function __construct(
        string $modelType,
        int $modelId,
        string $imageType,
        string $tmdbPath,
        ?int $seasonNumber = null,
        ?int $episodeNumber = null
    ) {
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->imageType = $imageType;
        $this->tmdbPath = $tmdbPath;
        $this->seasonNumber = $seasonNumber;
        $this->episodeNumber = $episodeNumber;
        
        $this->onQueue('image-downloads');
    }

    /**
     * Execute the job.
     */
    public function handle(TmdbImageDownloadService $downloadService): void
    {
        Log::info('Processing TMDB image download', [
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'image_type' => $this->imageType
        ]);

        try {
            $localPath = null;

            // Download based on model type and image type
            switch ($this->modelType) {
                case 'movie':
                    $localPath = $this->downloadMovieImage($downloadService);
                    break;
                
                case 'series':
                    $localPath = $this->downloadSeriesImage($downloadService);
                    break;
                
                case 'season':
                    $localPath = $this->downloadSeasonImage($downloadService);
                    break;
                
                case 'episode':
                    $localPath = $this->downloadEpisodeImage($downloadService);
                    break;
            }

            if ($localPath) {
                Log::info('TMDB image downloaded and saved', [
                    'model_type' => $this->modelType,
                    'model_id' => $this->modelId,
                    'local_path' => $localPath
                ]);
            } else {
                throw new \Exception('Failed to download image from TMDB');
            }

        } catch (\Exception $e) {
            Log::error('TMDB image download job failed', [
                'model_type' => $this->modelType,
                'model_id' => $this->modelId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Retry if not last attempt
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            } else {
                // Log final failure
                Log::error('TMDB image download job failed permanently', [
                    'model_type' => $this->modelType,
                    'model_id' => $this->modelId,
                    'tmdb_path' => $this->tmdbPath
                ]);
            }

            throw $e;
        }
    }

    /**
     * Download movie image
     */
    protected function downloadMovieImage(TmdbImageDownloadService $service): ?string
    {
        $movie = Movie::find($this->modelId);
        if (!$movie) {
            throw new \Exception("Movie not found: {$this->modelId}");
        }

        $localPath = null;

        if ($this->imageType === 'poster') {
            $localPath = $service->downloadMoviePoster($this->tmdbPath, $movie->tmdb_id);
            if ($localPath) {
                $movie->update(['local_poster_path' => $localPath]);
            }
        } elseif ($this->imageType === 'backdrop') {
            $localPath = $service->downloadMovieBackdrop($this->tmdbPath, $movie->tmdb_id);
            if ($localPath) {
                $movie->update(['local_backdrop_path' => $localPath]);
            }
        }

        return $localPath;
    }

    /**
     * Download series image
     */
    protected function downloadSeriesImage(TmdbImageDownloadService $service): ?string
    {
        $series = Series::find($this->modelId);
        if (!$series) {
            throw new \Exception("Series not found: {$this->modelId}");
        }

        $localPath = null;

        if ($this->imageType === 'poster') {
            $localPath = $service->downloadSeriesPoster($this->tmdbPath, $series->tmdb_id);
            if ($localPath) {
                $series->update(['local_poster_path' => $localPath]);
            }
        } elseif ($this->imageType === 'backdrop') {
            $localPath = $service->downloadSeriesBackdrop($this->tmdbPath, $series->tmdb_id);
            if ($localPath) {
                $series->update(['local_backdrop_path' => $localPath]);
            }
        }

        return $localPath;
    }

    /**
     * Download season image
     */
    protected function downloadSeasonImage(TmdbImageDownloadService $service): ?string
    {
        $season = SeriesSeason::find($this->modelId);
        if (!$season) {
            throw new \Exception("Season not found: {$this->modelId}");
        }

        $localPath = $service->downloadSeasonPoster(
            $this->tmdbPath,
            $season->series->tmdb_id,
            $season->season_number
        );

        if ($localPath) {
            $season->update(['local_poster_path' => $localPath]);
        }

        return $localPath;
    }

    /**
     * Download episode still
     */
    protected function downloadEpisodeImage(TmdbImageDownloadService $service): ?string
    {
        $episode = SeriesEpisode::find($this->modelId);
        if (!$episode) {
            throw new \Exception("Episode not found: {$this->modelId}");
        }

        $localPath = $service->downloadEpisodeStill(
            $this->tmdbPath,
            $episode->series->tmdb_id,
            $episode->season->season_number,
            $episode->episode_number
        );

        if ($localPath) {
            $episode->update(['local_still_path' => $localPath]);
        }

        return $localPath;
    }
}
