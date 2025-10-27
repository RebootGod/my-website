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

            // Process movie upload synchronously (no queue)
            // Bot needs immediate feedback, queue worker may not be running
            try {
                ProcessMovieUploadJob::dispatchSync(
                    $tmdbId,
                    $embedUrl,
                    $downloadUrl,
                    null, // telegramUserId (not provided by bot)
                    $telegramUsername
                );

                // After sync processing, get the created movie
                $createdMovie = $this->contentService->checkMovieExists($tmdbId);

                if ($createdMovie['exists']) {
                    Log::info('Movie created successfully via bot', [
                        'tmdb_id' => $tmdbId,
                        'movie_id' => $createdMovie['movie']->id,
                        'title' => $createdMovie['movie']->title,
                        'telegram_user' => $telegramUsername,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Movie uploaded successfully',
                        'skipped' => false,
                        'data' => [
                            'movie_id' => $createdMovie['movie']->id,
                            'title' => $createdMovie['movie']->title,
                            'year' => $createdMovie['movie']->year,
                            'slug' => $createdMovie['movie']->slug,
                            'status' => $createdMovie['movie']->status,
                            'tmdb_id' => $createdMovie['movie']->tmdb_id,
                            'url' => route('movies.show', $createdMovie['movie']->slug),
                        ],
                    ], 201); // 201 Created
                } else {
                    throw new \Exception('Movie creation succeeded but movie not found in database');
                }

            } catch (\Exception $jobException) {
                Log::error('Movie upload processing failed', [
                    'tmdb_id' => $tmdbId,
                    'error' => $jobException->getMessage(),
                    'telegram_user' => $telegramUsername,
                    'trace' => $jobException->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload movie',
                    'error' => $jobException->getMessage(),
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Movie upload failed', [
                'error' => $e->getMessage(),
                'tmdb_id' => $request->input('tmdb_id'),
                'telegram_user' => $request->input('telegram_username', 'unknown'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload movie',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
