<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserActivity;
use App\Models\User;
use App\Services\UserActivityService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserActivityController extends Controller
{
    protected $activityService;

    public function __construct(UserActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display user activity dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 7);
        $activityType = $request->get('activity_type');
        $userId = $request->get('user_id');

        // Get activity statistics
        $stats = $this->activityService->getActivityStats($period);

        // Get recent activities with filters
        $activitiesQuery = UserActivity::with('user')
            ->orderBy('activity_at', 'desc');

        if ($activityType) {
            $activitiesQuery->where('activity_type', $activityType);
        }

        if ($userId) {
            $activitiesQuery->where('user_id', $userId);
        }

        $activities = $activitiesQuery->paginate(50);

        // Get available activity types for filter
        $activityTypes = UserActivity::select('activity_type')
            ->distinct()
            ->orderBy('activity_type')
            ->pluck('activity_type');

        // Get active users for filter
        $users = User::select('id', 'username')
            ->whereHas('activities')
            ->orderBy('username')
            ->get();

        // Get popular content
        $popularContent = $this->activityService->getPopularContent($period);

        return view('admin.user-activity.index', compact(
            'stats',
            'activities',
            'activityTypes',
            'users',
            'popularContent',
            'period',
            'activityType',
            'userId'
        ));
    }

    /**
     * Show detailed activity for a specific user
     */
    public function show(Request $request, User $user)
    {
        $period = $request->get('period', 30);

        // Get user activity summary
        $summary = $this->activityService->getUserActivitySummary($user->id, $period);

        // Get user activities with pagination
        $activities = UserActivity::where('user_id', $user->id)
            ->orderBy('activity_at', 'desc')
            ->paginate(25);

        return view('admin.user-activity.show', compact(
            'user',
            'summary',
            'activities',
            'period'
        ));
    }

    /**
     * Get activity statistics API endpoint
     */
    public function getStats(Request $request)
    {
        $period = $request->get('period', 30);
        $stats = $this->activityService->getActivityStats($period);

        return response()->json($stats);
    }

    /**
     * Export activity data
     */
    public function export(Request $request)
    {
        $period = $request->get('period', 30);
        $activityType = $request->get('activity_type');
        $userId = $request->get('user_id');

        $query = UserActivity::with('user')
            ->where('activity_at', '>=', Carbon::now()->subDays($period))
            ->orderBy('activity_at', 'desc');

        if ($activityType) {
            $query->where('activity_type', $activityType);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $activities = $query->get();

        $csvData = [];
        $csvData[] = ['Date/Time', 'User', 'Activity Type', 'Description', 'IP Address'];

        foreach ($activities as $activity) {
            $csvData[] = [
                $activity->activity_at->format('Y-m-d H:i:s'),
                $activity->user ? $activity->user->username : 'Unknown',
                $activity->activity_type,
                $activity->description,
                $activity->ip_address,
            ];
        }

        $filename = 'user_activities_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clear activity statistics cache
     */
    public function clearCache()
    {
        $this->activityService->clearStatsCache();

        return response()->json([
            'success' => true,
            'message' => 'Activity cache cleared successfully'
        ]);
    }

    /**
     * Clean up old activities
     */
    public function cleanup(Request $request)
    {
        $olderThanDays = $request->get('older_than_days', 365);
        $deletedCount = $this->activityService->cleanupOldActivities($olderThanDays);

        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$deletedCount} old activity records",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Cleanup old activities (keep last 7 days, backup and delete older)
     * Only accessible by super_admin
     */
    public function cleanupOldActivities()
    {
        // Check if user is super_admin
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only super admin can perform this action.'
            ], 403);
        }

        try {
            // Get count of records that will be deleted
            $cutoffDate = Carbon::now()->subDays(7);
            $oldRecordsCount = UserActivity::where('created_at', '<', $cutoffDate)->count();

            if ($oldRecordsCount === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No old records to clean up.',
                    'records_to_delete' => 0,
                    'records_deleted' => 0,
                    'backup_file' => null
                ]);
            }

            // Get old records for backup
            $oldRecords = UserActivity::where('created_at', '<', $cutoffDate)
                ->with('user:id,username,email')
                ->orderBy('created_at', 'asc')
                ->get();

            // Create backup file
            $backupDir = storage_path('app/backups/user_activities');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filename = 'user_activities_backup_' . now()->format('Y_m_d_H_i_s') . '.json';
            $filepath = $backupDir . '/' . $filename;

            // Prepare backup data
            $backupData = [
                'backup_date' => now()->toIso8601String(),
                'cutoff_date' => $cutoffDate->toIso8601String(),
                'total_records' => $oldRecordsCount,
                'performed_by' => [
                    'id' => auth()->id(),
                    'username' => auth()->user()->username,
                    'email' => auth()->user()->email
                ],
                'records' => $oldRecords->map(function($activity) {
                    return [
                        'id' => $activity->id,
                        'user_id' => $activity->user_id,
                        'username' => $activity->user->username ?? 'Unknown',
                        'activity_type' => $activity->activity_type,
                        'description' => $activity->description,
                        'metadata' => $activity->metadata,
                        'ip_address' => $activity->ip_address,
                        'user_agent' => $activity->user_agent,
                        'activity_at' => $activity->activity_at,
                        'created_at' => $activity->created_at,
                    ];
                })
            ];

            // Save backup file
            file_put_contents($filepath, json_encode($backupData, JSON_PRETTY_PRINT));

            // Delete old records
            $deletedCount = UserActivity::where('created_at', '<', $cutoffDate)->delete();

            // Log to admin action logs
            \App\Models\AdminActionLog::create([
                'user_id' => auth()->id(),
                'action' => 'cleanup_user_activities',
                'description' => "Cleaned up {$deletedCount} user activity records older than 7 days",
                'model_type' => 'UserActivity',
                'model_id' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'changes' => [
                    'records_deleted' => $deletedCount,
                    'cutoff_date' => $cutoffDate->toIso8601String(),
                    'backup_file' => $filename
                ]
            ]);

            // Full logging
            \Log::info('User Activity Cleanup Performed', [
                'admin_id' => auth()->id(),
                'admin_username' => auth()->user()->username,
                'records_deleted' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'backup_file' => $filename,
                'backup_path' => $filepath,
                'ip_address' => request()->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$deletedCount} old activity records. Backup saved.",
                'records_to_delete' => $oldRecordsCount,
                'records_deleted' => $deletedCount,
                'backup_file' => $filename
            ]);

        } catch (\Exception $e) {
            \Log::error('User Activity Cleanup Failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup activities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get count of old activities (for preview before cleanup)
     */
    public function getOldActivitiesCount()
    {
        // Check if user is super_admin
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $cutoffDate = Carbon::now()->subDays(7);
        $count = UserActivity::where('created_at', '<', $cutoffDate)->count();
        $oldestDate = UserActivity::where('created_at', '<', $cutoffDate)
            ->orderBy('created_at', 'asc')
            ->value('created_at');

        return response()->json([
            'success' => true,
            'count' => $count,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'oldest_date' => $oldestDate ? Carbon::parse($oldestDate)->toDateTimeString() : null
        ]);
    }
}
