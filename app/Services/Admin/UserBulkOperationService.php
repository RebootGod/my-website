<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\UserBanHistory;
use App\Services\Admin\UserPermissionService;
use App\Services\Admin\UserActionLogger;
use App\Mail\BanNotificationMail;
use App\Mail\SuspensionNotificationMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * UserBulkOperationService - Handles all bulk user operations
 * Centralized service for bulk ban, unban, delete, and role change operations
 */
class UserBulkOperationService
{
    /**
     * Perform bulk ban operation
     */
    public static function bulkBan(array $userIds, string $banReason = null): array
    {
        try {
            DB::beginTransaction();

            // Validate permissions and get valid users
            $validation = self::validateBulkOperation($userIds, 'ban');
            if (!$validation['success']) {
                return $validation;
            }

            $users = User::whereIn('id', $validation['valid_user_ids']);
            $affectedUsers = $users->get(['id', 'username', 'email', 'role', 'status']);
            
            // Perform bulk ban
            $count = $users->update(['status' => 'banned']);
            
            $admin = auth()->user();
            $adminName = $admin->username ?? 'System Admin';
            $reason = $banReason ?? 'Terms of Service violation';

            // Send email notifications and save history for each user
            foreach ($affectedUsers as $user) {
                // Send ban notification email
                try {
                    Mail::to($user->email)->queue(
                        new BanNotificationMail($user, $reason, $adminName)
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send ban notification email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Save ban history
                try {
                    UserBanHistory::create([
                        'user_id' => $user->id,
                        'action_type' => 'ban',
                        'reason' => $reason,
                        'performed_by' => $admin->id,
                        'duration' => null,
                        'admin_ip' => request()->ip(),
                        'metadata' => [
                            'old_status' => $user->status,
                            'new_status' => 'banned',
                            'method' => 'bulk_ban'
                        ]
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to save ban history', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log the action (legacy logging)
            UserActionLogger::logBulkBan(
                $count, 
                $validation['valid_user_ids'], 
                $affectedUsers->pluck('username')->toArray(),
                ['ban_reason' => $reason]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => "{$count} users banned successfully!",
                'affected_count' => $count,
                'affected_users' => $affectedUsers->toArray(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to ban users: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform bulk unban operation
     */
    public static function bulkUnban(array $userIds): array
    {
        try {
            DB::beginTransaction();

            // Validate permissions and get valid users
            $validation = self::validateBulkOperation($userIds, 'unban');
            if (!$validation['success']) {
                return $validation;
            }

            $users = User::whereIn('id', $validation['valid_user_ids']);
            $affectedUsers = $users->get(['id', 'username', 'email', 'role', 'status']);
            
            // Perform bulk unban
            $count = $users->update(['status' => 'active']);
            
            $admin = auth()->user();

            // Save unban history for each user
            foreach ($affectedUsers as $user) {
                try {
                    UserBanHistory::create([
                        'user_id' => $user->id,
                        'action_type' => 'unban',
                        'reason' => 'Account reactivated by administrator',
                        'performed_by' => $admin->id,
                        'duration' => null,
                        'admin_ip' => request()->ip(),
                        'metadata' => [
                            'old_status' => $user->status,
                            'new_status' => 'active',
                            'method' => 'bulk_unban'
                        ]
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to save unban history', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log the action (legacy logging)
            UserActionLogger::logBulkUnban(
                $count, 
                $validation['valid_user_ids'], 
                $affectedUsers->pluck('username')->toArray()
            );

            DB::commit();

            return [
                'success' => true,
                'message' => "{$count} users unbanned successfully!",
                'affected_count' => $count,
                'affected_users' => $affectedUsers->toArray(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to unban users: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform bulk delete operation
     */
    public static function bulkDelete(array $userIds): array
    {
        try {
            DB::beginTransaction();

            // Special validation for delete operation
            $validation = UserPermissionService::canBulkDelete($userIds);
            if (!$validation['success']) {
                return [
                    'success' => false,
                    'message' => implode(' ', $validation['errors']),
                    'errors' => $validation['errors'],
                ];
            }

            $users = User::whereIn('id', $validation['valid_user_ids']);
            $affectedUsers = $users->get(['id', 'username', 'email', 'role', 'status']);
            $count = $affectedUsers->count();
            
            // Store user data for logging before deletion
            $deletedUsersData = $affectedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role
                ];
            })->toArray();

            // Log the action before deletion
            UserActionLogger::logBulkDelete(
                $count, 
                $validation['valid_user_ids'], 
                $deletedUsersData
            );

            // Perform bulk delete
            $users->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => "{$count} users deleted successfully!",
                'affected_count' => $count,
                'deleted_users' => $deletedUsersData,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to delete users: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform bulk role change operation
     */
    public static function bulkRoleChange(array $userIds, string $newRole): array
    {
        try {
            DB::beginTransaction();

            // Validate permissions and role
            $validation = self::validateBulkOperation($userIds, 'change_role');
            if (!$validation['success']) {
                return $validation;
            }

            // Check if current user can assign this role
            if (!UserPermissionService::canAssignRole($newRole)) {
                return [
                    'success' => false,
                    'message' => 'You cannot assign this role.',
                ];
            }

            $users = User::whereIn('id', $validation['valid_user_ids']);
            $affectedUsers = $users->get(['id', 'username', 'email', 'role', 'status']);
            
            // Store old roles for logging
            $roleChanges = $affectedUsers->map(function($user) use ($newRole) {
                return [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'old_role' => $user->role,
                    'new_role' => $newRole
                ];
            })->toArray();
            
            // Perform bulk role change
            $count = $users->update(['role' => $newRole]);

            // Log the action
            UserActionLogger::logBulkRoleChange(
                $count, 
                $validation['valid_user_ids'], 
                $newRole,
                $roleChanges
            );

            DB::commit();

            return [
                'success' => true,
                'message' => "{$count} users' role changed to {$newRole}!",
                'affected_count' => $count,
                'role_changes' => $roleChanges,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to change user roles: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform bulk activation operation
     */
    public static function bulkActivate(array $userIds): array
    {
        try {
            DB::beginTransaction();

            // Validate permissions and get valid users
            $validation = self::validateBulkOperation($userIds, 'activate');
            if (!$validation['success']) {
                return $validation;
            }

            $users = User::whereIn('id', $validation['valid_user_ids']);
            $affectedUsers = $users->get(['id', 'username', 'email', 'role', 'status']);
            
            // Perform bulk activation
            $count = $users->update(['status' => 'active']);
            
            $admin = auth()->user();

            // Save activation history for each user
            foreach ($affectedUsers as $user) {
                try {
                    UserBanHistory::create([
                        'user_id' => $user->id,
                        'action_type' => 'activate',
                        'reason' => 'Account reactivated by administrator',
                        'performed_by' => $admin->id,
                        'duration' => null,
                        'admin_ip' => request()->ip(),
                        'metadata' => [
                            'old_status' => $user->status,
                            'new_status' => 'active',
                            'method' => 'bulk_activate'
                        ]
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to save activation history', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log the action (legacy logging)
            UserActionLogger::logBulkUnban(
                $count, 
                $validation['valid_user_ids'], 
                $affectedUsers->pluck('username')->toArray(),
                ['action_type' => 'bulk_activate']
            );

            DB::commit();

            return [
                'success' => true,
                'message' => "{$count} users activated successfully!",
                'affected_count' => $count,
                'affected_users' => $affectedUsers->toArray(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to activate users: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform bulk suspension operation
     */
    public static function bulkSuspend(array $userIds, string $suspendReason = null): array
    {
        try {
            DB::beginTransaction();

            // Validate permissions and get valid users
            $validation = self::validateBulkOperation($userIds, 'suspend');
            if (!$validation['success']) {
                return $validation;
            }

            $users = User::whereIn('id', $validation['valid_user_ids']);
            $affectedUsers = $users->get(['id', 'username', 'email', 'role', 'status']);
            
            // Perform bulk suspension
            $count = $users->update(['status' => 'suspended']);
            
            $admin = auth()->user();
            $adminName = $admin->username ?? 'System Admin';
            $reason = $suspendReason ?? 'Community Guidelines violation';
            $duration = null; // Can be extended to include duration parameter

            // Send email notifications and save history for each user
            foreach ($affectedUsers as $user) {
                // Send suspension notification email
                try {
                    Mail::to($user->email)->queue(
                        new SuspensionNotificationMail($user, $reason, $adminName, $duration)
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send suspension notification email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Save suspension history
                try {
                    UserBanHistory::create([
                        'user_id' => $user->id,
                        'action_type' => 'suspend',
                        'reason' => $reason,
                        'performed_by' => $admin->id,
                        'duration' => $duration,
                        'admin_ip' => request()->ip(),
                        'metadata' => [
                            'old_status' => $user->status,
                            'new_status' => 'suspended',
                            'method' => 'bulk_suspend'
                        ]
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to save suspension history', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log the action (legacy logging)
            UserActionLogger::logBulkBan(
                $count, 
                $validation['valid_user_ids'], 
                $affectedUsers->pluck('username')->toArray(),
                [
                    'action_type' => 'bulk_suspend',
                    'suspend_reason' => $reason
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => "{$count} users suspended successfully!",
                'affected_count' => $count,
                'affected_users' => $affectedUsers->toArray(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to suspend users: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available bulk actions for current user
     */
    public static function getAvailableActions(): array
    {
        $actions = [];

        if (UserPermissionService::canPerformBulkActions()) {
            $actions = [
                'ban' => [
                    'label' => 'Ban Users',
                    'icon' => 'ban',
                    'class' => 'btn-danger',
                    'requires_confirmation' => true,
                    'requires_reason' => true,
                ],
                'unban' => [
                    'label' => 'Unban Users', 
                    'icon' => 'check',
                    'class' => 'btn-success',
                    'requires_confirmation' => false,
                    'requires_reason' => false,
                ],
                'activate' => [
                    'label' => 'Activate Users',
                    'icon' => 'play',
                    'class' => 'btn-primary',
                    'requires_confirmation' => false,
                    'requires_reason' => false,
                ],
                'suspend' => [
                    'label' => 'Suspend Users',
                    'icon' => 'pause',
                    'class' => 'btn-warning',
                    'requires_confirmation' => true,
                    'requires_reason' => true,
                ],
                'change_role' => [
                    'label' => 'Change Role',
                    'icon' => 'user-tag',
                    'class' => 'btn-info',
                    'requires_confirmation' => true,
                    'requires_reason' => false,
                    'requires_selection' => true,
                    'options' => UserPermissionService::getAssignableRoles(),
                ],
                'delete' => [
                    'label' => 'Delete Users',
                    'icon' => 'trash',
                    'class' => 'btn-danger',
                    'requires_confirmation' => true,
                    'requires_reason' => false,
                    'danger_level' => 'critical',
                ],
            ];
        }

        return $actions;
    }

    /**
     * Validate bulk operation
     */
    private static function validateBulkOperation(array $userIds, string $operation): array
    {
        // Check if user can perform bulk actions
        if (!UserPermissionService::canPerformBulkActions()) {
            return [
                'success' => false,
                'message' => 'Access denied. You cannot perform bulk operations.',
            ];
        }

        // Remove current user from the list
        $currentUserId = auth()->id();
        $validUserIds = array_filter($userIds, fn($id) => $id != $currentUserId);

        if (empty($validUserIds)) {
            return [
                'success' => false,
                'message' => 'No valid users selected for bulk operation.',
            ];
        }

        // Check permissions for each user
        $unauthorizedUsers = [];
        $users = User::whereIn('id', $validUserIds)->get();
        
        foreach ($users as $user) {
            if (!UserPermissionService::canManage($user)) {
                $unauthorizedUsers[] = $user->username;
            }
        }

        if (!empty($unauthorizedUsers)) {
            return [
                'success' => false,
                'message' => 'Cannot perform operation on users: ' . implode(', ', $unauthorizedUsers) . ' (insufficient permissions)',
                'unauthorized_users' => $unauthorizedUsers,
            ];
        }

        return [
            'success' => true,
            'valid_user_ids' => $validUserIds,
            'valid_users_count' => count($validUserIds),
        ];
    }

    /**
     * Get bulk operation statistics
     */
    public static function getBulkOperationStats(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'banned_users' => User::where('status', 'banned')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'member_users' => User::where('role', 'member')->count(),
            'can_perform_bulk' => UserPermissionService::canPerformBulkActions(),
            'available_actions' => array_keys(self::getAvailableActions()),
        ];
    }
}