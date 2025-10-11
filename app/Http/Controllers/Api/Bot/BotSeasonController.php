<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\UploadSeasonRequest;
use App\Jobs\ProcessSeasonUploadJob;
use App\Models\Series;
use App\Services\ContentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BotSeasonController extends Controller
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
     * Handle season upload from Telegram bot
     * 
     * @param UploadSeasonRequest $request
     * @param int $tmdbId The series TMDB ID from URL parameter
     * @return JsonResponse
     */
    public function store(UploadSeasonRequest $request, int $tmdbId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $seasonNumber = $validated['season_number'];
            $telegramUsername = $validated['telegram_username'] ?? 'unknown';

            // Log the upload attempt
            Log::info('Bot season upload initiated', [
                'series_tmdb_id' => $tmdbId,
                'season_number' => $seasonNumber,
                'telegram_user' => $telegramUsername,
            ]);

            // Check if series exists
            $series = Series::where('tmdb_id', $tmdbId)->first();

            if (!$series) {
                Log::warning('Series not found for season upload', [
                    'series_tmdb_id' => $tmdbId,
                    'season_number' => $seasonNumber,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Series not found. Please upload the series first using /uploadseries',
                    'error' => 'Series with TMDB ID ' . $tmdbId . ' does not exist',
                ], 404);
            }

            // Check if season already exists
            $existingCheck = $this->contentService->checkSeasonExists($series->id, $seasonNumber);

            if ($existingCheck['exists']) {
                Log::info('Season already exists, skipping upload', [
                    'series_tmdb_id' => $tmdbId,
                    'series_id' => $series->id,
                    'season_number' => $seasonNumber,
                    'season_id' => $existingCheck['season']->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Season already exists in database',
                    'skipped' => true,
                    'data' => [
                        'season_id' => $existingCheck['season']->id,
                        'series_id' => $series->id,
                        'series_title' => $series->title,
                        'season_number' => $existingCheck['season']->season_number,
                        'title' => $existingCheck['season']->title,
                        'tmdb_id' => $existingCheck['season']->tmdb_id,
                    ],
                ], 200);
            }

            // Dispatch job to queue
            ProcessSeasonUploadJob::dispatch(
                $tmdbId,
                $seasonNumber,
                null, // telegramUserId (not provided by bot)
                $telegramUsername
            )->onQueue('bot-uploads');

            Log::info('Season upload job dispatched', [
                'series_tmdb_id' => $tmdbId,
                'series_id' => $series->id,
                'season_number' => $seasonNumber,
                'telegram_user' => $telegramUsername,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Season upload queued successfully. Note: Episodes NOT created - use /uploadepisode',
                'skipped' => false,
                'data' => [
                    'job_id' => $jobId,
                    'series_tmdb_id' => $tmdbId,
                    'series_id' => $series->id,
                    'series_title' => $series->title,
                    'season_number' => $seasonNumber,
                    'status' => 'queued',
                    'queue' => 'bot-uploads',
                    'note' => 'Episodes must be uploaded separately',
                ],
            ], 202); // 202 Accepted - request accepted for processing

        } catch (\Exception $e) {
            Log::error('Season upload failed', [
                'error' => $e->getMessage(),
                'series_tmdb_id' => $tmdbId,
                'season_number' => $request->input('season_number'),
                'telegram_user' => $request->input('telegram_username', 'unknown'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue season upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
