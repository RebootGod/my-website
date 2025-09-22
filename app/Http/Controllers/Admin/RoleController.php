<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AdminActionLog;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $role = Role::create($request->only(['name', 'hierarchy']));
        $permissionIds = $request->permissions ?? [];
        $role->permissions()->sync($permissionIds);
        
        // Get permission names for logging
        $permissionNames = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
        
        // Log role creation
        AdminActionLog::logRoleAction('role_created', ['type' => 'role', 'id' => $role->id, 'name' => $role->name], [
            'description' => "Created new role '{$role->name}' with hierarchy level {$role->hierarchy}",
            'severity' => 'high',
            'metadata' => [
                'role_name' => $role->name,
                'hierarchy_level' => $role->hierarchy,
                'permissions_assigned' => $permissionNames,
                'permission_count' => count($permissionIds)
            ]
        ]);
        
        return redirect()->route('admin.roles.index')->with('success', 'Role created!');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $role->load('permissions');
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        // Store old values for logging
        $oldData = [
            'name' => $role->name,
            'hierarchy' => $role->hierarchy,
            'permissions' => $role->permissions->pluck('name')->toArray()
        ];
        
        $role->update($request->only(['name', 'hierarchy']));
        $newPermissionIds = $request->permissions ?? [];
        $role->permissions()->sync($newPermissionIds);
        
        // Get new permission names for logging
        $newPermissionNames = Permission::whereIn('id', $newPermissionIds)->pluck('name')->toArray();
        
        // Determine what changed
        $changes = [];
        if ($oldData['name'] !== $role->name) {
            $changes['name'] = ['from' => $oldData['name'], 'to' => $role->name];
        }
        if ($oldData['hierarchy'] !== $role->hierarchy) {
            $changes['hierarchy'] = ['from' => $oldData['hierarchy'], 'to' => $role->hierarchy];
        }
        
        $permissionsChanged = array_diff($oldData['permissions'], $newPermissionNames) || 
                             array_diff($newPermissionNames, $oldData['permissions']);
        
        if ($permissionsChanged) {
            $changes['permissions'] = [
                'from' => $oldData['permissions'],
                'to' => $newPermissionNames
            ];
        }
        
        // Log role update
        AdminActionLog::logRoleAction('role_updated', ['type' => 'role', 'id' => $role->id, 'name' => $role->name], [
            'description' => "Updated role '{$role->name}'",
            'severity' => 'high',
            'old_values' => $oldData,
            'new_values' => [
                'name' => $role->name,
                'hierarchy' => $role->hierarchy,
                'permissions' => $newPermissionNames
            ],
            'metadata' => [
                'role_name' => $role->name,
                'changes_made' => array_keys($changes),
                'permissions_count_before' => count($oldData['permissions']),
                'permissions_count_after' => count($newPermissionNames),
                'permissions_changed' => $permissionsChanged
            ]
        ]);
        
        return redirect()->route('admin.roles.index')->with('success', 'Role updated!');
    }

    public function destroy(Role $role)
    {
        // Store role data before deletion
        $roleData = [
            'name' => $role->name,
            'hierarchy' => $role->hierarchy,
            'permissions' => $role->permissions->pluck('name')->toArray()
        ];
        
        $role->delete();
        
        // Log role deletion
        AdminActionLog::logRoleAction('role_deleted', null, [
            'description' => "Deleted role '{$roleData['name']}' with hierarchy level {$roleData['hierarchy']}",
            'severity' => 'critical',
            'target_type' => 'role',
            'target_id' => $role->id,
            'old_values' => $roleData,
            'metadata' => [
                'deleted_role_name' => $roleData['name'],
                'deleted_role_hierarchy' => $roleData['hierarchy'],
                'deleted_permissions' => $roleData['permissions'],
                'permissions_count' => count($roleData['permissions'])
            ]
        ]);
        
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted!');
    }
}
