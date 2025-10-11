<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeriesEpisode extends Model
{
    use HasFactory;

    /**
     * Touch parent series updated_at when episode is created/updated/deleted
     * This ensures series appears at top of homepage when new episode is added
     */
    protected $touches = ['series'];

    protected $fillable = [
        'series_id',
        'season_id',
        'tmdb_id',
        'episode_number',
        'name',
        'overview',
        'still_path',
        'air_date',
        'runtime',
        'vote_average',
        'vote_count',
        'embed_url',
        'download_url',
        'is_active'
    ];

    protected $casts = [
        'air_date' => 'date',
        'episode_number' => 'integer',
        'runtime' => 'integer',
        'vote_average' => 'float',
        'vote_count' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function season()
    {
        return $this->belongsTo(SeriesSeason::class, 'season_id');
    }

    public function views()
    {
        return $this->hasMany(SeriesEpisodeView::class);
    }

    // Accessors
    public function getStillUrlAttribute(): string
    {
        // Priority 1: Local storage (from downloaded TMDB images)
        if (!empty($this->attributes['local_still_path'])) {
            return \Storage::url($this->attributes['local_still_path']);
        }

        // Priority 2: Placeholder (no TMDB fallback per requirement)
        return 'https://placehold.co/500x281?text=No+Still';
    }

    public function getFormattedRuntime(): string
    {
        if (!$this->runtime) {
            return 'N/A';
        }

        $hours = intval($this->runtime / 60);
        $minutes = $this->runtime % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $minutes . 'm';
        }
    }
}
