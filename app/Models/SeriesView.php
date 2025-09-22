<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeriesView extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_at'
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    // Relationships
    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
