<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Genre;
use App\Services\NewTMDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NewTMDBController extends Controller
{
    protected $tmdbService;

    public function __construct(NewTMDBService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    /**
     * Show TMDB search interface
     */
    public function index()
    {
        return view('admin.tmdb.new-index');
    }

    /**
     * Search movies - handles both TMDB ID and title search
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string|min:1|max:255',
                'page' => 'nullable|integer|min:1|max:1000',
            ]);

            $query = $request->get('query'); // Use get() instead of query property
            $page = $request->get('page', 1);

            Log::info('TMDB Search Request:', [
                'query' => $query,
                'page' => $page
            ]);

            $results = $this->tmdbService->search($query, $page);
            
            Log::info('TMDB Search Results:', [
                'success' => $results['success'],
                'results_count' => isset($results['results']) ? count($results['results']) : 0
            ]);
            
            if (!$results['success']) {
                return response()->json([
                    'error' => $results['message'] ?? 'Search failed'
                ], 400);
            }

            // Check which movies already exist in our database
            if (!empty($results['results'])) {
                $tmdbIds = collect($results['results'])->pluck('tmdb_id');
                $existingMovies = Movie::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();
                
                // Add 'exists' flag to each result
                $results['results'] = collect($results['results'])->map(function ($movie) use ($existingMovies) {
                    $movie['exists_in_db'] = in_array($movie['tmdb_id'], $existingMovies);
                    return $movie;
                })->toArray();
            }

            return response()->json($results);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('TMDB Search Validation Error:', $e->errors());
            return response()->json([
                'error' => 'Validation failed: ' . implode(', ', $e->errors()['query'] ?? ['Invalid query'])
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('New TMDB Search Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to search movies. Please try again later. Details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get popular movies
     */
    public function popular(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $results = $this->tmdbService->getPopularMovies($page);
            
            if (!$results['success']) {
                return response()->json([
                    'error' => 'Failed to get popular movies'
                ], 400);
            }

            // Check which movies already exist
            if (!empty($results['results'])) {
                $tmdbIds = collect($results['results'])->pluck('tmdb_id');
                $existingMovies = Movie::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();
                
                $results['results'] = collect($results['results'])->map(function ($movie) use ($existingMovies) {
                    $movie['exists_in_db'] = in_array($movie['tmdb_id'], $existingMovies);
                    return $movie;
                })->toArray();
            }

            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('New TMDB Popular Movies Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to get popular movies. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get trending movies
     */
    public function trending(Request $request)
    {
        try {
            $timeWindow = $request->get('time_window', 'week');
            $results = $this->tmdbService->getTrendingMovies($timeWindow);
            
            if (!$results['success']) {
                return response()->json([
                    'error' => 'Failed to get trending movies'
                ], 400);
            }

            // Check which movies already exist
            if (!empty($results['results'])) {
                $tmdbIds = collect($results['results'])->pluck('tmdb_id');
                $existingMovies = Movie::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();
                
                $results['results'] = collect($results['results'])->map(function ($movie) use ($existingMovies) {
                    $movie['exists_in_db'] = in_array($movie['tmdb_id'], $existingMovies);
                    return $movie;
                })->toArray();
            }

            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('New TMDB Trending Movies Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to get trending movies. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get movie details for preview
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
            Log::error('New TMDB Get Details Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to get movie details. Please try again later.'
            ], 500);
        }
    }

    /**
     * Import a movie from TMDB
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
            
            if (!$movieData['success']) {
                return response()->json([
                    'error' => $movieData['message'] ?? 'Failed to get movie details'
                ], 400);
            }

            $data = $movieData['data'];

            // Create unique slug
            $slug = Str::slug($data['title']);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Movie::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Import movie
            $movie = Movie::create([
                'tmdb_id' => $data['tmdb_id'],
                'title' => $data['title'],
                'original_title' => $data['original_title'] ?? $data['title'],
                'slug' => $slug,
                'description' => $data['overview'], // Use description field
                'embed_url' => encrypt($request->embed_url),
                'poster_path' => $data['poster_path'] 
                    ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] 
                    : null,
                'poster_url' => $data['poster_path'] 
                    ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] 
                    : null,
                'backdrop_path' => $data['backdrop_path']
                    ? 'https://image.tmdb.org/t/p/w1280' . $data['backdrop_path'] 
                    : null,
                'backdrop_url' => $data['backdrop_path']
                    ? 'https://image.tmdb.org/t/p/w1280' . $data['backdrop_path'] 
                    : null,
                'release_date' => $data['release_date'],
                'year' => $data['release_date'] 
                    ? Carbon::parse($data['release_date'])->year 
                    : null,
                'duration' => $data['runtime'], // Map runtime to duration
                'rating' => $data['vote_average'],
                'vote_count' => $data['vote_count'],
                'popularity' => $data['popularity'],
                'quality' => $request->quality,
                'status' => $request->status,
                'is_active' => true, // Ensure movie is active
                'added_by' => auth()->id(),
            ]);

            // Import genres
            if (isset($data['genres']) && is_array($data['genres'])) {
                $genreIds = [];
                
                foreach ($data['genres'] as $tmdbGenre) {
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
            Log::error('New TMDB Import Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to import movie. Please try again later.'
            ], 500);
        }
    }
}