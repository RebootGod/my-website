<?php
// app/Http/Controllers/HomeController.php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Series;
use App\Models\Genre;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // CONTENT TYPE FILTER (Movies/Series/All)
        $contentType = $request->get('content_type', session('content_type_filter', 'all'));
        
        // Store preference in session
        if ($request->has('content_type')) {
            session(['content_type_filter' => $contentType]);
        }

        // Initialize collections
        $movies = collect();
        $series = collect();

        // Build movie query (only if showing movies or all)
        if (in_array($contentType, ['all', 'movies'])) {
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
                    try {
                        // SECURITY & BUG FIX: Sanitize search term and limit length
                        // Prevents SQL charset errors with emoji/special characters
                        // Prevents XSS and overly long search terms
                        $sanitizedSearchTerm = mb_substr(strip_tags(trim($searchTerm)), 0, 255);
                        
                        SearchHistory::create([
                            'user_id' => Auth::id(),
                            'search_term' => $sanitizedSearchTerm,
                            'results_count' => $query->count(),
                            'ip_address' => $request->ip()
                        ]);
                    } catch (\Exception $e) {
                        // SILENT FAIL: Don't crash the search if logging fails
                        // Log error for debugging but continue showing search results
                        \Log::warning('Failed to log search history', [
                            'error' => $e->getMessage(),
                            'user_id' => Auth::id(),
                            'search_term' => $searchTerm,
                            'ip' => $request->ip()
                        ]);
                        
                        // Optional: Report to error tracking service
                        // report($e);
                    }
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

            // Get movies with all filters applied
            $movies = $query->get();
        }

        // Build series query (only if showing series or all)
        if (in_array($contentType, ['all', 'series'])) {
            $seriesQuery = Series::with(['genres', 'seasons'])->published();

            // Apply same filters to series
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $seriesQuery->where(function($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }
            if ($request->filled('genre')) {
                $genreId = $request->genre;
                $seriesQuery->whereHas('genres', function($q) use ($genreId) {
                    $q->where('genres.id', $genreId);
                });
            }
            if ($request->filled('year')) {
                $year = $request->year;
                $seriesQuery->where('year', $year);
            }
            if ($request->filled('rating')) {
                $seriesQuery->where('rating', '>=', $request->rating);
            }

            $series = $seriesQuery->get();
        }

        // Merge collections and apply sorting
        $merged = $movies->concat($series);

        // SORT OPTIONS for merged collection
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $merged = $merged->sortBy('updated_at')->values();
                break;
            case 'rating_high':
                $merged = $merged->sortByDesc('rating')->values();
                break;
            case 'rating_low':
                $merged = $merged->sortBy('rating')->values();
                break;
            case 'alphabetical':
                $merged = $merged->sortBy('title')->values();
                break;
            case 'latest':
            default:
                $merged = $merged->sortByDesc('updated_at')->values();
                break;
        }

            // Paginate manual (karena ini Collection, bukan Eloquent)
            $perPage = 20;
            $page = $request->input('page', 1);
            $total = $merged->count();
            $contents = new \Illuminate\Pagination\LengthAwarePaginator(
                $merged->slice(($page - 1) * $perPage, $perPage),
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

        // Get all genres for filter sidebar (cached for 1 hour)
        $genres = Cache::remember('home:genres', 3600, function() {
            return Genre::orderBy('name')->get();
        });

        // Calculate active filters count
        $activeFiltersCount = 0;
        if ($request->filled('search')) $activeFiltersCount++;
        if ($request->filled('genre')) $activeFiltersCount++;
        if ($request->filled('year')) $activeFiltersCount++;
        if ($request->filled('rating')) $activeFiltersCount++;
        if ($contentType !== 'all') $activeFiltersCount++; // Count content type filter

        return view('home', compact(
            'contents',
            'genres',
            'activeFiltersCount',
            'contentType'
        ));
    }

    /**
     * Get search suggestions via AJAX with caching
     */
    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Cache search suggestions for 10 minutes
        $cacheKey = 'search:suggestions:' . md5(strtolower($query));

        $movies = Cache::remember($cacheKey, 600, function() use ($query) {
            return Movie::select('id', 'title', 'slug', 'poster_path', 'release_date', 'rating')
                ->where('is_active', true)
                ->where(function($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('original_title', 'LIKE', "%{$query}%");
                })
                ->limit(8)
                ->get()
                ->map(function($movie) {
                    return [
                        'id' => $movie->id,
                        'title' => $movie->title,
                        'slug' => $movie->slug,
                        'poster' => $movie->poster_url,
                        'year' => $movie->year,
                        'rating' => $movie->rating,
                        'url' => route('movies.show', $movie->slug)
                    ];
                });
        });

        return response()->json($movies);
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        return redirect()->route('home');
    }
}