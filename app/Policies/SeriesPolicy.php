<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Series;

class SeriesPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Series are public, anyone can browse
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Series $series): bool
    {
        return true; // Series details are public
    }

    /**
     * Determine whether the user can watch/play the series.
     */
    public function play(User $user, Series $series): bool
    {
        // Only authenticated users can play series
        return $user !== null;
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
    public function update(User $user, Series $series): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Series $series): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Series $series): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Series $series): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can report issues with the series.
     */
    public function report(User $user, Series $series): bool
    {
        // Only authenticated users can report issues
        return $user !== null;
    }
}