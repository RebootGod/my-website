<?php

namespace App\Services\Admin;

use App\Models\BrokenLinkReport;
use App\Models\Movie;
use App\Models\MovieSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class MovieReportService
{
    /**
     * Get paginated broken link reports
     */
    public function getReports(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = BrokenLinkReport::with(['movie', 'user', 'movieSource'])
            ->latest();

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['movie_id'])) {
            $query->where('movie_id', $filters['movie_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('movie', function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Update report status
     */
    public function updateReportStatus(BrokenLinkReport $report, string $status, ?string $adminNote = null): BrokenLinkReport
    {
        try {
            $allowedStatuses = ['pending', 'reviewing', 'fixed', 'dismissed'];
            
            if (!in_array($status, $allowedStatuses)) {
                throw new \InvalidArgumentException('Invalid status provided');
            }

            $updateData = [
                'status' => $status,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()
            ];

            if ($adminNote) {
                $updateData['admin_note'] = $adminNote;
            }

            $report->update($updateData);

            // Update movie source report count if status changed to fixed
            if ($status === 'fixed' && $report->movieSource) {
                $this->decrementSourceReportCount($report->movieSource);
            }

            Log::info('Report status updated', [
                'report_id' => $report->id,
                'old_status' => $report->getOriginal('status'),
                'new_status' => $status,
                'admin_id' => auth()->id()
            ]);

            return $report->fresh();

        } catch (\Exception $e) {
            Log::error('Failed to update report status', [
                'report_id' => $report->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bulk update report statuses
     */
    public function bulkUpdateReports(array $reportIds, string $status, ?string $adminNote = null): array
    {
        try {
            $allowedStatuses = ['pending', 'reviewing', 'fixed', 'dismissed'];
            
            if (!in_array($status, $allowedStatuses)) {
                throw new \InvalidArgumentException('Invalid status provided');
            }

            $updateData = [
                'status' => $status,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()
            ];

            if ($adminNote) {
                $updateData['admin_note'] = $adminNote;
            }

            $updated = BrokenLinkReport::whereIn('id', $reportIds)->update($updateData);

            // If marking as fixed, update source report counts
            if ($status === 'fixed') {
                $this->updateSourceReportCountsForFixedReports($reportIds);
            }

            Log::info('Bulk report status update', [
                'report_ids' => $reportIds,
                'status' => $status,
                'updated_count' => $updated,
                'admin_id' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Updated {$updated} reports",
                'updated_count' => $updated
            ];

        } catch (\Exception $e) {
            Log::error('Bulk report update failed', [
                'report_ids' => $reportIds,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage(),
                'updated_count' => 0
            ];
        }
    }

    /**
     * Delete report
     */
    public function deleteReport(BrokenLinkReport $report): bool
    {
        try {
            $reportInfo = [
                'report_id' => $report->id,
                'movie_id' => $report->movie_id,
                'status' => $report->status
            ];

            $report->delete();

            Log::info('Report deleted', $reportInfo);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete report', [
                'report_id' => $report->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get report statistics
     */
    public function getReportStats(): array
    {
        try {
            $totalReports = BrokenLinkReport::count();
            $pendingReports = BrokenLinkReport::where('status', 'pending')->count();
            $reviewingReports = BrokenLinkReport::where('status', 'reviewing')->count();
            $fixedReports = BrokenLinkReport::where('status', 'fixed')->count();
            $dismissedReports = BrokenLinkReport::where('status', 'dismissed')->count();

            // Recent reports (last 7 days)
            $recentReports = BrokenLinkReport::where('created_at', '>=', now()->subDays(7))->count();

            // Top reported movies
            $topReportedMovies = BrokenLinkReport::select('movie_id')
                ->selectRaw('COUNT(*) as report_count')
                ->with('movie:id,title')
                ->groupBy('movie_id')
                ->orderByDesc('report_count')
                ->limit(5)
                ->get();

            // Reports by month (last 6 months)
            $monthlyReports = BrokenLinkReport::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return [
                'total_reports' => $totalReports,
                'pending_reports' => $pendingReports,
                'reviewing_reports' => $reviewingReports,
                'fixed_reports' => $fixedReports,
                'dismissed_reports' => $dismissedReports,
                'recent_reports' => $recentReports,
                'status_breakdown' => [
                    'pending' => $pendingReports,
                    'reviewing' => $reviewingReports,
                    'fixed' => $fixedReports,
                    'dismissed' => $dismissedReports
                ],
                'top_reported_movies' => $topReportedMovies,
                'monthly_reports' => $monthlyReports,
                'resolution_rate' => $totalReports > 0 ? 
                    round(($fixedReports / $totalReports) * 100, 2) : 0
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get report stats', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_reports' => 0,
                'pending_reports' => 0,
                'reviewing_reports' => 0,
                'fixed_reports' => 0,
                'dismissed_reports' => 0,
                'recent_reports' => 0,
                'status_breakdown' => [],
                'top_reported_movies' => [],
                'monthly_reports' => [],
                'resolution_rate' => 0
            ];
        }
    }

    /**
     * Reset all reports for a movie source
     */
    public function resetSourceReports(MovieSource $source): array
    {
        try {
            // Count existing pending reports
            $pendingCount = BrokenLinkReport::where('movie_source_id', $source->id)
                ->where('status', 'pending')
                ->count();

            // Update all pending reports to fixed
            $updated = BrokenLinkReport::where('movie_source_id', $source->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'fixed',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'admin_note' => 'Mass reset by admin'
                ]);

            // Reset source report count
            $source->update(['report_count' => 0]);

            Log::info('Source reports reset', [
                'source_id' => $source->id,
                'movie_id' => $source->movie_id,
                'pending_reports' => $pendingCount,
                'updated_reports' => $updated
            ]);

            return [
                'success' => true,
                'message' => "Reset {$updated} reports for this source",
                'updated_count' => $updated,
                'pending_count' => $pendingCount
            ];

        } catch (\Exception $e) {
            Log::error('Failed to reset source reports', [
                'source_id' => $source->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reset reports: ' . $e->getMessage(),
                'updated_count' => 0
            ];
        }
    }

    /**
     * Create new report (for API use)
     */
    public function createReport(array $data): BrokenLinkReport
    {
        try {
            $report = BrokenLinkReport::create([
                'movie_id' => $data['movie_id'],
                'movie_source_id' => $data['movie_source_id'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'report_type' => $data['report_type'] ?? 'broken_link',
                'description' => $data['description'] ?? null,
                'status' => 'pending'
            ]);

            // Increment source report count if source provided
            if ($report->movie_source_id) {
                $this->incrementSourceReportCount($report->movieSource);
            }

            Log::info('New report created', [
                'report_id' => $report->id,
                'movie_id' => $report->movie_id,
                'user_id' => $report->user_id,
                'type' => $report->report_type
            ]);

            return $report;

        } catch (\Exception $e) {
            Log::error('Failed to create report', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Increment source report count
     */
    protected function incrementSourceReportCount(MovieSource $source): void
    {
        $source->increment('report_count');
    }

    /**
     * Decrement source report count
     */
    protected function decrementSourceReportCount(MovieSource $source): void
    {
        if ($source->report_count > 0) {
            $source->decrement('report_count');
        }
    }

    /**
     * Update source report counts for fixed reports
     */
    protected function updateSourceReportCountsForFixedReports(array $reportIds): void
    {
        $reports = BrokenLinkReport::whereIn('id', $reportIds)
            ->whereNotNull('movie_source_id')
            ->with('movieSource')
            ->get();

        foreach ($reports as $report) {
            if ($report->movieSource) {
                $this->decrementSourceReportCount($report->movieSource);
            }
        }
    }

    /**
     * Get report status options
     */
    public function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending Review',
            'reviewing' => 'Under Review',
            'fixed' => 'Fixed',
            'dismissed' => 'Dismissed'
        ];
    }

    /**
     * Get report type options
     */
    public function getReportTypeOptions(): array
    {
        return [
            'broken_link' => 'Broken Link',
            'quality_issue' => 'Quality Issue',
            'wrong_content' => 'Wrong Content',
            'buffering' => 'Buffering Issues',
            'other' => 'Other'
        ];
    }
}