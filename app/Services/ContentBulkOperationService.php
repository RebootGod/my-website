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
        // Movies use 'title', Series use 'name'
        $titleField = $type === 'movie' ? 'title' : 'name';
        // Movies use 'release_date', Series use 'first_air_date'
        $dateField = $type === 'movie' ? 'release_date' : 'first_air_date';

        $updateData = [
            'title' => $tmdbData[$titleField] ?? $item->title,
            'description' => $tmdbData['overview'] ?? $item->description,
            'release_date' => $tmdbData[$dateField] ?? $item->release_date,
            'poster_url' => isset($tmdbData['poster_path']) && $tmdbData['poster_path']
                ? 'https://image.tmdb.org/t/p/w500' . $tmdbData['poster_path'] 
                : $item->poster_url,
            'backdrop_url' => isset($tmdbData['backdrop_path']) && $tmdbData['backdrop_path']
                ? 'https://image.tmdb.org/t/p/original' . $tmdbData['backdrop_path']
                : $item->backdrop_url,
            'rating' => $tmdbData['vote_average'] ?? $item->rating,
            'updated_at' => now() // Explicitly update timestamp
        ];

        // Log the update for debugging
        Log::info("Updating {$type} from TMDB", [
            'id' => $item->id,
            'title' => $updateData['title'],
            'rating' => $updateData['rating']
        ]);

        $item->update($updateData);
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
