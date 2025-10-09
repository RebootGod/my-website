<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBanHistory;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Http\Requests\Admin\UserPasswordResetRequest;
use App\Http\Requests\Admin\UserBulkActionRequest;
use App\Services\Admin\UserPermissionService;
use App\Services\Admin\UserActionLogger;
use App\Services\Admin\UserBulkOperationService;
use App\Services\Admin\UserStatsService;
use App\Services\Admin\UserExportService;
use App\Mail\BanNotificationMail;
use App\Mail\SuspensionNotificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * UserManagementController - Refactored for clean architecture
 * Uses dedicated services and form requests for better organization
 */
class UserManagementController extends Controller
{
    /**
     * Display a listing of users with search and filtering
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        // Apply search and filters
        $filters = $this->buildFilters($request);
        $query = $this->applyFilters($query, $filters);

        // Apply sorting
        $query = $this->applySorting($query, $request);

        // Paginate results
        $users = $query->paginate(20);

        // Get dashboard statistics
        $stats = UserStatsService::getOverviewStats();

        // Log search if parameters provided
        if ($request->hasAny(['search', 'role', 'status', 'date_from', 'date_to'])) {
            UserActionLogger::logUserSearch($filters, $users->total());
        }

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Show the form for creating a new user - DISABLED FOR SECURITY
     */
    public function create()
    {
        return redirect()->route('admin.users.index')
            ->with('error', 'Direct user creation is disabled. Users must register through the registration page with invite codes.');
    }

