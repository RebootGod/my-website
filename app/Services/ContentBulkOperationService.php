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

        foreach ($ids as $id) {
            try {
                $item = $model::findOrFail($id);
                
                if (!$item->tmdb_id) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'id' => $id,
                        'error' => 'No TMDB ID found'
                    ];
                    continue;
                }

                // Refresh from TMDB
                if ($type === 'movie') {
                    $tmdbData = $this->tmdbService->getMovieDetails($item->tmdb_id);
                } else {
                    $tmdbData = $this->tmdbService->getSeriesDetails($item->tmdb_id);
                }

                if ($tmdbData) {
                    $this->updateFromTMDBData($item, $tmdbData, $type);
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'id' => $id,
                        'error' => 'Failed to fetch TMDB data'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'id' => $id,
                    'error' => $e->getMessage()
                ];
                Log::error("TMDB refresh failed for {$type} ID {$id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

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
        $updateData = [
            'title' => $tmdbData['title'] ?? $tmdbData['name'] ?? $item->title,
            'description' => $tmdbData['overview'] ?? $item->description,
            'release_date' => $tmdbData['release_date'] ?? $tmdbData['first_air_date'] ?? $item->release_date,
            'poster_url' => $tmdbData['poster_path'] 
                ? 'https://image.tmdb.org/t/p/w500' . $tmdbData['poster_path'] 
                : $item->poster_url,
            'backdrop_url' => $tmdbData['backdrop_path']
                ? 'https://image.tmdb.org/t/p/original' . $tmdbData['backdrop_path']
                : $item->backdrop_url,
            'rating' => $tmdbData['vote_average'] ?? $item->rating,
        ];

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
