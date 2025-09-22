<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * UserPermissionService - Centralized user permission and authorization logic
 * Handles all permission checks for user management operations
 */
class UserPermissionService
{
    /**
     * Check if current user can edit the specified user
     */
    public static function canEdit(User $targetUser): bool
    {
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return false;
        }

        // Users cannot edit themselves through admin panel (use profile page instead)
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Check hierarchy level
        $currentLevel = self::getHierarchyLevel($currentUser);
        $targetLevel = self::getHierarchyLevel($targetUser);

        // Can only edit users with lower hierarchy level
        return $currentLevel > $targetLevel;
    }

    /**
     * Check if current user can ban/unban the specified user
     */
    public static function canBan(User $targetUser): bool
    {
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return false;
        }

        // Cannot ban yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Check hierarchy level
        $currentLevel = self::getHierarchyLevel($currentUser);
        $targetLevel = self::getHierarchyLevel($targetUser);

        // Can only ban users with lower hierarchy level
        return $currentLevel > $targetLevel;
    }

    /**
     * Check if current user can manage (delete/bulk actions) the specified user
     */
    public static function canManage(User $targetUser): bool
    {
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return false;
        }

        // Cannot manage yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Only admins and super admins can manage users
        if (!in_array($currentUser->role, ['admin', 'super_admin'])) {
            return false;
        }

        // Check hierarchy level
        $currentLevel = self::getHierarchyLevel($currentUser);
        $targetLevel = self::getHierarchyLevel($targetUser);

        // Can only manage users with lower hierarchy level
        return $currentLevel > $targetLevel;
    }

    /**
     * Check if current user can reset password for the specified user
     */
    public static function canResetPassword(User $targetUser): bool
    {
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return false;
        }

        // Can reset own password
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Check hierarchy level for other users
        $currentLevel = self::getHierarchyLevel($currentUser);
        $targetLevel = self::getHierarchyLevel($targetUser);

        // Can only reset passwords for users with lower hierarchy level
        return $currentLevel > $targetLevel;
    }

    /**
     * Check if current user can change role for the specified user
     */
    public static function canChangeRole(User $targetUser): bool
    {
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return false;
        }

        // Cannot change own role
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Only admins and super admins can change roles
        if (!in_array($currentUser->role, ['admin', 'super_admin'])) {
            return false;
        }

        // Check hierarchy level
        $currentLevel = self::getHierarchyLevel($currentUser);
        $targetLevel = self::getHierarchyLevel($targetUser);

        // Can only change roles for users with lower hierarchy level
        return $currentLevel > $targetLevel;
    }

    /**
     * Check if current user can assign the specified role
     */
    public static function canAssignRole(string $role): bool
    {
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return false;
        }

        $currentLevel = self::getHierarchyLevel($currentUser);
        $roleLevel = self::getRoleHierarchyLevel($role);

        // Cannot assign a role equal to or higher than current user's level
        return $currentLevel > $roleLevel;
    }

    /**
     * Check if current user can perform bulk actions
     */
    public static function canPerformBulkActions(): bool
    {
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return false;
        }

        // Only admins and super admins can perform bulk actions
        return in_array($currentUser->role, ['admin', 'super_admin']);
    }

    /**
     * Check if current user can delete all selected users (bulk delete validation)
     */
    public static function canBulkDelete(array $userIds): array
    {
        $currentUser = Auth::user();
        $errors = [];
        
        if (!$currentUser) {
            return ['error' => 'Authentication required'];
        }

        // Remove current user from the list
        $userIds = array_filter($userIds, fn($id) => $id != $currentUser->id);

        if (empty($userIds)) {
            return ['error' => 'No valid users selected for deletion'];
        }

        // Check if trying to delete all admins
        $adminCount = User::where('role', 'admin')->count();
        $adminsToDelete = User::whereIn('id', $userIds)->where('role', 'admin')->count();
        
        if ($adminCount - $adminsToDelete < 1) {
            $errors[] = 'Cannot delete all admin users. At least one admin must remain.';
        }

        // Check permissions for each user
        $unauthorizedUsers = [];
        $users = User::whereIn('id', $userIds)->get();
        
        foreach ($users as $user) {
            if (!self::canManage($user)) {
                $unauthorizedUsers[] = $user->username;
            }
        }

        if (!empty($unauthorizedUsers)) {
            $errors[] = 'Cannot delete users: ' . implode(', ', $unauthorizedUsers) . ' (insufficient permissions)';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'valid_user_ids' => $userIds,
            'unauthorized_users' => $unauthorizedUsers
        ];
    }

    /**
     * Get hierarchy level for a user
     */
    public static function getHierarchyLevel(User $user): int
    {
        return match($user->role) {
            'super_admin' => 100,
            'admin' => 80,
            'moderator' => 60,
            'user' => 0,
            default => 0
        };
    }

    /**
     * Get hierarchy level for a role
     */
    public static function getRoleHierarchyLevel(string $role): int
    {
        return match($role) {
            'super_admin' => 100,
            'admin' => 80,
            'moderator' => 60,
            'user' => 0,
            default => 0
        };
    }

    /**
     * Get available roles that current user can assign
     */
    public static function getAssignableRoles(): array
    {
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return [];
        }

        $allRoles = ['user', 'moderator', 'admin', 'super_admin'];
        $assignableRoles = [];

        foreach ($allRoles as $role) {
            if (self::canAssignRole($role)) {
                $assignableRoles[] = $role;
            }
        }

        return $assignableRoles;
    }

    /**
     * Check if current user is super admin
     */
    public static function isSuperAdmin(): bool
    {
        $currentUser = Auth::user();
        return $currentUser && $currentUser->role === 'super_admin';
    }

    /**
     * Check if current user is admin or higher
     */
    public static function isAdminOrHigher(): bool
    {
        $currentUser = Auth::user();
        return $currentUser && in_array($currentUser->role, ['admin', 'super_admin']);
    }

    /**
     * Get permission summary for a user
     */
    public static function getPermissionSummary(User $targetUser): array
    {
        return [
            'can_edit' => self::canEdit($targetUser),
            'can_ban' => self::canBan($targetUser),
            'can_manage' => self::canManage($targetUser),
            'can_reset_password' => self::canResetPassword($targetUser),
            'can_change_role' => self::canChangeRole($targetUser),
            'hierarchy_level' => self::getHierarchyLevel($targetUser),
        ];
    }
}