<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ContentScopes;

class Series extends Model
{
    use HasFactory, ContentScopes;

    protected $fillable = [
        'tmdb_id',
        'title',
        'slug',
        'original_title',
        'description',
        'overview',
        'poster_path',
        'backdrop_path',
        'local_poster_path',
        'local_backdrop_path',
        'poster_url',
        'backdrop_url',
        'year',
        'rating',
        'status',
        'vote_count',
        'popularity',
        'first_air_date',
        'last_air_date',
        'number_of_seasons',
        'number_of_episodes',
        'is_featured',
        'is_active',
        'created_by',
        'updated_by',
        'view_count'
    ];

    protected $casts = [
        'year' => 'integer',
        'rating' => 'float',
        'is_active' => 'boolean',
        'view_count' => 'integer'
    ];

    // ========================================
    // ROUTE KEY NAME - Use slug for public routes, ID for admin routes
    // ========================================

    /**
     * Get the route key for the model.
     * Use 'slug' for public routes, 'id' for admin routes.
     */
    public function getRouteKeyName()
    {
        // Check if current route is admin route
        if (request()->is('admin/*')) {
            return 'id';
        }
        
        return 'slug';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================
    
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'series_genres');
    }
    
    public function seasons()
    {
        return $this->hasMany(SeriesSeason::class)->orderBy('season_number');
    }
    
    public function episodes()
    {
        return $this->hasMany(SeriesEpisode::class)->orderBy('season_id')->orderBy('episode_number');
    }
    
    public function views()
    {
        return $this->hasMany(SeriesView::class);
    }
    
    // Removed unused watchHistory and favorites relationships
    // to prevent errors with non-existent models

    // ========================================
    // SEARCH SCOPES
    // ========================================
    
    /**
     * Scope for searching series
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function($q) use ($search) {
            $searchTerms = explode(' ', $search);
            
            foreach ($searchTerms as $term) {
                $q->where(function($subQuery) use ($term) {
                    $subQuery->where('title', 'LIKE', '%' . $term . '%')
                            ->orWhere('description', 'LIKE', '%' . $term . '%');
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
            $q->where('genres.id', $genre);
        });
    }
    
    /**
     * Scope for filtering by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope for filtering by year
     */
    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }
    
    /**
     * Scope for filtering by rating
     */
    public function scopeByRating(Builder $query, float $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }
    
    /**
     * Scope for popular series
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('popularity', 'desc');
    }
    
    /**
     * Scope for top rated series
     */
    public function scopeTopRated(Builder $query): Builder
    {
        return $query->orderBy('rating', 'desc');
    }
    
    /**
     * Scope for most viewed series
     */
    public function scopeMostViewed(Builder $query): Builder
    {
        return $query->orderBy('view_count', 'desc');
    }
    
    /**
     * Scope for new releases
     */
    public function scopeNewReleases(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    /**
     * Scope for series with subtitles
     */
    public function scopeWithSubtitles(Builder $query): Builder
    {
        return $query->where('has_subtitle', true);
    }
    
    /**
     * Scope for dubbed series
     */
    public function scopeDubbed(Builder $query): Builder
    {
        return $query->where('is_dubbed', true);
    }

    /**
     * Scope for published series
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                    ->where('is_active', true);
    }
    
    // ========================================
    // HELPER METHODS
    // ========================================
    
    /**
     * Get formatted rating
     */
    public function getFormattedRating(): string
    {
        return $this->rating ? number_format($this->rating, 1) . '/10' : 'N/A';
    }
    
    /**
     * Check if series is new (added within last 7 days)
     */
    public function getIsNewAttribute(): bool
    {
        return $this->created_at >= now()->subDays(7);
    }
    
    /**
     * Increment view count without updating timestamps
     */
    public function incrementViewCount(): void
    {
        // Use timestamps = false to prevent updated_at from being modified
        $this->timestamps = false;
        $this->increment('view_count');
        $this->timestamps = true;
    }

    /**
     * Check if series is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->is_active;
    }

    /**
     * Get poster URL (with fallback)
     */
    public function getPosterUrlAttribute(): string
    {
        // Priority 1: Local storage (from downloaded TMDB images)
        if (!empty($this->attributes['local_poster_path'])) {
            return \Storage::url($this->attributes['local_poster_path']);
        }

        // Priority 2: Direct poster_url field (custom uploads)
        if (!empty($this->attributes['poster_url'])) {
            return $this->attributes['poster_url'];
        }

        // Priority 3: Placeholder (no TMDB fallback per requirement)
        return 'https://placehold.co/500x750?text=No+Poster';
    }

    /**
     * Get backdrop URL (with fallback)
     */
    public function getBackdropUrlAttribute(): string
    {
        // Priority 1: Local storage (from downloaded TMDB images)
        if (!empty($this->attributes['local_backdrop_path'])) {
            return \Storage::url($this->attributes['local_backdrop_path']);
        }

        // Priority 2: Direct backdrop_url field (custom uploads)
        if (!empty($this->attributes['backdrop_url'])) {
            return $this->attributes['backdrop_url'];
        }

        // Priority 3: Placeholder (no TMDB fallback per requirement)
        return 'https://placehold.co/1920x1080?text=No+Backdrop';
    }

    /**
     * Get total episodes count
     */
    public function getTotalEpisodesAttribute(): int
    {
        return $this->episodes()->count();
    }

    /**
     * Get total seasons count
     */
    public function getTotalSeasonsAttribute(): int
    {
        return $this->seasons()->count();
    }

    /**
     * Get formatted duration (placeholder for series - episodes have actual duration)
     */
    public function getFormattedDuration(): string
    {
        return 'Series'; // Since series don't have duration, episodes do
    }
}
