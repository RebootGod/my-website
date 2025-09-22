<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\Admin\UserPermissionService;

class UserApiController extends Controller
{
    use ApiResponseTrait;
    public function index()
    {
        return User::with('role.permissions')->get();
    }

    public function updateRole(Request $request, User $user)
    {
        // Validate input
        $validated = $request->validate([
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id'
            ]
        ]);

        // Check authorization - only users with higher hierarchy can change roles
        if (!UserPermissionService::canManage($user)) {
            return $this->forbiddenResponse('Unauthorized to modify this user.');
        }

        // Check if target role exists and is valid
        $targetRole = Role::find($validated['role_id']);
        if (!$targetRole) {
            return $this->validationErrorResponse(null, 'Invalid role specified.');
        }

        // Prevent users from assigning roles higher than their own
        $currentUser = auth()->user();
        if ($currentUser->getHierarchyLevel() <= $targetRole->hierarchy) {
            return $this->forbiddenResponse('Cannot assign a role with equal or higher privileges than your own.');
        }

        try {
            $user->role_id = $validated['role_id'];
            $user->save();

            return $this->successResponse(
                $user->load('role.permissions'),
                'User role updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user role.');
        }
    }

    public function updatePermissions(Request $request, User $user)
    {
        // Validate input
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        // Check authorization
        if (!UserPermissionService::canManage($user)) {
            return $this->forbiddenResponse('Unauthorized to modify this user.');
        }

        try {
            // Custom permission override logic
            if (isset($validated['permissions'])) {
                $user->permissions()->sync($validated['permissions']);
            }

            return $this->successResponse(
                $user->load('role.permissions'),
                'User permissions updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user permissions.');
        }
    }
}
