<?php
// ========================================
// ENHANCED MOVIE MODEL
// ========================================
// File: app/Models/Movie.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ContentScopes;

class Movie extends Model
{
    use HasFactory, ContentScopes;

    protected $fillable = [
        'tmdb_id',
        'imdb_id',
        'title',
        'original_title',
        'slug',
        'description',
        'embed_url',
        'download_url',
        'poster_path',
        'backdrop_path',
        'year',
        'duration',
        'rating',
        'quality',
        'status',
        'view_count',
        'added_by',
        'overview',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'runtime',
        'release_date',
        'vote_count',
        'popularity',
        'language',
        'has_subtitle',
        'is_dubbed',
        'cast',
        'director',
        'is_featured',
        'is_active'
    ];

    protected $casts = [
        'release_date' => 'date',
        'year' => 'integer',
        'runtime' => 'integer',
        'duration' => 'integer',
        'rating' => 'float',
        'vote_count' => 'integer',
        'popularity' => 'float',
        'has_subtitle' => 'boolean',
        'is_dubbed' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================
    
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genres');
    }
    
    public function sources()
    {
        return $this->hasMany(MovieSource::class);
    }
    
    public function views()
    {
        return $this->hasMany(MovieView::class);
    }
    
    // Removed unused watchHistory and favorites relationships
    // to prevent errors with non-existent models    // ========================================
    // SEARCH SCOPES
    // ========================================
    
    /**
     * Scope for searching movies
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function($q) use ($search) {
            $searchTerms = explode(' ', $search);
            
            foreach ($searchTerms as $term) {
                $q->where(function($subQuery) use ($term) {
                    $subQuery->where('title', 'LIKE', '%' . $term . '%')
                            ->orWhere('original_title', 'LIKE', '%' . $term . '%')
                            ->orWhere('overview', 'LIKE', '%' . $term . '%')
                            ->orWhere('cast', 'LIKE', '%' . $term . '%')
                            ->orWhere('director', 'LIKE', '%' . $term . '%');
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
            } else {
                $q->where('slug', $genre)
                  ->orWhere('name', 'LIKE', '%' . $genre . '%');
            }
        });
    }
    
    /**
     * Scope for filtering by year range
     */
    public function scopeByYearRange(Builder $query, $startYear, $endYear = null): Builder
    {
        if ($endYear) {
            return $query->whereBetween('year', [$startYear, $endYear]);
        }
        return $query->where('year', $startYear);
    }
    
    /**
     * Scope for filtering by quality
     */
    public function scopeByQuality(Builder $query, string $quality): Builder
    {
        return $query->where('quality', $quality);
    }
    
    /**
     * Scope for filtering by minimum rating
     */
    public function scopeMinRating(Builder $query, float $rating): Builder
    {
        return $query->where('rating', '>=', $rating);
    }
    
    /**
     * Scope for featured movies
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
    
    /**
     * Scope for active movies
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for trending movies (most viewed in last N days)
     */
    public function scopeTrending(Builder $query, int $days = 7): Builder
    {
        return $query->whereHas('views', function($q) use ($days) {
            $q->where('created_at', '>=', now()->subDays($days));
        })
        ->withCount(['views' => function($q) use ($days) {
            $q->where('created_at', '>=', now()->subDays($days));
        }])
        ->orderBy('views_count', 'desc');
    }
    
    /**
     * Scope for new releases
     */
    public function scopeNewReleases(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    /**
     * Scope for movies with subtitles
     */
    public function scopeWithSubtitles(Builder $query): Builder
    {
        return $query->where('has_subtitle', true);
    }
    
    /**
     * Scope for dubbed movies
     */
    public function scopeDubbed(Builder $query): Builder
    {
        return $query->where('is_dubbed', true);
    }

    /**
     * Scope for published movies
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
     * Get formatted runtime
     */
    public function getFormattedRuntimeAttribute(): string
    {
        $hours = floor($this->runtime / 60);
        $minutes = $this->runtime % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }
    
    /**
     * Get quality badge color
     */
    public function getQualityColorAttribute(): string
    {
        return match($this->quality) {
            '4k' => 'purple',
            '1080p' => 'green',
            '720p' => 'blue',
            'cam' => 'red',
            default => 'gray'
        };
    }
    
    /**
     * Check if movie is new (added within last 7 days)
     */
    public function getIsNewAttribute(): bool
    {
        return $this->created_at >= now()->subDays(7);
    }
    
    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        // Only increment the view count, no watch history tracking
        // Watch history should only be tracked when user actually watches the movie (in player)
        $this->increment('view_count');
    }

    /**
     * Check if movie is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->is_active;
    }

    /**
     * Get formatted duration (hours and minutes)
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration) {
            return 'N/A';
        }

        $hours = intval($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $minutes . 'm';
        }
    }

    /**
     * Get poster URL (with fallback)
     */
    public function getPosterUrlAttribute(): string
    {
        // Priority: poster_url field -> poster_path field -> placeholder
        return $this->attributes['poster_url'] ?: $this->poster_path ?: 'https://placehold.co/500x750?text=No+Poster';
    }

    /**
     * Get backdrop URL (with fallback)
     */
    public function getBackdropUrlAttribute(): string
    {
        return $this->backdrop_path ?: 'https://placehold.co/1920x1080?text=No+Backdrop';
    }

    /**
     * Get formatted rating
     */
    public function getFormattedRating(): string
    {
        return $this->rating ? number_format($this->rating, 1) . '/10' : 'N/A';
    }

    /**
     * Get the route key for the model.
     * Use 'slug' for public routes, 'id' for admin routes.
     */
    public function getRouteKeyName(): string
    {
        // Check if current route is admin route
        if (request()->is('admin/*')) {
            return 'id';
        }

        return 'slug';
    }
}