<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\MovieView;
use App\Models\User;
use App\Models\BrokenLinkReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsService
{
    public static function getAnalyticsData()
    {
        return Cache::remember('admin_analytics', 600, function () {
            return [
                'overview' => self::getOverviewStats(),
                'charts' => self::getChartsData(),
                'top_content' => self::getTopContentStats(),
                'user_analytics' => self::getUserAnalytics(),
                'performance' => self::getPerformanceStats(),
            ];
        });
    }

    public static function getOverviewStats() {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $lastWeek = Carbon::now()->subWeek();
        $lastMonth = Carbon::now()->subMonth();

        return [
            'total_movies' => Movie::count(),
            'total_users' => User::count(),
            'total_views' => MovieView::count(),
            'total_reports' => BrokenLinkReport::count(),
            'today_views' => MovieView::whereDate('created_at', $today)->count(),
            'yesterday_views' => MovieView::whereDate('created_at', $yesterday)->count(),
            'today_registrations' => User::whereDate('created_at', $today)->count(),
            'yesterday_registrations' => User::whereDate('created_at', $yesterday)->count(),
            'views_growth' => self::calculateGrowth(
                MovieView::whereDate('created_at', $today)->count(),
                MovieView::whereDate('created_at', $yesterday)->count()
            ),
            'users_growth' => self::calculateGrowth(
                User::whereDate('created_at', $today)->count(),
                User::whereDate('created_at', $yesterday)->count()
            ),
            'week_views' => MovieView::where('created_at', '>=', $lastWeek)->count(),
            'month_views' => MovieView::where('created_at', '>=', $lastMonth)->count(),
            'week_users' => User::where('created_at', '>=', $lastWeek)->count(),
            'month_users' => User::where('created_at', '>=', $lastMonth)->count(),
        ];
    }

    public static function getChartsData() {
        return [
            'daily_views' => self::getDailyViewsChart(),
            'daily_registrations' => self::getDailyRegistrationsChart(),
            'genre_popularity' => self::getGenrePopularityChart(),
            'device_stats' => self::getDeviceStatsChart(),
        ];
    }

    public static function getDailyViewsChart() {
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $count = MovieView::whereDate('created_at', $date)->count();
            $days->push([
                'date' => $date,
                'views' => $count,
                'formatted_date' => Carbon::parse($date)->format('M d'),
            ]);
        }
        return $days;
    }

    public static function getDailyRegistrationsChart() {
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $count = User::whereDate('created_at', $date)->count();
            $days->push([
                'date' => $date,
                'registrations' => $count,
                'formatted_date' => Carbon::parse($date)->format('M d'),
            ]);
        }
        return $days;
    }

    public static function getGenrePopularityChart() {
        return DB::table('movie_views')
            ->join('movies', 'movie_views.movie_id', '=', 'movies.id')
            ->join('movie_genres', 'movies.id', '=', 'movie_genres.movie_id')
            ->join('genres', 'movie_genres.genre_id', '=', 'genres.id')
            ->select('genres.name', DB::raw('COUNT(*) as view_count'))
            ->groupBy('genres.id', 'genres.name')
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get();
    }

    public static function getDeviceStatsChart() {
        return collect([
            ['device' => 'Desktop', 'count' => 65],
            ['device' => 'Mobile', 'count' => 30],
            ['device' => 'Tablet', 'count' => 5],
        ]);
    }

    public static function getTopContentStats() {
        return [
            'most_viewed_movies' => Movie::withCount('views')
                ->orderBy('views_count', 'desc')
                ->limit(10)
                ->get(),
            'most_viewed_today' => Movie::join('movie_views', 'movies.id', '=', 'movie_views.movie_id')
                ->whereDate('movie_views.created_at', Carbon::today())
                ->select('movies.*', DB::raw('COUNT(movie_views.id) as today_views'))
                ->groupBy('movies.id')
                ->orderBy('today_views', 'desc')
                ->limit(5)
                ->get(),
            'most_reported_movies' => Movie::join('broken_link_reports', 'movies.id', '=', 'broken_link_reports.movie_id')
                ->select('movies.*', DB::raw('COUNT(broken_link_reports.id) as report_count'))
                ->groupBy('movies.id')
                ->orderBy('report_count', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    public static function getUserAnalytics() {
        return [
            'active_users_today' => User::whereHas('movieViews', function ($query) {
                $query->whereDate('created_at', Carbon::today());
            })->count(),
            'active_users_week' => User::whereHas('movieViews', function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subWeek());
            })->count(),
            'top_users_by_views' => User::withCount(['movieViews' => function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subMonth());
            }])
                ->orderBy('movie_views_count', 'desc')
                ->limit(10)
                ->get(),
            'user_retention' => self::calculateUserRetention(),
        ];
    }

    public static function getPerformanceStats() {
        return [
            'avg_views_per_movie' => round(MovieView::count() / max(Movie::count(), 1), 2),
            'avg_daily_views' => round(MovieView::where('created_at', '>=', Carbon::now()->subDays(30))->count() / 30, 2),
            'peak_hour' => self::getPeakViewingHour(),
            'bounce_rate' => self::calculateBounceRate(),
        ];
    }

    public static function calculateGrowth($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public static function calculateUserRetention() {
        $newUsersLastWeek = User::whereBetween('created_at', [
            Carbon::now()->subWeeks(2),
            Carbon::now()->subWeek()
        ])->count();
        $activeThisWeek = User::whereBetween('created_at', [
            Carbon::now()->subWeeks(2),
            Carbon::now()->subWeek()
        ])->whereHas('movieViews', function ($query) {
            $query->where('created_at', '>=', Carbon::now()->subWeek());
        })->count();
        return $newUsersLastWeek > 0 ? round(($activeThisWeek / $newUsersLastWeek) * 100, 1) : 0;
    }

    public static function getPeakViewingHour() {
        $hourlyViews = MovieView::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();
        return $hourlyViews ? $hourlyViews->hour . ':00' : 'N/A';
    }

    public static function calculateBounceRate() {
        $totalUsers = User::whereHas('movieViews')->count();
        $singleViewUsers = User::whereHas('movieViews', function ($query) {
            // Users with only one movie view
        }, '=', 1)->count();
        return $totalUsers > 0 ? round(($singleViewUsers / $totalUsers) * 100, 1) : 0;
    }

    public static function getCurrentViewers() {
        return MovieView::where('created_at', '>=', Carbon::now()->subMinutes(5))->count();
    }

    public static function getOnlineUsers() {
        return User::where('updated_at', '>=', Carbon::now()->subMinutes(15))->count();
    }
}
