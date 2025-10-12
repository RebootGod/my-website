<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Filter Service
 * 
 * Handles advanced filtering operations for admin content management
 * Max 350 lines per workinginstruction.md
 * 
 * Features:
 * - Filter preset management
 * - Complex filter combinations
 * - Export functionality
 */
class AdvancedFilterService
{
    /**
     * Save filter preset
     * 
     * @param int $userId
     * @param string $name
     * @param array $filters
     * @param string $type 'movie' or 'series'
     * @return array
     */
    public function saveFilterPreset(int $userId, string $name, array $filters, string $type): array
    {
        // Validate filter data
        $validated = $this->validateFilters($filters);
        
        $preset = [
            'id' => uniqid(),
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'filters' => $validated,
            'created_at' => now()->toDateTimeString()
        ];

        // Store in cache (or database if you prefer)
        $cacheKey = "filter_presets_{$userId}_{$type}";
        $presets = Cache::get($cacheKey, []);
        $presets[] = $preset;
        
        Cache::put($cacheKey, $presets, now()->addDays(30));

        return $preset;
    }

    /**
     * Get filter presets for user
     * 
     * @param int $userId
     * @param string $type
     * @return array
     */
    public function getFilterPresets(int $userId, string $type): array
    {
        $cacheKey = "filter_presets_{$userId}_{$type}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Delete filter preset
     * 
     * @param int $userId
     * @param string $presetId
     * @param string $type
     * @return bool
     */
    public function deleteFilterPreset(int $userId, string $presetId, string $type): bool
    {
        $cacheKey = "filter_presets_{$userId}_{$type}";
        $presets = Cache::get($cacheKey, []);
        
        $presets = array_filter($presets, function($preset) use ($presetId) {
            return $preset['id'] !== $presetId;
        });
        
        Cache::put($cacheKey, array_values($presets), now()->addDays(30));
        
        return true;
    }

    /**
     * Validate filter data
     * 
     * @param array $filters
     * @return array
     */
    protected function validateFilters(array $filters): array
    {
        $validated = [];

        // Search
        if (isset($filters['search']) && is_string($filters['search'])) {
            $validated['search'] = strip_tags($filters['search']);
        }

        // Status
        if (isset($filters['status']) && in_array($filters['status'], ['published', 'draft', 'archived'])) {
            $validated['status'] = $filters['status'];
        }

        // Year range
        if (isset($filters['year_from']) && is_numeric($filters['year_from'])) {
            $validated['year_from'] = (int) $filters['year_from'];
        }
        if (isset($filters['year_to']) && is_numeric($filters['year_to'])) {
            $validated['year_to'] = (int) $filters['year_to'];
        }

        // Rating range
        if (isset($filters['rating_from']) && is_numeric($filters['rating_from'])) {
            $validated['rating_from'] = (float) $filters['rating_from'];
        }
        if (isset($filters['rating_to']) && is_numeric($filters['rating_to'])) {
            $validated['rating_to'] = (float) $filters['rating_to'];
        }

        // Views range
        if (isset($filters['views_from']) && is_numeric($filters['views_from'])) {
            $validated['views_from'] = (int) $filters['views_from'];
        }
        if (isset($filters['views_to']) && is_numeric($filters['views_to'])) {
            $validated['views_to'] = (int) $filters['views_to'];
        }

        // Genre IDs
        if (isset($filters['genre_ids']) && is_array($filters['genre_ids'])) {
            $validated['genre_ids'] = array_map('intval', $filters['genre_ids']);
        }

        // Has TMDB ID
        if (isset($filters['has_tmdb'])) {
            $validated['has_tmdb'] = (bool) $filters['has_tmdb'];
        }

        // Quality (for movies)
        if (isset($filters['quality']) && is_string($filters['quality'])) {
            $validated['quality'] = strip_tags($filters['quality']);
        }

        return $validated;
    }

    /**
     * Build query with advanced filters
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyAdvancedFilters($query, array $filters)
    {
        // Year range
        if (isset($filters['year_from'])) {
            $query->where('year', '>=', $filters['year_from']);
        }
        if (isset($filters['year_to'])) {
            $query->where('year', '<=', $filters['year_to']);
        }

        // Rating range
        if (isset($filters['rating_from'])) {
            $query->where('rating', '>=', $filters['rating_from']);
        }
        if (isset($filters['rating_to'])) {
            $query->where('rating', '<=', $filters['rating_to']);
        }

        // Views range
        if (isset($filters['views_from'])) {
            $query->where('view_count', '>=', $filters['views_from']);
        }
        if (isset($filters['views_to'])) {
            $query->where('view_count', '<=', $filters['views_to']);
        }

        // Has TMDB ID
        if (isset($filters['has_tmdb'])) {
            if ($filters['has_tmdb']) {
                $query->whereNotNull('tmdb_id');
            } else {
                $query->whereNull('tmdb_id');
            }
        }

        // Quality
        if (isset($filters['quality']) && !empty($filters['quality'])) {
            $query->where('quality', $filters['quality']);
        }

        return $query;
    }

    /**
     * Get filter statistics
     * 
     * @param string $type 'movie' or 'series'
     * @return array
     */
    public function getFilterStats(string $type): array
    {
        $table = $type === 'movie' ? 'movies' : 'series';
        
        return Cache::remember("filter_stats_{$type}", 300, function() use ($table) {
            return [
                'year_range' => [
                    'min' => DB::table($table)->min('year') ?? 1900,
                    'max' => DB::table($table)->max('year') ?? date('Y')
                ],
                'rating_range' => [
                    'min' => 0,
                    'max' => 10
                ],
                'views_range' => [
                    'min' => DB::table($table)->min('view_count') ?? 0,
                    'max' => DB::table($table)->max('view_count') ?? 0
                ],
                'total_count' => DB::table($table)->count(),
                'with_tmdb' => DB::table($table)->whereNotNull('tmdb_id')->count(),
                'without_tmdb' => DB::table($table)->whereNull('tmdb_id')->count()
            ];
        });
    }

    /**
     * Export filtered results to CSV
     * 
     * @param array $items
     * @param string $type
     * @return string CSV content
     */
    public function exportToCSV(array $items, string $type): string
    {
        if (empty($items)) {
            return '';
        }

        // Define headers
        $headers = ['ID', 'Title', 'Year', 'Status', 'Rating', 'Views', 'TMDB ID'];
        
        if ($type === 'movie') {
            $headers[] = 'Quality';
        } else {
            $headers[] = 'Seasons';
        }

        // Start CSV
        $csv = implode(',', $headers) . "\n";

        // Add data rows
        foreach ($items as $item) {
            $row = [
                $item->id,
                '"' . str_replace('"', '""', $item->title) . '"',
                $item->year ?? 'N/A',
                $item->status,
                $item->rating ?? 'N/A',
                $item->view_count ?? 0,
                $item->tmdb_id ?? 'N/A'
            ];

            if ($type === 'movie') {
                $row[] = $item->quality ?? 'N/A';
            } else {
                $row[] = $item->seasons_count ?? 0;
            }

            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }

    /**
     * Clear all filter presets for user
     * 
     * @param int $userId
     * @param string $type
     * @return bool
     */
    public function clearAllPresets(int $userId, string $type): bool
    {
        $cacheKey = "filter_presets_{$userId}_{$type}";
        Cache::forget($cacheKey);
        return true;
    }
}
