<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'action_type', 
        'description',
        'target_user_id',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
        'request_method',
        'request_url',
        'metadata',
        'old_values',
        'new_values',
        'severity',
        'is_sensitive',
        'session_id',
        'status',
        'error_message'
    ];

    protected $casts = [
        'metadata' => 'array',
        'old_values' => 'array', 
        'new_values' => 'array',
        'is_sensitive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Scopes for filtering
     */
    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByTarget($query, $targetUserId)
    {
        return $query->where('target_user_id', $targetUserId);
    }

    /**
     * Static methods for common logging patterns
     */
    public static function logUserAction(string $action, $targetUser = null, array $data = []): self
    {
        return self::logAction($action, 'user_management', $targetUser, $data);
    }

    public static function logRoleAction(string $action, $target = null, array $data = []): self
    {
        return self::logAction($action, 'role_management', $target, $data);
    }

    public static function logSystemAction(string $action, array $data = []): self
    {
        return self::logAction($action, 'system', null, $data);
    }

    public static function logSecurityAction(string $action, $target = null, array $data = []): self
    {
        $data['severity'] = $data['severity'] ?? 'high';
        $data['is_sensitive'] = true;
        return self::logAction($action, 'security', $target, $data);
    }

    /**
     * Main logging method
     */
    public static function logAction(
        string $action, 
        string $actionType = 'general',
        $target = null,
        array $data = []
    ): self {
        // Get authenticated user ID, fallback to null for system actions
        $adminId = auth()->id();
        
        // For system actions without authenticated user, we can still log but mark as system
        if (!$adminId && $actionType === 'system') {
            // Skip logging if no admin context available for security reasons
            // Log to file instead for system diagnostics
            \Log::info("System action logged without authenticated admin", [
                'action' => $action,
                'action_type' => $actionType,
                'description' => $data['description'] ?? "System action: {$action}",
                'metadata' => $data['metadata'] ?? []
            ]);
            
            // Return a dummy object to prevent errors
            return new static();
        }
        
        // Require authenticated admin for all other action types
        if (!$adminId) {
            throw new \Exception("Admin action logging requires authenticated admin user");
        }
        
        $logData = [
            'admin_id' => $adminId,
            'action' => $action,
            'action_type' => $actionType,
            'description' => $data['description'] ?? self::generateDescription($action, $target),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'session_id' => session()->getId(),
            'severity' => $data['severity'] ?? 'medium',
            'is_sensitive' => $data['is_sensitive'] ?? false,
            'status' => $data['status'] ?? 'success',
            'metadata' => $data['metadata'] ?? [],
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'error_message' => $data['error_message'] ?? null,
        ];

        // Handle target information
        if ($target) {
            if ($target instanceof User) {
                $logData['target_user_id'] = $target->id;
                $logData['target_type'] = 'user';
                $logData['target_id'] = $target->id;
            } elseif (is_array($target)) {
                $logData['target_type'] = $target['type'] ?? null;
                $logData['target_id'] = $target['id'] ?? null;
                $logData['target_user_id'] = $target['user_id'] ?? null;
            }
        }

        return self::create($logData);
    }

    /**
     * Generate human-readable description
     */
    private static function generateDescription(string $action, $target = null): string
    {
        $adminName = auth()->user()?->username ?? 'System';
        $targetName = '';

        if ($target instanceof User) {
            $targetName = " for user '{$target->username}'";
        } elseif (is_array($target) && isset($target['name'])) {
            $targetName = " for {$target['name']}";
        }

        return "Admin '{$adminName}' performed action '{$action}'{$targetName}";
    }

    /**
     * Get formatted display information
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'admin_name' => $this->admin?->username ?? 'Unknown',
            'target_name' => $this->targetUser?->username ?? 'N/A',
            'formatted_date' => $this->created_at->format('Y-m-d H:i:s'),
            'severity_badge' => $this->getSeverityBadge(),
            'action_label' => str_replace('_', ' ', ucwords($this->action, '_')),
        ];
    }

    private function getSeverityBadge(): string
    {
        return match($this->severity) {
            'low' => '<span class="badge bg-success">Low</span>',
            'medium' => '<span class="badge bg-warning">Medium</span>',
            'high' => '<span class="badge bg-danger">High</span>',
            'critical' => '<span class="badge bg-dark">Critical</span>',
            default => '<span class="badge bg-secondary">Unknown</span>'
        };
    }

    /**
     * Get statistics for dashboard
     */
    public static function getStats(int $days = 30): array
    {
        $query = self::where('created_at', '>=', now()->subDays($days));
        
        return [
            'total_actions' => $query->count(),
            'sensitive_actions' => $query->where('is_sensitive', true)->count(),
            'failed_actions' => $query->where('status', 'failed')->count(),
            'by_severity' => $query->selectRaw('severity, COUNT(*) as count')
                                  ->groupBy('severity')
                                  ->pluck('count', 'severity')
                                  ->toArray(),
            'by_action_type' => $query->selectRaw('action_type, COUNT(*) as count')
                                     ->groupBy('action_type')
                                     ->pluck('count', 'action_type')
                                     ->toArray(),
            'top_admins' => $query->selectRaw('admin_id, COUNT(*) as count')
                                 ->with('admin:id,username')
                                 ->groupBy('admin_id')
                                 ->orderByDesc('count')
                                 ->limit(5)
                                 ->get()
                                 ->toArray()
        ];
    }
}
