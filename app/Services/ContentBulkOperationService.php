<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Series;
use App\Services\TMDBService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Content Bulk Operation Service
 * 
 * Handles bulk operations for movies and series
 * Max 350 lines per workinginstruction.md
 */
class ContentBulkOperationService
{
    protected TMDBService $tmdbService;
    
    public function __construct(TMDBService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    /**
     * Bulk update metadata
     * 
     * @param string $type 'movie' or 'series'
     * @param array $ids
     * @param array $data
     * @return array
     */
    public function bulkUpdateMetadata(string $type, array $ids, array $data): array
    {
        $model = $this->getModel($type);
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::beginTransaction();
        try {
            foreach ($ids as $id) {
                try {
                    $item = $model::findOrFail($id);
                    
                    // Only update fields that are provided
                    $updateData = [];
                    if (isset($data['title'])) {
                        $updateData['title'] = $data['title'];
                    }
                    if (isset($data['description'])) {
                        $updateData['description'] = $data['description'];
                    }
                    if (isset($data['release_date'])) {
                        $updateData['release_date'] = $data['release_date'];
                    }
                    if (isset($data['status'])) {
                        $updateData['status'] = $data['status'];
                    }
                    if (isset($data['is_featured'])) {
                        $updateData['is_featured'] = $data['is_featured'];
                    }

                    if (!empty($updateData)) {
                        $item->update($updateData);
                        $results['success']++;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'id' => $id,
                        'error' => $e->getMessage()
                    ];
                    Log::error("Bulk update failed for {$type} ID {$id}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Bulk refresh from TMDB
     * 
     * @param string $type
     * @param array $ids
     * @return array
     */
    public function bulkRefreshFromTMDB(string $type, array $ids): array
    {
        $model = $this->getModel($type);
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        Log::info("Starting bulk TMDB refresh", [
            'type' => $type,
            'count' => count($ids),
            'ids' => $ids
        ]);

        foreach ($ids as $id) {
            try {
                $item = $model::findOrFail($id);
                
                Log::info("Processing {$type} ID {$id}", [
                    'title' => $item->title,
                    'tmdb_id' => $item->tmdb_id
                ]);
                
                if (!$item->tmdb_id) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'id' => $id,
                        'title' => $item->title,
                        'error' => 'No TMDB ID found'
                    ];
                    Log::warning("No TMDB ID for {$type} ID {$id}");
                    continue;
                }

                // Refresh from TMDB
                $tmdbResult = null;
                if ($type === 'movie') {
                    $tmdbResult = $this->tmdbService->getMovieDetails($item->tmdb_id);
                } else {
                    // For series, use getTvDetails instead of getSeriesDetails
                    $tmdbResult = $this->tmdbService->getTvDetails($item->tmdb_id);
                }

                // Validate TMDB response
                if (!$tmdbResult || !isset($tmdbResult['success']) || !$tmdbResult['success']) {
                    $results['failed']++;
                    $error = 'Failed to fetch TMDB data';
                    if (isset($tmdbResult['error'])) {
                        $error .= ': ' . $tmdbResult['error'];
                    }
                    $results['errors'][] = [
                        'id' => $id,
                        'title' => $item->title,
                        'error' => $error
                    ];
                    Log::error("TMDB API failed for {$type} ID {$id}", [
                        'tmdb_id' => $item->tmdb_id,
                        'response' => $tmdbResult
                    ]);
                    continue;
                }

                // Extract data from response
                $tmdbData = $tmdbResult['data'] ?? $tmdbResult;
                
                // Validate data exists
                if (empty($tmdbData) || !is_array($tmdbData)) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'id' => $id,
                        'title' => $item->title,
                        'error' => 'Invalid TMDB response format'
                    ];
                    Log::error("Invalid TMDB response for {$type} ID {$id}", [
                        'tmdb_id' => $item->tmdb_id,
                        'data' => $tmdbData
                    ]);
                    continue;
                }

                // Update item with TMDB data
                $this->updateFromTMDBData($item, $tmdbData, $type);
                $results['success']++;
                Log::info("Successfully refreshed {$type} ID {$id} from TMDB");
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'id' => $id,
                    'title' => $item->title ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
                Log::error("TMDB refresh failed for {$type} ID {$id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info("Bulk TMDB refresh completed", [
            'type' => $type,
            'success' => $results['success'],
            'failed' => $results['failed'],
            'errors_count' => count($results['errors'])
        ]);

        return $results;
    }

    /**
     * Bulk change status
     * 
     * @param string $type
     * @param array $ids
     * @param string $status
     * @return array
     */
    public function bulkChangeStatus(string $type, array $ids, string $status): array
    {
        $model = $this->getModel($type);
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Validate status
        $validStatuses = ['published', 'draft', 'archived'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Invalid status');
        }

        DB::beginTransaction();
        try {
            $count = $model::whereIn('id', $ids)->update(['status' => $status]);
            $results['success'] = $count;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Bulk delete
     * 
     * @param string $type
     * @param array $ids
     * @return array
     */
    public function bulkDelete(string $type, array $ids): array
    {
        $model = $this->getModel($type);
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::beginTransaction();
        try {
            foreach ($ids as $id) {
                try {
                    $item = $model::findOrFail($id);
                    
                    // Delete related data first
                    if ($type === 'movie') {
                        $item->sources()->delete();
                        $item->views()->delete();
                    } else {
                        $item->seasons()->each(function($season) {
                            $season->episodes()->delete();
                        });
                        $item->seasons()->delete();
                        $item->views()->delete();
                    }
                    
                    $item->delete();
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'id' => $id,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Bulk toggle featured
     * 
     * @param string $type
     * @param array $ids
     * @param bool $featured
     * @return array
     */
    public function bulkToggleFeatured(string $type, array $ids, bool $featured): array
    {
        $model = $this->getModel($type);
        
        DB::beginTransaction();
        try {
            $count = $model::whereIn('id', $ids)->update(['is_featured' => $featured]);
            DB::commit();
            
            return [
                'success' => $count,
                'failed' => 0,
                'errors' => []
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get model class
     */
    protected function getModel(string $type)
    {
        return $type === 'movie' ? Movie::class : Series::class;
    }

    /**
     * Update item from TMDB data
     */
    protected function updateFromTMDBData($item, array $tmdbData, string $type): void
    {
        if ($type === 'movie') {
            $this->updateMovieFromTMDB($item, $tmdbData);
        } else {
            $this->updateSeriesFromTMDB($item, $tmdbData);
        }
    }

    /**
     * Update movie from TMDB data
     */
    protected function updateMovieFromTMDB($movie, array $tmdbData): void
    {
        $updateData = [
            'title' => $tmdbData['title'] ?? $movie->title,
            'original_title' => $tmdbData['original_title'] ?? $movie->original_title,
            'description' => $tmdbData['overview'] ?? $movie->description,
            'overview' => $tmdbData['overview'] ?? $movie->overview,
            'release_date' => $tmdbData['release_date'] ?? $movie->release_date,
            'poster_url' => isset($tmdbData['poster_path']) && $tmdbData['poster_path']
                ? 'https://image.tmdb.org/t/p/w500' . $tmdbData['poster_path'] 
                : $movie->poster_url,
            'backdrop_url' => isset($tmdbData['backdrop_path']) && $tmdbData['backdrop_path']
                ? 'https://image.tmdb.org/t/p/original' . $tmdbData['backdrop_path']
                : $movie->backdrop_url,
            'poster_path' => $tmdbData['poster_path'] ?? $movie->poster_path,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? $movie->backdrop_path,
            'rating' => $tmdbData['vote_average'] ?? $movie->rating,
            'vote_count' => $tmdbData['vote_count'] ?? $movie->vote_count,
            'popularity' => $tmdbData['popularity'] ?? $movie->popularity,
            'updated_at' => now()
        ];

        // Add year from release_date if available
        if (isset($tmdbData['release_date']) && $tmdbData['release_date']) {
            $updateData['year'] = (int) date('Y', strtotime($tmdbData['release_date']));
        }

        Log::info("Updating movie from TMDB", [
            'id' => $movie->id,
            'title' => $updateData['title'],
            'rating' => $updateData['rating']
        ]);

        $movie->update($updateData);
    }

    /**
     * Update series from TMDB data
     */
    protected function updateSeriesFromTMDB($series, array $tmdbData): void
    {
        $updateData = [
            'title' => $tmdbData['name'] ?? $series->title,
            'original_title' => $tmdbData['original_name'] ?? $series->original_title,
            'description' => $tmdbData['overview'] ?? $series->description,
            'overview' => $tmdbData['overview'] ?? $series->overview,
            'first_air_date' => $tmdbData['first_air_date'] ?? $series->first_air_date,
            'last_air_date' => $tmdbData['last_air_date'] ?? $series->last_air_date,
            'poster_url' => isset($tmdbData['poster_path']) && $tmdbData['poster_path']
                ? 'https://image.tmdb.org/t/p/w500' . $tmdbData['poster_path'] 
                : $series->poster_url,
            'backdrop_url' => isset($tmdbData['backdrop_path']) && $tmdbData['backdrop_path']
                ? 'https://image.tmdb.org/t/p/original' . $tmdbData['backdrop_path']
                : $series->backdrop_url,
            'poster_path' => $tmdbData['poster_path'] ?? $series->poster_path,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? $series->backdrop_path,
            'rating' => $tmdbData['vote_average'] ?? $series->rating,
            'vote_count' => $tmdbData['vote_count'] ?? $series->vote_count,
            'popularity' => $tmdbData['popularity'] ?? $series->popularity,
            'number_of_seasons' => $tmdbData['number_of_seasons'] ?? $series->number_of_seasons,
            'number_of_episodes' => $tmdbData['number_of_episodes'] ?? $series->number_of_episodes,
            'updated_at' => now()
        ];

        // Add year from first_air_date if available
        if (isset($tmdbData['first_air_date']) && $tmdbData['first_air_date']) {
            $updateData['year'] = (int) date('Y', strtotime($tmdbData['first_air_date']));
        }

        Log::info("Updating series from TMDB", [
            'id' => $series->id,
            'title' => $updateData['title'],
            'rating' => $updateData['rating'],
            'seasons' => $updateData['number_of_seasons'],
            'episodes' => $updateData['number_of_episodes']
        ]);

        $series->update($updateData);

        // Refresh episodes data from TMDB
        $episodeResults = $this->refreshSeriesEpisodes($series);
        
        Log::info("Series episodes refresh completed", [
            'series_id' => $series->id,
            'episodes_updated' => $episodeResults['episodes_updated'],
            'episodes_created' => $episodeResults['episodes_created'],
            'episodes_failed' => $episodeResults['episodes_failed']
        ]);
    }

    /**
     * Refresh episodes for a series from TMDB
     */
    protected function refreshSeriesEpisodes($series): array
    {
        $results = [
            'episodes_updated' => 0,
            'episodes_created' => 0,
            'episodes_failed' => 0
        ];

        if (!$series->tmdb_id || !$series->number_of_seasons) {
            Log::warning("Cannot refresh episodes - missing TMDB ID or seasons", [
                'series_id' => $series->id,
                'tmdb_id' => $series->tmdb_id
            ]);
            return $results;
        }

        try {
            // Get all seasons for this series
            $seasons = $series->seasons()->get();
            
            foreach ($seasons as $season) {
                if (!$season->season_number) continue;

                // Fetch season details with episodes from TMDB
                $seasonData = $this->tmdbService->getSeasonDetails(
                    $series->tmdb_id, 
                    $season->season_number
                );

                if (!$seasonData || !$seasonData['success']) {
                    Log::warning("Failed to fetch season data from TMDB", [
                        'series_id' => $series->id,
                        'season_number' => $season->season_number
                    ]);
                    continue;
                }

                $episodes = $seasonData['data']['episodes'] ?? [];

                // Update or create episodes
                foreach ($episodes as $episodeData) {
                    try {
                        $episode = \App\Models\SeriesEpisode::updateOrCreate(
                            [
                                'series_id' => $series->id,
                                'season_id' => $season->id,
                                'episode_number' => $episodeData['episode_number']
                            ],
                            [
                                'tmdb_id' => $episodeData['id'] ?? null,
                                'name' => $episodeData['name'] ?? 'Episode ' . $episodeData['episode_number'],
                                'overview' => $episodeData['overview'] ?? null,
                                'still_path' => $episodeData['still_path'] ?? null,
                                'air_date' => $episodeData['air_date'] ?? null,
                                'runtime' => $episodeData['runtime'] ?? null,
                                'vote_average' => $episodeData['vote_average'] ?? 0,
                                'vote_count' => $episodeData['vote_count'] ?? 0,
                            ]
                        );

                        if ($episode->wasRecentlyCreated) {
                            $results['episodes_created']++;
                        } else {
                            $results['episodes_updated']++;
                        }

                    } catch (\Exception $e) {
                        $results['episodes_failed']++;
                        Log::error("Failed to update episode", [
                            'series_id' => $series->id,
                            'season_number' => $season->season_number,
                            'episode_number' => $episodeData['episode_number'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info("Series episodes refresh completed", [
                'series_id' => $series->id,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error("Series episodes refresh failed", [
                'series_id' => $series->id,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Create progress cache key
     */
    public function createProgressKey(string $operation, string $type): string
    {
        return "bulk_operation_{$operation}_{$type}_" . auth()->id() . "_" . time();
    }

    /**
     * Update progress in cache
     */
    public function updateProgress(string $key, array $data): void
    {
        Cache::put($key, $data, now()->addMinutes(30));
    }

    /**
     * Get progress from cache
     */
    public function getProgress(string $key): ?array
    {
        return Cache::get($key);
    }
}
