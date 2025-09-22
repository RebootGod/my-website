<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminActionLog::with(['admin', 'targetUser']);

        // Filter by admin user
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        // Filter by action type
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by target type
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        // Order by latest first
        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get all admin users for filter dropdown
        $adminUsers = User::where('role', 'admin')->get(['id', 'username']);

        // Get unique actions for filter dropdown
        $actions = AdminActionLog::distinct()->pluck('action')->filter()->sort();

        // Get unique target types for filter dropdown
        $targetTypes = AdminActionLog::distinct()->pluck('target_type')->filter()->sort();

        return view('admin.logs.index', compact('logs', 'adminUsers', 'actions', 'targetTypes'));
    }

    public function show(AdminActionLog $log)
    {
        $log->load(['admin', 'targetUser']);
        
        return view('admin.logs.show', compact('log'));
    }

    public function export(Request $request)
    {
        $query = AdminActionLog::with(['admin', 'targetUser']);

        // Apply same filters as index
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $filename = 'admin_logs_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'Date/Time',
                'Admin User',
                'Action',
                'Target Type',
                'Target ID',
                'IP Address',
                'User Agent',
                'Description',
                'Metadata'
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->admin ? $log->admin->username : 'Unknown',
                    $log->action,
                    $log->target_type,
                    $log->target_id,
                    $log->ip_address,
                    $log->user_agent,
                    $log->description,
                    is_array($log->metadata) ? json_encode($log->metadata) : $log->metadata
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}