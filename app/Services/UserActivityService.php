<?php

namespace App\Services;

use App\Models\UserActivity;
use App\Models\User;
use App\Models\Movie;
use App\Models\Series;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * ========================================
 * USER ACTIVITY TRACKING SERVICE
 * Comprehensive user activity logging and analytics
 * ========================================
 */
class UserActivityService
{
    const CACHE_DURATION = 1800; // 30 minutes

    /**
     * Log user activity
     */
    public function logActivity(
        int $userId,
        string $activityType,
        string $description,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): UserActivity {
        return UserActivity::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'activity_at' => now(),
        ]);
    }

    /**
     * Log user login activity
     */
    public function logLogin(User $user): UserActivity
    {
        return $this->logActivity(
            $user->id,
            UserActivity::TYPE_LOGIN,
            "User '{$user->username}' logged in"
        );
    }

    /**
     * Log user logout activity
     */
    public function logLogout(User $user): UserActivity
    {
        return $this->logActivity(
            $user->id,
            UserActivity::TYPE_LOGOUT,
            "User '{$user->username}' logged out"
        );
    }

    /**
     * Log movie watching activity
     */
    public function logMovieWatch(User $user, Movie $movie): UserActivity
    {
        return $this->logActivity(
            $user->id,
            UserActivity::TYPE_WATCH_MOVIE,
            "User '{$user->username}' watched movie '{$movie->title}'",
            [
                'movie_id' => $movie->id,
                'movie_title' => $movie->title,
                'movie_year' => $movie->year,
            ]
        );
    }

    /**
     * Log series watching activity
     */
    public function logSeriesWatch(User $user, Series $series, ?int $episodeId = null): UserActivity
    {
        $description = "User '{$user->username}' watched series '{$series->title}'";
        $metadata = [
            'series_id' => $series->id,
            'series_title' => $series->title,
        ];

        if ($episodeId) {
            $description .= " (Episode ID: {$episodeId})";
            $metadata['episode_id'] = $episodeId;
        }

        return $this->logActivity(
            $user->id,
            UserActivity::TYPE_WATCH_SERIES,
            $description,
            $metadata
        );
    }

    /**
     * Log user search activity
     */
    public function logSearch(User $user, string $searchQuery, int $resultsCount = 0): UserActivity
    {
        return $this->logActivity(
            $user->id,
            UserActivity::TYPE_SEARCH,
            "User '{$user->username}' searched for '{$searchQuery}'",
            [
                'search_query' => $searchQuery,
                'results_count' => $resultsCount,
            ]
        );
    }

    /**
     * Log user registration activity
     */
    public function logRegistration(User $user): UserActivity
    {
        return $this->logActivity(
            $user->id,
            UserActivity::TYPE_REGISTER,
            "User '{$user->username}' registered"
        );
    }

    /**
     * Log profile update activity
     */
    public function logProfileUpdate(User $user, array $updatedFields = []): UserActivity
    {
        return $this->logActivity(
            $user->id,
            UserActivity::TYPE_PROFILE_UPDATE,
            "User '{$user->username}' updated profile",
            [
                'updated_fields' => $updatedFields,
            ]
        );
    }

    /**
     * Log password change activity
     */
    public function logPasswordChange(User $user): UserActivity
    {
        return $this->logActivity(
            $user->id,
            UserActivity::TYPE_PASSWORD_CHANGE,
            "User '{$user->username}' changed password"
        );
    }

    /**
     * Get recent activities with pagination
     */
    public function getRecentActivities(int $limit = 50, int $offset = 0)
    {
        return UserActivity::with('user')
            ->orderBy('activity_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    /**
     * Get activities by type
     */
    public function getActivitiesByType(string $type, int $limit = 50)
    {
        return UserActivity::with('user')
            ->byType($type)
            ->orderBy('activity_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by user
     */
    public function getActivitiesByUser(int $userId, int $limit = 50)
    {
        return UserActivity::with('user')
            ->byUser($userId)
            ->orderBy('activity_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity statistics for dashboard
     */
    public function getActivityStats(int $days = 30): array
    {
        $cacheKey = "user_activity_stats_{$days}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($days) {
            $startDate = Carbon::now()->subDays($days);

            // Basic activity counts
            $totalActivities = UserActivity::where('activity_at', '>=', $startDate)->count();
            $todayActivities = UserActivity::today()->count();
            $weekActivities = UserActivity::thisWeek()->count();
            $monthActivities = UserActivity::thisMonth()->count();

            // Activity breakdown by type
            $activityBreakdown = UserActivity::where('activity_at', '>=', $startDate)
                ->select('activity_type', DB::raw('count(*) as count'))
                ->groupBy('activity_type')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'activity_type')
                ->toArray();

            // Most active users
            $mostActiveUsers = UserActivity::where('activity_at', '>=', $startDate)
                ->select('user_id', DB::raw('count(*) as activity_count'))
                ->with('user:id,username')
                ->groupBy('user_id')
                ->orderBy('activity_count', 'desc')
                ->limit(10)
                ->get();

            // Daily activity trend
            $dailyTrend = $this->getDailyActivityTrend($days);

            // Hourly activity pattern (for today)
            $hourlyPattern = $this->getHourlyActivityPattern();

            return [
                'total_activities' => $totalActivities,
                'today_activities' => $todayActivities,
                'week_activities' => $weekActivities,
                'month_activities' => $monthActivities,
                'activity_breakdown' => $activityBreakdown,
                'most_active_users' => $mostActiveUsers,
                'daily_trend' => $dailyTrend,
                'hourly_pattern' => $hourlyPattern,
            ];
        });
    }

    /**
     * Get daily activity trend
     */
    private function getDailyActivityTrend(int $days): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $dailyData = UserActivity::where('activity_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(activity_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('DATE(activity_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trend = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $trend[] = $dailyData->get($dateString)?->count ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $trend,
        ];
    }

    /**
     * Get hourly activity pattern for today
     */
    private function getHourlyActivityPattern(): array
    {
        $hourlyData = UserActivity::whereDate('activity_at', Carbon::today())
            ->select(
                DB::raw('HOUR(activity_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('HOUR(activity_at)'))
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $pattern = [];
        $labels = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $labels[] = sprintf('%02d:00', $hour);
            $pattern[] = $hourlyData->get($hour)?->count ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $pattern,
        ];
    }

    /**
     * Get activity summary for a specific user
     */
    public function getUserActivitySummary(int $userId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $totalActivities = UserActivity::byUser($userId)
            ->where('activity_at', '>=', $startDate)
            ->count();

        $activityBreakdown = UserActivity::byUser($userId)
            ->where('activity_at', '>=', $startDate)
            ->select('activity_type', DB::raw('count(*) as count'))
            ->groupBy('activity_type')
            ->get()
            ->pluck('count', 'activity_type')
            ->toArray();

        $lastActivity = UserActivity::byUser($userId)
            ->orderBy('activity_at', 'desc')
            ->first();

        return [
            'total_activities' => $totalActivities,
            'activity_breakdown' => $activityBreakdown,
            'last_activity' => $lastActivity,
            'period_days' => $days,
        ];
    }

    /**
     * Clear activity statistics cache
     */
    public function clearStatsCache(): void
    {
        $cacheKeys = [
            'user_activity_stats_7',
            'user_activity_stats_30',
            'user_activity_stats_90',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clean up old activities (older than specified days)
     */
    public function cleanupOldActivities(int $olderThanDays = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($olderThanDays);

        return UserActivity::where('activity_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get popular content based on activity
     */
    public function getPopularContent(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        // Popular movies
        $popularMovies = UserActivity::where('activity_type', UserActivity::TYPE_WATCH_MOVIE)
            ->where('activity_at', '>=', $startDate)
            ->select(
                DB::raw('JSON_EXTRACT(metadata, "$.movie_id") as movie_id'),
                DB::raw('MAX(JSON_EXTRACT(metadata, "$.movie_title")) as movie_title'),
                DB::raw('COUNT(*) as watch_count')
            )
            ->whereNotNull(DB::raw('JSON_EXTRACT(metadata, "$.movie_id")'))
            ->groupBy(DB::raw('JSON_EXTRACT(metadata, "$.movie_id")'))
            ->orderBy('watch_count', 'desc')
            ->limit(10)
            ->get();

        // Popular series
        $popularSeries = UserActivity::where('activity_type', UserActivity::TYPE_WATCH_SERIES)
            ->where('activity_at', '>=', $startDate)
            ->select(
                DB::raw('JSON_EXTRACT(metadata, "$.series_id") as series_id'),
                DB::raw('MAX(JSON_EXTRACT(metadata, "$.series_title")) as series_title'),
                DB::raw('COUNT(*) as watch_count')
            )
            ->whereNotNull(DB::raw('JSON_EXTRACT(metadata, "$.series_id")'))
            ->groupBy(DB::raw('JSON_EXTRACT(metadata, "$.series_id")'))
            ->orderBy('watch_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'movies' => $popularMovies,
            'series' => $popularSeries,
        ];
    }
}