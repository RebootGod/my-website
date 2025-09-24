<?php

namespace App\Http\Controllers;

use App\Models\BrokenLinkReport;
use App\Models\Movie;
use App\Models\Series;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{

    /**
     * Display broken link reports
     */
    public function index(Request $request)
    {
        $query = BrokenLinkReport::with(['movie', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('movie', function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%");
            });
        }

        $reports = $query->paginate(20);
        
        // Get statistics
        $stats = [
            'total' => BrokenLinkReport::count(),
            'pending' => BrokenLinkReport::where('status', 'pending')->count(),
            'resolved' => BrokenLinkReport::where('status', 'resolved')->count(),
            'dismissed' => BrokenLinkReport::where('status', 'dismissed')->count(),
        ];

        return view('admin.reports.index', compact('reports', 'stats'));
    }

    /**
     * Show specific report details
     */
    public function show(BrokenLinkReport $report)
    {
        $report->load(['movie', 'user']);
        
        return view('admin.reports.show', compact('report'));
    }

    /**
     * Store a new broken link report (from movie player)
     */
    public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'source_id' => 'nullable|integer',
            'issue_type' => 'required|in:not_loading,wrong_movie,poor_quality,no_audio,no_subtitle,buffering,other',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $report = BrokenLinkReport::create([
                'movie_id' => $request->movie_id,
                'movie_source_id' => $request->source_id,
                'user_id' => Auth::id(),
                'issue_type' => $request->issue_type,
                'description' => $request->description,
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Update movie's broken link count
            if ($request->source_id) {
                DB::table('movie_sources')
                    ->where('id', $request->source_id)
                    ->increment('report_count');
            }

            return response()->json([
                'success' => true,
                'message' => 'Thank you for reporting this issue. We will investigate and fix it soon.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report. Please try again.',
            ], 500);
        }
    }

    /**
     * Store a new episode report (from series player)
     */
    public function storeEpisodeReport(Request $request, Series $series, Episode $episode)
    {
        $request->validate([
            'series_id' => 'required|exists:series,id',
            'episode_id' => 'required|exists:episodes,id',
            'issue_type' => 'required|in:not_loading,wrong_episode,poor_quality,no_audio,no_subtitle,buffering,other',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $report = BrokenLinkReport::create([
                'series_id' => $request->series_id,
                'episode_id' => $request->episode_id,
                'user_id' => Auth::id(),
                'issue_type' => $request->issue_type,
                'description' => $request->description,
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for reporting this episode issue. We will investigate and fix it soon.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report. Please try again.',
            ], 500);
        }
    }

    /**
     * Update report status
     */
    public function update(Request $request, BrokenLinkReport $report)
    {
        $request->validate([
            'status' => 'required|in:pending,resolved,dismissed,fixed',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $updateData = [
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
            ];
            if (in_array($request->status, ['resolved', 'fixed'])) {
                $updateData['resolved_at'] = now();
                $updateData['resolved_by'] = Auth::id();
                $updateData['reviewed_at'] = now();
                $updateData['reviewed_by'] = Auth::id();
            } elseif ($request->status === 'dismissed') {
                $updateData['reviewed_at'] = now();
                $updateData['reviewed_by'] = Auth::id();
            }
            $report->update($updateData);

            return redirect()->back()->with('success', 'Report status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update report status.');
        }
    }

    /**
     * Bulk update reports
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'reports' => 'required|array',
            'reports.*' => 'exists:broken_link_reports,id',
            'action' => 'required|in:resolve,dismiss,delete',
        ]);

        try {
            $reportIds = $request->reports;
            
            switch ($request->action) {
                case 'resolve':
                    BrokenLinkReport::whereIn('id', $reportIds)->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                        'resolved_by' => Auth::id(),
                    ]);
                    break;
                    
                case 'dismiss':
                    BrokenLinkReport::whereIn('id', $reportIds)->update([
                        'status' => 'dismissed',
                    ]);
                    break;
                    
                case 'delete':
                    BrokenLinkReport::whereIn('id', $reportIds)->delete();
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk action completed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action.',
            ], 500);
        }
    }

    /**
     * Delete a report
     */
    public function destroy(BrokenLinkReport $report)
    {
        try {
            $report->delete();
            
            return redirect()->route('admin.reports.index')
                ->with('success', 'Report deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete report.');
        }
    }

    /**
     * Get reports statistics for API/AJAX
     */
    public function statistics()
    {
        $stats = [
            'total_reports' => BrokenLinkReport::count(),
            'pending_reports' => BrokenLinkReport::where('status', 'pending')->count(),
            'resolved_reports' => BrokenLinkReport::where('status', 'resolved')->count(),
            'dismissed_reports' => BrokenLinkReport::where('status', 'dismissed')->count(),
            'recent_reports' => BrokenLinkReport::where('created_at', '>=', now()->subDays(7))->count(),
            'most_reported_movies' => BrokenLinkReport::select('movie_id')
                ->with('movie:id,title')
                ->groupBy('movie_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(5)
                ->get()
                ->map(function ($report) {
                    return [
                        'movie' => $report->movie ? $report->movie->title : 'Unknown Movie',
                        'count' => BrokenLinkReport::where('movie_id', $report->movie_id)->count(),
                    ];
                }),
        ];

        return response()->json($stats);
    }
}