<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleApiController extends Controller
{
    use ApiResponseTrait;
    public function index()
    {
        return Role::with('permissions')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                Rule::unique('roles', 'name')
            ],
            'hierarchy' => [
                'required',
                'integer',
                'min:0',
                'max:100'
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        // Check if current user has sufficient hierarchy to create this role
        $currentUser = auth()->user();
        if ($currentUser->getHierarchyLevel() <= $validated['hierarchy']) {
            return $this->forbiddenResponse('Cannot create a role with equal or higher hierarchy than your own.');
        }

        try {
            $role = Role::create([
                'name' => $validated['name'],
                'hierarchy' => $validated['hierarchy']
            ]);

            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            return $this->createdResponse(
                $role->load('permissions'),
                'Role created successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create role.');
        }
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                Rule::unique('roles', 'name')->ignore($role->id)
            ],
            'hierarchy' => [
                'required',
                'integer',
                'min:0',
                'max:100'
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        // Check if current user has sufficient hierarchy to update this role
        $currentUser = auth()->user();
        if ($currentUser->getHierarchyLevel() <= $role->hierarchy ||
            $currentUser->getHierarchyLevel() <= $validated['hierarchy']) {
            return $this->forbiddenResponse('Cannot modify a role with equal or higher hierarchy than your own.');
        }

        try {
            $role->update([
                'name' => $validated['name'],
                'hierarchy' => $validated['hierarchy']
            ]);

            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            return $this->updatedResponse(
                $role->load('permissions'),
                'Role updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update role.');
        }
    }

    public function destroy(Role $role)
    {
        // Check if current user has sufficient hierarchy to delete this role
        $currentUser = auth()->user();
        if ($currentUser->getHierarchyLevel() <= $role->hierarchy) {
            return $this->forbiddenResponse('Cannot delete a role with equal or higher hierarchy than your own.');
        }

        // Check if role is in use
        if ($role->users()->count() > 0) {
            return $this->validationErrorResponse(null, 'Cannot delete role that is assigned to users.');
        }

        try {
            $role->delete();
            return $this->deletedResponse('Role deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete role.');
        }
    }
}
