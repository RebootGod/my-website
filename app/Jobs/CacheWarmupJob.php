<?php

namespace App\Jobs;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\SearchHistory;
use App\Models\Series;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheWarmupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('CacheWarmupJob: Starting cache warmup process');

            $startTime = microtime(true);
            $cachedItems = 0;

            // 1. Cache all genres (critical for filters and navigation)
            if ($this->cacheGenres()) {
                $cachedItems++;
            }

            // 2. Cache featured movies (homepage carousel)
            if ($this->cacheFeaturedMovies()) {
                $cachedItems++;
            }

            // 3. Cache trending movies (homepage section)
            if ($this->cacheTrendingMovies()) {
                $cachedItems++;
            }

            // 4. Cache new movies (homepage section)
            if ($this->cacheNewMovies()) {
                $cachedItems++;
            }

            // 5. Cache popular searches (search autocomplete)
            if ($this->cachePopularSearches()) {
                $cachedItems++;
            }

            // 6. Cache featured series (series page)
            if ($this->cacheFeaturedSeries()) {
                $cachedItems++;
            }

            // 7. Cache trending series (series page)
            if ($this->cacheTrendingSeries()) {
                $cachedItems++;
            }

            // 8. Cache top rated movies (browse page)
            if ($this->cacheTopRatedMovies()) {
                $cachedItems++;
            }

            // 9. Cache top rated series (browse page)
            if ($this->cacheTopRatedSeries()) {
                $cachedItems++;
            }

            $duration = round(microtime(true) - $startTime, 2);

            Log::info('CacheWarmupJob: Cache warmup completed', [
                'cached_items' => $cachedItems,
                'duration_seconds' => $duration,
            ]);

        } catch (\Exception $e) {
            Log::error('CacheWarmupJob: Cache warmup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Cache all genres for filter sidebar.
     */
    private function cacheGenres(): bool
    {
        try {
            Cache::remember('home:genres', 3600, function () {
                return Genre::orderBy('name')->get();
            });

            Cache::remember('admin:genres_list', 3600, function () {
                return Genre::orderBy('name')->get();
            });

            Log::debug('CacheWarmupJob: Cached genres');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache genres', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache featured movies for homepage carousel.
     */
    private function cacheFeaturedMovies(): bool
    {
        try {
            Cache::remember('home:featured_movies', 3600, function () {
                return Movie::where('is_featured', true)
                    ->where('is_active', true)
                    ->with('genres')
                    ->limit(10)
                    ->get();
            });

            Log::debug('CacheWarmupJob: Cached featured movies');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache featured movies', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache trending movies (most viewed in last 7 days).
     */
    private function cacheTrendingMovies(): bool
    {
        try {
            Cache::remember('home:trending_movies', 1800, function () {
                return Movie::withCount(['views' => function ($q) {
                    $q->where('created_at', '>=', now()->subDays(7));
                }])
                    ->where('is_active', true)
                    ->orderBy('views_count', 'desc')
                    ->limit(10)
                    ->get();
            });

            Log::debug('CacheWarmupJob: Cached trending movies');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache trending movies', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache newly added movies.
     */
    private function cacheNewMovies(): bool
    {
        try {
            Cache::remember('home:new_movies', 900, function () {
                return Movie::where('is_active', true)
                    ->whereDate('created_at', '>=', now()->subDays(7))
                    ->latest()
                    ->limit(10)
                    ->get();
            });

            Log::debug('CacheWarmupJob: Cached new movies');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache new movies', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache popular search terms for autocomplete.
     */
    private function cachePopularSearches(): bool
    {
        try {
            Cache::remember('home:popular_searches', 1800, function () {
                return SearchHistory::select('search_term as query')
                    ->groupBy('search_term')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(10)
                    ->pluck('query');
            });

            Log::debug('CacheWarmupJob: Cached popular searches');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache popular searches', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache featured series.
     */
    private function cacheFeaturedSeries(): bool
    {
        try {
            Cache::remember('series:featured', 3600, function () {
                return Series::where('is_featured', true)
                    ->where('is_active', true)
                    ->with('genres')
                    ->limit(10)
                    ->get();
            });

            Log::debug('CacheWarmupJob: Cached featured series');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache featured series', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache trending series (most viewed in last 7 days).
     */
    private function cacheTrendingSeries(): bool
    {
        try {
            Cache::remember('series:trending', 1800, function () {
                return Series::withCount(['views' => function ($q) {
                    $q->where('created_at', '>=', now()->subDays(7));
                }])
                    ->where('is_active', true)
                    ->orderBy('views_count', 'desc')
                    ->limit(10)
                    ->get();
            });

            Log::debug('CacheWarmupJob: Cached trending series');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache trending series', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache top rated movies.
     */
    private function cacheTopRatedMovies(): bool
    {
        try {
            Cache::remember('movies:top_rated', 7200, function () {
                return Movie::where('is_active', true)
                    ->where('rating', '>', 7.0)
                    ->orderBy('rating', 'desc')
                    ->orderBy('vote_count', 'desc')
                    ->limit(20)
                    ->get();
            });

            Log::debug('CacheWarmupJob: Cached top rated movies');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache top rated movies', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache top rated series.
     */
    private function cacheTopRatedSeries(): bool
    {
        try {
            Cache::remember('series:top_rated', 7200, function () {
                return Series::where('is_active', true)
                    ->where('rating', '>', 7.0)
                    ->orderBy('rating', 'desc')
                    ->orderBy('vote_count', 'desc')
                    ->limit(20)
                    ->get();
            });

            Log::debug('CacheWarmupJob: Cached top rated series');
            return true;
        } catch (\Exception $e) {
            Log::warning('CacheWarmupJob: Failed to cache top rated series', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CacheWarmupJob: Job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
