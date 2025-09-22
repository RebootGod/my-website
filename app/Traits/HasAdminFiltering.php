<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * ========================================
 * ADMIN FILTERING TRAIT
 * Shared filtering logic for admin controllers
 * ========================================
 */
trait HasAdminFiltering
{
    /**
     * Apply search filter to query
     *
     * @param Builder $query
     * @param string|null $search
     * @param array $fields
     * @return Builder
     */
    protected function applySearch(Builder $query, ?string $search, array $fields = ['title']): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search, $fields) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Apply status filter to query
     *
     * @param Builder $query
     * @param string|null $status
     * @param string $column
     * @return Builder
     */
    protected function applyStatusFilter(Builder $query, ?string $status, string $column = 'status'): Builder
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where($column, $status);
    }

    /**
     * Apply date range filter to query
     *
     * @param Builder $query
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string $column
     * @return Builder
     */
    protected function applyDateFilter(Builder $query, ?string $dateFrom, ?string $dateTo, string $column = 'created_at'): Builder
    {
        if (!empty($dateFrom)) {
            $query->whereDate($column, '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate($column, '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Apply genre filter to query (for movies/series)
     *
     * @param Builder $query
     * @param array|null $genreIds
     * @return Builder
     */
    protected function applyGenreFilter(Builder $query, ?array $genreIds): Builder
    {
        if (empty($genreIds)) {
            return $query;
        }

        return $query->whereHas('genres', function ($q) use ($genreIds) {
            $q->whereIn('genres.id', $genreIds);
        });
    }

    /**
     * Apply sorting to query
     *
     * @param Builder $query
     * @param string $sortBy
     * @param string $sortOrder
     * @param array $allowedSorts
     * @return Builder
     */
    protected function applySorting(Builder $query, string $sortBy = 'created_at', string $sortOrder = 'desc', array $allowedSorts = ['created_at', 'title']): Builder
    {
        // Validate sort parameters
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Apply numeric range filter
     *
     * @param Builder $query
     * @param int|null $min
     * @param int|null $max
     * @param string $column
     * @return Builder
     */
    protected function applyNumericRangeFilter(Builder $query, ?int $min, ?int $max, string $column): Builder
    {
        if (!is_null($min)) {
            $query->where($column, '>=', $min);
        }

        if (!is_null($max)) {
            $query->where($column, '<=', $max);
        }

        return $query;
    }

    /**
     * Get paginated results with optimized queries
     *
     * @param Builder $query
     * @param int $perPage
     * @param array $relations
     * @param array $counts
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function getPaginatedResults(Builder $query, int $perPage = 15, array $relations = [], array $counts = [])
    {
        // Apply eager loading
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Apply count relationships
        if (!empty($counts)) {
            $query->withCount($counts);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Build filter summary for display
     *
     * @param array $filters
     * @return array
     */
    protected function buildFilterSummary(array $filters): array
    {
        $summary = [];

        if (!empty($filters['search'])) {
            $summary[] = "Search: \"{$filters['search']}\"";
        }

        if (!empty($filters['status'])) {
            $summary[] = "Status: {$filters['status']}";
        }

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $dateRange = '';
            if (!empty($filters['date_from'])) {
                $dateRange .= "From: {$filters['date_from']}";
            }
            if (!empty($filters['date_to'])) {
                $dateRange .= (!empty($dateRange) ? ' ' : '') . "To: {$filters['date_to']}";
            }
            $summary[] = "Date: {$dateRange}";
        }

        return $summary;
    }

    /**
     * Clear all filters (redirect to base route)
     *
     * @param string $route
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function clearFilters(string $route)
    {
        return redirect()->route($route);
    }
}