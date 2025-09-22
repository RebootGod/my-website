<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewTMDBService
{
    protected $apiKey;
    protected $baseUrl;
    protected $imageUrl;

    public function __construct()
    {
        $this->apiKey = config('services.tmdb.api_key', env('TMDB_API_KEY'));
        $this->baseUrl = 'https://api.themoviedb.org/3';
        $this->imageUrl = 'https://image.tmdb.org/t/p';
        
        // Log for debugging
        Log::info('NewTMDBService initialized', [
            'api_key_set' => !empty($this->apiKey),
            'api_key_preview' => $this->apiKey ? substr($this->apiKey, 0, 8) . '...' : 'NOT SET'
        ]);
    }

    /**
     * Universal search - handles both TMDB ID and title search
     */
    public function search($query, $page = 1)
    {
        try {
            // Check if query is numeric (TMDB ID)
            if (is_numeric($query)) {
                return $this->searchById($query);
            }
            
            // Search by title
            return $this->searchByTitle($query, $page);
            
        } catch (\Exception $e) {
            Log::error('TMDB Search Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
                'results' => []
            ];
        }
    }

    /**
     * Search movie by TMDB ID
     */
    protected function searchById($tmdbId)
    {
        try {
            $url = "{$this->baseUrl}/movie/{$tmdbId}";
            
            Log::info('TMDB ID Search:', [
                'tmdb_id' => $tmdbId,
                'url' => $url,
                'api_key_set' => !empty($this->apiKey)
            ]);
            
            $response = Http::get($url, [
                'api_key' => $this->apiKey,
                'language' => 'en-US'
            ]);

            Log::info('TMDB ID Response:', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_preview' => substr($response->body(), 0, 200)
            ]);

            if (!$response->successful()) {
                Log::warning('TMDB ID Search Failed:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Movie not found with ID: ' . $tmdbId,
                    'results' => []
                ];
            }

            $movie = $response->json();
            
            return [
                'success' => true,
                'results' => [$this->formatMovie($movie)],
                'total_results' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'search_type' => 'id'
            ];

        } catch (\Exception $e) {
            Log::error('TMDB ID Search Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to fetch movie details',
                'results' => []
            ];
        }
    }

    /**
     * Search movies by title
     */
    protected function searchByTitle($query, $page = 1)
    {
        try {
            $response = Http::get("{$this->baseUrl}/search/movie", [
                'api_key' => $this->apiKey,
                'query' => $query,
                'page' => $page,
                'language' => 'en-US',
                'include_adult' => false
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Search request failed',
                    'results' => []
                ];
            }

            $data = $response->json();
            
            $results = collect($data['results'])->map(function ($movie) {
                return $this->formatMovie($movie);
            })->toArray();

            return [
                'success' => true,
                'results' => $results,
                'total_pages' => $data['total_pages'] ?? 1,
                'total_results' => $data['total_results'] ?? 0,
                'current_page' => $data['page'] ?? 1,
                'search_type' => 'title'
            ];

        } catch (\Exception $e) {
            Log::error('TMDB Title Search Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Search failed',
                'results' => []
            ];
        }
    }

    /**
     * Get movie details by TMDB ID (for import)
     */
    public function getMovieDetails($tmdbId)
    {
        try {
            $response = Http::get("{$this->baseUrl}/movie/{$tmdbId}", [
                'api_key' => $this->apiKey,
                'language' => 'en-US',
                'append_to_response' => 'credits,videos'
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Movie not found with ID: ' . $tmdbId
                ];
            }

            $movie = $response->json();
            
            return [
                'success' => true,
                'data' => [
                    'id' => $movie['id'],
                    'tmdb_id' => $movie['id'],
                    'title' => $movie['title'],
                    'original_title' => $movie['original_title'] ?? $movie['title'],
                    'overview' => $movie['overview'] ?? '',
                    'poster_path' => $movie['poster_path'],
                    'backdrop_path' => $movie['backdrop_path'],
                    'release_date' => $movie['release_date'] ?? null,
                    'runtime' => $movie['runtime'] ?? null,
                    'vote_average' => $movie['vote_average'] ?? 0,
                    'vote_count' => $movie['vote_count'] ?? 0,
                    'popularity' => $movie['popularity'] ?? 0,
                    'genres' => $movie['genres'] ?? [],
                ]
            ];

        } catch (\Exception $e) {
            Log::error('TMDB Get Details Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to fetch movie details'
            ];
        }
    }

    /**
     * Get popular movies
     */
    public function getPopularMovies($page = 1)
    {
        try {
            $response = Http::get("{$this->baseUrl}/movie/popular", [
                'api_key' => $this->apiKey,
                'page' => $page,
                'language' => 'en-US'
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'results' => []
                ];
            }

            $data = $response->json();
            
            $results = collect($data['results'])->map(function ($movie) {
                return $this->formatMovie($movie);
            })->toArray();

            return [
                'success' => true,
                'results' => $results,
                'total_pages' => $data['total_pages'] ?? 1,
                'current_page' => $data['page'] ?? 1
            ];

        } catch (\Exception $e) {
            Log::error('TMDB Popular Movies Error: ' . $e->getMessage());
            return [
                'success' => false,
                'results' => []
            ];
        }
    }

    /**
     * Get trending movies
     */
    public function getTrendingMovies($timeWindow = 'week')
    {
        try {
            $response = Http::get("{$this->baseUrl}/trending/movie/{$timeWindow}", [
                'api_key' => $this->apiKey
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'results' => []
                ];
            }

            $data = $response->json();
            
            $results = collect($data['results'])->map(function ($movie) {
                return $this->formatMovie($movie);
            })->toArray();

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('TMDB Trending Movies Error: ' . $e->getMessage());
            return [
                'success' => false,
                'results' => []
            ];
        }
    }

    /**
     * Format movie data consistently for frontend
     */
    protected function formatMovie($movie)
    {
        return [
            'tmdb_id' => $movie['id'],
            'title' => $movie['title'],
            'original_title' => $movie['original_title'] ?? $movie['title'],
            'description' => $movie['overview'] ?? '',
            'overview' => $movie['overview'] ?? '', // Alias for compatibility
            'poster_path' => $movie['poster_path'], // Keep as relative path
            'backdrop_path' => $movie['backdrop_path'], // Keep as relative path
            'year' => isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null,
            'release_date' => $movie['release_date'] ?? null,
            'rating' => $movie['vote_average'] ?? 0,
            'vote_average' => $movie['vote_average'] ?? 0, // Alias for compatibility
            'vote_count' => $movie['vote_count'] ?? 0,
            'popularity' => $movie['popularity'] ?? 0,
            'genre_ids' => $movie['genre_ids'] ?? [],
        ];
    }

    /**
     * Search TV series - handles both TMDB ID and title search
     */
    public function searchSeries($query, $page = 1)
    {
        try {
            // Clean and validate query
            $query = trim($query);
            if (empty($query)) {
                return [
                    'success' => false,
                    'error' => 'Query cannot be empty',
                    'data' => []
                ];
            }

            Log::info('NewTMDBService Series Search:', ['query' => $query, 'page' => $page]);

            // Check if query is numeric (TMDB ID)
            if (is_numeric($query)) {
                return $this->getSeriesById($query, $page);
            }

            // Search by title
            return $this->searchSeriesByTitle($query, $page);

        } catch (\Exception $e) {
            Log::error('NewTMDBService Series Search Error:', [
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get series by TMDB ID
     */
    protected function getSeriesById($tmdbId, $page = 1)
    {
        try {
            $response = Http::get("{$this->baseUrl}/tv/{$tmdbId}", [
                'api_key' => $this->apiKey,
                'language' => 'en-US'
            ]);

            if ($response->successful()) {
                $series = $response->json();

                // Check if series already exists in our database
                $existingIds = \App\Models\Series::where('tmdb_id', $tmdbId)->pluck('tmdb_id')->toArray();

                $formattedSeries = $this->formatSeries($series);
                $formattedSeries['exists_in_db'] = in_array($tmdbId, $existingIds);

                return [
                    'success' => true,
                    'data' => [
                        'results' => [$formattedSeries],
                        'total_results' => 1,
                        'total_pages' => 1,
                        'page' => 1
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Series not found with ID: ' . $tmdbId,
                'data' => []
            ];

        } catch (\Exception $e) {
            Log::error('Get Series by ID Error:', [
                'tmdb_id' => $tmdbId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch series details: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Search series by title
     */
    protected function searchSeriesByTitle($query, $page = 1)
    {
        try {
            $response = Http::get("{$this->baseUrl}/search/tv", [
                'api_key' => $this->apiKey,
                'query' => $query,
                'page' => $page,
                'language' => 'en-US',
                'include_adult' => false
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('TMDB Series API Response:', $data);

                // Check which series already exist in our database
                $tmdbIds = collect($data['results'])->pluck('id')->toArray();
                $existingIds = \App\Models\Series::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();

                $results = collect($data['results'])->map(function ($series) use ($existingIds) {
                    $formatted = $this->formatSeries($series);
                    $formatted['exists_in_db'] = in_array($series['id'], $existingIds);
                    return $formatted;
                })->toArray();

                return [
                    'success' => true,
                    'data' => [
                        'results' => $results,
                        'total_results' => $data['total_results'],
                        'total_pages' => $data['total_pages'],
                        'page' => $data['page']
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Search request failed',
                'data' => []
            ];

        } catch (\Exception $e) {
            Log::error('Search Series by Title Error:', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get popular TV series
     */
    public function getPopularSeries($page = 1)
    {
        try {
            $response = Http::get("{$this->baseUrl}/tv/popular", [
                'api_key' => $this->apiKey,
                'page' => $page,
                'language' => 'en-US'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check which series already exist in our database
                $tmdbIds = collect($data['results'])->pluck('id')->toArray();
                $existingIds = \App\Models\Series::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();

                $results = collect($data['results'])->map(function ($series) use ($existingIds) {
                    $formatted = $this->formatSeries($series);
                    $formatted['exists_in_db'] = in_array($series['id'], $existingIds);
                    return $formatted;
                })->toArray();

                return [
                    'success' => true,
                    'data' => [
                        'results' => $results,
                        'total_results' => $data['total_results'],
                        'total_pages' => $data['total_pages'],
                        'page' => $data['page']
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch popular series',
                'data' => []
            ];

        } catch (\Exception $e) {
            Log::error('Get Popular Series Error:', $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to fetch popular series: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get trending TV series
     */
    public function getTrendingSeries($timeWindow = 'week')
    {
        try {
            $response = Http::get("{$this->baseUrl}/trending/tv/{$timeWindow}", [
                'api_key' => $this->apiKey,
                'language' => 'en-US'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check which series already exist in our database
                $tmdbIds = collect($data['results'])->pluck('id')->toArray();
                $existingIds = \App\Models\Series::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();

                $results = collect($data['results'])->map(function ($series) use ($existingIds) {
                    $formatted = $this->formatSeries($series);
                    $formatted['exists_in_db'] = in_array($series['id'], $existingIds);
                    return $formatted;
                })->toArray();

                return [
                    'success' => true,
                    'data' => [
                        'results' => $results,
                        'total_results' => $data['total_results'] ?? count($results),
                        'total_pages' => $data['total_pages'] ?? 1,
                        'page' => 1
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch trending series',
                'data' => []
            ];

        } catch (\Exception $e) {
            Log::error('Get Trending Series Error:', $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to fetch trending series: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get TV series details
     */
    public function getSeriesDetails($tmdbId)
    {
        try {
            $response = Http::get("{$this->baseUrl}/tv/{$tmdbId}", [
                'api_key' => $this->apiKey,
                'language' => 'en-US',
                'append_to_response' => 'credits,videos,genres'
            ]);

            if ($response->successful()) {
                $series = $response->json();

                return [
                    'success' => true,
                    'data' => [
                        'id' => $series['id'],
                        'name' => $series['name'],
                        'original_name' => $series['original_name'],
                        'overview' => $series['overview'],
                        'poster_path' => $series['poster_path'],
                        'backdrop_path' => $series['backdrop_path'],
                        'first_air_date' => $series['first_air_date'],
                        'vote_average' => $series['vote_average'],
                        'vote_count' => $series['vote_count'],
                        'popularity' => $series['popularity'],
                        'genres' => $series['genres'] ?? []
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Series not found with ID: ' . $tmdbId
            ];

        } catch (\Exception $e) {
            Log::error('Get Series Details Error:', [
                'tmdb_id' => $tmdbId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch series details: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format series data for consistent output
     */
    protected function formatSeries($series)
    {
        return [
            'id' => $series['id'],
            'tmdb_id' => $series['id'],
            'title' => $series['name'] ?? '',
            'name' => $series['name'] ?? '',
            'original_name' => $series['original_name'] ?? $series['name'] ?? '',
            'description' => $series['overview'] ?? '',
            'overview' => $series['overview'] ?? '',
            'poster_path' => $series['poster_path'],
            'backdrop_path' => $series['backdrop_path'],
            'first_air_date' => $series['first_air_date'] ?? null,
            'vote_average' => $series['vote_average'] ?? 0,
            'vote_count' => $series['vote_count'] ?? 0,
            'popularity' => $series['popularity'] ?? 0,
            'genre_ids' => $series['genre_ids'] ?? [],
        ];
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured()
    {
        return !empty($this->apiKey);
    }
}