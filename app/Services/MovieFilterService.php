<?php
// app/Services/MovieFilterService.php

namespace App\Services;

use App\Models\Movie;
use App\Models\SearchHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MovieFilterService
{
    /**
     * Apply all filters to movie query
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Search filter
        if (!empty($filters['search'])) {
            $query = $this->applySearchFilter($query, $filters['search']);
            $this->logSearch($filters['search']);
        }

        // Genre filter
        if (!empty($filters['genres'])) {
            $query = $this->applyGenreFilter($query, $filters['genres']);
        }

        // Year range filter
        if (!empty($filters['year_from']) || !empty($filters['year_to'])) {
            $query = $this->applyYearFilter($query, $filters['year_from'] ?? null, $filters['year_to'] ?? null);
        }

        // Rating filter
        if (!empty($filters['min_rating'])) {
            $query = $this->applyRatingFilter($query, $filters['min_rating']);
        }

        // Quality filter
        if (!empty($filters['quality'])) {
            $query = $this->applyQualityFilter($query, $filters['quality']);
        }

        // Sort
        if (!empty($filters['sort'])) {
            $query = $this->applySorting($query, $filters['sort']);
        } else {
            $query = $query->latest();
        }

        return $query;
    }

    /**
     * Apply search filter
     */
    protected function applySearchFilter(Builder $query, string $search): Builder
    {
        $searchTerms = explode(' ', $search);
        
        return $query->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->where(function ($subQuery) use ($term) {
                    $subQuery->where('title', 'LIKE', "%{$term}%")
                            ->orWhere('original_title', 'LIKE', "%{$term}%")
                            ->orWhere('overview', 'LIKE', "%{$term}%")
                            ->orWhere('cast', 'LIKE', "%{$term}%")
                            ->orWhere('director', 'LIKE', "%{$term}%")
                            ->orWhere('keywords', 'LIKE', "%{$term}%");
                });
            }
        });
    }

    /**
     * Apply genre filter
     */
    protected function applyGenreFilter(Builder $query, array $genreIds): Builder
    {
        return $query->whereHas('genres', function ($q) use ($genreIds) {
            $q->whereIn('genres.id', $genreIds);
        }, '>=', count($genreIds)); // Must have ALL selected genres
    }

    /**
     * Apply year range filter
     */
    protected function applyYearFilter(Builder $query, ?string $yearFrom, ?string $yearTo): Builder
    {
        if ($yearFrom) {
            $query->where('release_date', '>=', $yearFrom . '-01-01');
        }
        
        if ($yearTo) {
            $query->where('release_date', '<=', $yearTo . '-12-31');
        }
        
        return $query;
    }

    /**
     * Apply rating filter
     */
    protected function applyRatingFilter(Builder $query, float $minRating): Builder
    {
        return $query->where('vote_average', '>=', $minRating);
    }

    /**
     * Apply quality filter
     */
    protected function applyQualityFilter(Builder $query, string $quality): Builder
    {
        return $query->whereHas('sources', function ($q) use ($quality) {
            $q->where('quality', $quality)
              ->where('is_active', true);
        });
    }

    /**
     * Apply sorting
     */
    protected function applySorting(Builder $query, string $sort): Builder
    {
        switch ($sort) {
            case 'popular':
                return $query->orderBy('popularity', 'desc');
            case 'rating':
                return $query->orderBy('vote_average', 'desc')
                            ->orderBy('vote_count', 'desc');
            case 'name_asc':
                return $query->orderBy('title', 'asc');
            case 'name_desc':
                return $query->orderBy('title', 'desc');
            case 'year_desc':
                return $query->orderBy('release_date', 'desc');
            case 'year_asc':
                return $query->orderBy('release_date', 'asc');
            case 'views':
                return $query->withCount('views')
                            ->orderBy('views_count', 'desc');
            case 'latest':
            default:
                return $query->latest();
        }
    }

    /**
     * Log search query
     */
    protected function logSearch(string $query): void
    {
        if (Auth::check()) {
            SearchHistory::create([
                'user_id' => Auth::id(),
                'query' => $query,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
    }

    /**
     * Get search suggestions
     */
    public function getSearchSuggestions(string $query, int $limit = 8): array
    {
        $cacheKey = 'search_suggestions_' . md5($query);
        
        return Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            return Movie::select('id', 'title', 'slug', 'poster_path', 'release_date', 'vote_average')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('original_title', 'LIKE', "%{$query}%");
                })
                ->orderByRaw("
                    CASE 
                        WHEN title LIKE ? THEN 1
                        WHEN title LIKE ? THEN 2
                        WHEN title LIKE ? THEN 3
                        ELSE 4
                    END
                ", [$query, "$query%", "%$query%"])
                ->limit($limit)
                ->get()
                ->map(function ($movie) {
                    return [
                        'id' => $movie->id,
                        'title' => $movie->title,
                        'slug' => $movie->slug,
                        'poster' => $movie->poster_url,
                        'year' => $movie->year,
                        'rating' => number_format($movie->vote_average, 1),
                        'url' => route('movies.show', $movie->slug)
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get popular searches
     */
    public function getPopularSearches(int $limit = 10): array
    {
        return Cache::remember('popular_searches', 3600, function () use ($limit) {
            return SearchHistory::select('query')
                ->selectRaw('COUNT(*) as search_count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('query')
                ->orderBy('search_count', 'desc')
                ->limit($limit)
                ->pluck('query')
                ->toArray();
        });
    }

    /**
     * Get trending searches (last 24 hours)
     */
    public function getTrendingSearches(int $limit = 5): array
    {
        return Cache::remember('trending_searches', 1800, function () use ($limit) {
            return SearchHistory::select('query')
                ->selectRaw('COUNT(*) as search_count')
                ->where('created_at', '>=', now()->subHours(24))
                ->groupBy('query')
                ->orderBy('search_count', 'desc')
                ->limit($limit)
                ->pluck('query')
                ->toArray();
        });
    }

    /**
     * Get filter statistics
     */
    public function getFilterStats(): array
    {
        return Cache::remember('filter_stats', 3600, function () {
            return [
                'total_movies' => Movie::where('is_active', true)->count(),
                'genres_count' => Movie::where('is_active', true)
                    ->join('movie_genre', 'movies.id', '=', 'movie_genre.movie_id')
                    ->distinct('movie_genre.genre_id')
                    ->count('movie_genre.genre_id'),
                'qualities' => Movie::where('is_active', true)
                    ->join('movie_sources', 'movies.id', '=', 'movie_sources.movie_id')
                    ->where('movie_sources.is_active', true)
                    ->distinct('movie_sources.quality')
                    ->pluck('movie_sources.quality')
                    ->toArray(),
                'year_range' => [
                    'min' => Movie::where('is_active', true)
                        ->whereNotNull('release_date')
                        ->min(DB::raw('YEAR(release_date)')),
                    'max' => Movie::where('is_active', true)
                        ->whereNotNull('release_date')
                        ->max(DB::raw('YEAR(release_date)'))
                ]
            ];
        });
    }

    /**
     * Clear filter cache
     */
    public function clearCache(): void
    {
        Cache::tags(['filters', 'search'])->flush();
    }
}