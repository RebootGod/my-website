<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionApiController extends Controller
{
    use ApiResponseTrait;
    public function index()
    {
        return Permission::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-z_]+$/',
                Rule::unique('permissions', 'name')
            ],
            'description' => [
                'nullable',
                'string',
                'max:500'
            ]
        ]);

        // Check if user has permission to create permissions (super admin only)
        if (!auth()->user()->isSuperAdmin()) {
            return $this->forbiddenResponse('Only super administrators can create permissions.');
        }

        try {
            $permission = Permission::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null
            ]);

            return $this->createdResponse(
                $permission,
                'Permission created successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create permission.');
        }
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-z_]+$/',
                Rule::unique('permissions', 'name')->ignore($permission->id)
            ],
            'description' => [
                'nullable',
                'string',
                'max:500'
            ]
        ]);

        // Check if user has permission to update permissions (super admin only)
        if (!auth()->user()->isSuperAdmin()) {
            return $this->forbiddenResponse('Only super administrators can modify permissions.');
        }

        try {
            $permission->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null
            ]);

            return $this->updatedResponse(
                $permission,
                'Permission updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update permission.');
        }
    }

    public function destroy(Permission $permission)
    {
        // Check if user has permission to delete permissions (super admin only)
        if (!auth()->user()->isSuperAdmin()) {
            return $this->forbiddenResponse('Only super administrators can delete permissions.');
        }

        // Check if permission is in use by any role
        if ($permission->roles()->count() > 0) {
            return $this->validationErrorResponse(null, 'Cannot delete permission that is assigned to roles.');
        }

        try {
            $permission->delete();
            return $this->deletedResponse('Permission deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete permission.');
        }
    }
}
