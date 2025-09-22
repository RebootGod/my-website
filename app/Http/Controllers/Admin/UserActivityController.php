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
}
