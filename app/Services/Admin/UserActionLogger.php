<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\AdminActionLog;
use Illuminate\Support\Facades\Auth;

/**
 * UserActionLogger - Centralized user action logging service
 * Handles all admin action logging with consistent patterns and metadata
 */
class UserActionLogger
{
    /**
     * Log user update action
     */
    public static function logUserUpdate(User $user, array $oldValues, array $newValues, array $metadata = []): void
    {
        $changes = [];
        $severity = 'medium';
        
        // Calculate changes
        foreach (['name', 'email', 'role', 'status'] as $field) {
            if (isset($newValues[$field]) && isset($oldValues[$field]) && $oldValues[$field] !== $newValues[$field]) {
                $changes[$field] = [
                    'from' => $oldValues[$field],
                    'to' => $newValues[$field]
                ];
                
                // Role changes are high severity
                if ($field === 'role') {
                    $severity = 'high';
                }
            }
        }

        if (empty($changes)) {
            return; // No changes to log
        }

        AdminActionLog::logUserAction('user_updated', $user, [
            'description' => "Updated user '{$user->username}' profile",
            'severity' => $severity,
            'old_values' => $oldValues,
            'new_values' => array_intersect_key($newValues, $changes),
            'metadata' => array_merge([
                'changed_fields' => array_keys($changes),
                'password_changed' => $metadata['password_changed'] ?? false,
                'role_change' => isset($changes['role']),
                'status_change' => isset($changes['status']),
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log user deletion action
     */
    public static function logUserDelete(User $user): void
    {
        // Store user data before deletion
        $deletedUserData = [
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => $user->created_at
        ];

        AdminActionLog::logUserAction('user_deleted', null, [
            'description' => "Deleted user '{$deletedUserData['username']}' with role '{$deletedUserData['role']}'",
            'severity' => 'high',
            'target_type' => 'user',
            'target_id' => $user->id,
            'old_values' => $deletedUserData,
            'metadata' => [
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $deletedUserData['email'],
                'deleted_user_role' => $deletedUserData['role'],
                'was_super_admin' => $user->role === 'super_admin',
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ]
        ]);
    }

    /**
     * Log user ban/unban action
     */
    public static function logUserBanToggle(User $user, string $oldStatus, string $action, array $metadata = []): void
    {
        $isBan = $action === 'user_banned';
        
        AdminActionLog::logUserAction($action, $user, [
            'description' => "Changed user '{$user->username}' status from '{$oldStatus}' to '{$user->status}'",
            'severity' => 'high',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $user->status],
            'metadata' => array_merge([
                'ban_reason' => $metadata['ban_reason'] ?? null,
                'previous_status' => $oldStatus,
                'new_status' => $user->status,
                'is_ban' => $isBan,
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log password reset action
     */
    public static function logPasswordReset(User $user, bool $isOwnPassword = false, array $metadata = []): void
    {
        $action = $isOwnPassword ? 'admin_password_changed' : 'user_password_reset';
        
        AdminActionLog::logSecurityAction($action, $user, [
            'description' => $isOwnPassword 
                ? "Admin changed their own password"
                : "Reset password for user '{$user->username}'",
            'severity' => 'high',
            'is_sensitive' => true,
            'metadata' => array_merge([
                'target_user_id' => $user->id,
                'target_username' => $user->username,
                'is_own_password' => $isOwnPassword,
                'password_length' => $metadata['password_length'] ?? null,
                'email_sent' => $metadata['email_sent'] ?? false,
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log bulk ban action
     */
    public static function logBulkBan(int $count, array $userIds, array $affectedUsers, array $metadata = []): void
    {
        AdminActionLog::logUserAction('bulk_user_ban', null, [
            'description' => "Bulk banned {$count} users",
            'severity' => 'high',
            'metadata' => array_merge([
                'action_type' => 'bulk_ban',
                'affected_count' => $count,
                'user_ids' => $userIds,
                'ban_reason' => $metadata['ban_reason'] ?? null,
                'affected_users' => $affectedUsers,
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log bulk unban action
     */
    public static function logBulkUnban(int $count, array $userIds, array $affectedUsers, array $metadata = []): void
    {
        AdminActionLog::logUserAction('bulk_user_unban', null, [
            'description' => "Bulk unbanned {$count} users",
            'severity' => 'medium',
            'metadata' => array_merge([
                'action_type' => 'bulk_unban',
                'affected_count' => $count,
                'user_ids' => $userIds,
                'affected_users' => $affectedUsers,
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log bulk delete action
     */
    public static function logBulkDelete(int $count, array $userIds, array $deletedUsers, array $metadata = []): void
    {
        AdminActionLog::logUserAction('bulk_user_delete', null, [
            'description' => "Bulk deleted {$count} users",
            'severity' => 'critical',
            'metadata' => array_merge([
                'action_type' => 'bulk_delete',
                'affected_count' => $count,
                'user_ids' => $userIds,
                'deleted_users' => $deletedUsers,
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log bulk role change action
     */
    public static function logBulkRoleChange(int $count, array $userIds, string $newRole, array $roleChanges, array $metadata = []): void
    {
        AdminActionLog::logUserAction('bulk_role_change', null, [
            'description' => "Bulk changed {$count} users' role to '{$newRole}'",
            'severity' => 'high',
            'metadata' => array_merge([
                'action_type' => 'bulk_role_change',
                'affected_count' => $count,
                'user_ids' => $userIds,
                'new_role' => $newRole,
                'role_changes' => $roleChanges,
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log user export action
     */
    public static function logUserExport(string $format, int $totalUsers, array $filters = [], array $metadata = []): void
    {
        AdminActionLog::logSystemAction('user_data_export', [
            'description' => "Exported {$totalUsers} users data in {$format} format",
            'severity' => 'medium',
            'metadata' => array_merge([
                'export_format' => $format,
                'total_users' => $totalUsers,
                'applied_filters' => $filters,
                'file_size' => $metadata['file_size'] ?? null,
                'filename' => $metadata['filename'] ?? null,
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log user search action
     */
    public static function logUserSearch(array $searchParams, int $resultCount, array $metadata = []): void
    {
        // Only log complex searches or searches that return sensitive data
        if (empty($searchParams) || !isset($searchParams['search'])) {
            return;
        }

        AdminActionLog::logSystemAction('user_search', [
            'description' => "Searched users with parameters: " . json_encode($searchParams),
            'severity' => 'low',
            'metadata' => array_merge([
                'search_parameters' => $searchParams,
                'result_count' => $resultCount,
                'search_query' => $searchParams['search'] ?? '',
                'filters_applied' => array_keys(array_filter($searchParams, fn($v) => !empty($v))),
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Log security-related user action
     */
    public static function logSecurityAction(string $action, User $user = null, string $description = '', array $metadata = []): void
    {
        AdminActionLog::logSecurityAction($action, $user, [
            'description' => $description ?: "Security action: {$action}",
            'severity' => 'high',
            'is_sensitive' => true,
            'metadata' => array_merge([
                'security_level' => 'high',
                'requires_review' => true,
                'admin_id' => Auth::id(),
                'admin_username' => Auth::user()?->username,
            ], $metadata)
        ]);
    }

    /**
     * Create comprehensive metadata for user actions
     */
    public static function createMetadata(array $customMetadata = []): array
    {
        return array_merge([
            'timestamp' => now()->toISOString(),
            'admin_id' => Auth::id(),
            'admin_username' => Auth::user()?->username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
        ], $customMetadata);
    }
}