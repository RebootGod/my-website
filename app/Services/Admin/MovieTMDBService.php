<?php

namespace App\Services\Admin;

use App\Models\Movie;
use App\Models\Genre;
use App\Services\TMDBService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class MovieTMDBService
{
    protected $tmdbService;
    protected $dataService;

    public function __construct()
    {
        $this->tmdbService = new TMDBService();
        $this->dataService = new MovieTMDBDataService();
    }

    /**
     * Search movies on TMDB
     */
    public function searchMovies(string $query, int $page = 1): array
    {
        try {
            $results = $this->tmdbService->searchMovies($query, $page);
            
            if (!$results['success']) {
                return [
                    'success' => false,
                    'message' => 'TMDB search failed: ' . ($results['error'] ?? 'Unknown error'),
                    'data' => []
                ];
            }

            // Check which movies already exist in our database
            $tmdbIds = collect($results['data']['results'])->pluck('id')->toArray();
            $existingIds = Movie::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();

            // Mark existing movies
            $resultsWithStatus = collect($results['data']['results'])->map(function ($movie) use ($existingIds) {
                $movie['exists_in_db'] = in_array($movie['id'], $existingIds);
                return $movie;
            });

            return [
                'success' => true,
                'data' => [
                    'results' => $resultsWithStatus->toArray(),
                    'total_results' => $results['data']['total_results'] ?? 0,
                    'total_pages' => $results['data']['total_pages'] ?? 1,
                    'page' => $page
                ]
            ];

        } catch (\Exception $e) {
            Log::error('TMDB search error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get movie details from TMDB
     */
    public function getMovieDetails(int $tmdbId): array
    {
        try {
            $result = $this->tmdbService->getMovieDetails($tmdbId);
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch movie details: ' . ($result['error'] ?? 'Unknown error'),
                    'data' => null
                ];
            }

            // Check if movie already exists
            $existingMovie = Movie::where('tmdb_id', $tmdbId)->first();
            
            $movieData = $result['data'];
            $movieData['exists_in_db'] = $existingMovie !== null;
            $movieData['existing_movie'] = $existingMovie;

            return [
                'success' => true,
                'data' => $movieData
            ];

        } catch (\Exception $e) {
            Log::error('TMDB details error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch details: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Import movie from TMDB
     */
    public function importMovie(int $tmdbId): array
    {
        try {
            // Check if movie already exists
            if (Movie::where('tmdb_id', $tmdbId)->exists()) {
                return [
                    'success' => false,
                    'message' => 'Movie already exists in database',
                    'data' => null
                ];
            }

            // Get movie details from TMDB
            $tmdbResult = $this->getMovieDetails($tmdbId);
            
            if (!$tmdbResult['success']) {
                return $tmdbResult;
            }

            $tmdbData = $tmdbResult['data'];

            // Prepare movie data using data service
            $movieData = $this->dataService->prepareTMDBMovieData($tmdbData);

            // Create movie
            $movie = Movie::create($movieData);

            // Sync genres using data service
            $this->dataService->syncMovieGenres($movie, $tmdbData['genres'] ?? []);

            // Dispatch image download jobs using data service
            $this->dataService->dispatchImageDownloads($movie, $movieData);

            Log::info('Movie imported from TMDB', [
                'tmdb_id' => $tmdbId,
                'movie_id' => $movie->id,
                'title' => $movie->title
            ]);

            return [
                'success' => true,
                'message' => 'Movie imported successfully',
                'data' => $movie->load('genres')
            ];

        } catch (\Exception $e) {
            Log::error('TMDB import error: ' . $e->getMessage(), ['tmdb_id' => $tmdbId]);
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Bulk import movies from TMDB
     */
    public function bulkImportMovies(array $tmdbIds): array
    {
        $imported = [];
        $failed = [];
        $skipped = [];

        foreach ($tmdbIds as $tmdbId) {
            $result = $this->importMovie($tmdbId);
            
            if ($result['success']) {
                $imported[] = [
                    'tmdb_id' => $tmdbId,
                    'title' => $result['data']['title'],
                    'movie_id' => $result['data']['id']
                ];
            } elseif (str_contains($result['message'], 'already exists')) {
                $skipped[] = $tmdbId;
            } else {
                $failed[] = [
                    'tmdb_id' => $tmdbId,
                    'error' => $result['message']
                ];
            }
        }

        $summary = [
            'imported_count' => count($imported),
            'skipped_count' => count($skipped),
            'failed_count' => count($failed),
            'total_processed' => count($tmdbIds)
        ];

        Log::info('Bulk TMDB import completed', $summary);

        return [
            'success' => true,
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
                'summary' => $summary
            ]
        ];
    }

    /**
     * Get TMDB configuration for frontend
     */
    public function getTMDBConfiguration(): array
    {
        try {
            $config = $this->tmdbService->getConfiguration();
            
            return [
                'success' => true,
                'data' => $config
            ];
            
        } catch (\Exception $e) {
            Log::error('TMDB configuration error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get TMDB configuration',
                'data' => []
            ];
        }
    }

    /**
     * Get popular movies from TMDB
     */
    public function getPopularMovies(int $page = 1): array
    {
        try {
            $results = $this->tmdbService->getPopularMovies($page);
            
            if (!$results['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch popular movies',
                    'data' => []
                ];
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('TMDB popular movies error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch popular movies: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Validate TMDB ID
     */
    public function validateTMDBId(int $tmdbId): bool
    {
        $result = $this->getMovieDetails($tmdbId);
        return $result['success'];
    }
}