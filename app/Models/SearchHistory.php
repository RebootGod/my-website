<?php
// ========================================
// SEARCH HISTORY MODEL
// ========================================
// File: app/Models/SearchHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'search_term',
        'results_count',
        'ip_address'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'results_count' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->select('search_term')
            ->groupBy('search_term')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit);
    }
}