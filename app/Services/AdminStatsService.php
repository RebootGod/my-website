<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\User;
use App\Models\InviteCode;
use App\Models\BrokenLinkReport;
use App\Models\Series;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * ========================================
 * ADMIN STATISTICS SERVICE
 * Optimized statistics calculations for admin dashboard
 * ========================================
 */
class AdminStatsService
{
    /**
     * Cache duration for stats (30 minutes)
     */
    const CACHE_DURATION = 1800;

    /**
     * Get dashboard statistics with caching
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('admin:dashboard_stats', self::CACHE_DURATION, function () {
            return $this->calculateDashboardStats();
        });
    }

    /**
     * Calculate dashboard statistics using optimized queries
     *
     * @return array
     */
    private function calculateDashboardStats(): array
    {
        // Single query for basic counts
        $basicStats = DB::select("
            SELECT
                (SELECT COUNT(*) FROM movies) as total_movies,
                (SELECT COUNT(*) FROM series) as total_series,
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM invite_codes) as total_invite_codes,
                (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
                (SELECT COUNT(*) FROM broken_link_reports WHERE status = 'pending') as pending_reports,
                (SELECT COUNT(*) FROM movies WHERE status = 'published') as published_movies,
                (SELECT COUNT(*) FROM movies WHERE status = 'draft') as draft_movies
        ")[0];

        // Convert stdClass to array
        $stats = (array) $basicStats;

        // Add calculated stats
        $stats['total_content'] = $stats['total_movies'] + $stats['total_series'];
        $stats['publish_rate'] = $stats['total_movies'] > 0
            ? round(($stats['published_movies'] / $stats['total_movies']) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get content growth statistics
     *
     * @param int $days
     * @return array
     */
    public function getContentGrowthStats(int $days = 30): array
    {
        $cacheKey = "admin:content_growth_{$days}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($days) {
            $startDate = now()->subDays($days);

            return [
                'movies_added' => Movie::where('created_at', '>=', $startDate)->count(),
                'series_added' => Series::where('created_at', '>=', $startDate)->count(),
                'users_registered' => User::where('created_at', '>=', $startDate)->count(),
                'daily_breakdown' => $this->getDailyContentBreakdown($days)
            ];
        });
    }

    /**
     * Get daily content breakdown for charts
     *
     * @param int $days
     * @return array
     */
    private function getDailyContentBreakdown(int $days): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $movieData = DB::select("
            SELECT
                DATE(created_at) as date,
                COUNT(*) as count
            FROM movies
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate]);

        $seriesData = DB::select("
            SELECT
                DATE(created_at) as date,
                COUNT(*) as count
            FROM series
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate]);

        $userData = DB::select("
            SELECT
                DATE(created_at) as date,
                COUNT(*) as count
            FROM users
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate]);

        return [
            'movies' => $this->formatDailyData($movieData, $days),
            'series' => $this->formatDailyData($seriesData, $days),
            'users' => $this->formatDailyData($userData, $days),
            'labels' => $this->generateDateLabels($days)
        ];
    }

    /**
     * Format daily data for charts
     *
     * @param array $data
     * @param int $days
     * @return array
     */
    private function formatDailyData(array $data, int $days): array
    {
        $formatted = [];
        $dataByDate = collect($data)->keyBy('date');

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $formatted[] = $dataByDate->get($date)?->count ?? 0;
        }

        return $formatted;
    }

    /**
     * Generate date labels for charts
     *
     * @param int $days
     * @return array
     */
    private function generateDateLabels(int $days): array
    {
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->format('M j');
        }

        return $labels;
    }

    /**
     * Get user activity statistics
     *
     * @return array
     */
    public function getUserActivityStats(): array
    {
        return Cache::remember('admin:user_activity', self::CACHE_DURATION, function () {
            return [
                'daily_active' => $this->getDailyActiveUsers(),
                'weekly_active' => $this->getWeeklyActiveUsers(),
                'monthly_active' => $this->getMonthlyActiveUsers(),
                'user_roles' => $this->getUserRoleDistribution(),
                'login_frequency' => $this->getLoginFrequency()
            ];
        });
    }

    /**
     * Get daily active users
     *
     * @return int
     */
    private function getDailyActiveUsers(): int
    {
        return User::where('last_login_at', '>=', now()->subDay())->count();
    }

    /**
     * Get weekly active users
     *
     * @return int
     */
    private function getWeeklyActiveUsers(): int
    {
        return User::where('last_login_at', '>=', now()->subWeek())->count();
    }

    /**
     * Get monthly active users
     *
     * @return int
     */
    private function getMonthlyActiveUsers(): int
    {
        return User::where('last_login_at', '>=', now()->subMonth())->count();
    }

    /**
     * Get user role distribution
     *
     * @return array
     */
    private function getUserRoleDistribution(): array
    {
        $roleData = DB::select("
            SELECT
                role,
                COUNT(*) as count
            FROM users
            GROUP BY role
            ORDER BY count DESC
        ");

        return collect($roleData)->mapWithKeys(function ($item) {
            return [$item->role => $item->count];
        })->toArray();
    }

    /**
     * Get login frequency data
     *
     * @return array
     */
    private function getLoginFrequency(): array
    {
        $data = DB::select("
            SELECT
                DATE(last_login_at) as date,
                COUNT(*) as logins
            FROM users
            WHERE last_login_at >= ?
            GROUP BY DATE(last_login_at)
            ORDER BY date DESC
            LIMIT 7
        ", [now()->subWeek()]);

        return collect($data)->mapWithKeys(function ($item) {
            return [$item->date => $item->logins];
        })->toArray();
    }

    /**
     * Get top performing content
     *
     * @param int $limit
     * @return array
     */
    public function getTopPerformingContent(int $limit = 10): array
    {
        $cacheKey = "admin:top_content_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($limit) {
            $topMovies = Movie::select(['id', 'title', 'view_count', 'created_at'])
                ->where('status', 'published')
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();

            $topSeries = Series::select(['id', 'title', 'view_count', 'created_at'])
                ->where('status', 'published')
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();

            return [
                'movies' => $topMovies,
                'series' => $topSeries
            ];
        });
    }

    /**
     * Get recent admin activity
     *
     * @param int $limit
     * @return array
     */
    public function getRecentActivity(int $limit = 10): array
    {
        return Cache::remember('admin:recent_activity', 300, function () use ($limit) {
            // Get recent movies
            $recentMovies = Movie::select(['title', 'created_at', 'status'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($movie) {
                    return [
                        'type' => 'movie',
                        'title' => $movie->title,
                        'action' => 'created',
                        'status' => $movie->status,
                        'date' => $movie->created_at
                    ];
                });

            // Get recent users
            $recentUsers = User::select(['username', 'created_at', 'role'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'type' => 'user',
                        'title' => $user->username,
                        'action' => 'registered',
                        'status' => $user->role,
                        'date' => $user->created_at
                    ];
                });

            // Combine and sort by date
            return $recentMovies->concat($recentUsers)
                ->sortByDesc('date')
                ->take($limit)
                ->values()
                ->toArray();
        });
    }

    /**
     * Clear all admin statistics cache
     *
     * @return void
     */
    public function clearStatsCache(): void
    {
        $cacheKeys = [
            'admin:dashboard_stats',
            'admin:content_growth_30',
            'admin:content_growth_7',
            'admin:user_activity',
            'admin:top_content_10',
            'admin:recent_activity'
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Force refresh statistics
     *
     * @return array
     */
    public function refreshStats(): array
    {
        $this->clearStatsCache();
        return $this->getDashboardStats();
    }
}