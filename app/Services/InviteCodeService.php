<?php

namespace App\Services;

use App\Models\InviteCode;
use App\Models\UserRegistration;
use App\Models\AdminActionLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InviteCodeService
{
    public static function searchInviteCodes($filters = [], $sortBy = 'created_at', $sortOrder = 'desc')
    {
        $query = InviteCode::with(['creator', 'registrations']);
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['expiry_filter'])) {
            switch ($filters['expiry_filter']) {
                case 'expired':
                    $query->where('expires_at', '<', now());
                    break;
                case 'expiring_soon':
                    $query->whereBetween('expires_at', [now(), now()->addDays(7)]);
                    break;
                case 'active':
                    $query->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
                    break;
            }
        }
        $allowedSorts = ['code', 'description', 'max_uses', 'used_count', 'expires_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        return $query;
    }

    public static function getStats()
    {
        return [
            'total_codes' => InviteCode::count(),
            'active_codes' => InviteCode::where('status', 'active')->count(),
            'expired_codes' => InviteCode::where('expires_at', '<', now())->count(),
            'used_codes' => InviteCode::where('used_count', '>', 0)->count(),
            'total_registrations' => UserRegistration::count(),
        ];
    }

    public static function createInviteCodes($data, $bulkCount = 1)
    {
        $codes = [];
        for ($i = 0; $i < $bulkCount; $i++) {
            if (!empty($data['auto_generate_code']) || empty($data['code'])) {
                do {
                    $code = self::generateUniqueCode();
                } while (InviteCode::where('code', $code)->exists());
            } else {
                $code = $data['code'];
                if ($i > 0) {
                    $code .= '-' . ($i + 1);
                }
            }
            $inviteCode = InviteCode::create([
                'code' => $code,
                'description' => $data['description'] . ($bulkCount > 1 ? ' #' . ($i + 1) : ''),
                'max_uses' => $data['max_uses'] ?? null,
                'used_count' => 0,
                'expires_at' => $data['expires_at'] ?? null,
                'status' => 'active',
                'created_by' => $data['created_by'],
            ]);
            $codes[] = $inviteCode;
        }
        return $codes;
    }

    public static function updateInviteCode(InviteCode $inviteCode, $data)
    {
        $inviteCode->update([
            'description' => $data['description'],
            'max_uses' => $data['max_uses'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'status' => $data['status'],
        ]);
        return $inviteCode;
    }

    public static function deleteInviteCode(InviteCode $code)
    {
        AdminActionLog::logSystemAction('invite_code_deleted', [
            'description' => "Deleted invite code '{$code->code}' (used {$code->used_count}/{$code->max_uses} times)",
            'severity' => 'medium',
            'metadata' => [
                'code' => $code->code,
                'description' => $code->description,
                'used_count' => $code->used_count,
                'max_uses' => $code->max_uses,
                'status' => $code->status,
                'expires_at' => $code->expires_at?->toDateTimeString()
            ]
        ]);
        $code->delete();
    }

    public static function toggleStatus(InviteCode $code)
    {
        $newStatus = $code->status === 'active' ? 'inactive' : 'active';
        $code->update(['status' => $newStatus]);
        $code->refresh();
        return $code->status;
    }

    public static function bulkAction($action, $inviteCodeIds, $extendDays = null)
    {
        $inviteCodes = InviteCode::whereIn('id', $inviteCodeIds);
        switch ($action) {
            case 'activate':
                return $inviteCodes->update(['status' => 'active']);
            case 'deactivate':
                return $inviteCodes->update(['status' => 'inactive']);
            case 'delete':
                $count = $inviteCodes->count();
                $usedCount = $inviteCodes->where('used_count', '>', 0)->count();
                AdminActionLog::logSystemAction('bulk_invite_codes_deleted', [
                    'description' => "Bulk deleted {$count} invite codes ({$usedCount} were used)",
                    'severity' => 'medium',
                    'metadata' => [
                        'total_deleted' => $count,
                        'used_codes_deleted' => $usedCount,
                        'selected_codes' => $inviteCodeIds
                    ]
                ]);
                $inviteCodes->delete();
                return $count;
            case 'extend_expiry':
                $inviteCodes->each(function ($code) use ($extendDays) {
                    $newExpiry = $code->expires_at 
                        ? $code->expires_at->addDays($extendDays)
                        : now()->addDays($extendDays);
                    $code->update(['expires_at' => $newExpiry]);
                });
                return $inviteCodes->count();
        }
        return 0;
    }

    public static function getAnalytics()
    {
        $analytics = [
            'total_codes' => InviteCode::count(),
            'total_uses' => InviteCode::sum('used_count'),
            'active_codes' => InviteCode::where('status', 'active')->count(),
            'expired_codes' => InviteCode::where('expires_at', '<', now())->count(),
            'unused_codes' => InviteCode::where('used_count', 0)->count(),
            'fully_used_codes' => InviteCode::whereRaw('used_count >= max_uses')->count(),
            'codes_created_this_month' => InviteCode::whereMonth('created_at', now()->month)->count(),
            'registrations_this_month' => UserRegistration::whereMonth('created_at', now()->month)->count(),
        ];
        $monthlyUsage = InviteCode::selectRaw('
                YEAR(created_at) as year, 
                MONTH(created_at) as month, 
                COUNT(*) as codes_created,
                SUM(used_count) as total_uses
            ')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('year, month')
            ->get();
        $topCodes = InviteCode::orderBy('used_count', 'desc')
            ->limit(10)
            ->get(['code', 'description', 'used_count', 'max_uses']);
        return compact('analytics', 'monthlyUsage', 'topCodes');
    }

    public static function generateUniqueCode($length = 8)
    {
        do {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (InviteCode::where('code', $code)->exists());
        return $code;
    }
}
