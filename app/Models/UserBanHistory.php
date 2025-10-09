<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserBanHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_ban_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action_type',
        'reason',
        'performed_by',
        'duration',
        'admin_ip',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who was affected by this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who performed this action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope: Filter by specific user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by action type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope: Filter by admin who performed the action.
     */
    public function scopeByAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where('performed_by', $adminId);
    }

    /**
     * Scope: Order by most recent first.
     */
    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope: Search by username or email.
     */
    public function scopeSearchUser(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->whereHas('user', function (Builder $q) use ($search) {
            $q->where('username', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Get human-readable action type.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action_type) {
            'ban' => 'Banned',
            'unban' => 'Unbanned',
            'suspend' => 'Suspended',
            'activate' => 'Activated',
            default => ucfirst($this->action_type),
        };
    }

    /**
     * Get badge color for UI display.
     */
    public function getBadgeColorAttribute(): string
    {
        return match ($this->action_type) {
            'ban' => 'red',
            'unban' => 'green',
            'suspend' => 'yellow',
            'activate' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get duration text for display.
     */
    public function getDurationTextAttribute(): string
    {
        if (empty($this->duration)) {
            return 'Permanent';
        }

        $days = $this->duration;
        
        if ($days == 1) {
            return '1 day';
        }

        if ($days < 30) {
            return "{$days} days";
        }

        $months = round($days / 30);
        return "{$months} month" . ($months > 1 ? 's' : '');
    }
}
