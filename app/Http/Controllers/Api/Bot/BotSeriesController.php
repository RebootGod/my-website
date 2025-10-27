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

            // Process series upload synchronously (no queue)
            // Bot needs immediate feedback, queue worker may not be running
            try {
                ProcessSeriesUploadJob::dispatchSync(
                    $tmdbId,
                    null, // telegramUserId (not provided by bot)
                    $telegramUsername
                );

                // After sync processing, get the created series
                $createdSeries = $this->contentService->checkSeriesExists($tmdbId);

                if ($createdSeries['exists']) {
                    Log::info('Series created successfully via bot', [
                        'tmdb_id' => $tmdbId,
                        'series_id' => $createdSeries['series']->id,
                        'title' => $createdSeries['series']->title,
                        'telegram_user' => $telegramUsername,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Series uploaded successfully. Note: Seasons and episodes NOT created - use season/episode upload endpoints',
                        'skipped' => false,
                        'data' => [
                            'series_id' => $createdSeries['series']->id,
                            'title' => $createdSeries['series']->title,
                            'year' => $createdSeries['series']->year,
                            'slug' => $createdSeries['series']->slug,
                            'status' => $createdSeries['series']->status,
                            'tmdb_id' => $createdSeries['series']->tmdb_id,
                            'url' => route('series.show', $createdSeries['series']->slug),
                            'note' => 'Seasons and episodes must be uploaded separately',
                        ],
                    ], 201); // 201 Created
                } else {
                    throw new \Exception('Series creation succeeded but series not found in database');
                }

            } catch (\Exception $jobException) {
                Log::error('Series upload processing failed', [
                    'tmdb_id' => $tmdbId,
                    'error' => $jobException->getMessage(),
                    'telegram_user' => $telegramUsername,
                    'trace' => $jobException->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload series',
                    'error' => $jobException->getMessage(),
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Series upload failed', [
                'error' => $e->getMessage(),
                'tmdb_id' => $request->input('tmdb_id'),
                'telegram_user' => $request->input('telegram_username', 'unknown'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload series',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