    /**
     * Store a newly created user - DISABLED FOR SECURITY  
     */
    public function store(Request $request)
    {
        return redirect()->route('admin.users.index')
            ->with('error', 'Direct user creation is disabled. Users must register through the registration page with invite codes.');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        // Load relationships
        $user->load(['registration.inviteCode']);

        // Get comprehensive user statistics
        $stats = UserStatsService::getUserStats($user);

        // Get permission summary for UI
        $permissions = UserPermissionService::getPermissionSummary($user);

        // Get recent movie views for the user
        $recentViews = $user->movieViews()
            ->with(['movie'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.users.show', compact('user', 'stats', 'permissions', 'recentViews'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        // Check permissions
        if (!UserPermissionService::canEdit($user)) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Access denied. You cannot edit users with equal or higher privileges.');
        }

        // Load relationships
        $user->load('role');
        
        // Get assignable roles for current user
        $availableRoles = UserPermissionService::getAssignableRoles();

        return view('admin.users.edit', compact('user', 'availableRoles'));
    }

    /**
     * Update the specified user
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        // Check permissions
        if (!UserPermissionService::canEdit($user)) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Access denied. You cannot edit users with equal or higher privileges.');
        }

        // Additional role change validation
        if ($request->role !== $user->role) {
            if (!UserPermissionService::canChangeRole($user)) {
                return back()->with('error', 'You cannot change roles for this user.');
            }
            
            if (!UserPermissionService::canAssignRole($request->role)) {
                return back()->with('error', 'You cannot assign this role.');
            }
        }

        // Store old values for logging
        $oldValues = [
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status
        ];

        // Prepare update data
        $updateData = [
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
        ];
        
        // Update status if provided
        if ($request->has('status') && $request->status !== $user->status) {
            $updateData['status'] = $request->status;
        }

        // Update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Update user
        $user->update($updateData);

        // Log the action
        UserActionLogger::logUserUpdate(
            $user, 
            $oldValues, 
            $updateData,
            ['password_changed' => $request->filled('password')]
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deletion of current admin user
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        // Check permissions
        if (!UserPermissionService::canManage($user)) {
            return back()->with('error', 'Access denied. You cannot delete users with equal or higher privileges.');
        }

        // Prevent deletion of the only Super Admin
        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            return back()->with('error', 'Cannot delete the only Super Admin user!');
        }

        // Log action before deletion
        UserActionLogger::logUserDelete($user);

        // Delete user
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    /**
     * Toggle user ban status
     */
    public function toggleBan(Request $request, User $user)
    {
        // Prevent banning current admin user
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot ban yourself!');
        }

        // Check permissions
        if (!UserPermissionService::canBan($user)) {
            return back()->with('error', 'Access denied. You cannot ban users with equal or higher privileges.');
        }

        $oldStatus = $user->status;
        $admin = auth()->user();
        $banReason = $request->input('ban_reason', 'Terms of Service violation');
        
        if ($user->status === 'banned') {
            // UNBAN user
            $user->update(['status' => 'active']);
            $message = 'User unbanned successfully!';
            $action = 'user_unbanned';
            $actionType = 'unban';
        } else {
            // BAN user
            $user->update(['status' => 'banned']);
            $message = 'User banned successfully!';
            $action = 'user_banned';
            $actionType = 'ban';
            
            // Send ban notification email
            try {
                Mail::to($user->email)->queue(
                    new BanNotificationMail($user, $banReason, $admin->username)
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send ban notification email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Save to ban history
        try {
            UserBanHistory::create([
                'user_id' => $user->id,
                'action_type' => $actionType,
                'reason' => $banReason,
                'performed_by' => $admin->id,
                'duration' => null,
                'admin_ip' => $request->ip(),
                'metadata' => [
                    'old_status' => $oldStatus,
                    'new_status' => $user->status,
                    'method' => 'toggle_ban'
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save ban history', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        // Log action (legacy logging)
        UserActionLogger::logUserBanToggle(
            $user, 
            $oldStatus, 
            $action,
            ['ban_reason' => $banReason]
        );

        return back()->with('success', $message);
    }

    /**
     * Reset user password
     */
    public function resetPassword(UserPasswordResetRequest $request, User $user)
    {
        // Check permissions
        if (!UserPermissionService::canResetPassword($user)) {
            return back()->with('error', 'Access denied. You cannot reset passwords for users with equal or higher privileges.');
        }

        $isOwnPassword = $user->id === auth()->id();
        
        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_required' => false,
        ]);

        // Log action
        UserActionLogger::logPasswordReset(
            $user, 
            $isOwnPassword,
            [
                'password_length' => strlen($request->password),
                'email_sent' => $request->boolean('send_email', false),
            ]
        );

        $message = $isOwnPassword 
            ? 'Your password has been updated successfully!'
            : 'User password has been reset successfully!';

        return back()->with('success', $message);
    }

    /**
     * Bulk actions for users
     */
    public function bulkAction(UserBulkActionRequest $request)
    {
        $action = $request->action;
        $userIds = $request->user_ids;

        // Perform bulk action using service
        $result = match($action) {
            'ban' => UserBulkOperationService::bulkBan($userIds, $request->ban_reason),
            'unban' => UserBulkOperationService::bulkUnban($userIds),
            'activate' => UserBulkOperationService::bulkActivate($userIds),
            'suspend' => UserBulkOperationService::bulkSuspend($userIds, $request->ban_reason),
            'delete' => UserBulkOperationService::bulkDelete($userIds),
            'change_role' => UserBulkOperationService::bulkRoleChange($userIds, $request->new_role),
            default => ['success' => false, 'message' => 'Invalid action'],
        };

        if ($result['success']) {
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        // Validate export parameters
        $params = [
            'format' => $request->input('format', 'csv'),
            'columns' => $request->input('columns'),
            'search' => $request->input('search'),
            'role' => $request->input('role'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'limit' => $request->input('limit'),
        ];

        $validation = UserExportService::validateExportParams($params);
        if (!$validation['valid']) {
            return back()->withErrors($validation['errors']);
        }

        // Build filters from request
        $filters = array_filter([
            'search' => $request->search,
            'role' => $request->role,
            'status' => $request->status,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'limit' => $request->limit,
        ]);

        // Perform export
        $result = match($params['format']) {
            'csv' => UserExportService::exportToCsv($filters, $params['columns']),
            'json' => UserExportService::exportToJson($filters, $params['columns']),
            'excel' => UserExportService::exportToExcel($filters, $params['columns']),
            'pdf' => UserExportService::exportToPdf($filters, $params['columns']),
            default => ['success' => false, 'message' => 'Invalid export format'],
        };

        if ($result['success']) {
            return response()->download(storage_path('app/public/' . $result['filepath']));
        } else {
            return back()->with('error', $result['message']);
        }
    }

    /**
     * Generate a random password
     */
    public function generatePassword()
    {
        $password = Str::random(12);
        return response()->json(['password' => $password]);
    }

    /**
     * Get user statistics for dashboard
     */
    public function getStats()
    {
        $stats = UserStatsService::getDashboardStats();
        return response()->json($stats);
    }

    /**
     * Get bulk operation statistics
     */
    public function getBulkStats()
    {
        $stats = UserBulkOperationService::getBulkOperationStats();
        return response()->json($stats);
    }

    /**
     * Get available bulk actions
     */
    public function getAvailableBulkActions()
    {
        $actions = UserBulkOperationService::getAvailableActions();
        return response()->json($actions);
    }

    /**
     * Get export statistics
     */
    public function getExportStats()
    {
        $stats = UserExportService::getExportStats();
        $formats = UserExportService::getSupportedFormats();
        $columns = UserExportService::getAvailableColumns();
        
        return response()->json([
            'stats' => $stats,
            'formats' => $formats,
            'columns' => $columns,
        ]);
    }

    /**
     * Build filters array from request
     */
    private function buildFilters(Request $request): array
    {
        return array_filter([
            'search' => $request->search,
            'role' => $request->role,
            'status' => $request->status,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters)
    {
        // Search functionality
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by registration date
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        $allowedSorts = ['username', 'email', 'name', 'role', 'status', 'created_at', 'last_login_at'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }
}