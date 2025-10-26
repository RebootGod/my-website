<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Services\TmdbDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller: Bot TMDB Data Controller
 * 
 * Expose TMDB data to Telegram bot
 * Uses existing TmdbDataService
 * 
 * @package App\Http\Controllers\Api\Bot
 */
class BotTmdbController extends Controller
{
    protected TmdbDataService $tmdbService;

    public function __construct(TmdbDataService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    /**
     * Get movie details from TMDB
     * 
     * GET /api/bot/tmdb/movie/{tmdbId}
     * 
     * @param int $tmdbId
     * @return JsonResponse
     */
    public function getMovie(int $tmdbId): JsonResponse
    {
        $data = $this->tmdbService->fetchMovie($tmdbId);

        if (!$data) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TMDB_FETCH_FAILED',
                    'message' => 'Failed to fetch movie data from TMDB'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tmdb_id' => $data['id'],
                'imdb_id' => $data['imdb_id'] ?? null,
                'title' => $data['title'] ?? $data['name'] ?? 'Untitled',
                'original_title' => $data['original_title'] ?? null,
                'overview' => $data['overview'] ?? null,
                'release_date' => $data['release_date'] ?? null,
                'year' => $this->tmdbService->extractYear($data['release_date'] ?? null),
                'runtime' => $data['runtime'] ?? null,
                'vote_average' => $data['vote_average'] ?? 0,
                'vote_count' => $data['vote_count'] ?? 0,
                'popularity' => $data['popularity'] ?? 0,
                'poster_path' => $data['poster_path'] ?? null,
                'poster_url' => $this->tmdbService->getPosterUrl($data['poster_path'] ?? null),
                'backdrop_path' => $data['backdrop_path'] ?? null,
                'backdrop_url' => $this->tmdbService->getBackdropUrl($data['backdrop_path'] ?? null),
                'genres' => $data['genres'] ?? [],
                'original_language' => $data['original_language'] ?? null,
                'status' => $data['status'] ?? null,
            ]
        ]);
    }

    /**
     * Get series details from TMDB
     * 
     * GET /api/bot/tmdb/series/{tmdbId}
     * 
     * @param int $tmdbId
     * @return JsonResponse
     */
    public function getSeries(int $tmdbId): JsonResponse
    {
        $data = $this->tmdbService->fetchSeries($tmdbId);

        if (!$data) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TMDB_FETCH_FAILED',
                    'message' => 'Failed to fetch series data from TMDB'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tmdb_id' => $data['id'],
                'imdb_id' => $data['imdb_id'] ?? null,
                'title' => $data['name'] ?? $data['title'] ?? 'Untitled',
                'original_title' => $data['original_name'] ?? null,
                'overview' => $data['overview'] ?? null,
                'first_air_date' => $data['first_air_date'] ?? null,
                'last_air_date' => $data['last_air_date'] ?? null,
                'year' => $this->tmdbService->extractYear($data['first_air_date'] ?? null),
                'number_of_seasons' => $data['number_of_seasons'] ?? 0,
                'number_of_episodes' => $data['number_of_episodes'] ?? 0,
                'vote_average' => $data['vote_average'] ?? 0,
                'vote_count' => $data['vote_count'] ?? 0,
                'popularity' => $data['popularity'] ?? 0,
                'poster_path' => $data['poster_path'] ?? null,
                'poster_url' => $this->tmdbService->getPosterUrl($data['poster_path'] ?? null),
                'backdrop_path' => $data['backdrop_path'] ?? null,
                'backdrop_url' => $this->tmdbService->getBackdropUrl($data['backdrop_path'] ?? null),
                'genres' => $data['genres'] ?? [],
                'seasons' => $data['seasons'] ?? [],
                'original_language' => $data['original_language'] ?? null,
                'status' => $data['status'] ?? null,
            ]
        ]);
    }

    /**
     * Get season details from TMDB
     * 
     * GET /api/bot/tmdb/series/{tmdbId}/season/{seasonNumber}
     * 
     * @param int $tmdbId
     * @param int $seasonNumber
     * @return JsonResponse
     */
    public function getSeason(int $tmdbId, int $seasonNumber): JsonResponse
    {
        $data = $this->tmdbService->fetchSeason($tmdbId, $seasonNumber);

        if (!$data) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TMDB_FETCH_FAILED',
                    'message' => 'Failed to fetch season data from TMDB'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tmdb_id' => $tmdbId,
                'season_id' => $data['id'] ?? null,
                'season_number' => $data['season_number'] ?? $seasonNumber,
                'name' => $data['name'] ?? "Season {$seasonNumber}",
                'overview' => $data['overview'] ?? null,
                'air_date' => $data['air_date'] ?? null,
                'episode_count' => $data['episode_count'] ?? count($data['episodes'] ?? []),
                'poster_path' => $data['poster_path'] ?? null,
                'poster_url' => $this->tmdbService->getPosterUrl($data['poster_path'] ?? null),
                'episodes' => array_map(function ($episode) {
                    return [
                        'episode_number' => $episode['episode_number'] ?? null,
                        'name' => $episode['name'] ?? "Episode {$episode['episode_number']}",
                        'overview' => $episode['overview'] ?? null,
                        'air_date' => $episode['air_date'] ?? null,
                        'runtime' => $episode['runtime'] ?? null,
                        'still_path' => $episode['still_path'] ?? null,
                        'vote_average' => $episode['vote_average'] ?? 0,
                    ];
                }, $data['episodes'] ?? [])
            ]
        ]);
    }
}

