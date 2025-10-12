<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;

/**
 * AdminController - Handles main admin dashboard only
 * Each feature has its own dedicated controller for better organization
 */
class AdminController extends Controller
{
    protected $statsService;

    public function __construct(AdminStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Display the admin dashboard with overview statistics
     */
    public function dashboard()
    {
        // Use dashboard-v2 view
        return view('admin.dashboard-v2');
    }
}