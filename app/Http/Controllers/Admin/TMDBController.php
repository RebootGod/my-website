<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Genre;
use App\Services\TMDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * TMDBController - Handles TMDB API integration for movie imports
 * Separated for better organization and API management
 */
class TMDBController extends Controller
{
    protected $tmdbService;

    public function __construct(TMDBService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    /**
     * Show TMDB search interface
     */
    public function index()
    {
        return view('admin.tmdb.index');
    }

    /**
     * Search movies on TMDB (supports both title search and TMDB ID lookup)
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'page' => 'nullable|integer|min:1|max:1000',
        ]);

        try {
            // Use smartSearch which can handle both title search and TMDB ID lookup
            if (is_numeric($request->query)) {
                // For numeric queries (TMDB ID), use smartSearch which will call getMovieDetails
                $results = $this->tmdbService->smartSearch($request->query);
            } else {
                // For text queries, use regular search with pagination
                $results = $this->tmdbService->searchMovies($request->query, $request->get('page', 1));
            }
            
            if (!$results['success']) {
                return response()->json([
                    'error' => $results['message'] ?? 'Search failed'
                ], 400);
            }

            // Check which movies already exist in our database
            $tmdbIds = collect($results['results'])->pluck('tmdb_id');
            $existingMovies = Movie::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();
            
            // Add 'exists' flag to each result
            $results['results'] = collect($results['results'])->map(function ($movie) use ($existingMovies) {
                $movie['exists_in_db'] = in_array($movie['tmdb_id'], $existingMovies);
                return $movie;
            })->toArray();

            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('TMDB Search Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to search movies. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get detailed movie information from TMDB
     */
    public function getDetails($tmdbId)
    {
        try {
            $movieDetails = $this->tmdbService->getMovieDetails($tmdbId);
            
            if (!$movieDetails['success']) {
                return response()->json([
                    'error' => $movieDetails['message'] ?? 'Failed to get movie details'
                ], 400);
            }

            // Check if movie already exists
            $existingMovie = Movie::where('tmdb_id', $tmdbId)->first();
            $movieDetails['data']['exists_in_db'] = $existingMovie ? true : false;
            $movieDetails['data']['local_movie'] = $existingMovie;

            return response()->json($movieDetails['data']);
            
        } catch (\Exception $e) {
            Log::error('TMDB Get Details Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to get movie details. Please try again later.'
            ], 500);
        }
    }

