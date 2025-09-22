<?php
// ========================================
// UPDATE ADMIN INVITE CODE CONTROLLER
// ========================================
// File: app/Http/Controllers/Admin/AdminInviteCodeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InviteCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminInviteCodeController extends Controller
{
    /**
     * Display listing of invite codes
     */
    public function index()
    {
        $inviteCodes = InviteCode::with('creator')->latest()->paginate(20);
        return view('admin.invite-codes.index', compact('inviteCodes'));
    }

    /**
     * Show form for creating new invite code
     */
    public function create()
    {
        return view('admin.invite-codes.create');
    }

    /**
     * Store new invite code
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|unique:invite_codes,code|min:5|max:20|regex:/^[A-Z0-9_-]+$/i',
            'description' => 'nullable|string|max:255',
            'max_uses' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now',
            'status' => 'required|in:active,inactive'
        ], [
            'code.unique' => 'This invite code already exists.',
            'code.regex' => 'Code can only contain letters, numbers, underscore and hyphen.',
        ]);

        // Generate code if not provided
        if (empty($validated['code'])) {
            do {
                $generatedCode = strtoupper(Str::random(10));
            } while (InviteCode::where('code', $generatedCode)->exists());
            
            $validated['code'] = $generatedCode;
        } else {
            $validated['code'] = strtoupper($validated['code']);
        }

        // Set creator
        $validated['created_by'] = auth()->id();
        $validated['used_count'] = 0;

        InviteCode::create($validated);

        return redirect()->route('admin.invite-codes.index')
            ->with('success', 'Invite code created successfully: ' . $validated['code']);
    }

    /**
     * Generate random invite code (AJAX)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'count' => 'nullable|integer|min:1|max:10'
        ]);

        $count = $request->get('count', 1);
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            do {
                $code = strtoupper(Str::random(10));
            } while (InviteCode::where('code', $code)->exists());

            $inviteCode = InviteCode::create([
                'code' => $code,
                'description' => 'Auto-generated code',
                'status' => 'active',
                'used_count' => 0,
                'created_by' => auth()->id()
            ]);

            $codes[] = $inviteCode;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'codes' => $codes
            ]);
        }

        return redirect()->route('admin.invite-codes.index')
            ->with('success', $count . ' invite code(s) generated successfully!');
    }

    /**
     * Toggle invite code status
     */
    public function toggleStatus(InviteCode $inviteCode)
    {
        $newStatus = $inviteCode->status === 'active' ? 'inactive' : 'active';
        $inviteCode->update(['status' => $newStatus]);

        return back()->with('success', 'Invite code status updated!');
    }

    /**
     * Delete invite code
     */
    public function destroy(InviteCode $inviteCode)
    {
        $inviteCode->delete();
        
        return redirect()->route('admin.invite-codes.index')
            ->with('success', 'Invite code deleted successfully!');
    }
}