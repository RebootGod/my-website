<?php
// ========================================
// BROKEN LINK REPORT MODEL
// ========================================
// File: app/Models/BrokenLinkReport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrokenLinkReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'series_id',
        'episode_id',
        'movie_source_id',
        'user_id',
        'issue_type',
        'description',
        'ip_address',
        'user_agent',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime'
    ];

    // Issue type labels
    const ISSUE_TYPES = [
        'not_loading' => 'Video Not Loading',
        'wrong_movie' => 'Wrong Movie',
        'wrong_episode' => 'Wrong Episode',
        'poor_quality' => 'Poor Quality',
        'no_audio' => 'No Audio',
        'no_subtitle' => 'No Subtitle',
        'buffering' => 'Constant Buffering',
        'other' => 'Other Issue'
    ];

    // Status labels
    const STATUSES = [
        'pending' => 'Pending Review',
        'reviewing' => 'Under Review',
        'fixed' => 'Fixed',
        'dismissed' => 'Dismissed'
    ];

    // Relationships
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function episode()
    {
        return $this->belongsTo(SeriesEpisode::class);
    }

    public function movieSource()
    {
        return $this->belongsTo(MovieSource::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByMovie($query, $movieId)
    {
        return $query->where('movie_id', $movieId);
    }

    public function scopeBySource($query, $sourceId)
    {
        return $query->where('movie_source_id', $sourceId);
    }

    // Helper Methods
    public function getIssueTypeLabel()
    {
        return self::ISSUE_TYPES[$this->issue_type] ?? 'Unknown';
    }

    public function getStatusLabel()
    {
        return self::STATUSES[$this->status] ?? 'Unknown';
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'reviewing' => 'blue',
            'fixed' => 'green',
            'dismissed' => 'gray',
            default => 'gray'
        };
    }

    public function markAsReviewing($adminId = null)
    {
        $this->update([
            'status' => 'reviewing',
            'reviewed_by' => $adminId ?? auth()->id(),
            'reviewed_at' => now()
        ]);
    }

    public function markAsFixed($adminNotes = null)
    {
        $this->update([
            'status' => 'fixed',
            'admin_notes' => $adminNotes,
            'reviewed_at' => now()
        ]);
    }

    public function markAsDismissed($reason = null)
    {
        $this->update([
            'status' => 'dismissed',
            'admin_notes' => $reason,
            'reviewed_at' => now()
        ]);
    }

    // Static Methods
    public static function checkAndDisableSource($sourceId, $threshold = 5)
    {
        $reportCount = self::where('movie_source_id', $sourceId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($reportCount >= $threshold) {
            MovieSource::find($sourceId)?->update([
                'is_active' => false,
                'notes' => 'Auto-disabled due to multiple reports'
            ]);
            return true;
        }

        return false;
    }
}