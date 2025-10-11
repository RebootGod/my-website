<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\UploadMovieRequest;
use App\Jobs\ProcessMovieUploadJob;
use App\Services\ContentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BotMovieController extends Controller
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
     * Handle movie upload from Telegram bot
     * 
     * @param UploadMovieRequest $request
     * @return JsonResponse
     */
    public function store(UploadMovieRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tmdbId = $validated['tmdb_id'];
            $embedUrl = $validated['embed_url'];
            $downloadUrl = $validated['download_url'] ?? null;
            $telegramUsername = $validated['telegram_username'] ?? 'unknown';

            // Log the upload attempt
            Log::info('Bot movie upload initiated', [
                'tmdb_id' => $tmdbId,
                'telegram_user' => $telegramUsername,
                'has_download_url' => !is_null($downloadUrl),
            ]);

            // Check if movie already exists
            $existingCheck = $this->contentService->checkMovieExists($tmdbId);

            if ($existingCheck['exists']) {
                Log::info('Movie already exists, skipping upload', [
                    'tmdb_id' => $tmdbId,
                    'movie_id' => $existingCheck['movie']->id,
                    'movie_title' => $existingCheck['movie']->title,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Movie already exists in database',
                    'skipped' => true,
                    'data' => [
                        'movie_id' => $existingCheck['movie']->id,
                        'title' => $existingCheck['movie']->title,
                        'year' => $existingCheck['movie']->year,
                        'slug' => $existingCheck['movie']->slug,
                        'status' => $existingCheck['movie']->status,
                        'tmdb_id' => $existingCheck['movie']->tmdb_id,
                    ],
                ], 200);
            }

            // Generate unique job ID for tracking
            $jobId = Str::uuid()->toString();

            // Dispatch job to queue
            ProcessMovieUploadJob::dispatch(
                $tmdbId,
                $embedUrl,
                $downloadUrl,
                $telegramUsername,
                $jobId
            )->onQueue('bot-uploads');

            Log::info('Movie upload job dispatched', [
                'job_id' => $jobId,
                'tmdb_id' => $tmdbId,
                'telegram_user' => $telegramUsername,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movie upload queued successfully',
                'skipped' => false,
                'data' => [
                    'job_id' => $jobId,
                    'tmdb_id' => $tmdbId,
                    'status' => 'queued',
                    'queue' => 'bot-uploads',
                ],
            ], 202); // 202 Accepted - request accepted for processing

        } catch (\Exception $e) {
            Log::error('Movie upload failed', [
                'error' => $e->getMessage(),
                'tmdb_id' => $request->input('tmdb_id'),
                'telegram_user' => $request->input('telegram_username', 'unknown'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue movie upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
