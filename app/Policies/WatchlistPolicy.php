<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Watchlist;

class WatchlistPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Authenticated users can view their own watchlist
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Watchlist $watchlist): bool
    {
        // Users can only view their own watchlist items
        return $user->id === $watchlist->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Authenticated users can add to their watchlist
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Watchlist $watchlist): bool
    {
        // Users can only update their own watchlist items
        return $user->id === $watchlist->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Watchlist $watchlist): bool
    {
        // Users can only delete their own watchlist items
        return $user->id === $watchlist->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Watchlist $watchlist): bool
    {
        return $user->id === $watchlist->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Watchlist $watchlist): bool
    {
        return $user->id === $watchlist->user_id || $user->isAdmin();
    }
}