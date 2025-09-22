<?php

// ========================================
// 6. MOVIE VIEW MODEL
// ========================================
// File: app/Models/MovieView.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'movie_id',
        'watched_at',
        'watch_duration',
        'ip_address'
    ];

    protected $casts = [
        'watched_at' => 'datetime',
        'watch_duration' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    // Static Methods
    public static function logView($movieId, $userId = null)
    {
        return self::create([
            'movie_id' => $movieId,
            'user_id' => $userId ?? auth()->id(),
            'watched_at' => now(),
            'ip_address' => request()->ip()
        ]);
    }
}