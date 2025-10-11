<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service: TMDB Data Fetcher
 * 
 * Fetch and process data from TMDB API
 * Caching enabled to reduce API calls
 * 
 * Security: API key from config, rate limit aware
 * 
 * @package App\Services
 */
class TmdbDataService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->apiKey = config('services.tmdb.api_key');
        $this->baseUrl = config('services.tmdb.base_url', 'https://api.themoviedb.org/3');
        $this->cacheTtl = config('services.tmdb.cache_ttl', 3600);
    }

    /**
     * Fetch movie details from TMDB with Indonesian language priority
     *
     * @param int $tmdbId
     * @return array|null
     */
    public function fetchMovie(int $tmdbId): ?array
    {
        $cacheKey = "tmdb:movie:{$tmdbId}:id";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tmdbId) {
            try {
                // Try Indonesian first
                $idResponse = Http::timeout(30)
                    ->get("{$this->baseUrl}/movie/{$tmdbId}", [
                        'api_key' => $this->apiKey,
                        'language' => 'id-ID'
                    ]);

                $enResponse = null;
                
                // Also fetch English as fallback
                if ($idResponse->successful()) {
                    $enResponse = Http::timeout(30)
                        ->get("{$this->baseUrl}/movie/{$tmdbId}", [
                            'api_key' => $this->apiKey,
                            'language' => 'en-US'
                        ]);
                }

                if (!$idResponse->successful()) {
                    Log::warning('TMDB API fetch movie failed', [
                        'tmdb_id' => $tmdbId,
                        'status' => $idResponse->status()
                    ]);
                    return null;
                }

                $idData = $idResponse->json();
                $enData = $enResponse && $enResponse->successful() ? $enResponse->json() : [];

                // Merge data: Use Indonesian if available, fallback to English
                return $this->mergeLanguageData($idData, $enData);

            } catch (\Exception $e) {
                Log::error('TMDB API exception fetching movie', [
                    'tmdb_id' => $tmdbId,
                    'error' => $e->getMessage()
                ]);

                return null;
            }
        });
    }

    /**
     * Fetch series details from TMDB with Indonesian language priority
     *
     * @param int $tmdbId
     * @return array|null
     */
    public function fetchSeries(int $tmdbId): ?array
    {
        $cacheKey = "tmdb:series:{$tmdbId}:id";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tmdbId) {
            try {
                // Try Indonesian first
                $idResponse = Http::timeout(30)
                    ->get("{$this->baseUrl}/tv/{$tmdbId}", [
                        'api_key' => $this->apiKey,
                        'language' => 'id-ID'
                    ]);

                $enResponse = null;
                
                // Also fetch English as fallback
                if ($idResponse->successful()) {
                    $enResponse = Http::timeout(30)
                        ->get("{$this->baseUrl}/tv/{$tmdbId}", [
                            'api_key' => $this->apiKey,
                            'language' => 'en-US'
                        ]);
                }

                if (!$idResponse->successful()) {
                    Log::warning('TMDB API fetch series failed', [
                        'tmdb_id' => $tmdbId,
                        'status' => $idResponse->status()
                    ]);
                    return null;
                }

                $idData = $idResponse->json();
                $enData = $enResponse && $enResponse->successful() ? $enResponse->json() : [];

                // Merge data: Use Indonesian if available, fallback to English
                return $this->mergeLanguageData($idData, $enData);

            } catch (\Exception $e) {
                Log::error('TMDB API exception fetching series', [
                    'tmdb_id' => $tmdbId,
                    'error' => $e->getMessage()
                ]);

                return null;
            }
        });
    }

    /**
     * Fetch season details from TMDB with Indonesian language priority
     *
     * @param int $tmdbId
     * @param int $seasonNumber
     * @return array|null
     */
    public function fetchSeason(int $tmdbId, int $seasonNumber): ?array
    {
        $cacheKey = "tmdb:season:{$tmdbId}:{$seasonNumber}:id";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tmdbId, $seasonNumber) {
            try {
                // Try Indonesian first
                $idResponse = Http::timeout(30)
                    ->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$seasonNumber}", [
                        'api_key' => $this->apiKey,
                        'language' => 'id-ID'
                    ]);

                $enResponse = null;
                
                // Also fetch English as fallback
                if ($idResponse->successful()) {
                    $enResponse = Http::timeout(30)
                        ->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$seasonNumber}", [
                            'api_key' => $this->apiKey,
                            'language' => 'en-US'
                        ]);
                }

                if (!$idResponse->successful()) {
                    Log::warning('TMDB API fetch season failed', [
                        'tmdb_id' => $tmdbId,
                        'season_number' => $seasonNumber,
                        'status' => $idResponse->status()
                    ]);
                    return null;
                }

                $idData = $idResponse->json();
                $enData = $enResponse && $enResponse->successful() ? $enResponse->json() : [];

                // Merge data: Use Indonesian if available, fallback to English
                return $this->mergeLanguageData($idData, $enData);

            } catch (\Exception $e) {
                Log::error('TMDB API exception fetching season', [
                    'tmdb_id' => $tmdbId,
                    'season_number' => $seasonNumber,
                    'error' => $e->getMessage()
                ]);

                return null;
            }
        });
    }

    /**
     * Fetch episode details from TMDB with Indonesian language priority
     *
     * @param int $tmdbId
     * @param int $seasonNumber
     * @param int $episodeNumber
     * @return array|null
     */
    public function fetchEpisode(int $tmdbId, int $seasonNumber, int $episodeNumber): ?array
    {
        $cacheKey = "tmdb:episode:{$tmdbId}:{$seasonNumber}:{$episodeNumber}:id";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tmdbId, $seasonNumber, $episodeNumber) {
            try {
                // Try Indonesian first
                $idResponse = Http::timeout(30)
                    ->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$seasonNumber}/episode/{$episodeNumber}", [
                        'api_key' => $this->apiKey,
                        'language' => 'id-ID'
                    ]);

                $enResponse = null;
                
                // Also fetch English as fallback
                if ($idResponse->successful()) {
                    $enResponse = Http::timeout(30)
                        ->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$seasonNumber}/episode/{$episodeNumber}", [
                            'api_key' => $this->apiKey,
                            'language' => 'en-US'
                        ]);
                }

                if (!$idResponse->successful()) {
                    Log::warning('TMDB API fetch episode failed', [
                        'tmdb_id' => $tmdbId,
                        'season_number' => $seasonNumber,
                        'episode_number' => $episodeNumber,
                        'status' => $idResponse->status()
                    ]);
                    return null;
                }

                $idData = $idResponse->json();
                $enData = $enResponse && $enResponse->successful() ? $enResponse->json() : [];

                // Merge data: Use Indonesian if available, fallback to English
                return $this->mergeLanguageData($idData, $enData);

            } catch (\Exception $e) {
                Log::error('TMDB API exception fetching episode', [
                    'tmdb_id' => $tmdbId,
                    'season_number' => $seasonNumber,
                    'episode_number' => $episodeNumber,
                    'error' => $e->getMessage()
                ]);

                return null;
            }
        });
    }

    /**
     * Extract year from date string (YYYY-MM-DD)
     *
     * @param string|null $date
     * @return int|null
     */
    public function extractYear(?string $date): ?int
    {
        if (!$date) {
            return null;
        }

        $year = substr($date, 0, 4);
        return is_numeric($year) ? (int) $year : null;
    }

    /**
     * Get poster URL from path
     *
     * @param string|null $posterPath
     * @return string|null
     */
    public function getPosterUrl(?string $posterPath): ?string
    {
        if (!$posterPath) {
            return null;
        }

        return config('services.tmdb.image_url') . '/w500' . $posterPath;
    }

    /**
     * Get backdrop URL from path
     *
     * @param string|null $backdropPath
     * @return string|null
     */
    public function getBackdropUrl(?string $backdropPath): ?string
    {
        if (!$backdropPath) {
            return null;
        }

        return config('services.tmdb.image_url') . '/original' . $backdropPath;
    }

    /**
     * Merge Indonesian and English data, prioritizing Indonesian
     * Falls back to English if Indonesian fields are empty
     *
     * @param array $idData Indonesian data
     * @param array $enData English data
     * @return array Merged data
     */
    private function mergeLanguageData(array $idData, array $enData): array
    {
        // Text fields that should use Indonesian if available
        $textFields = ['title', 'name', 'overview', 'tagline'];
        
        foreach ($textFields as $field) {
            // If Indonesian field is empty or null, use English
            if (empty($idData[$field]) && !empty($enData[$field])) {
                $idData[$field] = $enData[$field];
            }
        }

        // For genres, merge both and deduplicate
        if (!empty($enData['genres']) && !empty($idData['genres'])) {
            // Keep Indonesian names but ensure all genre IDs are present
            $idGenreIds = array_column($idData['genres'], 'id');
            foreach ($enData['genres'] as $enGenre) {
                if (!in_array($enGenre['id'], $idGenreIds)) {
                    $idData['genres'][] = $enGenre;
                }
            }
        } elseif (empty($idData['genres']) && !empty($enData['genres'])) {
            $idData['genres'] = $enData['genres'];
        }

        return $idData;
    }
}
