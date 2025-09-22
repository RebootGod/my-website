<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeriesEpisode extends Model
{
    use HasFactory;

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
        return $this->still_path ?: 'https://via.placeholder.com/500x281?text=No+Still';
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
