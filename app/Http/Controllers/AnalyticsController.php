<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin'); // Assuming admin middleware exists
    }

    /**
     * Main analytics dashboard
     */
    public function index()
    {
        $analytics = AnalyticsService::getAnalyticsData();
        return view('admin.analytics.index', compact('analytics'));
    }

    /**
     * Get comprehensive analytics data
     */
    // ...existing code...

    /**
     * Get overview statistics
     */
    // ...existing code...

    /**
     * Get data for charts
     */
    // ...existing code...

    /**
     * Get daily views for chart (last 30 days)
     */
    // ...existing code...

    /**
     * Get daily registrations for chart (last 30 days)
     */
    // ...existing code...

    /**
     * Get genre popularity chart data
     */
    // ...existing code...

    /**
     * Get device/browser statistics (if tracking is available)
     */
    // ...existing code...

    /**
     * Get top content statistics
     */
    // ...existing code...

    /**
     * Get user analytics
     */
    // ...existing code...

    /**
     * Get performance statistics
     */
    // ...existing code...

    /**
     * Calculate growth percentage
     */
    // ...existing code...

    /**
     * Calculate user retention rate
     */
    // ...existing code...

    /**
     * Get peak viewing hour
     */
    // ...existing code...

    /**
     * Calculate bounce rate (users who view only one movie)
     */
    // ...existing code...

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'json');
        $analytics = AnalyticsService::getAnalyticsData();
        switch ($format) {
            case 'csv':
                return response()->json(['message' => 'CSV export not implemented yet']);
            case 'pdf':
                return response()->json(['message' => 'PDF export not implemented yet']);
            default:
                return response()->json($analytics);
        }
    }

    /**
     * Get real-time statistics for AJAX updates
     */
    public function realtime()
    {
        return response()->json([
            'current_viewers' => AnalyticsService::getCurrentViewers(),
            'today_views' => \App\Models\MovieView::whereDate('created_at', \Carbon\Carbon::today())->count(),
            'online_users' => AnalyticsService::getOnlineUsers(),
            'latest_registrations' => \App\Models\User::latest()->limit(5)->get(['id', 'username', 'email', 'created_at']),
        ]);
    }

    /**
     * Get current viewers (simplified - would need session tracking)
     */
    private function getCurrentViewers()
    {
        // This would require more sophisticated session tracking
        // For now, return estimated based on recent activity
        return MovieView::where('created_at', '>=', Carbon::now()->subMinutes(5))->count();
    }

    /**
     * Get online users count
     */
    private function getOnlineUsers()
    {
        // This would require session tracking or last_activity column
        return User::where('updated_at', '>=', Carbon::now()->subMinutes(15))->count();
    }

    /**
     * Export to CSV
     */
    private function exportCsv($analytics)
    {
        // Implementation for CSV export
        return response()->json(['message' => 'CSV export not implemented yet']);
    }

    /**
     * Export to PDF
     */
    private function exportPdf($analytics)
    {
        // Implementation for PDF export
        return response()->json(['message' => 'PDF export not implemented yet']);
    }
}