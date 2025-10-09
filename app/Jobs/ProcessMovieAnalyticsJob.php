<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Models\MovieView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Job: Process Movie Analytics
 * 
 * Security: SQL injection protected via Eloquent
 * OWASP: Safe data aggregation
 * 
 * @package App\Jobs
 * @created 2025-10-09
 */
class ProcessMovieAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 300;
    public $backoff = [120, 600]; // 2min, 10min

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('analytics');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $startTime = microtime(true);

            // Calculate trending movies (last 7 days)
            $this->calculateTrendingMovies();

            // Update view counts cache
            $this->updateViewCountsCache();

            // Calculate genre popularity
            $this->calculateGenrePopularity();

            $duration = round((microtime(true) - $startTime), 2);

            Log::info('Movie analytics processed successfully', [
                'duration_seconds' => $duration,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process movie analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Calculate trending movies based on recent views
     *
     * @return void
     */
    protected function calculateTrendingMovies(): void
    {
        $sevenDaysAgo = now()->subDays(7);

        $trendingMovies = MovieView::select('movie_id', DB::raw('COUNT(*) as view_count'))
            ->where('created_at', '>=', $sevenDaysAgo)
            ->groupBy('movie_id')
            ->orderByDesc('view_count')
            ->limit(50)
            ->get();

        // Cache trending movies for 1 hour
        Cache::put('trending_movies_7_days', $trendingMovies, 3600);

        Log::info('Trending movies calculated', [
            'total_trending' => $trendingMovies->count(),
            'top_movie_id' => $trendingMovies->first()?->movie_id,
            'top_views' => $trendingMovies->first()?->view_count
        ]);
    }

    /**
     * Update view counts cache for all movies
     *
     * @return void
     */
    protected function updateViewCountsCache(): void
    {
        $viewCounts = MovieView::select('movie_id', DB::raw('COUNT(*) as total_views'))
            ->groupBy('movie_id')
            ->get()
            ->pluck('total_views', 'movie_id');

        // Cache for 6 hours
        Cache::put('movie_view_counts', $viewCounts, 21600);

        Log::info('View counts cache updated', [
            'total_movies_with_views' => $viewCounts->count()
        ]);
    }

    /**
     * Calculate genre popularity
     *
     * @return void
     */
    protected function calculateGenrePopularity(): void
    {
        $genreViews = DB::table('movie_views')
            ->join('movie_genres', 'movie_views.movie_id', '=', 'movie_genres.movie_id')
            ->join('genres', 'movie_genres.genre_id', '=', 'genres.id')
            ->select('genres.id', 'genres.name', DB::raw('COUNT(*) as view_count'))
            ->where('movie_views.created_at', '>=', now()->subDays(30))
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('view_count')
            ->get();

        // Cache for 4 hours
        Cache::put('genre_popularity_30_days', $genreViews, 14400);

        Log::info('Genre popularity calculated', [
            'total_genres' => $genreViews->count(),
            'most_popular' => $genreViews->first()?->name ?? 'N/A'
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Movie analytics job failed after all retries', [
            'error' => $exception->getMessage(),
            'timestamp' => now()
        ]);
    }
}
