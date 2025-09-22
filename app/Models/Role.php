<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'hierarchy'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get hierarchy level name
     */
    public function getHierarchyLevelAttribute()
    {
        return match($this->hierarchy) {
            100 => 'Super Admin',
            80 => 'Admin', 
            60 => 'Moderator',
            40 => 'Editor',
            20 => 'Contributor',
            0 => 'User/Member',
            default => "Level {$this->hierarchy}"
        };
    }

    /**
     * Check if this role can manage another role
     */
    public function canManage(Role $otherRole)
    {
        return $this->hierarchy > $otherRole->hierarchy;
    }

    /**
     * Get all roles that this role can manage
     */
    public function getManageableRoles()
    {
        return static::where('hierarchy', '<', $this->hierarchy)->get();
    }
}
