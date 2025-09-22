<?php
// ========================================
// 1. USER MODEL
// ========================================
// File: app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
        'last_login_ip',
        'role_id'
    ];
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission($permission)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        // Support both string role name and Role relation
        if (is_string($this->role)) {
            $role = Role::with('permissions')->whereRaw('LOWER(name) = ?', [strtolower($this->role)])->first();
            return $role && $role->permissions && $role->permissions->contains('name', $permission);
        }
        return $this->role && $this->role->permissions && $this->role->permissions->contains('name', $permission);
    }

    public function isSuperAdmin()
    {
        if (is_string($this->role)) {
            $normalized = strtolower(str_replace(['-', ' '], '_', $this->role));
            return in_array($normalized, ['super_admin', 'superadmin'], true);
        }
        return $this->role && strtolower($this->role->name) === 'super_admin';
    }

    public function isAdmin()
    {
        // Support legacy string role or relasi Role
        if (is_string($this->role)) {
            $normalized = strtolower(str_replace(['-', ' '], '_', $this->role));
            return in_array($normalized, ['admin', 'super_admin', 'superadmin'], true);
        }
        return $this->role && in_array(strtolower($this->role->name), ['admin', 'super_admin']);
    }

    public function isModerator()
    {
        if (is_string($this->role)) {
            return strtolower($this->role) === 'moderator';
        }
        return $this->role && strtolower($this->role->name) === 'moderator';
    }

    public function isUser()
    {
        if (is_string($this->role)) {
            return strtolower($this->role) === 'user';
        }
        return $this->role && strtolower($this->role->name) === 'user';
    }

    /**
     * Get user's hierarchy level
     */
    public function getHierarchyLevel()
    {
        // Check if user has role_id and the role relation is loaded and is a Role model
        if ($this->role_id && $this->relationLoaded('role') && $this->role !== null && is_object($this->role) && method_exists($this->role, 'getTable')) {
            return $this->role->hierarchy ?? 0;
        }
        
        // Try to load role if role_id exists but relation not loaded or null
        if ($this->role_id) {
            try {
                $roleModel = Role::find($this->role_id);
                if ($roleModel && isset($roleModel->hierarchy)) {
                    return $roleModel->hierarchy;
                }
            } catch (\Exception $e) {
                // Fallback to string role matching
            }
        }
        
        // Fallback for string roles
        $roleString = is_string($this->role) ? strtolower($this->role) : '';
        return match($roleString) {
            'super_admin', 'super admin', 'superadmin' => 100,
            'admin' => 80,
            'moderator' => 60,
            'editor' => 40,
            'user', 'member' => 0,
            default => 0
        };
    }

    /**
     * Check if current user can manage another user
     */
    public function canManage(User $otherUser)
    {
        // Super Admin can manage everyone except themselves
        if ($this->isSuperAdmin() && $this->id !== $otherUser->id) {
            return true;
        }

        // Users cannot manage themselves in terms of role changes
        if ($this->id === $otherUser->id) {
            return false;
        }

        // Check hierarchy - can only manage users with lower hierarchy
        return $this->getHierarchyLevel() > $otherUser->getHierarchyLevel();
    }

    /**
     * Check if current user can edit another user
     */
    public function canEdit(User $otherUser)
    {
        // Users can always edit their own profile (email, password, basic info)
        if ($this->id === $otherUser->id) {
            return true;
        }
        
        // For editing other users, use the manage logic
        return $this->canManage($otherUser);
    }

        /**
     * Check if current user can change another user's role
     */
    public function canChangeRole($otherUser)
    {
        // Users cannot change their own role (security)
        if ($this->id === $otherUser->id) {
            return false;
        }
        
        // Must have higher hierarchy to change roles
        return $this->getHierarchyLevel() > $otherUser->getHierarchyLevel();
    }

    /**
     * Check if current user can reset another user's password
     */
    public function canResetPassword($otherUser)
    {
        // Users can reset their own password
        if ($this->id === $otherUser->id) {
            return true;
        }
        
        // Must have higher hierarchy to reset other users' passwords
        return $this->getHierarchyLevel() > $otherUser->getHierarchyLevel();
    }

    /**
     * Check if current user can ban another user
     */
    public function canBan(User $otherUser)
    {
        return $this->canManage($otherUser);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function inviteCodes()
    {
        return $this->hasMany(InviteCode::class, 'created_by');
    }

    public function registration()
    {
        return $this->hasOne(UserRegistration::class);
    }

    public function movieViews()
    {
        return $this->hasMany(MovieView::class);
    }

    public function addedMovies()
    {
        return $this->hasMany(Movie::class, 'added_by');
    }

    public function watchlist()
    {
        return $this->hasMany(Watchlist::class);
    }

    public function watchlistMovies()
    {
        return $this->belongsToMany(Movie::class, 'watchlist', 'user_id', 'movie_id')
                    ->withPivot('created_at', 'updated_at')
                    ->withTimestamps();
    }


    public function isActive()
    {
        return $this->status === 'active';
    }

    public function updateLastLogin()
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip()
        ]);
    }

    /**
     * Check if user's password needs rehashing for security upgrade
     * Laravel automatically uses latest algorithms, this ensures compatibility
     */
    public function needsPasswordRehash(): bool
    {
        // Check if password was hashed with older/weaker algorithm
        return password_needs_rehash($this->password, PASSWORD_DEFAULT);
    }

    /**
     * Rehash user's password with current security standards
     * This should be called after successful authentication
     */
    public function rehashPassword(string $plainPassword): bool
    {
        // Verify the password is correct before rehashing
        if (!password_verify($plainPassword, $this->password)) {
            return false;
        }

        // Only rehash if actually needed
        if (!$this->needsPasswordRehash()) {
            return true; // Already using current hash
        }

        // Update with new hash using current default algorithm
        $this->update([
            'password' => password_hash($plainPassword, PASSWORD_DEFAULT)
        ]);

        return true;
    }

    /**
     * Get password hashing info for debugging/monitoring
     */
    public function getPasswordHashInfo(): array
    {
        $info = password_get_info($this->password);
        
        return [
            'algorithm' => $info['algo'],
            'algorithm_name' => $info['algoName'],
            'needs_rehash' => $this->needsPasswordRehash(),
            'hash_prefix' => substr($this->password, 0, 10) . '...',
        ];
    }

    /**
     * Get user activities
     */
    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }
}