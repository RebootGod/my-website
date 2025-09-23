<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Movie;

class MoviePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Movies are public, anyone can browse
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Movie $movie): bool
    {
        return true; // Movie details are public
    }

    /**
     * Determine whether the user can watch/play the movie.
     */
    public function play(User $user, Movie $movie): bool
    {
        // Only authenticated users can play movies
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
    public function update(User $user, Movie $movie): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Movie $movie): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Movie $movie): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Movie $movie): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can report issues with the movie.
     */
    public function report(User $user, Movie $movie): bool
    {
        // Only authenticated users can report issues
        return $user !== null;
    }
}