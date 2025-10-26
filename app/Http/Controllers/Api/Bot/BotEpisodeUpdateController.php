<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\UpdateEpisodeRequest;
use App\Models\SeriesEpisode;
use App\Services\ContentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Bot Episode Update Controller
 * 
 * Updates existing episodes with embed and download URLs
 * Used when episodes exist in database but have no URLs
 * 
 * Security: Bot token authentication required
 * OWASP: Input validation, SQL injection protection
 * 
 * @package App\Http\Controllers\Api\Bot
 */
class BotEpisodeUpdateController extends Controller
{
    /**
     * Content upload service instance
     */
    protected ContentUploadService $contentService;

    /**
     * Create a new controller instance
     */
    public function __construct(ContentUploadService $contentService)
    {
        $this->contentService = $contentService;
    }

    /**
     * Update episode URLs
     * 
     * PUT /api/bot/episodes/{episodeId}
     * 
     * @param UpdateEpisodeRequest $request
     * @param int $episodeId
     * @return JsonResponse
     */
    public function update(UpdateEpisodeRequest $request, int $episodeId): JsonResponse
    {
        try {
            // Validate episode exists
            $episode = SeriesEpisode::find($episodeId);

            if (!$episode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Episode not found',
                ], 404);
            }

            Log::info('Bot episode update initiated', [
                'episode_id' => $episodeId,
                'series_id' => $episode->series_id,
                'season_id' => $episode->season_id,
                'episode_number' => $episode->episode_number,
            ]);

            // Get validated data
            $validated = $request->validated();
            $embedUrl = $validated['embed_url'];
            $downloadUrl = $validated['download_url'] ?? null;

            // Check if episode already has URLs
            if (!empty($episode->embed_url)) {
                Log::warning('Episode already has URLs, skipping update', [
                    'episode_id' => $episodeId,
                    'existing_embed' => $episode->embed_url,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Episode already has URLs. Cannot overwrite existing URLs.',
                    'data' => [
                        'episode_id' => $episode->id,
                        'episode_number' => $episode->episode_number,
                        'has_embed' => true,
                        'has_download' => !empty($episode->download_url),
                    ],
                ], 409); // 409 Conflict
            }

            // Update episode
            $result = $this->contentService->updateEpisodeUrls(
                $episodeId,
                $embedUrl,
                $downloadUrl
            );

            if (!$result['success']) {
                throw new \Exception('Failed to update episode URLs');
            }

            $updatedEpisode = $result['episode'];

            Log::info('Episode URLs updated successfully', [
                'episode_id' => $updatedEpisode->id,
                'series_id' => $updatedEpisode->series_id,
                'season_id' => $updatedEpisode->season_id,
                'episode_number' => $updatedEpisode->episode_number,
                'status' => $updatedEpisode->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Episode URLs updated successfully',
                'data' => [
                    'episode_id' => $updatedEpisode->id,
                    'episode_number' => $updatedEpisode->episode_number,
                    'season_number' => $updatedEpisode->season->season_number ?? null,
                    'name' => $updatedEpisode->name,
                    'embed_url' => $updatedEpisode->embed_url,
                    'download_url' => $updatedEpisode->download_url,
                    'status' => $updatedEpisode->status,
                    'is_active' => $updatedEpisode->is_active,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to update episode URLs', [
                'episode_id' => $episodeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update episode URLs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
