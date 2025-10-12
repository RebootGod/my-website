<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\User;
use App\Models\Genre;
use App\Models\InviteCode;
use App\Models\BrokenLinkReport;
use App\Models\Series;
use App\Services\AdminStatsService;
use Carbon\Carbon;

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

    /**
     * Display analytics page
     */
    public function analytics()
    {
        // Views analytics
        $totalViews = Movie::sum('view_count');
        $viewsThisMonth = MovieView::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $viewsLastMonth = MovieView::whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();

        // Calculate growth percentage
        $viewsGrowth = $viewsLastMonth > 0 
            ? (($viewsThisMonth - $viewsLastMonth) / $viewsLastMonth) * 100 
            : 0;

        // Top genres by views
        $topGenres = Genre::select('genres.*', DB::raw('SUM(movies.view_count) as total_views'))
            ->join('movie_genres', 'genres.id', '=', 'movie_genres.genre_id')
            ->join('movies', 'movie_genres.movie_id', '=', 'movies.id')
            ->groupBy('genres.id', 'genres.name', 'genres.slug', 'genres.created_at', 'genres.updated_at')
            ->orderBy('total_views', 'desc')
            ->limit(10)
            ->get();

        // Daily views for the last 30 days
        $dailyViews = MovieView::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as views')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'), 'asc')
            ->get();

        // User registration analytics
        $totalUsers = User::count();
        $usersThisMonth = User::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $usersLastMonth = User::whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();

        $usersGrowth = $usersLastMonth > 0 
            ? (($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100 
            : 0;

        // Search analytics
        $totalSearches = SearchHistory::count();
        $searchesThisMonth = SearchHistory::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        
        $topSearchTerms = SearchHistory::select('search_term', DB::raw('count(*) as search_count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('search_term')
            ->orderBy('search_count', 'desc')
            ->limit(15)
            ->get();

        return view('admin.analytics', compact(
            'totalViews',
            'viewsThisMonth',
            'viewsGrowth',
            'topGenres',
            'dailyViews',
            'totalUsers',
            'usersThisMonth',
            'usersGrowth',
            'totalSearches',
            'searchesThisMonth',
            'topSearchTerms'
        ));
    }

    /**
     * Get dashboard data for AJAX requests
     */
    public function getDashboardData(Request $request)
    {
        $type = $request->get('type', 'overview');

        switch ($type) {
            case 'movies':
                return response()->json([
                    'total' => Movie::count(),
                    'published' => Movie::where('status', 'published')->count(),
                    'draft' => Movie::where('status', 'draft')->count(),
                    'recent' => Movie::where('created_at', '>=', Carbon::now()->subDays(7))->count()
                ]);

            case 'users':
                return response()->json([
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'new_this_month' => User::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
                    'admin' => User::where('role', 'admin')->count()
                ]);

            case 'views':
                return response()->json([
                    'total' => Movie::sum('view_count'),
                    'today' => MovieView::whereDate('created_at', Carbon::today())->count(),
                    'this_week' => MovieView::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
                    'this_month' => MovieView::where('created_at', '>=', Carbon::now()->startOfMonth())->count()
                ]);

            default:
                return response()->json([
                    'movies' => Movie::count(),
                    'users' => User::count(),
                    'views' => Movie::sum('view_count'),
                    'genres' => Genre::count()
                ]);
        }
    }

    /**
     * System information
     */
    public function systemInfo()
    {
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_size' => $this->getDatabaseSize(),
            'storage_used' => $this->getStorageUsed(),
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];

        return view('admin.system-info', compact('systemInfo'));
    }

    /**
     * Get database size (approximate)
     */
    private function getDatabaseSize()
    {
        try {
            $result = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [config('database.connections.mysql.database')]);

            return $result[0]->size_mb . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get storage used (approximate)
     */
    private function getStorageUsed()
    {
        try {
            $storagePath = storage_path();
            $size = $this->getFolderSize($storagePath);
            return $this->formatBytes($size);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get folder size recursively
     */
    private function getFolderSize($dir)
    {
        $size = 0;
        if (is_dir($dir)) {
            foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
                $size += is_file($each) ? filesize($each) : $this->getFolderSize($each);
            }
        }
        return $size;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}