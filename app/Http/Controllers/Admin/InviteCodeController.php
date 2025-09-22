<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InviteCode;
use App\Services\InviteCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

/**
 * InviteCodeController - Handles all invite code management operations
 * Separated for better organization and security
 */
class InviteCodeController extends Controller
{
    /**
     * Display a listing of invite codes
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'expiry_filter']);
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query = InviteCodeService::searchInviteCodes($filters, $sortBy, $sortOrder);
        $inviteCodes = $query->paginate(20);
        $stats = InviteCodeService::getStats();
        return view('admin.invite-codes.index', compact('inviteCodes', 'stats'));
    }

    /**
     * Show the form for creating a new invite code
     */
    public function create()
    {
        return view('admin.invite-codes.create');
    }

    /**
     * Store a newly created invite code
     */
    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'max_uses' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now',
            'auto_generate_code' => 'boolean',
            'code' => 'nullable|string|max:50|unique:invite_codes,code',
            'bulk_create' => 'boolean',
            'bulk_count' => 'required_if:bulk_create,true|integer|min:1|max:100',
        ]);

        $bulkCount = $request->boolean('bulk_create') ? $request->bulk_count : 1;
        $data = $request->only(['description', 'max_uses', 'expires_at', 'auto_generate_code', 'code']);
        $data['created_by'] = auth()->id();
        $codes = InviteCodeService::createInviteCodes($data, $bulkCount);
        $message = $bulkCount > 1 
            ? "Successfully created {$bulkCount} invite codes!"
            : 'Invite code created successfully!';
        return redirect()->route('admin.invite-codes.index')
            ->with('success', $message)
            ->with('new_codes', $codes);
    }

    /**
     * Display the specified invite code
     */
    public function show(InviteCode $inviteCode)
    {
        $inviteCode->load(['creator', 'registrations']);
        
        // Get usage statistics
        $usageStats = [
            'total_uses' => $inviteCode->used_count,
            'remaining_uses' => max(0, $inviteCode->max_uses - $inviteCode->used_count),
            'usage_percentage' => $inviteCode->max_uses > 0 
                ? round(($inviteCode->used_count / $inviteCode->max_uses) * 100, 2) 
                : 0,
            'is_expired' => $inviteCode->expires_at && $inviteCode->expires_at < now(),
            'days_until_expiry' => $inviteCode->expires_at 
                ? $inviteCode->expires_at->diffInDays(now()) 
                : null,
        ];
        return view('admin.invite-codes.show', compact('inviteCode', 'usageStats'));
    }

    /**
     * Show the form for editing the specified invite code
     */
    public function edit(InviteCode $inviteCode)
    {
        return view('admin.invite-codes.edit', compact('inviteCode'));
    }

    /**
     * Update the specified invite code
     */
    public function update(Request $request, InviteCode $inviteCode)
    {
        $minUses = $inviteCode->used_count > 0 ? $inviteCode->used_count : 1;
        
        $request->validate([
            'description' => 'required|string|max:255',
            'max_uses' => 'nullable|integer|min:' . $minUses . '|max:1000',
            'expires_at' => 'nullable|date',
            'status' => 'required|in:active,inactive,expired',
        ]);

        $data = $request->only(['description', 'max_uses', 'expires_at', 'status']);
        InviteCodeService::updateInviteCode($inviteCode, $data);
        return redirect()->route('admin.invite-codes.index')
            ->with('success', 'Invite code updated successfully!');
    }

    /**
     * Remove the specified invite code
     */
    public function destroy(InviteCode $code)
    {
        // Allow deletion of used invite codes
        // Note: This will permanently remove the invite code from database
        // Users who registered with this code will remain unaffected
        
        // Log admin action
        InviteCodeService::deleteInviteCode($code);
        return redirect()->route('admin.invite-codes.index')
            ->with('success', 'Invite code deleted successfully!');
    }

    /**
     * Toggle invite code status
     */
    public function toggleStatus(InviteCode $code)
    {
        $oldStatus = $code->status;
        $newStatus = InviteCodeService::toggleStatus($code);
        if ($newStatus === ($oldStatus === 'active' ? 'inactive' : 'active')) {
            return back()->with('success', "Invite code status changed from {$oldStatus} to {$newStatus} successfully!");
        } else {
            return back()->with('error', "Failed to update invite code status. Current status: {$code->status}");
        }
    }

    /**
     * Bulk actions for invite codes
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,extend_expiry',
            'invite_code_ids' => 'required|array',
            'invite_code_ids.*' => 'exists:invite_codes,id',
            'extend_days' => 'required_if:action,extend_expiry|integer|min:1|max:365',
        ]);

        $action = $request->action;
        $inviteCodeIds = $request->invite_code_ids;
        $extendDays = $request->extend_days ?? null;
        $count = InviteCodeService::bulkAction($action, $inviteCodeIds, $extendDays);
        switch ($action) {
            case 'activate':
                return back()->with('success', "{$count} invite codes activated!");
            case 'deactivate':
                return back()->with('success', "{$count} invite codes deactivated!");
            case 'delete':
                return back()->with('success', "{$count} invite codes deleted!");
            case 'extend_expiry':
                return back()->with('success', "{$count} invite codes expiry extended by {$extendDays} days!");
        }
        return back();
    }

    /**
     * Generate analytics for invite codes
     */
    public function analytics()
    {
        $data = InviteCodeService::getAnalytics();
        return view('admin.invite-codes.analytics', $data);
    }

    /**
     * Generate a single invite code quickly
     */
    public function generate(Request $request)
    {
        try {
            $data = [
                'description' => 'Quick Generated Code',
                'max_uses' => 10,
                'created_by' => auth()->id(),
                'auto_generate_code' => true
            ];
            $codes = InviteCodeService::createInviteCodes($data, 1);
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invite code generated successfully!',
                    'codes' => $codes
                ]);
            }
            return back()->with('success', 'Invite code generated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate invite code: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to generate invite code: ' . $e->getMessage());
        }
    }

    /**
     * Generate multiple invite codes in bulk
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'count' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string|max:255',
            'max_uses' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            $data = [
                'description' => $request->input('description', 'Bulk Generated Code'),
                'max_uses' => $request->input('max_uses', 10),
                'expires_at' => $request->input('expires_at'),
                'created_by' => auth()->id(),
                'auto_generate_code' => true
            ];
            $count = $request->input('count', 1);
            $codes = InviteCodeService::createInviteCodes($data, $count);
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully generated {$count} invite codes!",
                    'codes' => $codes
                ]);
            }
            return back()->with('success', "Successfully generated {$count} invite codes!");
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate invite codes: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to generate invite codes: ' . $e->getMessage());
        }
    }

    /**
     * Export invite codes data
     */
    public function export(Request $request)
    {
    // TODO: Implement CSV/Excel export functionality
    return back()->with('info', 'Export functionality will be implemented soon!');
    }

    /**
     * Generate a unique invite code
     */
    // ...existing code...
}