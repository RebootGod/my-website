<?php
// ========================================
// ENHANCED TMDB SERVICE
// ========================================
// File: app/Services/TMDBService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TMDBService
{
    protected $apiKey;
    protected $baseUrl;
    protected $imageUrl;
    protected $useV4Token = false;

    // Cache durations (in seconds)
    protected $cacheMovieDetails = 3600; // 1 hour
    protected $cacheSearchResults = 1800; // 30 minutes
    protected $cachePopularMovies = 3600; // 1 hour
    protected $cacheTrendingMovies = 1800; // 30 minutes

    public function __construct()
    {
        $this->apiKey = config('services.tmdb.api_key', env('TMDB_API_KEY'));
        $this->baseUrl = config('services.tmdb.base_url', 'https://api.themoviedb.org/3');
        $this->imageUrl = config('services.tmdb.image_url', 'https://image.tmdb.org/t/p');
        // Heuristic: TMDB v4 tokens are long JWT strings typically starting with 'eyJ'
        // If user provided a v4 token in TMDB_API_KEY, transparently support it
        if (is_string($this->apiKey) && str_starts_with($this->apiKey, 'eyJ')) {
            $this->useV4Token = true;
        }
    }

    /**
     * Smart search - detects if input is ID or title
     */
    public function smartSearch($query)
    {
        // Check if query is numeric (TMDB ID)
        if (is_numeric($query)) {
            // Try to get movie by ID first
            $movieDetails = $this->getMovieDetails($query);
            
            if ($movieDetails['success']) {
                // Format the detailed movie data to match search result format
                $formattedData = [
                    'tmdb_id' => $movieDetails['data']['tmdb_id'],
                    'title' => $movieDetails['data']['title'],
                    'original_title' => $movieDetails['data']['original_title'],
                    'description' => $movieDetails['data']['description'],
                    'overview' => $movieDetails['data']['description'], // Alias for compatibility
                    'poster_path' => $this->extractRelativePath($movieDetails['data']['poster_path']),
                    'backdrop_path' => $this->extractRelativePath($movieDetails['data']['backdrop_path']),
                    'year' => $movieDetails['data']['year'],
                    'release_date' => $movieDetails['data']['release_date'],
                    'rating' => $movieDetails['data']['rating'],
                    'vote_average' => $movieDetails['data']['rating'], // Alias for compatibility
                    'vote_count' => $movieDetails['data']['vote_count'],
                    'popularity' => $movieDetails['data']['popularity'],
                    'genre_ids' => $movieDetails['data']['genre_ids'],
                ];
                
                // Format as search result array
                return [
                    'success' => true,
                    'results' => [$formattedData],
                    'total_results' => 1,
                    'total_pages' => 1,
                    'current_page' => 1,
                    'search_type' => 'id'
                ];
            }

            // If numeric lookup failed, don't silently fall back to text search.
            // Surface a clear error so the UI can inform the user.
            return [
                'success' => false,
                'message' => $movieDetails['message'] ?? 'Movie not found for the provided TMDB ID',
                'results' => []
            ];
        }
        
        // If not numeric or ID search failed, search by title
        return $this->searchMovies($query);
    }

    /**
     * Search movies by title with caching
     */
    public function searchMovies($query, $page = 1)
    {
        $cacheKey = "tmdb:search:movies:" . md5($query . '_' . $page);

        return Cache::remember($cacheKey, $this->cacheSearchResults, function () use ($query, $page) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/search/movie", [
                    'query' => $query,
                    'page' => $page,
                    'language' => 'en-US',
                    'include_adult' => false
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    $results = collect($data['results'])->map(function ($movie) {
                        return $this->formatMovieData($movie);
                    })->toArray();

                    return [
                        'success' => true,
                        'results' => $results,
                        'total_pages' => $data['total_pages'] ?? 1,
                        'total_results' => $data['total_results'] ?? 0,
                        'current_page' => $data['page'] ?? 1,
                        'search_type' => 'title'
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Failed to search movies',
                    'results' => []
                ];

            } catch (\Exception $e) {
                Log::error('TMDB Search Error: ' . $e->getMessage());

                return [
                    'success' => false,
                    'message' => 'Error searching TMDB: ' . $e->getMessage(),
                    'results' => []
                ];
            }
        });
    }

    /**
     * Get movie details by TMDB ID with caching
     */
    public function getMovieDetails($tmdbId)
    {
        $cacheKey = "tmdb:movie:details:{$tmdbId}";

        return Cache::remember($cacheKey, $this->cacheMovieDetails, function () use ($tmdbId) {
            try {
                // Main movie details
                $response = $this->makeRequest("{$this->baseUrl}/movie/{$tmdbId}", [
                    'language' => 'en-US',
                    'append_to_response' => 'credits,videos,images,external_ids'
                ]);

                if (!$response->successful()) {
                    $status = $response->status();
                    $body = $response->body();
                    Log::error('TMDB Get Details Error', [
                        'tmdb_id' => $tmdbId,
                        'status' => $status,
                        'body' => $body,
                    ]);
                    return [
                        'success' => false,
                        'message' => ($status === 401 || $status === 403)
                            ? 'TMDB authentication failed. Check TMDB_API_KEY.'
                            : 'Movie not found or TMDB error (status ' . $status . ')',
                    ];
                }

                $movie = $response->json();

                // Get additional details
                $credits = $movie['credits'] ?? [];
                $videos = $movie['videos']['results'] ?? [];
                $externalIds = $movie['external_ids'] ?? [];

                return [
                    'success' => true,
                    'data' => [
                        'tmdb_id' => $movie['id'],
                        'imdb_id' => $externalIds['imdb_id'] ?? $movie['imdb_id'] ?? null,
                        'title' => $movie['title'],
                        'original_title' => $movie['original_title'] ?? $movie['title'],
                        'description' => $movie['overview'],
                        'poster_path' => $movie['poster_path'] ? $this->imageUrl . '/w500' . $movie['poster_path'] : null,
                        'backdrop_path' => $movie['backdrop_path'] ? $this->imageUrl . '/original' . $movie['backdrop_path'] : null,
                        'year' => isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null,
                        'release_date' => $movie['release_date'] ?? null,
                        'duration' => $movie['runtime'] ?? null,
                        'rating' => $movie['vote_average'] ?? 0,
                        'vote_count' => $movie['vote_count'] ?? 0,
                        'popularity' => $movie['popularity'] ?? 0,
                        'genres' => collect($movie['genres'] ?? [])->pluck('name')->toArray(),
                        'genre_ids' => collect($movie['genres'] ?? [])->pluck('id')->toArray(),
                        'tagline' => $movie['tagline'] ?? null,
                        'status' => $movie['status'] ?? null,
                        'budget' => $movie['budget'] ?? 0,
                        'revenue' => $movie['revenue'] ?? 0,
                        'production_companies' => collect($movie['production_companies'] ?? [])->pluck('name')->toArray(),
                        'production_countries' => collect($movie['production_countries'] ?? [])->pluck('name')->toArray(),
                        'spoken_languages' => collect($movie['spoken_languages'] ?? [])->pluck('english_name')->toArray(),
                        'director' => $this->getDirector($credits),
                        'cast' => $this->getMainCast($credits, 10),
                        'trailer' => $this->getTrailer($videos),
                        'homepage' => $movie['homepage'] ?? null,
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('TMDB Get Details Error: ' . $e->getMessage());

                return [
                    'success' => false,
                    'message' => 'Error fetching movie details: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get movie by IMDB ID
     */
    public function getMovieByImdbId($imdbId)
    {
        try {
            $response = $this->makeRequest("{$this->baseUrl}/find/{$imdbId}", [
                'external_source' => 'imdb_id'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['movie_results'])) {
                    $movie = $data['movie_results'][0];
                    return $this->getMovieDetails($movie['id']);
                }
            }

            return [
                'success' => false,
                'message' => 'Movie not found with IMDB ID: ' . $imdbId
            ];

        } catch (\Exception $e) {
            Log::error('TMDB IMDB Search Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error searching by IMDB ID: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get popular movies with caching
     */
    public function getPopularMovies($page = 1)
    {
        $cacheKey = "tmdb:movies:popular:page_{$page}";

        return Cache::remember($cacheKey, $this->cachePopularMovies, function () use ($page) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/movie/popular", [
                    'page' => $page,
                    'language' => 'en-US'
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    $results = collect($data['results'])->map(function ($movie) {
                        return $this->formatMovieData($movie);
                    })->toArray();

                    return [
                        'success' => true,
                        'results' => $results,
                        'total_pages' => $data['total_pages'] ?? 1,
                        'current_page' => $data['page'] ?? 1
                    ];
                }

                return [
                    'success' => false,
                    'results' => []
                ];

            } catch (\Exception $e) {
                Log::error('TMDB Popular Movies Error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'results' => []
                ];
            }
        });
    }

    /**
     * Get trending movies with caching
     */
    public function getTrendingMovies($timeWindow = 'week')
    {
        $cacheKey = "tmdb:movies:trending:{$timeWindow}";

        return Cache::remember($cacheKey, $this->cacheTrendingMovies, function () use ($timeWindow) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/trending/movie/{$timeWindow}");

                if ($response->successful()) {
                    $data = $response->json();

                    $results = collect($data['results'])->map(function ($movie) {
                        return $this->formatMovieData($movie);
                    })->toArray();

                    return [
                        'success' => true,
                        'results' => $results
                    ];
                }

                return [
                    'success' => false,
                    'results' => []
                ];

            } catch (\Exception $e) {
                Log::error('TMDB Trending Movies Error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'results' => []
                ];
            }
        });
    }

    /**
     * Format movie data consistently
     */
    protected function formatMovieData($movie)
    {
        return [
            'tmdb_id' => $movie['id'],
            'title' => $movie['title'],
            'original_title' => $movie['original_title'] ?? $movie['title'],
            'description' => $movie['overview'] ?? '',
            'poster_path' => $movie['poster_path'] ? $this->imageUrl . '/w500' . $movie['poster_path'] : null,
            'backdrop_path' => $movie['backdrop_path'] ? $this->imageUrl . '/original' . $movie['backdrop_path'] : null,
            'year' => isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null,
            'release_date' => $movie['release_date'] ?? null,
            'rating' => $movie['vote_average'] ?? 0,
            'vote_average' => $movie['vote_average'] ?? 0, // Alias for frontend that expects vote_average
            'vote_count' => $movie['vote_count'] ?? 0,
            'popularity' => $movie['popularity'] ?? 0,
            'genre_ids' => $movie['genre_ids'] ?? [],
        ];
    }

    /**
     * Get director from credits
     */
    protected function getDirector($credits)
    {
        $crew = $credits['crew'] ?? [];
        $director = collect($crew)->firstWhere('job', 'Director');
        
        return $director ? $director['name'] : null;
    }

    /**
     * Get main cast
     */
    protected function getMainCast($credits, $limit = 10)
    {
        $cast = $credits['cast'] ?? [];
        
        return collect($cast)
            ->take($limit)
            ->map(function ($actor) {
                return [
                    'name' => $actor['name'],
                    'character' => $actor['character'] ?? null,
                    'profile_path' => $actor['profile_path'] ? $this->imageUrl . '/w200' . $actor['profile_path'] : null
                ];
            })
            ->toArray();
    }

    /**
     * Get trailer URL
     */
    protected function getTrailer($videos)
    {
        $trailer = collect($videos)
            ->where('type', 'Trailer')
            ->where('site', 'YouTube')
            ->first();
        
        if ($trailer) {
            return 'https://www.youtube.com/watch?v=' . $trailer['key'];
        }
        
        // Fallback to any YouTube video
        $video = collect($videos)
            ->where('site', 'YouTube')
            ->first();
        
        return $video ? 'https://www.youtube.com/watch?v=' . $video['key'] : null;
    }

    /**
     * Search TV series with caching
     */
    public function searchTv($query, $page = 1)
    {
        $cacheKey = "tmdb:search:tv:" . md5($query . '_' . $page);

        return Cache::remember($cacheKey, $this->cacheSearchResults, function () use ($query, $page) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/search/tv", [
                    'query' => $query,
                    'page' => $page,
                    'include_adult' => false
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'data' => [
                            'results' => $data['results'],
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
                Log::error('TMDB TV Search Error: ' . $e->getMessage());

                return [
                    'success' => false,
                    'error' => 'Search failed: ' . $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * Get TV series details with caching
     */
    public function getTvDetails($tvId)
    {
        $cacheKey = "tmdb:tv:details:{$tvId}";

        return Cache::remember($cacheKey, $this->cacheMovieDetails, function () use ($tvId) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/tv/{$tvId}", [
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
                            'last_air_date' => $series['last_air_date'],
                            'vote_average' => $series['vote_average'],
                            'vote_count' => $series['vote_count'],
                            'popularity' => $series['popularity'],
                            'number_of_seasons' => $series['number_of_seasons'],
                            'number_of_episodes' => $series['number_of_episodes'],
                            'genres' => $series['genres'] ?? [],
                            'credits' => $series['credits'] ?? [],
                            'videos' => $series['videos']['results'] ?? []
                        ]
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'TV series not found with ID: ' . $tvId
                ];

            } catch (\Exception $e) {
                Log::error('TMDB TV Details Error: ' . $e->getMessage());

                return [
                    'success' => false,
                    'error' => 'Error fetching TV series details: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get popular TV series with caching
     */
    public function getPopularTv($page = 1)
    {
        $cacheKey = "tmdb:tv:popular:page_{$page}";

        return Cache::remember($cacheKey, $this->cachePopularMovies, function () use ($page) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/tv/popular", [
                    'page' => $page,
                    'language' => 'en-US'
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'data' => [
                            'results' => $data['results'],
                            'total_results' => $data['total_results'],
                            'total_pages' => $data['total_pages'],
                            'page' => $data['page']
                        ]
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Failed to fetch popular TV series'
                ];

            } catch (\Exception $e) {
                Log::error('TMDB Popular TV Error: ' . $e->getMessage());

                return [
                    'success' => false,
                    'error' => 'Error fetching popular TV series: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get trending TV series with caching
     */
    public function getTrendingTv($timeWindow = 'week')
    {
        $cacheKey = "tmdb:tv:trending:{$timeWindow}";

        return Cache::remember($cacheKey, $this->cacheTrendingMovies, function () use ($timeWindow) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/trending/tv/{$timeWindow}");

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'data' => [
                            'results' => $data['results'],
                            'total_results' => $data['total_results'] ?? count($data['results']),
                            'total_pages' => $data['total_pages'] ?? 1,
                            'page' => 1
                        ]
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Failed to fetch trending TV series'
                ];

            } catch (\Exception $e) {
                Log::error('TMDB Trending TV Error: ' . $e->getMessage());

                return [
                    'success' => false,
                    'error' => 'Error fetching trending TV series: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    /**
     * Extract relative path from full TMDB image URL
     */
    protected function extractRelativePath($fullUrl)
    {
        if (!$fullUrl) {
            return null;
        }
        
        // Extract the path part from full URL
        // Example: "https://image.tmdb.org/t/p/w500/abc123.jpg" -> "/abc123.jpg"
        $parsedUrl = parse_url($fullUrl);
        if (isset($parsedUrl['path'])) {
            // Remove the size prefix (/w500, /original, etc.) and return just the file path
            $path = $parsedUrl['path'];
            $pathParts = explode('/', $path);
            // Last part is the filename, second to last is the size
            if (count($pathParts) >= 2) {
                return '/' . end($pathParts);
            }
        }
        
        return null;
    }

    /**
     * Make TMDB request supporting both v3 (api_key param) and v4 (Bearer token)
     */
    protected function makeRequest(string $url, array $params = [])
    {
        if ($this->useV4Token) {
            return Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($url, $params);
        }

        // v3 key via query param
        $params = array_merge(['api_key' => $this->apiKey], $params);
        return Http::get($url, $params);
    }
}