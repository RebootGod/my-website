<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile, admins can view any profile
        return $user->id === $model->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile, admins can update profiles they can manage
        if ($user->id === $model->id) {
            return true;
        }

        return $user->isAdmin() && $user->canManage($model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users can delete their own account, admins can delete accounts they can manage
        if ($user->id === $model->id) {
            return true;
        }

        return $user->isAdmin() && $user->canManage($model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->canManage($model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->canManage($model);
    }

    /**
     * Determine whether the user can change roles.
     */
    public function changeRole(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->canManage($model);
    }

    /**
     * Determine whether the user can manage other users.
     */
    public function manage(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->canManage($model);
    }
}