<?php

// ========================================
// 7. MOVIE SOURCE MODEL
// ========================================
// File: app/Models/MovieSource.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MovieSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'source_name',
        'embed_url',
        'quality',
        'is_active',
        'priority'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer'
    ];

    // Relationships
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    // Accessors & Mutators
    public function setEmbedUrlAttribute($value)
    {
        $this->attributes['embed_url'] = Crypt::encryptString($value);
    }

    public function getEmbedUrlAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}