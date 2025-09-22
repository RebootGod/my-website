<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;

class DashboardController extends Controller
{
    protected $statsService;

    public function __construct(AdminStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Display the admin dashboard with optimized statistics
     */
    public function index()
    {
        $stats = $this->statsService->getDashboardStats();
        $contentGrowth = $this->statsService->getContentGrowthStats(30);
        $userActivity = $this->statsService->getUserActivityStats();
        $topContent = $this->statsService->getTopPerformingContent(5);
        $recentActivity = $this->statsService->getRecentActivity(10);

        return view('admin.dashboard', compact(
            'stats',
            'contentGrowth',
            'userActivity',
            'topContent',
            'recentActivity'
        ));
    }

    /**
     * Refresh dashboard statistics (clear cache)
     */
    public function refreshStats()
    {
        $stats = $this->statsService->refreshStats();

        return response()->json([
            'success' => true,
            'message' => 'Statistics refreshed successfully',
            'stats' => $stats
        ]);
    }
}