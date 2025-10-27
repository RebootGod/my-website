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
                        'name' => $existingCheck['season']->name,
                    ],
                ], 200);
            }

            // Process season upload synchronously (no queue)
            try {
                ProcessSeasonUploadJob::dispatchSync(
                    $tmdbId,
                    $seasonNumber,
                    null, // telegramUserId (not provided by bot)
                    $telegramUsername
                );

                // After sync processing, get the created season
                $createdSeason = $this->contentService->checkSeasonExists($series->id, $seasonNumber);

                if ($createdSeason['exists']) {
                    Log::info('Season created successfully via bot', [
                        'series_tmdb_id' => $tmdbId,
                        'series_id' => $series->id,
                        'season_number' => $seasonNumber,
                        'season_id' => $createdSeason['season']->id,
                        'telegram_user' => $telegramUsername,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Season uploaded successfully. Note: Episodes NOT created - use episode upload endpoint',
                        'skipped' => false,
                        'data' => [
                            'season_id' => $createdSeason['season']->id,
                            'series_id' => $series->id,
                            'series_title' => $series->title,
                            'season_number' => $createdSeason['season']->season_number,
                            'name' => $createdSeason['season']->name,
                            'episode_count' => $createdSeason['season']->episode_count ?? 0,
                            'note' => 'Episodes must be uploaded separately',
                        ],
                    ], 201); // 201 Created
                } else {
                    throw new \Exception('Season creation succeeded but season not found in database');
                }

            } catch (\Exception $jobException) {
                Log::error('Season upload processing failed', [
                    'series_tmdb_id' => $tmdbId,
                    'season_number' => $seasonNumber,
                    'error' => $jobException->getMessage(),
                    'telegram_user' => $telegramUsername,
                    'trace' => $jobException->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload season',
                    'error' => $jobException->getMessage(),
                ], 500);
            }

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
                'message' => 'Failed to upload season',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
