<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Series;
use App\Models\SeriesSeason;
use App\Models\SeriesEpisode;
use App\Jobs\DownloadTmdbImageJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command: Download All TMDB Images
 * 
 * Bulk download existing TMDB images to local storage
 * Queues jobs for async processing
 * 
 * @package App\Console\Commands
 */
class DownloadAllTmdbImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:download-images 
                            {--type=all : Type of images to download (all, movies, series, seasons, episodes)}
                            {--limit=0 : Limit number of records (0 = no limit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download TMDB images to local storage (bulk operation)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $limit = (int) $this->option('limit');

        $this->info("🖼️  Starting TMDB image download (type: {$type})...");

        $stats = [
            'movies_posters' => 0,
            'movies_backdrops' => 0,
            'series_posters' => 0,
            'series_backdrops' => 0,
            'seasons_posters' => 0,
            'episodes_stills' => 0,
        ];

        try {
            if (in_array($type, ['all', 'movies'])) {
                $stats['movies_posters'] = $this->downloadMoviePosters($limit);
                $stats['movies_backdrops'] = $this->downloadMovieBackdrops($limit);
            }

            if (in_array($type, ['all', 'series'])) {
                $stats['series_posters'] = $this->downloadSeriesPosters($limit);
                $stats['series_backdrops'] = $this->downloadSeriesBackdrops($limit);
            }

            if (in_array($type, ['all', 'seasons'])) {
                $stats['seasons_posters'] = $this->downloadSeasonPosters($limit);
            }

            if (in_array($type, ['all', 'episodes'])) {
                $stats['episodes_stills'] = $this->downloadEpisodeStills($limit);
            }

            $this->info("\n✅ Download jobs queued successfully!");
            $this->table(
                ['Category', 'Jobs Queued'],
                [
                    ['Movie Posters', $stats['movies_posters']],
                    ['Movie Backdrops', $stats['movies_backdrops']],
                    ['Series Posters', $stats['series_posters']],
                    ['Series Backdrops', $stats['series_backdrops']],
                    ['Season Posters', $stats['seasons_posters']],
                    ['Episode Stills', $stats['episodes_stills']],
                    ['Total', array_sum($stats)],
                ]
            );

            $this->info("\n📝 Monitor progress with: php artisan queue:work image-downloads");
            $this->info("📊 Check logs at: storage/logs/laravel.log");

            Log::info('TMDB bulk download initiated', $stats);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Download failed: {$e->getMessage()}");

            Log::error('TMDB bulk download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Download movie posters
     */
    protected function downloadMoviePosters(int $limit): int
    {
        $query = Movie::whereNotNull('poster_path')
            ->whereNull('local_poster_path');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $movies = $query->get();
        $count = 0;

        $this->info("📥 Queueing {$movies->count()} movie posters...");

        foreach ($movies as $movie) {
            DownloadTmdbImageJob::dispatch(
                'movie',
                $movie->id,
                'poster',
                $movie->poster_path
            );
            $count++;
        }

        return $count;
    }

    /**
     * Download movie backdrops
     */
    protected function downloadMovieBackdrops(int $limit): int
    {
        $query = Movie::whereNotNull('backdrop_path')
            ->whereNull('local_backdrop_path');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $movies = $query->get();
        $count = 0;

        $this->info("📥 Queueing {$movies->count()} movie backdrops...");

        foreach ($movies as $movie) {
            DownloadTmdbImageJob::dispatch(
                'movie',
                $movie->id,
                'backdrop',
                $movie->backdrop_path
            );
            $count++;
        }

        return $count;
    }

    /**
     * Download series posters
     */
    protected function downloadSeriesPosters(int $limit): int
    {
        $query = Series::whereNotNull('poster_path')
            ->whereNull('local_poster_path')
            ->orWhereNotNull('poster_url')
            ->whereNull('local_poster_path');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $series = $query->get();
        $count = 0;

        $this->info("📥 Queueing {$series->count()} series posters...");

        foreach ($series as $item) {
            $posterPath = $item->poster_path ?: $this->extractTmdbPath($item->poster_url);
            if ($posterPath) {
                DownloadTmdbImageJob::dispatch(
                    'series',
                    $item->id,
                    'poster',
                    $posterPath
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Download series backdrops
     */
    protected function downloadSeriesBackdrops(int $limit): int
    {
        $query = Series::whereNotNull('backdrop_path')
            ->whereNull('local_backdrop_path')
            ->orWhereNotNull('backdrop_url')
            ->whereNull('local_backdrop_path');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $series = $query->get();
        $count = 0;

        $this->info("📥 Queueing {$series->count()} series backdrops...");

        foreach ($series as $item) {
            $backdropPath = $item->backdrop_path ?: $this->extractTmdbPath($item->backdrop_url);
            if ($backdropPath) {
                DownloadTmdbImageJob::dispatch(
                    'series',
                    $item->id,
                    'backdrop',
                    $backdropPath
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Download season posters
     */
    protected function downloadSeasonPosters(int $limit): int
    {
        $query = SeriesSeason::whereNotNull('poster_path')
            ->whereNull('local_poster_path');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $seasons = $query->get();
        $count = 0;

        $this->info("📥 Queueing {$seasons->count()} season posters...");

        foreach ($seasons as $season) {
            DownloadTmdbImageJob::dispatch(
                'season',
                $season->id,
                'poster',
                $season->poster_path,
                $season->season_number
            );
            $count++;
        }

        return $count;
    }

    /**
     * Download episode stills
     */
    protected function downloadEpisodeStills(int $limit): int
    {
        $query = SeriesEpisode::whereNotNull('still_path')
            ->whereNull('local_still_path');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $episodes = $query->get();
        $count = 0;

        $this->info("📥 Queueing {$episodes->count()} episode stills...");

        foreach ($episodes as $episode) {
            DownloadTmdbImageJob::dispatch(
                'episode',
                $episode->id,
                'still',
                $episode->still_path,
                $episode->season->season_number,
                $episode->episode_number
            );
            $count++;
        }

        return $count;
    }

    /**
     * Extract TMDB path from full URL
     */
    protected function extractTmdbPath(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // Extract path from TMDB URL (e.g., /abc123.jpg from https://image.tmdb.org/t/p/w500/abc123.jpg)
        if (preg_match('/\/([a-zA-Z0-9]+\.jpg)$/', $url, $matches)) {
            return '/' . $matches[1];
        }

        return null;
    }
}
