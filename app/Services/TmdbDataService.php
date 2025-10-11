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
     * Fetch movie details from TMDB
     *
     * @param int $tmdbId
     * @return array|null
     */
    public function fetchMovie(int $tmdbId): ?array
    {
        $cacheKey = "tmdb:movie:{$tmdbId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tmdbId) {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->baseUrl}/movie/{$tmdbId}", [
                        'api_key' => $this->apiKey,
                        'language' => 'en-US'
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('TMDB API fetch movie failed', [
                    'tmdb_id' => $tmdbId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
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
     * Fetch series details from TMDB
     *
     * @param int $tmdbId
     * @return array|null
     */
    public function fetchSeries(int $tmdbId): ?array
    {
        $cacheKey = "tmdb:series:{$tmdbId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tmdbId) {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->baseUrl}/tv/{$tmdbId}", [
                        'api_key' => $this->apiKey,
                        'language' => 'en-US'
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('TMDB API fetch series failed', [
                    'tmdb_id' => $tmdbId,
                    'status' => $response->status()
                ]);

                return null;
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
     * Fetch season details from TMDB
     *
     * @param int $tmdbId
     * @param int $seasonNumber
     * @return array|null
     */
    public function fetchSeason(int $tmdbId, int $seasonNumber): ?array
    {
        $cacheKey = "tmdb:season:{$tmdbId}:{$seasonNumber}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tmdbId, $seasonNumber) {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$seasonNumber}", [
                        'api_key' => $this->apiKey,
                        'language' => 'en-US'
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('TMDB API fetch season failed', [
                    'tmdb_id' => $tmdbId,
                    'season_number' => $seasonNumber,
                    'status' => $response->status()
                ]);

                return null;
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
     * Fetch episode details from TMDB
     *
     * @param int $tmdbId
     * @param int $seasonNumber
     * @param int $episodeNumber
     * @return array|null
     */
    public function fetchEpisode(int $tmdbId, int $seasonNumber, int $episodeNumber): ?array
    {
        $cacheKey = "tmdb:episode:{$tmdbId}:{$seasonNumber}:{$episodeNumber}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tmdbId, $seasonNumber, $episodeNumber) {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$seasonNumber}/episode/{$episodeNumber}", [
                        'api_key' => $this->apiKey,
                        'language' => 'en-US'
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('TMDB API fetch episode failed', [
                    'tmdb_id' => $tmdbId,
                    'season_number' => $seasonNumber,
                    'episode_number' => $episodeNumber,
                    'status' => $response->status()
                ]);

                return null;
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
}
