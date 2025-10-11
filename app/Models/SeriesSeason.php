<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeriesSeason extends Model
{
    use HasFactory;

    /**
     * Touch parent series updated_at when season is created/updated/deleted
     */
    protected $touches = ['series'];

    protected $fillable = [
        'series_id',
        'tmdb_id',
        'season_number',
        'name',
        'overview',
        'poster_path',
        'air_date',
        'episode_count',
        'is_active'
    ];

    protected $casts = [
        'air_date' => 'date',
        'season_number' => 'integer',
        'episode_count' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($season) {
            // Delete all episodes when season is deleted
            $season->episodes()->delete();
        });
    }

    // Relationships
    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function episodes()
    {
        return $this->hasMany(SeriesEpisode::class, 'season_id')->orderBy('episode_number');
    }

    // Accessors
    public function getPosterUrlAttribute(): string
    {
        if (!empty($this->attributes['poster_path'])) {
            // If poster_path starts with http, it's already a full URL
            if (str_starts_with($this->attributes['poster_path'], 'http')) {
                return $this->attributes['poster_path'];
            }
            // Otherwise, construct TMDB image URL
            return config('services.tmdb.image_url', 'https://image.tmdb.org/t/p') . '/w500' . $this->attributes['poster_path'];
        }
        
        return 'https://placehold.co/500x750?text=No+Poster';
    }
}
