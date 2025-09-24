<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\MovieView;
use App\Models\InviteCode;
use App\Models\BrokenLinkReport;
use App\Models\UserRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * UserStatsService - Handles all user statistics calculations
 * Provides comprehensive user analytics and dashboard data
 */
class UserStatsService
{
    /**
     * Get comprehensive dashboard statistics
     */
    public static function getDashboardStats(): array
    {
        return Cache::remember('user_dashboard_stats', 300, function () { // Cache for 5 minutes
            return [
                'overview' => self::getOverviewStats(),
                'registrations' => self::getRegistrationStats(),
                'activity' => self::getActivityStats(),
                'roles' => self::getRoleDistribution(),
                'status' => self::getStatusDistribution(),
                'recent_activity' => self::getRecentActivityStats(),
            ];
        });
    }

    /**
     * Get basic overview statistics
     */
    public static function getOverviewStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'suspended')->count(),
            'banned' => User::where('status', 'banned')->count(),
            'new_today' => User::whereDate('created_at', today())->count(),
            'new_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'new_this_month' => User::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];
    }

    /**
     * Get registration statistics
     */
    public static function getRegistrationStats(): array
    {
        $registrationsByDate = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(fn($item) => $item->count);

        // Fill missing dates with 0
        $dates = collect();
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[$date] = $registrationsByDate->get($date, 0);
        }

        return [
            'daily_registrations' => $dates->toArray(),
            'total_registrations' => User::count(),
            'registrations_today' => User::whereDate('created_at', today())->count(),
            'registrations_yesterday' => User::whereDate('created_at', yesterday())->count(),
            'avg_daily_registrations' => round(User::count() / max(1, User::oldest()->first()?->created_at?->diffInDays(now()) ?? 1), 2),
            'peak_registration_date' => self::getPeakRegistrationDate(),
        ];
    }

    /**
     * Get user activity statistics
     */
    public static function getActivityStats(): array
    {
        return [
            'active_users_today' => User::whereDate('last_login_at', today())->count(),
            'active_users_week' => User::whereBetween('last_login_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'active_users_month' => User::whereBetween('last_login_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'never_logged_in' => User::whereNull('last_login_at')->count(),
            'inactive_30_days' => User::where('last_login_at', '<', now()->subDays(30))->count(),
            'avg_session_duration' => 0, // TODO: Implement session tracking
            'total_movie_views' => MovieView::count(),
            'unique_viewers' => MovieView::distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * Get role distribution statistics
     */
    public static function getRoleDistribution(): array
    {
        $roles = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get()
            ->keyBy('role')
            ->map(fn($item) => $item->count);

        return [
            'super_admin' => $roles->get('super_admin', 0),
            'admin' => $roles->get('admin', 0),
            'moderator' => $roles->get('moderator', 0),
            'user' => $roles->get('user', 0),
            'distribution' => $roles->toArray(),
            'total' => $roles->sum(),
        ];
    }

    /**
     * Get status distribution statistics
     */
    public static function getStatusDistribution(): array
    {
        $statuses = User::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn($item) => $item->count);

        return [
            'active' => $statuses->get('active', 0),
            'banned' => $statuses->get('banned', 0),
            'suspended' => $statuses->get('suspended', 0),
            'distribution' => $statuses->toArray(),
            'total' => $statuses->sum(),
        ];
    }

    /**
     * Get recent activity statistics
     */
    public static function getRecentActivityStats(): array
    {
        return [
            'recent_registrations' => User::orderBy('created_at', 'desc')->take(5)->get(['id', 'username', 'email', 'created_at']),
            'recent_logins' => User::whereNotNull('last_login_at')->orderBy('last_login_at', 'desc')->take(5)->get(['id', 'username', 'last_login_at']),
            'most_active_users' => self::getMostActiveUsers(),
            'banned_users_today' => User::where('status', 'banned')->whereDate('updated_at', today())->count(),
        ];
    }

    /**
     * Get comprehensive user statistics for specific user
     */
    public static function getUserStats(User $user): array
    {
        return Cache::remember("user_stats_{$user->id}", 300, function () use ($user) {
            $registration = UserRegistration::where('user_id', $user->id)->first();

            // Calculate stats - use separate queries for accuracy
            $totalViews = MovieView::where('user_id', $user->id)->count();
            $uniqueMovies = MovieView::where('user_id', $user->id)->distinct('movie_id')->count('movie_id');
            $inviteCodesCreated = InviteCode::where('created_by', $user->id)->count();
            $totalReports = BrokenLinkReport::where('user_id', $user->id)->count();

            // Calculate series watched (unique series viewed)
            $seriesWatched = DB::table('series_views')->where('user_id', $user->id)->distinct('series_id')->count('series_id');

            return [
                // Flat structure for view compatibility
                'total_views' => $totalViews,
                'unique_movies' => $uniqueMovies,
                'series_watched' => $seriesWatched,
                'invite_codes_created' => $inviteCodesCreated,

                // Detailed nested structure for future use
                'profile' => [
                    'registration_date' => $user->created_at,
                    'last_login' => $user->last_login_at,
                    'status' => $user->status,
                    'role' => $user->role,
                    'days_since_registration' => $user->created_at->diffInDays(now()),
                    'invite_code_used' => $registration?->inviteCode?->code,
                ],
                'activity' => [
                    'total_movie_views' => $totalViews,
                    'unique_movies_watched' => $uniqueMovies,
                    'series_watched' => $seriesWatched,
                    'avg_daily_views' => self::calculateAvgDailyViews($user),
                    'last_movie_watched' => MovieView::where('user_id', $user->id)->latest()->first()?->movie?->title,
                    'last_activity_date' => MovieView::where('user_id', $user->id)->latest()->first()?->created_at,
                ],
                'content' => [
                    'favorite_genres' => self::getUserFavoriteGenres($user),
                    'most_watched_movie' => self::getMostWatchedMovie($user),
                    'watch_streak' => self::getWatchStreak($user),
                    'total_reports_submitted' => $totalReports,
                ],
                'social' => [
                    'invite_codes_created' => $inviteCodesCreated,
                    'users_invited' => self::getUsersInvitedCount($user),
                    'watchlist_items' => 0, // TODO: Implement watchlist count
                ],
            ];
        });
    }

    /**
     * Get top performing users statistics
     */
    public static function getTopUsersStats(): array
    {
        return [
            'most_active_viewers' => self::getMostActiveUsers(10),
            'top_content_reporters' => self::getTopReporters(10),
            'top_invite_creators' => self::getTopInviteCreators(10),
            'longest_members' => User::oldest()->take(10)->get(['id', 'username', 'created_at']),
            'most_recent_members' => User::latest()->take(10)->get(['id', 'username', 'created_at']),
        ];
    }

    /**
     * Get user growth trends
     */
    public static function getUserGrowthTrends(): array
    {
        $monthlyGrowth = User::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subMonths(12), now()])
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $weeklyGrowth = User::selectRaw('YEAR(created_at) as year, WEEK(created_at) as week, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subWeeks(12), now()])
            ->groupBy('year', 'week')
            ->orderBy('year')
            ->orderBy('week')
            ->get();

        return [
            'monthly_growth' => $monthlyGrowth->toArray(),
            'weekly_growth' => $weeklyGrowth->toArray(),
            'growth_rate' => self::calculateGrowthRate(),
            'projected_next_month' => self::projectNextMonthRegistrations(),
        ];
    }

    /**
     * Get platform usage statistics
     */
    public static function getPlatformUsageStats(): array
    {
        return [
            'registration_sources' => self::getRegistrationSources(),
            'device_types' => self::getDeviceTypeStats(),
            'browser_distribution' => self::getBrowserStats(),
            'peak_usage_hours' => self::getPeakUsageHours(),
            'geographic_distribution' => self::getGeographicStats(),
        ];
    }

    /**
     * Calculate average daily views for a user
     */
    private static function calculateAvgDailyViews(User $user): float
    {
        $totalViews = MovieView::where('user_id', $user->id)->count();
        $daysSinceRegistration = max(1, $user->created_at->diffInDays(now()));
        
        return round($totalViews / $daysSinceRegistration, 2);
    }

    /**
     * Get user's favorite genres
     */
    private static function getUserFavoriteGenres(User $user): array
    {
        return DB::table('movie_views')
            ->join('movies', 'movie_views.movie_id', '=', 'movies.id')
            ->join('movie_genres', 'movies.id', '=', 'movie_genres.movie_id')
            ->join('genres', 'movie_genres.genre_id', '=', 'genres.id')
            ->where('movie_views.user_id', $user->id)
            ->selectRaw('genres.name, COUNT(*) as count')
            ->groupBy('genres.name')
            ->orderBy('count', 'desc')
            ->take(5)
            ->pluck('count', 'name')
            ->toArray();
    }

    /**
     * Get most watched movie for a user
     */
    private static function getMostWatchedMovie(User $user): ?array
    {
        $result = DB::table('movie_views')
            ->join('movies', 'movie_views.movie_id', '=', 'movies.id')
            ->where('movie_views.user_id', $user->id)
            ->selectRaw('movies.title, COUNT(*) as view_count')
            ->groupBy('movies.id', 'movies.title')
            ->orderBy('view_count', 'desc')
            ->first();

        return $result ? [
            'title' => $result->title,
            'view_count' => $result->view_count
        ] : null;
    }

    /**
     * Get watch streak for a user
     */
    private static function getWatchStreak(User $user): int
    {
        // TODO: Implement proper watch streak calculation
        return 0;
    }

    /**
     * Get users invited count
     */
    private static function getUsersInvitedCount(User $user): int
    {
        return UserRegistration::whereHas('inviteCode', function($query) use ($user) {
            $query->where('created_by', $user->id);
        })->count();
    }

    /**
     * Get most active users
     */
    private static function getMostActiveUsers(int $limit = 5): array
    {
        return User::select('users.*')
            ->withCount('movieViews')
            ->orderBy('movie_views_count', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get top reporters
     */
    private static function getTopReporters(int $limit = 5): array
    {
        return User::select('users.*')
            ->withCount('brokenLinkReports')
            ->orderBy('broken_link_reports_count', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get top invite creators
     */
    private static function getTopInviteCreators(int $limit = 5): array
    {
        return User::select('users.*')
            ->withCount('createdInviteCodes')
            ->orderBy('created_invite_codes_count', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get peak registration date
     */
    private static function getPeakRegistrationDate(): ?array
    {
        $peak = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('count', 'desc')
            ->first();

        return $peak ? [
            'date' => $peak->date,
            'registrations' => $peak->count
        ] : null;
    }

    /**
     * Calculate growth rate
     */
    private static function calculateGrowthRate(): float
    {
        $thisMonth = User::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $lastMonth = User::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count();
        
        if ($lastMonth == 0) return 100.0;
        
        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Project next month registrations
     */
    private static function projectNextMonthRegistrations(): int
    {
        $lastThreeMonths = [];
        for ($i = 2; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            $lastThreeMonths[] = User::whereBetween('created_at', [$start, $end])->count();
        }
        
        return (int) round(array_sum($lastThreeMonths) / count($lastThreeMonths));
    }

    /**
     * Get registration sources (placeholder)
     */
    private static function getRegistrationSources(): array
    {
        // TODO: Implement registration source tracking
        return [
            'direct' => 0,
            'invite_code' => UserRegistration::count(),
            'social_media' => 0,
            'referral' => 0,
        ];
    }

    /**
     * Get device type statistics (placeholder)
     */
    private static function getDeviceTypeStats(): array
    {
        // TODO: Implement device tracking
        return [
            'desktop' => 0,
            'mobile' => 0,
            'tablet' => 0,
        ];
    }

    /**
     * Get browser statistics (placeholder)
     */
    private static function getBrowserStats(): array
    {
        // TODO: Implement browser tracking
        return [
            'chrome' => 0,
            'firefox' => 0,
            'safari' => 0,
            'edge' => 0,
            'other' => 0,
        ];
    }

    /**
     * Get peak usage hours (placeholder)
     */
    private static function getPeakUsageHours(): array
    {
        // TODO: Implement hour-based activity tracking
        return [];
    }

    /**
     * Get geographic statistics (placeholder)
     */
    private static function getGeographicStats(): array
    {
        // TODO: Implement geographic tracking
        return [];
    }

    /**
     * Clear all cached statistics
     */
    public static function clearCache(): void
    {
        Cache::forget('user_dashboard_stats');
        
        // Clear individual user stats cache
        $userIds = User::pluck('id');
        foreach ($userIds as $userId) {
            Cache::forget("user_stats_{$userId}");
        }
    }
}