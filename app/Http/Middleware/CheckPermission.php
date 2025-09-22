<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $roleValue = $user->role;
        $roleModel = null;

        // Super admin bypass (supports string or relation, case/format insensitive)
        if (is_string($roleValue)) {
            $normalized = strtolower(str_replace(['-', ' '], '_', $roleValue));
            if (in_array($normalized, ['super_admin', 'superadmin'], true)) {
                return $next($request);
            }
        } elseif ($roleValue instanceof Role) {
            if (strtolower($roleValue->name) === 'super_admin') {
                return $next($request);
            }
        }

        if (is_string($roleValue) && $roleValue !== '') {
            $roleModel = Role::with('permissions')->whereRaw('LOWER(name) = ?', [strtolower($roleValue)])->first();
        } elseif ($roleValue instanceof Role) {
            $roleModel = $roleValue->relationLoaded('permissions') ? $roleValue : $roleValue->load('permissions');
        } else {
            // Try relation if attribute name conflicts
            $roleModel = $user->role()->with('permissions')->first();
        }

        if (!$roleModel || !$roleModel->permissions || !$roleModel->permissions->contains('name', $permission)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
