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
        return $this->poster_path ?: 'https://via.placeholder.com/500x750?text=No+Poster';
    }
}
