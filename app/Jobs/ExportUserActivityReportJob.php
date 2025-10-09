<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserActivity;
use App\Models\Movie;
use App\Models\Series;
use App\Models\SearchHistory;
use App\Models\MovieView;
use App\Models\SeriesView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ExportUserActivityReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Report period type (daily, weekly, monthly).
     */
    protected string $periodType;

    /**
     * Start date for the report.
     */
    protected Carbon $startDate;

    /**
     * End date for the report.
     */
    protected Carbon $endDate;

    /**
     * Admin emails to send report to.
     */
    protected array $adminEmails;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $periodType = 'weekly',
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        array $adminEmails = []
    ) {
        $this->periodType = $periodType;
        $this->startDate = $startDate ?? $this->calculateStartDate($periodType);
        $this->endDate = $endDate ?? now();
        $this->adminEmails = $adminEmails ?: $this->getAdminEmails();
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('ExportUserActivityReportJob: Starting report generation', [
                'period_type' => $this->periodType,
                'start_date' => $this->startDate->toDateString(),
                'end_date' => $this->endDate->toDateString(),
            ]);

            $startTime = microtime(true);

            // Generate CSV report
            $csvPath = $this->generateCSVReport();

            // Send email to admins
            $this->sendReportEmail($csvPath);

            // Clean up old reports (keep last 30 days)
            $this->cleanupOldReports();

            $duration = round(microtime(true) - $startTime, 2);

            Log::info('ExportUserActivityReportJob: Report generation completed', [
                'period_type' => $this->periodType,
                'csv_path' => $csvPath,
                'duration_seconds' => $duration,
                'recipients' => count($this->adminEmails),
            ]);

        } catch (\Exception $e) {
            Log::error('ExportUserActivityReportJob: Report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate CSV report with user activity data.
     */
    private function generateCSVReport(): string
    {
        $reportData = $this->collectReportData();

        // Generate filename
        $filename = sprintf(
            'user_activity_report_%s_%s_to_%s.csv',
            $this->periodType,
            $this->startDate->format('Y-m-d'),
            $this->endDate->format('Y-m-d')
        );

        $directory = 'reports/user_activity';
        $path = "{$directory}/{$filename}";

        // Create CSV content
        $csv = $this->generateCSVContent($reportData);

        // Store CSV file
        Storage::disk('local')->put($path, $csv);

        Log::info('ExportUserActivityReportJob: CSV report generated', [
            'path' => $path,
            'size_kb' => round(strlen($csv) / 1024, 2),
        ]);

        return $path;
    }

    /**
     * Collect all report data.
     */
    private function collectReportData(): array
    {
        return [
            'summary' => $this->getSummaryMetrics(),
            'user_stats' => $this->getUserStatistics(),
            'content_stats' => $this->getContentStatistics(),
            'engagement_stats' => $this->getEngagementStatistics(),
            'search_stats' => $this->getSearchStatistics(),
            'security_stats' => $this->getSecurityStatistics(),
        ];
    }

    /**
     * Get summary metrics.
     */
    private function getSummaryMetrics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'new_users' => User::whereBetween('created_at', [$this->startDate, $this->endDate])->count(),
            'total_activities' => UserActivity::whereBetween('activity_at', [$this->startDate, $this->endDate])->count(),
            'unique_active_users' => UserActivity::whereBetween('activity_at', [$this->startDate, $this->endDate])
                ->distinct('user_id')
                ->count('user_id'),
            'total_logins' => UserActivity::where('activity_type', UserActivity::TYPE_LOGIN)
                ->whereBetween('activity_at', [$this->startDate, $this->endDate])
                ->count(),
            'failed_logins' => UserActivity::where('activity_type', UserActivity::TYPE_LOGIN_FAILED)
                ->whereBetween('activity_at', [$this->startDate, $this->endDate])
                ->count(),
        ];
    }

    /**
     * Get user statistics.
     */
    private function getUserStatistics(): array
    {
        $topUsers = UserActivity::whereBetween('activity_at', [$this->startDate, $this->endDate])
            ->selectRaw('user_id, COUNT(*) as activity_count')
            ->groupBy('user_id')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->with('user:id,username,email')
            ->get()
            ->map(function ($activity) {
                return [
                    'user_id' => $activity->user_id,
                    'username' => $activity->user->username ?? 'Unknown',
                    'email' => $activity->user->email ?? 'Unknown',
                    'activity_count' => $activity->activity_count,
                ];
            })
            ->toArray();

        return [
            'top_users' => $topUsers,
        ];
    }

    /**
     * Get content statistics.
     */
    private function getContentStatistics(): array
    {
        $topMovies = MovieView::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('movie_id, COUNT(*) as view_count')
            ->groupBy('movie_id')
            ->orderByDesc('view_count')
            ->limit(10)
            ->with('movie:id,title,year,rating')
            ->get()
            ->map(function ($view) {
                return [
                    'movie_id' => $view->movie_id,
                    'title' => $view->movie->title ?? 'Unknown',
                    'year' => $view->movie->year ?? 'N/A',
                    'rating' => $view->movie->rating ?? 'N/A',
                    'view_count' => $view->view_count,
                ];
            })
            ->toArray();

        $topSeries = SeriesView::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('series_id, COUNT(*) as view_count')
            ->groupBy('series_id')
            ->orderByDesc('view_count')
            ->limit(10)
            ->with('series:id,title,year,rating')
            ->get()
            ->map(function ($view) {
                return [
                    'series_id' => $view->series_id,
                    'title' => $view->series->title ?? 'Unknown',
                    'year' => $view->series->year ?? 'N/A',
                    'rating' => $view->series->rating ?? 'N/A',
                    'view_count' => $view->view_count,
                ];
            })
            ->toArray();

        return [
            'top_movies' => $topMovies,
            'top_series' => $topSeries,
            'total_movie_views' => MovieView::whereBetween('created_at', [$this->startDate, $this->endDate])->count(),
            'total_series_views' => SeriesView::whereBetween('created_at', [$this->startDate, $this->endDate])->count(),
        ];
    }

    /**
     * Get engagement statistics.
     */
    private function getEngagementStatistics(): array
    {
        $activityByType = UserActivity::whereBetween('activity_at', [$this->startDate, $this->endDate])
            ->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->pluck('count', 'activity_type')
            ->toArray();

        return [
            'activity_by_type' => $activityByType,
        ];
    }

    /**
     * Get search statistics.
     */
    private function getSearchStatistics(): array
    {
        $topSearches = SearchHistory::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('search_term, COUNT(*) as search_count')
            ->groupBy('search_term')
            ->orderByDesc('search_count')
            ->limit(20)
            ->get()
            ->map(function ($search) {
                return [
                    'search_term' => $search->search_term,
                    'search_count' => $search->search_count,
                ];
            })
            ->toArray();

        return [
            'top_searches' => $topSearches,
            'total_searches' => SearchHistory::whereBetween('created_at', [$this->startDate, $this->endDate])->count(),
        ];
    }

    /**
     * Get security statistics.
     */
    private function getSecurityStatistics(): array
    {
        $suspiciousIPs = UserActivity::where('activity_type', UserActivity::TYPE_LOGIN_FAILED)
            ->whereBetween('activity_at', [$this->startDate, $this->endDate])
            ->selectRaw('ip_address, COUNT(*) as failed_attempts')
            ->groupBy('ip_address')
            ->having('failed_attempts', '>=', 5)
            ->orderByDesc('failed_attempts')
            ->limit(20)
            ->get()
            ->map(function ($activity) {
                return [
                    'ip_address' => $activity->ip_address,
                    'failed_attempts' => $activity->failed_attempts,
                ];
            })
            ->toArray();

        return [
            'suspicious_ips' => $suspiciousIPs,
        ];
    }

    /**
     * Generate CSV content from report data.
     */
    private function generateCSVContent(array $reportData): string
    {
        $csv = [];

        // Header
        $csv[] = "User Activity Report - {$this->periodType}";
        $csv[] = "Period: {$this->startDate->format('Y-m-d')} to {$this->endDate->format('Y-m-d')}";
        $csv[] = "Generated: " . now()->format('Y-m-d H:i:s');
        $csv[] = "";

        // Summary Metrics
        $csv[] = "=== SUMMARY METRICS ===";
        foreach ($reportData['summary'] as $key => $value) {
            $csv[] = ucwords(str_replace('_', ' ', $key)) . "," . $value;
        }
        $csv[] = "";

        // Top Users
        $csv[] = "=== TOP 10 ACTIVE USERS ===";
        $csv[] = "User ID,Username,Email,Activity Count";
        foreach ($reportData['user_stats']['top_users'] as $user) {
            $csv[] = implode(',', [
                $user['user_id'],
                $user['username'],
                $user['email'],
                $user['activity_count'],
            ]);
        }
        $csv[] = "";

        // Top Movies
        $csv[] = "=== TOP 10 WATCHED MOVIES ===";
        $csv[] = "Movie ID,Title,Year,Rating,View Count";
        foreach ($reportData['content_stats']['top_movies'] as $movie) {
            $csv[] = implode(',', [
                $movie['movie_id'],
                '"' . $movie['title'] . '"',
                $movie['year'],
                $movie['rating'],
                $movie['view_count'],
            ]);
        }
        $csv[] = "";

        // Top Series
        $csv[] = "=== TOP 10 WATCHED SERIES ===";
        $csv[] = "Series ID,Title,Year,Rating,View Count";
        foreach ($reportData['content_stats']['top_series'] as $series) {
            $csv[] = implode(',', [
                $series['series_id'],
                '"' . $series['title'] . '"',
                $series['year'],
                $series['rating'],
                $series['view_count'],
            ]);
        }
        $csv[] = "";

        // Top Searches
        $csv[] = "=== TOP 20 SEARCH TERMS ===";
        $csv[] = "Search Term,Search Count";
        foreach ($reportData['search_stats']['top_searches'] as $search) {
            $csv[] = '"' . $search['search_term'] . '",' . $search['search_count'];
        }
        $csv[] = "";

        // Activity by Type
        $csv[] = "=== ACTIVITY BY TYPE ===";
        $csv[] = "Activity Type,Count";
        foreach ($reportData['engagement_stats']['activity_by_type'] as $type => $count) {
            $csv[] = ucwords(str_replace('_', ' ', $type)) . "," . $count;
        }
        $csv[] = "";

        // Suspicious IPs
        $csv[] = "=== SUSPICIOUS IP ADDRESSES (5+ Failed Logins) ===";
        $csv[] = "IP Address,Failed Attempts";
        foreach ($reportData['security_stats']['suspicious_ips'] as $ip) {
            $csv[] = $ip['ip_address'] . "," . $ip['failed_attempts'];
        }

        return implode("\n", $csv);
    }

    /**
     * Send report email to admins.
     */
    private function sendReportEmail(string $csvPath): void
    {
        if (empty($this->adminEmails)) {
            Log::warning('ExportUserActivityReportJob: No admin emails configured');
            return;
        }

        $fullPath = Storage::disk('local')->path($csvPath);
        $periodLabel = ucfirst($this->periodType);

        foreach ($this->adminEmails as $email) {
            try {
                Mail::raw(
                    "User Activity Report - {$periodLabel}\n\n" .
                    "Period: {$this->startDate->format('Y-m-d')} to {$this->endDate->format('Y-m-d')}\n" .
                    "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n" .
                    "Please find the detailed report attached.\n\n" .
                    "Best regards,\nNoobz Cinema System",
                    function ($message) use ($email, $periodLabel, $fullPath) {
                        $message->to($email)
                            ->subject("User Activity Report - {$periodLabel}")
                            ->attach($fullPath);
                    }
                );

                Log::info('ExportUserActivityReportJob: Report email sent', [
                    'email' => $email,
                ]);
            } catch (\Exception $e) {
                Log::warning('ExportUserActivityReportJob: Failed to send email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Clean up old reports (keep last 30 days).
     */
    private function cleanupOldReports(): void
    {
        $directory = 'reports/user_activity';
        $files = Storage::disk('local')->files($directory);

        $cutoffDate = now()->subDays(30);
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);

            if ($lastModified < $cutoffDate->timestamp) {
                Storage::disk('local')->delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            Log::info('ExportUserActivityReportJob: Old reports cleaned up', [
                'deleted_count' => $deletedCount,
            ]);
        }
    }

    /**
     * Calculate start date based on period type.
     */
    private function calculateStartDate(string $periodType): Carbon
    {
        return match ($periodType) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            default => now()->subWeek(),
        };
    }

    /**
     * Get admin emails from database.
     */
    private function getAdminEmails(): array
    {
        return User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })
            ->where('status', 'active')
            ->pluck('email')
            ->toArray();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ExportUserActivityReportJob: Job failed permanently', [
            'period_type' => $this->periodType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
