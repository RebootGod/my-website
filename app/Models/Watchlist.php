<?php
// ========================================
// WATCHLIST MODEL
// ========================================
// File: app/Models/Watchlist.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    use HasFactory;

    protected $table = 'watchlist';

    protected $fillable = [
        'user_id',
        'movie_id'
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
    
    // Static methods
    public static function isInWatchlist($userId, $movieId)
    {
        return self::where('user_id', $userId)
            ->where('movie_id', $movieId)
            ->exists();
    }
}