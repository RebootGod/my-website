<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Jobs\DownloadTmdbImageJob;
use Illuminate\Console\Command;

/**
 * Command: Retry Failed TMDB Image Downloads
 * 
 * Re-dispatches DownloadTmdbImageJob for movies that have TMDB paths
 * but missing local image files. Used after migration or failed downloads.
 */
class RetryTmdbImageDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:retry-images
                            {--type=all : Type of content to process (all, movies, series)}
                            {--limit=100 : Maximum number of items to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry downloading TMDB images for content missing local files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $limit = (int) $this->option('limit');

        $this->info("Retrying TMDB image downloads (type: {$type}, limit: {$limit})...");

        $totalDispatched = 0;

        // Process movies
        if ($type === 'all' || $type === 'movies') {
            $totalDispatched += $this->processMovies($limit);
        }

        // Process series (future implementation)
        if ($type === 'all' || $type === 'series') {
            $totalDispatched += $this->processSeries($limit);
        }

        $this->info("Total jobs dispatched: {$totalDispatched}");
        $this->info('Check queue status with: php artisan queue:work');

        return 0;
    }

    /**
     * Process movies missing local poster/backdrop
     */
    protected function processMovies(int $limit): int
    {
        $dispatched = 0;

        // Movies missing local poster
        $moviesNeedingPoster = Movie::whereNull('local_poster_path')
            ->whereNotNull('poster_path')
            ->limit($limit)
            ->get();

        $this->info("Found {$moviesNeedingPoster->count()} movies needing poster download");

        foreach ($moviesNeedingPoster as $movie) {
            DownloadTmdbImageJob::dispatch(
                'movie',
                $movie->id,
                'poster',
                $movie->poster_path
            );
            $dispatched++;
        }

        // Movies missing local backdrop
        $moviesNeedingBackdrop = Movie::whereNull('local_backdrop_path')
            ->whereNotNull('backdrop_path')
            ->limit($limit)
            ->get();

        $this->info("Found {$moviesNeedingBackdrop->count()} movies needing backdrop download");

        foreach ($moviesNeedingBackdrop as $movie) {
            DownloadTmdbImageJob::dispatch(
                'movie',
                $movie->id,
                'backdrop',
                $movie->backdrop_path
            );
            $dispatched++;
        }

        return $dispatched;
    }

    /**
     * Process series missing local poster/backdrop
     */
    protected function processSeries(int $limit): int
    {
        // Future implementation for series
        $this->warn('Series processing not yet implemented');
        return 0;
    }
}