    /**
     * Import a single movie from TMDB
     */
    public function import(Request $request)
    {
        $request->validate([
            'tmdb_id' => 'required|integer',
            'embed_url' => 'required|url',
            'quality' => 'required|in:CAM,HD,FHD,4K',
            'status' => 'required|in:draft,published,archived',
        ]);

        try {
            // Check if movie already exists
            if (Movie::where('tmdb_id', $request->tmdb_id)->exists()) {
                return response()->json([
                    'error' => 'Movie already exists in database!'
                ], 400);
            }

            $movieData = $this->tmdbService->getMovieDetails($request->tmdb_id);
            
            if (isset($movieData['error'])) {
                return response()->json([
                    'error' => $movieData['error']
                ], 400);
            }

            // Create unique slug
            $slug = Str::slug($movieData['title']);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Movie::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Import movie
            $movie = Movie::create([
                'tmdb_id' => $movieData['id'],
                'title' => $movieData['title'],
                'original_title' => $movieData['original_title'] ?? $movieData['title'],
                'slug' => $slug,
                'overview' => $movieData['overview'] ?? '',
                'poster_url' => $movieData['poster_path'] 
                    ? 'https://image.tmdb.org/t/p/w500' . $movieData['poster_path'] 
                    : null,
                'backdrop_url' => $movieData['backdrop_path'] 
                    ? 'https://image.tmdb.org/t/p/w1280' . $movieData['backdrop_path'] 
                    : null,
                'embed_url' => encrypt($request->embed_url),
                'year' => $movieData['release_date'] 
                    ? Carbon::parse($movieData['release_date'])->year 
                    : null,
                'duration' => $movieData['runtime'] ?? null,
                'rating' => $movieData['vote_average'] ?? 0,
                'vote_count' => $movieData['vote_count'] ?? 0,
                'popularity' => $movieData['popularity'] ?? 0,
                'quality' => $request->quality,
                'status' => $request->status,
                'added_by' => auth()->id(),
            ]);

            // Import genres
            if (isset($movieData['genres']) && is_array($movieData['genres'])) {
                $genreIds = [];
                
                foreach ($movieData['genres'] as $tmdbGenre) {
                    $genre = Genre::firstOrCreate(
                        ['tmdb_id' => $tmdbGenre['id']],
                        [
                            'name' => $tmdbGenre['name'],
                            'slug' => Str::slug($tmdbGenre['name']),
                        ]
                    );
                    $genreIds[] = $genre->id;
                }
                
                $movie->genres()->attach($genreIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Movie imported successfully!',
                'movie' => $movie->load('genres')
            ]);
            
        } catch (\Exception $e) {
            Log::error('TMDB Import Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to import movie. Please try again later.'
            ], 500);
        }
    }

    /**
     * Bulk import movies from TMDB
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'movies' => 'required|array|min:1|max:50',
            'movies.*.tmdb_id' => 'required|integer',
            'movies.*.embed_url' => 'required|url',
            'movies.*.quality' => 'required|in:CAM,HD,FHD,4K',
            'default_status' => 'required|in:draft,published,archived',
        ]);

        $imported = [];
        $errors = [];
        $skipped = [];

        foreach ($request->movies as $movieData) {
            try {
                // Check if movie already exists
                if (Movie::where('tmdb_id', $movieData['tmdb_id'])->exists()) {
                    $skipped[] = [
                        'tmdb_id' => $movieData['tmdb_id'],
                        'reason' => 'Already exists in database'
                    ];
                    continue;
                }

                $tmdbData = $this->tmdbService->getMovieDetails($movieData['tmdb_id']);
                
                if (isset($tmdbData['error'])) {
                    $errors[] = [
                        'tmdb_id' => $movieData['tmdb_id'],
                        'error' => $tmdbData['error']
                    ];
                    continue;
                }

                // Create unique slug
                $slug = Str::slug($tmdbData['title']);
                $originalSlug = $slug;
                $counter = 1;
                
                while (Movie::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                // Import movie
                $movie = Movie::create([
                    'tmdb_id' => $tmdbData['id'],
                    'title' => $tmdbData['title'],
                    'original_title' => $tmdbData['original_title'] ?? $tmdbData['title'],
                    'slug' => $slug,
                    'overview' => $tmdbData['overview'] ?? '',
                    'poster_url' => $tmdbData['poster_path'] 
                        ? 'https://image.tmdb.org/t/p/w500' . $tmdbData['poster_path'] 
                        : null,
                    'backdrop_url' => $tmdbData['backdrop_path'] 
                        ? 'https://image.tmdb.org/t/p/w1280' . $tmdbData['backdrop_path'] 
                        : null,
                    'embed_url' => encrypt($movieData['embed_url']),
                    'year' => $tmdbData['release_date'] 
                        ? Carbon::parse($tmdbData['release_date'])->year 
                        : null,
                    'duration' => $tmdbData['runtime'] ?? null,
                    'rating' => $tmdbData['vote_average'] ?? 0,
                    'vote_count' => $tmdbData['vote_count'] ?? 0,
                    'popularity' => $tmdbData['popularity'] ?? 0,
                    'quality' => $movieData['quality'],
                    'status' => $request->default_status,
                    'added_by' => auth()->id(),
                ]);

                // Import genres
                if (isset($tmdbData['genres']) && is_array($tmdbData['genres'])) {
                    $genreIds = [];
                    
                    foreach ($tmdbData['genres'] as $tmdbGenre) {
                        $genre = Genre::firstOrCreate(
                            ['tmdb_id' => $tmdbGenre['id']],
                            [
                                'name' => $tmdbGenre['name'],
                                'slug' => Str::slug($tmdbGenre['name']),
                            ]
                        );
                        $genreIds[] = $genre->id;
                    }
                    
                    $movie->genres()->attach($genreIds);
                }

                $imported[] = $movie->load('genres');
                
            } catch (\Exception $e) {
                Log::error("TMDB Bulk Import Error for movie {$movieData['tmdb_id']}: " . $e->getMessage());
                
                $errors[] = [
                    'tmdb_id' => $movieData['tmdb_id'],
                    'error' => 'Import failed: ' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'errors' => $errors,
            'skipped' => $skipped,
            'summary' => [
                'imported_count' => count($imported),
                'error_count' => count($errors),
                'skipped_count' => count($skipped),
                'total_processed' => count($request->movies)
            ]
        ]);
    }

    /**
     * Get popular movies from TMDB
     */
    public function popular(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $results = $this->tmdbService->getPopularMovies($page);
            
            if (!$results['success']) {
                return response()->json([
                    'error' => $results['message'] ?? 'Failed to get popular movies'
                ], 400);
            }

            // Check which movies already exist
            $tmdbIds = collect($results['results'])->pluck('tmdb_id');
            $existingMovies = Movie::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();
            
            $results['results'] = collect($results['results'])->map(function ($movie) use ($existingMovies) {
                $movie['exists_in_db'] = in_array($movie['tmdb_id'], $existingMovies);
                return $movie;
            })->toArray();

            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('TMDB Popular Movies Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to get popular movies. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get trending movies from TMDB
     */
    public function trending(Request $request)
    {
        try {
            $timeWindow = $request->get('time_window', 'week'); // day or week
            $results = $this->tmdbService->getTrendingMovies($timeWindow);
            
            if (!$results['success']) {
                return response()->json([
                    'error' => $results['message'] ?? 'Failed to get trending movies'
                ], 400);
            }

            // Check which movies already exist
            $tmdbIds = collect($results['results'])->pluck('tmdb_id');
            $existingMovies = Movie::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();
            
            $results['results'] = collect($results['results'])->map(function ($movie) use ($existingMovies) {
                $movie['exists_in_db'] = in_array($movie['tmdb_id'], $existingMovies);
                return $movie;
            })->toArray();

            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('TMDB Trending Movies Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to get trending movies. Please try again later.'
            ], 500);
        }
    }

    /**
     * Sync movie data with TMDB (update existing movies)
     */
    public function syncMovie(Movie $movie)
    {
        if (!$movie->tmdb_id) {
            return response()->json([
                'error' => 'Movie does not have TMDB ID!'
            ], 400);
        }

        try {
            $tmdbData = $this->tmdbService->getMovieDetails($movie->tmdb_id);
            
            if (isset($tmdbData['error'])) {
                return response()->json([
                    'error' => $tmdbData['error']
                ], 400);
            }

            // Update movie data (preserve custom fields)
            $movie->update([
                'title' => $tmdbData['title'],
                'original_title' => $tmdbData['original_title'] ?? $tmdbData['title'],
                'overview' => $tmdbData['overview'] ?? $movie->overview,
                'poster_url' => $tmdbData['poster_path'] 
                    ? 'https://image.tmdb.org/t/p/w500' . $tmdbData['poster_path'] 
                    : $movie->poster_url,
                'backdrop_url' => $tmdbData['backdrop_path'] 
                    ? 'https://image.tmdb.org/t/p/w1280' . $tmdbData['backdrop_path'] 
                    : $movie->backdrop_url,
                'duration' => $tmdbData['runtime'] ?? $movie->duration,
                'rating' => $tmdbData['vote_average'] ?? $movie->rating,
                'vote_count' => $tmdbData['vote_count'] ?? $movie->vote_count,
                'popularity' => $tmdbData['popularity'] ?? $movie->popularity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movie synced successfully!',
                'movie' => $movie->fresh()
            ]);
            
        } catch (\Exception $e) {
            Log::error('TMDB Sync Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to sync movie. Please try again later.'
            ], 500);
        }
    }
}