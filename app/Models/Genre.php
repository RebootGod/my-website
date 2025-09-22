<?php

// ========================================
// 4. GENRE MODEL
// ========================================
// File: app/Models/Genre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'name',
        'slug'
    ];

    // Relationships
    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_genres');
    }

    // Model Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($genre) {
            if (empty($genre->slug)) {
                $genre->slug = Str::slug($genre->name);
            }
        });
    }
}