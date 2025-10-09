<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserBanHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * BanHistoryController
 * 
 * Manages the ban/suspension history timeline for administrators.
 * Provides filtering, searching, and exporting capabilities.
 */
class BanHistoryController extends Controller
{
    /**
     * Display ban history timeline with filters
     */
    public function index(Request $request)
    {
        $query = UserBanHistory::with(['user', 'admin'])
            ->recentFirst();

        // Filter by action type (ban, unban, suspend, activate)
        if ($request->filled('action_type')) {
            $query->byType($request->action_type);
        }

        // Search by username or email
        if ($request->filled('search')) {
            $query->searchUser($request->search);
        }

        // Filter by date range
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Filter by specific admin
        if ($request->filled('admin_id')) {
            $query->byAdmin($request->admin_id);
        }

        // Pagination
        $histories = $query->paginate(20)->withQueryString();

        // Get statistics for dashboard
        $stats = $this->getStatistics();

        return view('admin.ban-history.index', compact('histories', 'stats'));
    }

    /**
     * Export ban history to CSV
     */
    public function export(Request $request)
    {
        $query = UserBanHistory::with(['user', 'admin'])
            ->recentFirst();

        // Apply same filters as index
        if ($request->filled('action_type')) {
            $query->byType($request->action_type);
        }

        if ($request->filled('search')) {
            $query->searchUser($request->search);
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        if ($request->filled('admin_id')) {
            $query->byAdmin($request->admin_id);
        }

        // Limit export to prevent memory issues
        $histories = $query->limit(10000)->get();

        // Generate CSV
        $filename = 'ban-history-' . now()->format('Y-m-d-His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        // CSV Headers
        fputcsv($handle, [
            'ID',
            'Username',
            'Email',
            'Action',
            'Reason',
            'Duration',
            'Admin',
            'Admin IP',
            'Date',
        ]);

        // CSV Data
        foreach ($histories as $history) {
            fputcsv($handle, [
                $history->id,
                $history->user->username ?? 'N/A',
                $history->user->email ?? 'N/A',
                $history->action_label,
                $history->reason,
                $history->duration_text,
                $history->admin->username ?? 'System',
                $history->admin_ip ?? 'N/A',
                $history->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get ban history statistics
     */
    private function getStatistics(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'total_events' => UserBanHistory::count(),
            'today_events' => UserBanHistory::whereDate('created_at', $today)->count(),
            'week_events' => UserBanHistory::where('created_at', '>=', $thisWeek)->count(),
            'month_events' => UserBanHistory::where('created_at', '>=', $thisMonth)->count(),
            'bans_count' => UserBanHistory::byType('ban')->count(),
            'suspensions_count' => UserBanHistory::byType('suspend')->count(),
            'unbans_count' => UserBanHistory::byType('unban')->count(),
            'activations_count' => UserBanHistory::byType('activate')->count(),
        ];
    }

    /**
     * Get history for a specific user (AJAX endpoint)
     */
    public function userHistory(Request $request, $userId)
    {
        $histories = UserBanHistory::with(['admin'])
            ->byUser($userId)
            ->recentFirst()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'histories' => $histories,
        ]);
    }
}
