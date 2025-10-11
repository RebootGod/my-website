<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\UploadEpisodeRequest;
use App\Jobs\ProcessEpisodeUploadJob;
use App\Models\Series;
use App\Models\SeriesSeason;
use App\Services\ContentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BotEpisodeController extends Controller
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
     * Handle episode upload from Telegram bot
     * 
     * @param UploadEpisodeRequest $request
     * @param int $tmdbId The series TMDB ID from URL parameter
     * @return JsonResponse
     */
    public function store(UploadEpisodeRequest $request, int $tmdbId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $seasonNumber = $validated['season_number'];
            $episodeNumber = $validated['episode_number'];
            $embedUrl = $validated['embed_url'];
            $downloadUrl = $validated['download_url'] ?? null;
            $telegramUsername = $validated['telegram_username'] ?? 'unknown';

            // Log the upload attempt
            Log::info('Bot episode upload initiated', [
                'series_tmdb_id' => $tmdbId,
                'season_number' => $seasonNumber,
                'episode_number' => $episodeNumber,
                'telegram_user' => $telegramUsername,
                'has_download_url' => !is_null($downloadUrl),
            ]);

            // Check if series exists
            $series = Series::where('tmdb_id', $tmdbId)->first();

            if (!$series) {
                Log::warning('Series not found for episode upload', [
                    'series_tmdb_id' => $tmdbId,
                    'season_number' => $seasonNumber,
                    'episode_number' => $episodeNumber,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Series not found. Please upload the series first using /uploadseries',
                    'error' => 'Series with TMDB ID ' . $tmdbId . ' does not exist',
                ], 404);
            }

            // Check if season exists
            $season = SeriesSeason::where('series_id', $series->id)
                ->where('season_number', $seasonNumber)
                ->first();

            if (!$season) {
                Log::warning('Season not found for episode upload', [
                    'series_tmdb_id' => $tmdbId,
                    'series_id' => $series->id,
                    'season_number' => $seasonNumber,
                    'episode_number' => $episodeNumber,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Season not found. Please upload the season first using /uploadseason',
                    'error' => 'Season ' . $seasonNumber . ' does not exist for this series',
                ], 404);
            }

            // Check if episode already exists
            $existingCheck = $this->contentService->checkEpisodeExists(
                $season->id,
                $episodeNumber
            );

            if ($existingCheck['exists']) {
                Log::info('Episode already exists, skipping upload', [
                    'series_tmdb_id' => $tmdbId,
                    'series_id' => $series->id,
                    'season_id' => $season->id,
                    'season_number' => $seasonNumber,
                    'episode_number' => $episodeNumber,
                    'episode_id' => $existingCheck['episode']->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Episode already exists in database',
                    'skipped' => true,
                    'data' => [
                        'episode_id' => $existingCheck['episode']->id,
                        'series_id' => $series->id,
                        'series_title' => $series->title,
                        'season_id' => $season->id,
                        'season_number' => $seasonNumber,
                        'episode_number' => $existingCheck['episode']->episode_number,
                        'title' => $existingCheck['episode']->title,
                        'tmdb_id' => $existingCheck['episode']->tmdb_id,
                    ],
                ], 200);
            }

            // Dispatch job to queue
            ProcessEpisodeUploadJob::dispatch(
                $tmdbId,
                $seasonNumber,
                $episodeNumber,
                $embedUrl,
                $downloadUrl,
                null, // telegramUserId (not provided by bot)
                $telegramUsername
            )->onQueue('bot-uploads');

            Log::info('Episode upload job dispatched', [
                'series_tmdb_id' => $tmdbId,
                'series_id' => $series->id,
                'season_id' => $season->id,
                'season_number' => $seasonNumber,
                'episode_number' => $episodeNumber,
                'telegram_user' => $telegramUsername,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Episode upload queued successfully',
                'skipped' => false,
                'data' => [
                    'job_id' => $jobId,
                    'series_tmdb_id' => $tmdbId,
                    'series_id' => $series->id,
                    'series_title' => $series->title,
                    'season_id' => $season->id,
                    'season_number' => $seasonNumber,
                    'episode_number' => $episodeNumber,
                    'status' => 'queued',
                    'queue' => 'bot-uploads',
                ],
            ], 202); // 202 Accepted - request accepted for processing

        } catch (\Exception $e) {
            Log::error('Episode upload failed', [
                'error' => $e->getMessage(),
                'series_tmdb_id' => $tmdbId,
                'season_number' => $request->input('season_number'),
                'episode_number' => $request->input('episode_number'),
                'telegram_user' => $request->input('telegram_username', 'unknown'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue episode upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
