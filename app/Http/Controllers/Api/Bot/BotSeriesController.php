<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\UploadSeriesRequest;
use App\Jobs\ProcessSeriesUploadJob;
use App\Services\ContentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BotSeriesController extends Controller
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
     * Handle series upload from Telegram bot
     * 
     * @param UploadSeriesRequest $request
     * @return JsonResponse
     */
    public function store(UploadSeriesRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tmdbId = $validated['tmdb_id'];
            $telegramUsername = $validated['telegram_username'] ?? 'unknown';

            // Log the upload attempt
            Log::info('Bot series upload initiated', [
                'tmdb_id' => $tmdbId,
                'telegram_user' => $telegramUsername,
            ]);

            // Check if series already exists
            $existingCheck = $this->contentService->checkSeriesExists($tmdbId);

            if ($existingCheck['exists']) {
                Log::info('Series already exists, skipping upload', [
                    'tmdb_id' => $tmdbId,
                    'series_id' => $existingCheck['series']->id,
                    'series_title' => $existingCheck['series']->title,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Series already exists in database',
                    'skipped' => true,
                    'data' => [
                        'series_id' => $existingCheck['series']->id,
                        'title' => $existingCheck['series']->title,
                        'year' => $existingCheck['series']->year,
                        'slug' => $existingCheck['series']->slug,
                        'status' => $existingCheck['series']->status,
                        'tmdb_id' => $existingCheck['series']->tmdb_id,
                    ],
                ], 200);
            }

            // Generate unique job ID for tracking
            $jobId = Str::uuid()->toString();

            // Dispatch job to queue
            ProcessSeriesUploadJob::dispatch(
                $tmdbId,
                $telegramUsername,
                $jobId
            )->onQueue('bot-uploads');

            Log::info('Series upload job dispatched', [
                'job_id' => $jobId,
                'tmdb_id' => $tmdbId,
                'telegram_user' => $telegramUsername,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Series upload queued successfully. Note: Seasons and episodes NOT created - use /uploadseason and /uploadepisode',
                'skipped' => false,
                'data' => [
                    'job_id' => $jobId,
                    'tmdb_id' => $tmdbId,
                    'status' => 'queued',
                    'queue' => 'bot-uploads',
                    'note' => 'Seasons and episodes must be uploaded separately',
                ],
            ], 202); // 202 Accepted - request accepted for processing

        } catch (\Exception $e) {
            Log::error('Series upload failed', [
                'error' => $e->getMessage(),
                'tmdb_id' => $request->input('tmdb_id'),
                'telegram_user' => $request->input('telegram_username', 'unknown'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue series upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
