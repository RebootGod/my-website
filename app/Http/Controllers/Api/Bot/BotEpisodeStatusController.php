<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\SeriesSeason;
use App\Models\SeriesEpisode;
use App\Services\TmdbDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Bot Episode Status Controller
 * 
 * Provides episode status information for series upload bot
 * Shows which episodes exist, which need URLs, and which need creation
 * 
 * Security: Bot token authentication required
 * OWASP: Input validation, parameterized queries
 * 
 * @package App\Http\Controllers\Api\Bot
 */
class BotEpisodeStatusController extends Controller
{
    /**
     * TMDB data service instance
     */
    protected TmdbDataService $tmdbService;

    /**
     * Create a new controller instance
     */
    public function __construct(TmdbDataService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    /**
     * Get episode status for a series season
     * 
     * GET /api/bot/series/{tmdbId}/episodes-status?season={seasonNumber}
     * 
     * @param Request $request
     * @param int $tmdbId Series TMDB ID
     * @return JsonResponse
     */
    public function getStatus(Request $request, int $tmdbId): JsonResponse
    {
        try {
            // Validate season parameter
            $seasonNumber = $request->input('season');
            
            if (!$seasonNumber || !is_numeric($seasonNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Season number is required and must be numeric',
                ], 400);
            }

            $seasonNumber = (int) $seasonNumber;

            // Validate season number range
            if ($seasonNumber < 0 || $seasonNumber > 999) {
                return response()->json([
                    'success' => false,
                    'message' => 'Season number must be between 0 and 999',
                ], 400);
            }

            Log::info('Fetching episode status', [
                'tmdb_id' => $tmdbId,
                'season_number' => $seasonNumber,
            ]);

            // Find series by TMDB ID
            $series = Series::where('tmdb_id', $tmdbId)->first();

            if (!$series) {
                return response()->json([
                    'success' => false,
                    'message' => 'Series not found. Please upload series first.',
                    'data' => [
                        'tmdb_id' => $tmdbId,
                        'season_number' => $seasonNumber,
                    ],
                ], 404);
            }

            // Find season
            $season = SeriesSeason::where('series_id', $series->id)
                ->where('season_number', $seasonNumber)
                ->first();

            // Try to fetch episodes from TMDB
            $tmdbEpisodes = $this->tmdbService->fetchSeason($tmdbId, $seasonNumber);
            $tmdbDataAvailable = !empty($tmdbEpisodes['episodes']);

            $episodesStatus = [];
            $summary = [
                'total_episodes' => 0,
                'complete' => 0,
                'needs_urls' => 0,
                'not_created' => 0,
            ];

            if ($tmdbDataAvailable) {
                // Process TMDB episodes
                foreach ($tmdbEpisodes['episodes'] as $tmdbEpisode) {
                    $episodeNumber = $tmdbEpisode['episode_number'];
                    $episodeName = $tmdbEpisode['name'] ?? "Episode {$episodeNumber}";

                    // Check if episode exists in database
                    $dbEpisode = null;
                    if ($season) {
                        $dbEpisode = SeriesEpisode::where('season_id', $season->id)
                            ->where('episode_number', $episodeNumber)
                            ->first();
                    }

                    $status = 'not_created';
                    $hasUrls = false;
                    $episodeId = null;

                    if ($dbEpisode) {
                        $episodeId = $dbEpisode->id;
                        $hasUrls = !empty($dbEpisode->embed_url);
                        
                        if ($hasUrls) {
                            $status = 'complete';
                            $summary['complete']++;
                        } else {
                            $status = 'needs_urls';
                            $summary['needs_urls']++;
                        }
                    } else {
                        $summary['not_created']++;
                    }

                    $episodesStatus[] = [
                        'episode_number' => $episodeNumber,
                        'name' => $episodeName,
                        'exists_in_db' => !is_null($dbEpisode),
                        'has_urls' => $hasUrls,
                        'episode_id' => $episodeId,
                        'status' => $status,
                    ];

                    $summary['total_episodes']++;
                }
            }

            // Prepare response
            $responseData = [
                'series' => [
                    'id' => $series->id,
                    'tmdb_id' => $series->tmdb_id,
                    'title' => $series->title,
                    'slug' => $series->slug,
                ],
                'season' => $season ? [
                    'id' => $season->id,
                    'season_number' => $season->season_number,
                    'episode_count' => $season->episode_count ?? 0,
                ] : null,
                'episodes' => $episodesStatus,
                'tmdb_data_available' => $tmdbDataAvailable,
                'summary' => $summary,
            ];

            if (!$tmdbDataAvailable) {
                $responseData['message'] = 'TMDB data incomplete or unavailable. Use manual mode.';
            }

            if (!$season) {
                $responseData['message'] = 'Season not created yet. Will be created when first episode is uploaded.';
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch episode status', [
                'tmdb_id' => $tmdbId,
                'season' => $request->input('season'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch episode status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
