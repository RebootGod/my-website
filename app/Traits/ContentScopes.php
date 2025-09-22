<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Content Scopes Trait
 * Shared query scopes for Movie and Series models
 * Eliminates duplicate scope methods across content models
 */
trait ContentScopes
{
    /**
     * Scope for published content
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                    ->where('is_active', true);
    }

    /**
     * Scope for draft content
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for archived content
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope for active content
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured content
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)
                    ->where('is_active', true);
    }

    /**
     * Scope for searching content by title and description
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function($q) use ($search) {
            $searchTerms = explode(' ', trim($search));

            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (empty($term)) continue;

                $q->where(function($subQuery) use ($term) {
                    $subQuery->where('title', 'LIKE', '%' . $term . '%')
                            ->orWhere('original_title', 'LIKE', '%' . $term . '%')
                            ->orWhere('overview', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }

    /**
     * Scope for filtering by genre
     */
    public function scopeByGenre(Builder $query, $genre): Builder
    {
        return $query->whereHas('genres', function($q) use ($genre) {
            if (is_numeric($genre)) {
                $q->where('genres.id', $genre);
            } elseif (is_array($genre)) {
                $q->whereIn('genres.id', $genre);
            } else {
                $q->where('slug', $genre)
                  ->orWhere('name', 'LIKE', '%' . $genre . '%');
            }
        });
    }

    /**
     * Scope for filtering by year
     */
    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('release_date', $year);
    }

    /**
     * Scope for filtering by year range
     */
    public function scopeByYearRange(Builder $query, int $startYear, int $endYear): Builder
    {
        return $query->whereBetween('release_date', [
            Carbon::createFromDate($startYear, 1, 1),
            Carbon::createFromDate($endYear, 12, 31)
        ]);
    }

    /**
     * Scope for filtering by rating
     */
    public function scopeByRating(Builder $query, float $minRating, float $maxRating = 10.0): Builder
    {
        return $query->whereBetween('vote_average', [$minRating, $maxRating]);
    }

    /**
     * Scope for popular content (high rating and vote count)
     */
    public function scopePopular(Builder $query, float $minRating = 7.0, int $minVotes = 100): Builder
    {
        return $query->where('vote_average', '>=', $minRating)
                    ->where('vote_count', '>=', $minVotes)
                    ->orderBy('popularity', 'desc');
    }

    /**
     * Scope for trending content (based on recent views)
     */
    public function scopeTrending(Builder $query, int $days = 7): Builder
    {
        return $query->withCount(['views' => function($q) use ($days) {
                        $q->where('created_at', '>=', now()->subDays($days));
                    }])
                    ->where('is_active', true)
                    ->orderBy('views_count', 'desc');
    }

    /**
     * Scope for recently added content
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for recently updated content
     */
    public function scopeRecentlyUpdated(Builder $query, int $days = 7): Builder
    {
        return $query->where('updated_at', '>=', now()->subDays($days))
                    ->where('updated_at', '!=', 'created_at')
                    ->orderBy('updated_at', 'desc');
    }

    /**
     * Scope for content with poster
     */
    public function scopeWithPoster(Builder $query): Builder
    {
        return $query->whereNotNull('poster_path')
                    ->where('poster_path', '!=', '');
    }

    /**
     * Scope for content without poster
     */
    public function scopeWithoutPoster(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNull('poster_path')
              ->orWhere('poster_path', '');
        });
    }

    /**
     * Scope for content with TMDB ID
     */
    public function scopeWithTmdbId(Builder $query): Builder
    {
        return $query->whereNotNull('tmdb_id');
    }

    /**
     * Scope for content without TMDB ID
     */
    public function scopeWithoutTmdbId(Builder $query): Builder
    {
        return $query->whereNull('tmdb_id');
    }

    /**
     * Scope for ordering by popularity
     */
    public function scopeOrderByPopularity(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('popularity', $direction);
    }

    /**
     * Scope for ordering by rating
     */
    public function scopeOrderByRating(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('vote_average', $direction)
                    ->orderBy('vote_count', $direction);
    }

    /**
     * Scope for ordering by release date
     */
    public function scopeOrderByReleaseDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('release_date', $direction);
    }

    /**
     * Scope for ordering by title
     */
    public function scopeOrderByTitle(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('title', $direction);
    }

    /**
     * Scope for ordering by view count
     */
    public function scopeOrderByViews(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->withCount('views')
                    ->orderBy('views_count', $direction);
    }

    /**
     * Scope for content added by specific user
     */
    public function scopeAddedBy(Builder $query, int $userId): Builder
    {
        return $query->where('added_by', $userId);
    }

    /**
     * Scope for filtering by language
     */
    public function scopeByLanguage(Builder $query, string $language): Builder
    {
        return $query->where('original_language', $language);
    }

    /**
     * Scope for filtering by minimum runtime/duration
     */
    public function scopeMinRuntime(Builder $query, int $minutes): Builder
    {
        $column = $this->getTable() === 'movies' ? 'runtime' : 'episode_run_time';
        return $query->where($column, '>=', $minutes);
    }

    /**
     * Scope for filtering by maximum runtime/duration
     */
    public function scopeMaxRuntime(Builder $query, int $minutes): Builder
    {
        $column = $this->getTable() === 'movies' ? 'runtime' : 'episode_run_time';
        return $query->where($column, '<=', $minutes);
    }
}