<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display audit logs
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        // Apply filters
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }

        $logs = $query->paginate(50);

        // Get filter options
        $actions = AuditLog::distinct()->pluck('action')->filter()->sort();
        $modelTypes = AuditLog::distinct()->pluck('model_type')->filter()->sort();
        $users = User::select('id', 'username')->orderBy('username')->get();

        return view('admin.audit-logs.index', compact('logs', 'actions', 'modelTypes', 'users'));
    }

    /**
     * Show detailed audit log
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');
        return view('admin.audit-logs.show', compact('auditLog'));
    }

    /**
     * Get recent security events
     */
    public function security(Request $request)
    {
        $query = AuditLog::where('action', 'like', 'security_%')
            ->orWhere('action', 'like', 'failed_%')
            ->orWhere('action', 'login')
            ->orWhere('action', 'logout')
            ->with('user')
            ->latest();

        if ($request->filled('hours')) {
            $query->where('created_at', '>=', now()->subHours($request->hours));
        } else {
            $query->where('created_at', '>=', now()->subHours(24));
        }

        $logs = $query->paginate(25);

        return view('admin.audit-logs.security', compact('logs'));
    }

    /**
     * Export audit logs
     */
    public function export(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        // Apply same filters as index
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->limit(10000)->get(); // Limit for performance

        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Date/Time',
                'User',
                'Action',
                'Description',
                'Model Type',
                'Model ID',
                'IP Address',
                'User Agent',
                'URL',
                'Method'
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? $log->user->username : 'System',
                    $log->action,
                    $log->description,
                    $log->model_type,
                    $log->model_id,
                    $log->ip_address,
                    $log->user_agent,
                    $log->url,
                    $log->method,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}