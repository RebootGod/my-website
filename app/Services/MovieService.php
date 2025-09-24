<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Genre;
use App\Models\Series;
use App\Models\MovieSource;
use App\Models\BrokenLinkReport;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class MovieService
{
    /**
     * Get movie index data with filtering and pagination
     */
    public static function getMovieIndexData(Request $request): array
    {
        // Build base query (only published movies)
        $query = Movie::with(['genres', 'sources'])
            ->published();

        // SEARCH FUNCTIONALITY
        if ($request->filled('search')) {
            $searchTerm = $request->search;

            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('original_title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('overview', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('cast', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('director', 'LIKE', "%{$searchTerm}%");
            });

            // Log search if user is authenticated
            if (Auth::check()) {
                SearchHistory::create([
                    'user_id' => Auth::id(),
                    'search_term' => $searchTerm,
                    'results_count' => $query->count(),
                    'ip_address' => $request->ip()
                ]);
            }
        }

        // GENRE FILTER
        if ($request->filled('genre')) {
            $genreId = $request->genre;
            $query->whereHas('genres', function($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        // YEAR FILTER
        if ($request->filled('year')) {
            $year = $request->year;
            $query->whereYear('release_date', $year);
        }

        // RATING FILTER
        if ($request->filled('rating')) {
            $query->where('rating', '>=', $request->rating);
        }

        // QUALITY FILTER
        if ($request->filled('quality')) {
            $quality = $request->quality;
            $query->whereHas('sources', function($q) use ($quality) {
                $q->where('quality', $quality);
            });
        }

        // SORT OPTIONS
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'rating_high':
                $query->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('rating', 'asc');
                break;
            case 'alphabetical':
                $query->orderBy('title', 'asc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        // Paginate results
        $movies = $query->paginate(20);

        // Get all genres for filter sidebar (cached for 1 hour)
        $genres = Cache::remember('home:genres', 3600, function() {
            return Genre::orderBy('name')->get();
        });

        return [
            'movies' => $movies,
            'genres' => $genres,
            'currentGenre' => $request->genre,
            'currentYear' => $request->year,
            'currentRating' => $request->rating,
            'currentQuality' => $request->quality,
            'currentSort' => $sortBy,
            'searchQuery' => $request->search,
        ];
    }

    /**
     * Get movie search data with combined movies and series
     */
    public static function getMovieSearchData(Request $request): array
    {
        $searchTerm = $request->get('search') ?: $request->get('search_alt');

        // Build movies query
        $moviesQuery = Movie::with(['genres', 'sources'])->published();
        $seriesQuery = Series::with(['genres', 'seasons'])->published();

        if ($searchTerm) {
            $moviesQuery->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('original_title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('overview', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('cast', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('director', 'LIKE', "%{$searchTerm}%");
            });

            $seriesQuery->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });

            // Log search if user is authenticated
            if (Auth::check()) {
                SearchHistory::create([
                    'user_id' => Auth::id(),
                    'search_term' => $searchTerm,
                    'results_count' => $moviesQuery->count() + $seriesQuery->count(),
                    'ip_address' => $request->ip()
                ]);
            }
        }

        // Apply additional filters
        if ($request->filled('genre')) {
            $genreId = $request->genre;
            $moviesQuery->whereHas('genres', function($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
            $seriesQuery->whereHas('genres', function($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        if ($request->filled('year')) {
            $year = $request->year;
            $moviesQuery->whereYear('release_date', $year);
            $seriesQuery->where('year', $year);
        }

        if ($request->filled('rating')) {
            $moviesQuery->where('rating', '>=', $request->rating);
            $seriesQuery->where('rating', '>=', $request->rating);
        }

        // Get results
        $movies = $moviesQuery->get();
        $series = $seriesQuery->get();

        // Combine and sort
        $merged = $movies->concat($series)->sortByDesc('created_at')->values();

        // Manual pagination for combined results
        $perPage = 20;
        $page = $request->input('page', 1);
        $total = $merged->count();

        $paginatedResults = new LengthAwarePaginator(
            $merged->slice(($page - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Get all genres for filter sidebar
        $genres = Cache::remember('search:genres', 3600, function() {
            return Genre::orderBy('name')->get();
        });

        return [
            'movies' => $paginatedResults,
            'genres' => $genres,
            'searchQuery' => $searchTerm,
            'totalResults' => $total,
            'currentGenre' => $request->genre,
            'currentYear' => $request->year,
            'currentRating' => $request->rating,
        ];
    }

    /**
     * Get movie player data
     */
    public static function getMoviePlayerData(Request $request, Movie $movie): array
    {
        // Get all active sources ordered by priority and quality
        $sources = $movie->sources()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderByDesc('quality')
            ->get();

        // If no sources, try to use main embed_url
        if ($sources->isEmpty() && $movie->embed_url) {
            // Create temporary source object for backward compatibility
            $tempSource = new MovieSource([
                'movie_id' => $movie->id,
                'source_name' => 'Main Server',
                'embed_url' => $movie->embed_url,
                'quality' => $movie->quality,
                'is_active' => true,
                'priority' => 0
            ]);
            $sources = collect([$tempSource]);
        }

        // Get requested source or best available
        $sourceId = $request->get('source');
        $currentSource = null;

        if ($sourceId) {
            $currentSource = $sources->firstWhere('id', $sourceId);
        }

        // If no specific source requested or not found, get best quality
        if (!$currentSource) {
            // Sort by quality priority (4K > FHD > HD > TS > CAM)
            $qualityOrder = ['4K' => 5, 'FHD' => 4, 'HD' => 3, 'TS' => 2, 'CAM' => 1];
            $currentSource = $sources->sortByDesc(function ($source) use ($qualityOrder) {
                return $qualityOrder[$source->quality] ?? 0;
            })->first();
        }

        // Group sources by quality for selector
        $sourcesByQuality = $sources->groupBy('quality');

        // Get best available quality
        $bestQuality = $sources->pluck('quality')->unique()->sortByDesc(function ($quality) {
            $order = ['4K' => 5, 'FHD' => 4, 'HD' => 3, 'TS' => 2, 'CAM' => 1];
            return $order[$quality] ?? 0;
        })->first();

        // Get related movies (cached for 1 hour)
        $relatedMovies = Cache::remember("movie:related:{$movie->id}", 3600, function() use ($movie) {
            return Movie::published()
                ->where('id', '!=', $movie->id)
                ->whereHas('genres', function ($query) use ($movie) {
                    $query->whereIn('genres.id', $movie->genres->pluck('id'));
                })
                ->with('genres') // Eager load to prevent N+1
                ->inRandomOrder()
                ->limit(5)
                ->get();
        });

        return [
            'movie' => $movie,
            'sources' => $sources,
            'currentSource' => $currentSource,
            'sourcesByQuality' => $sourcesByQuality,
            'bestQuality' => $bestQuality,
            'relatedMovies' => $relatedMovies,
            'qualityOrder' => ['4K' => 5, 'FHD' => 4, 'HD' => 3, 'TS' => 2, 'CAM' => 1]
        ];
    }
}
