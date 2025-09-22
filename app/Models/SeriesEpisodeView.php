<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeriesEpisodeView extends Model
{
    use HasFactory;

    protected $fillable = [
        'episode_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_at'
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    // Relationships
    public function episode()
    {
        return $this->belongsTo(SeriesEpisode::class, 'episode_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
