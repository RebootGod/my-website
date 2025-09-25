<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'activity_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Activity type constants
    const TYPE_LOGIN = 'login';
    const TYPE_LOGIN_FAILED = 'login_failed';
    const TYPE_LOGOUT = 'logout';
    const TYPE_WATCH_MOVIE = 'watch_movie';
    const TYPE_WATCH_SERIES = 'watch_series';
    const TYPE_SEARCH = 'search';
    const TYPE_REGISTER = 'register';
    const TYPE_PROFILE_UPDATE = 'profile_update';
    const TYPE_PASSWORD_CHANGE = 'password_change';

    // Scope methods for filtering
    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('activity_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('activity_at', Carbon::today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('activity_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('activity_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ]);
    }

    // Helper method to get formatted activity description
    public function getFormattedDescriptionAttribute()
    {
        return $this->description;
    }

    // Helper method to get activity icon based on type
    public function getIconAttribute()
    {
        return match($this->activity_type) {
            self::TYPE_LOGIN => 'fas fa-sign-in-alt',
            self::TYPE_LOGIN_FAILED => 'fas fa-exclamation-triangle',
            self::TYPE_LOGOUT => 'fas fa-sign-out-alt',
            self::TYPE_WATCH_MOVIE => 'fas fa-film',
            self::TYPE_WATCH_SERIES => 'fas fa-tv',
            self::TYPE_SEARCH => 'fas fa-search',
            self::TYPE_REGISTER => 'fas fa-user-plus',
            self::TYPE_PROFILE_UPDATE => 'fas fa-user-edit',
            self::TYPE_PASSWORD_CHANGE => 'fas fa-key',
            default => 'fas fa-circle'
        };
    }

    // Helper method to get activity color class
    public function getColorClassAttribute()
    {
        return match($this->activity_type) {
            self::TYPE_LOGIN => 'success',
            self::TYPE_LOGIN_FAILED => 'danger',
            self::TYPE_LOGOUT => 'warning',
            self::TYPE_WATCH_MOVIE, self::TYPE_WATCH_SERIES => 'primary',
            self::TYPE_SEARCH => 'info',
            self::TYPE_REGISTER => 'success',
            self::TYPE_PROFILE_UPDATE, self::TYPE_PASSWORD_CHANGE => 'secondary',
            default => 'default'
        };
    }
}
