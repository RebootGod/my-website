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
    public function getMovieDetails($tmdbId, $language = 'id-ID')
    {
        $cacheKey = "tmdb:movie:details:{$tmdbId}:{$language}";

        return Cache::remember($cacheKey, $this->cacheMovieDetails, function () use ($tmdbId, $language) {
            try {
                // Fetch with requested language first (default: id-ID)
                $response = $this->makeRequest("{$this->baseUrl}/movie/{$tmdbId}", [
                    'language' => $language,
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

                $moviePrimary = $response->json();
                
                // Fetch English fallback if primary language is not English
                $movieFallback = null;
                if ($language !== 'en-US') {
                    $responseFallback = $this->makeRequest("{$this->baseUrl}/movie/{$tmdbId}", [
                        'language' => 'en-US',
                        'append_to_response' => 'credits,videos,images,external_ids'
                    ]);
                    
                    if ($responseFallback->successful()) {
                        $movieFallback = $responseFallback->json();
                    }
                }

                // Merge data with field-level fallback
                $movie = $this->mergeMovieData($moviePrimary, $movieFallback, $language);

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
                        'original_language' => $movie['original_language'] ?? 'en',
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
                        'language_used' => $movie['_language_used'] ?? $language,
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
    public function getTvDetails($tvId, $language = 'id-ID')
    {
        $cacheKey = "tmdb:tv:details:{$tvId}:{$language}";

        return Cache::remember($cacheKey, $this->cacheMovieDetails, function () use ($tvId, $language) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/tv/{$tvId}", [
                    'language' => $language,
                    'append_to_response' => 'credits,videos,genres'
                ]);

                if (!$response->successful()) {
                    return [
                        'success' => false,
                        'error' => 'TV series not found with ID: ' . $tvId
                    ];
                }

                $seriesPrimary = $response->json();
                
                // Fetch English fallback if primary language is not English
                $seriesFallback = null;
                if ($language !== 'en-US') {
                    $responseFallback = $this->makeRequest("{$this->baseUrl}/tv/{$tvId}", [
                        'language' => 'en-US',
                        'append_to_response' => 'credits,videos,genres'
                    ]);
                    
                    if ($responseFallback->successful()) {
                        $seriesFallback = $responseFallback->json();
                    }
                }

                // Merge data with field-level fallback
                $series = $this->mergeTvData($seriesPrimary, $seriesFallback, $language);

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
                        'last_air_date' => $series['last_air_date'] ?? null,
                        'vote_average' => $series['vote_average'],
                        'vote_count' => $series['vote_count'],
                        'popularity' => $series['popularity'],
                        'number_of_seasons' => $series['number_of_seasons'],
                        'number_of_episodes' => $series['number_of_episodes'],
                        'genres' => $series['genres'] ?? [],
                        'credits' => $series['credits'] ?? [],
                        'videos' => $series['videos']['results'] ?? [],
                        'language_used' => $series['_language_used'] ?? $language,
                    ]
                ];

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
     * Get TV season details with episodes
     */
    public function getSeasonDetails($tvId, $seasonNumber, $language = 'id-ID')
    {
        $cacheKey = "tmdb:tv:{$tvId}:season:{$seasonNumber}:{$language}";

        return Cache::remember($cacheKey, $this->cacheMovieDetails, function () use ($tvId, $seasonNumber, $language) {
            try {
                $response = $this->makeRequest("{$this->baseUrl}/tv/{$tvId}/season/{$seasonNumber}", [
                    'language' => $language
                ]);

                if (!$response->successful()) {
                    return [
                        'success' => false,
                        'error' => 'Season not found'
                    ];
                }

                $seasonPrimary = $response->json();
                
                // Fetch English fallback if primary language is not English
                $seasonFallback = null;
                if ($language !== 'en-US') {
                    $responseFallback = $this->makeRequest("{$this->baseUrl}/tv/{$tvId}/season/{$seasonNumber}", [
                        'language' => 'en-US'
                    ]);
                    
                    if ($responseFallback->successful()) {
                        $seasonFallback = $responseFallback->json();
                    }
                }

                // Merge season data
                $season = $this->mergeSeasonData($seasonPrimary, $seasonFallback, $language);

                return [
                    'success' => true,
                    'data' => [
                        'id' => $season['id'],
                        'name' => $season['name'],
                        'overview' => $season['overview'],
                        'season_number' => $season['season_number'],
                        'air_date' => $season['air_date'] ?? null,
                        'poster_path' => $season['poster_path'],
                        'episodes' => $season['episodes'] ?? [],
                        'language_used' => $season['_language_used'] ?? $language,
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('TMDB Season Details Error: ' . $e->getMessage());

                return [
                    'success' => false,
                    'error' => 'Error fetching season details: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Merge movie data with field-level fallback (ID → EN)
     */
    protected function mergeMovieData($primary, $fallback, $language)
    {
        if (!$fallback) {
            // No fallback available, use primary as-is
            return array_merge($primary, ['_language_used' => $language]);
        }

        $fieldsUsed = [];
        
        // Field-by-field merge with fallback
        // Smart fallback: Use English if Indonesian returns non-Latin title (Korean, Chinese, Japanese, etc.)
        $useEnglishTitle = false;
        if ($language === 'id-ID' && isset($primary['title'])) {
            // Check if title contains CJK characters (same logic as Python bot)
            $useEnglishTitle = $this->containsNonLatinChars($primary['title']);
            if ($useEnglishTitle) {
                Log::info('Using English title, Indonesian returned non-Latin', [
                    'indonesian_title' => $primary['title'],
                    'english_title' => $fallback['title'] ?? 'N/A'
                ]);
            }
        }

        $merged = [
            'id' => $primary['id'],
            'title' => $useEnglishTitle ? ($fallback['title'] ?? $primary['title']) : 
                       (!empty($primary['title']) ? $primary['title'] : ($fallback['title'] ?? '')),
            'original_title' => !empty($primary['original_title']) ? $primary['original_title'] : ($fallback['original_title'] ?? ''),
            'overview' => !empty($primary['overview']) ? $primary['overview'] : ($fallback['overview'] ?? ''),
            'tagline' => !empty($primary['tagline']) ? $primary['tagline'] : ($fallback['tagline'] ?? ''),
            'poster_path' => $primary['poster_path'] ?? $fallback['poster_path'] ?? null,
            'backdrop_path' => $primary['backdrop_path'] ?? $fallback['backdrop_path'] ?? null,
            'release_date' => $primary['release_date'] ?? $fallback['release_date'] ?? null,
            'runtime' => $primary['runtime'] ?? $fallback['runtime'] ?? null,
            'vote_average' => $primary['vote_average'] ?? $fallback['vote_average'] ?? 0,
            'vote_count' => $primary['vote_count'] ?? $fallback['vote_count'] ?? 0,
            'popularity' => $primary['popularity'] ?? $fallback['popularity'] ?? 0,
            'original_language' => $primary['original_language'] ?? $fallback['original_language'] ?? 'en',
            'status' => $primary['status'] ?? $fallback['status'] ?? null,
            'budget' => $primary['budget'] ?? $fallback['budget'] ?? 0,
            'revenue' => $primary['revenue'] ?? $fallback['revenue'] ?? 0,
            'homepage' => $primary['homepage'] ?? $fallback['homepage'] ?? null,
            'imdb_id' => $primary['imdb_id'] ?? $fallback['imdb_id'] ?? null,
            
            // Complex fields (genres translated, so prefer primary if available)
            'genres' => !empty($primary['genres']) ? $primary['genres'] : ($fallback['genres'] ?? []),
            'production_companies' => !empty($primary['production_companies']) ? $primary['production_companies'] : ($fallback['production_companies'] ?? []),
            'production_countries' => !empty($primary['production_countries']) ? $primary['production_countries'] : ($fallback['production_countries'] ?? []),
            'spoken_languages' => !empty($primary['spoken_languages']) ? $primary['spoken_languages'] : ($fallback['spoken_languages'] ?? []),
            
            // Credits/videos are language-independent
            'credits' => $primary['credits'] ?? $fallback['credits'] ?? [],
            'videos' => $primary['videos'] ?? $fallback['videos'] ?? [],
            'external_ids' => $primary['external_ids'] ?? $fallback['external_ids'] ?? [],
            'images' => $primary['images'] ?? $fallback['images'] ?? [],
        ];

        // Track which language was used per field for logging
        $languageUsage = [];
        foreach (['title', 'overview', 'tagline'] as $field) {
            if (!empty($primary[$field])) {
                $languageUsage[$field] = $language;
            } elseif (!empty($fallback[$field])) {
                $languageUsage[$field] = 'en-US (fallback)';
            }
        }

        $merged['_language_used'] = $language;
        $merged['_language_usage_per_field'] = $languageUsage;

        Log::info("Movie data merged", [
            'tmdb_id' => $merged['id'],
            'language_usage' => $languageUsage
        ]);

        return $merged;
    }

    /**
     * Merge TV series data with field-level fallback (ID → EN)
     */
    protected function mergeTvData($primary, $fallback, $language)
    {
        if (!$fallback) {
            return array_merge($primary, ['_language_used' => $language]);
        }

        // Smart fallback: Use English if Indonesian returns non-Latin name (Korean, Chinese, Japanese, etc.)
        $useEnglishName = false;
        if ($language === 'id-ID' && isset($primary['name'])) {
            // Check if name contains CJK characters (same logic as Python bot)
            $useEnglishName = $this->containsNonLatinChars($primary['name']);
            if ($useEnglishName) {
                Log::info('Using English series name, Indonesian returned non-Latin', [
                    'indonesian_name' => $primary['name'],
                    'english_name' => $fallback['name'] ?? 'N/A'
                ]);
            }
        }

        $merged = [
            'id' => $primary['id'],
            'name' => $useEnglishName ? ($fallback['name'] ?? $primary['name']) : 
                      (!empty($primary['name']) ? $primary['name'] : ($fallback['name'] ?? '')),
            'original_name' => !empty($primary['original_name']) ? $primary['original_name'] : ($fallback['original_name'] ?? ''),
            'overview' => !empty($primary['overview']) ? $primary['overview'] : ($fallback['overview'] ?? ''),
            'tagline' => !empty($primary['tagline']) ? $primary['tagline'] : ($fallback['tagline'] ?? ''),
            'poster_path' => $primary['poster_path'] ?? $fallback['poster_path'] ?? null,
            'backdrop_path' => $primary['backdrop_path'] ?? $fallback['backdrop_path'] ?? null,
            'first_air_date' => $primary['first_air_date'] ?? $fallback['first_air_date'] ?? null,
            'last_air_date' => $primary['last_air_date'] ?? $fallback['last_air_date'] ?? null,
            'number_of_seasons' => $primary['number_of_seasons'] ?? $fallback['number_of_seasons'] ?? 0,
            'number_of_episodes' => $primary['number_of_episodes'] ?? $fallback['number_of_episodes'] ?? 0,
            'vote_average' => $primary['vote_average'] ?? $fallback['vote_average'] ?? 0,
            'vote_count' => $primary['vote_count'] ?? $fallback['vote_count'] ?? 0,
            'popularity' => $primary['popularity'] ?? $fallback['popularity'] ?? 0,
            'status' => $primary['status'] ?? $fallback['status'] ?? null,
            'type' => $primary['type'] ?? $fallback['type'] ?? null,
            'genres' => !empty($primary['genres']) ? $primary['genres'] : ($fallback['genres'] ?? []),
            'credits' => $primary['credits'] ?? $fallback['credits'] ?? [],
            'videos' => $primary['videos'] ?? $fallback['videos'] ?? [],
        ];

        $languageUsage = [];
        foreach (['name', 'overview', 'tagline'] as $field) {
            if (!empty($primary[$field])) {
                $languageUsage[$field] = $language;
            } elseif (!empty($fallback[$field])) {
                $languageUsage[$field] = 'en-US (fallback)';
            }
        }

        $merged['_language_used'] = $language;
        $merged['_language_usage_per_field'] = $languageUsage;

        Log::info("TV series data merged", [
            'tmdb_id' => $merged['id'],
            'language_usage' => $languageUsage
        ]);

        return $merged;
    }

    /**
     * Merge season data with field-level fallback (ID → EN)
     */
    protected function mergeSeasonData($primary, $fallback, $language)
    {
        if (!$fallback) {
            return array_merge($primary, ['_language_used' => $language]);
        }

        // Smart fallback for season name: prefer English if Indonesian not available
        $seasonName = !empty($primary['name']) && $primary['name'] !== 'Season ' . $primary['season_number'] 
                      ? $primary['name'] 
                      : ($fallback['name'] ?? $primary['name'] ?? '');

        $merged = [
            'id' => $primary['id'],
            'name' => $seasonName,
            'overview' => !empty($primary['overview']) ? $primary['overview'] : ($fallback['overview'] ?? ''),
            'season_number' => $primary['season_number'],
            'air_date' => $primary['air_date'] ?? $fallback['air_date'] ?? null,
            'poster_path' => $primary['poster_path'] ?? $fallback['poster_path'] ?? null,
            'episodes' => $this->mergeEpisodesData(
                $primary['episodes'] ?? [], 
                $fallback['episodes'] ?? [], 
                $language
            ),
        ];

        $merged['_language_used'] = $language;

        return $merged;
    }

    /**
     * Merge episodes data with field-level fallback (ID → EN)
     */
    protected function mergeEpisodesData($primaryEpisodes, $fallbackEpisodes, $language)
    {
        $merged = [];
        
        foreach ($primaryEpisodes as $index => $primaryEp) {
            $fallbackEp = $fallbackEpisodes[$index] ?? null;
            
            // Smart fallback for episode name: Use English if:
            // 1. Name is empty
            // 2. Name is generic "Episode X" format
            // 3. Name contains CJK characters (Korean, Chinese, Japanese)
            $episodeName = $primaryEp['name'] ?? '';
            $isGenericName = preg_match('/^Episode\s+\d+$/i', $episodeName);
            $hasNonLatinChars = $this->containsNonLatinChars($episodeName);
            
            // Use English if any condition is true
            if (empty($episodeName) || $isGenericName || $hasNonLatinChars) {
                $episodeName = $fallbackEp['name'] ?? $episodeName;
            }
            
            $merged[] = [
                'id' => $primaryEp['id'],
                'name' => $episodeName,
                'overview' => !empty($primaryEp['overview']) ? $primaryEp['overview'] : ($fallbackEp['overview'] ?? ''),
                'episode_number' => $primaryEp['episode_number'],
                'season_number' => $primaryEp['season_number'],
                'still_path' => $primaryEp['still_path'] ?? $fallbackEp['still_path'] ?? null,
                'air_date' => $primaryEp['air_date'] ?? $fallbackEp['air_date'] ?? null,
                'runtime' => $primaryEp['runtime'] ?? $fallbackEp['runtime'] ?? null,
                'vote_average' => $primaryEp['vote_average'] ?? $fallbackEp['vote_average'] ?? 0,
                'vote_count' => $primaryEp['vote_count'] ?? $fallbackEp['vote_count'] ?? 0,
            ];
        }

        return $merged;
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

    /**
     * Check if text contains non-Latin characters (Korean, Chinese, Japanese, etc.)
     * Same logic as Python bot's _is_non_latin() function
     * 
     * @param string|null $text Text to check
     * @return bool True if text contains CJK or other non-Latin characters
     */
    protected function containsNonLatinChars(?string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        // Unicode ranges for CJK characters (same as bot):
        // Hangul (Korean): \x{AC00}-\x{D7AF}
        // Hiragana/Katakana (Japanese): \x{3040}-\x{309F}, \x{30A0}-\x{30FF}
        // CJK Unified Ideographs (Chinese/Japanese/Korean): \x{4E00}-\x{9FFF}
        return preg_match('/[\x{AC00}-\x{D7AF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $text) === 1;
    }
}