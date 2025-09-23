<?php
// ========================================
// USER PROFILE CONTROLLER
// ========================================
// File: app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Movie;
use App\Models\Watchlist;
use App\Rules\StrongPasswordRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\AuditLogger;

class ProfileController extends Controller
{
    /**
     * Show user profile
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get statistics
        $stats = [
            'total_watched' => 0, // Watch history removed
            'watchlist_count' => Watchlist::where('user_id', $user->id)->count(),
            'member_since' => $user->created_at->diffForHumans(),
            'last_login' => $user->updated_at ? $user->updated_at->diffForHumans() : 'Recently'
        ];
        
        return view('profile.index', compact('user', 'stats'));
    }

    /**
     * Show edit profile form
     */
    public function edit()
    {
        $user = Auth::user();
        $this->authorize('update', $user);

        return view('profile.edit', compact('user'));
    }

    /**
     * Update username
     */
    public function updateUsername(Request $request)
    {
        $user = Auth::user();
        $this->authorize('update', $user);
        
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users')->ignore($user->id)
            ]
        ], [
            'username.regex' => 'Username can only contain letters, numbers, and underscores.',
            'username.unique' => 'This username is already taken.'
        ]);
        
        $oldUsername = $user->username;
        $user->update(['username' => $validated['username']]);

        // Log the username change
        AuditLogger::logUserAction('updated', $user,
            ['username' => $oldUsername],
            ['username' => $validated['username']]
        );

        return back()->with('success', 'Username updated successfully!');
    }

    /**
     * Update email
     */
    public function updateEmail(Request $request)
    {
        $user = Auth::user();
        $this->authorize('update', $user);
        
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'current_password' => 'required'
        ]);
        
        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        
        $oldEmail = $user->email;
        $user->update(['email' => $validated['email']]);

        // Log the email change
        AuditLogger::logUserAction('updated', $user,
            ['email' => $oldEmail],
            ['email' => $validated['email']]
        );

        return back()->with('success', 'Email updated successfully!');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        $this->authorize('update', $user);
        
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'string', 'confirmed', new StrongPasswordRule()],
        ], [
            'password.confirmed' => 'New password confirmation does not match.'
        ]);
        
        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        
        $user->update(['password' => Hash::make($validated['password'])]);

        // Log the password change
        AuditLogger::logAuthAction('password_changed', $user);

        return back()->with('success', 'Password changed successfully!');
    }

    /**
     * Show watchlist
     */
    public function watchlist()
    {
        $user = Auth::user();
        
        $movies = $user->watchlistMovies()
            ->with('genres')
            ->latest('watchlist.created_at')
            ->paginate(20);
        
        return view('profile.watchlist', compact('movies'));
    }

    /**
     * Add to watchlist
     */
    public function addToWatchlist(Movie $movie)
    {
        $user = Auth::user();
        
        // Check if already in watchlist
        $exists = Watchlist::where('user_id', $user->id)
            ->where('movie_id', $movie->id)
            ->exists();
        
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Movie already in watchlist'
            ]);
        }
        
        Watchlist::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Added to watchlist!'
        ]);
    }

    /**
     * Remove from watchlist
     */
    public function removeFromWatchlist(Movie $movie)
    {
        $user = Auth::user();
        
        Watchlist::where('user_id', $user->id)
            ->where('movie_id', $movie->id)
            ->delete();
        
        return back()->with('success', 'Removed from watchlist!');
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();
        $this->authorize('delete', $user);

        // Validate current password for security
        $request->validate([
            'current_password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE'
        ], [
            'current_password.required' => 'Current password is required for account deletion.',
            'confirmation.in' => 'You must type "DELETE" to confirm account deletion.'
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        try {
            // Delete related data first
            $user->watchlistMovies()->detach(); // Remove watchlist entries
            $user->movieViews()->delete(); // Remove view history if exists

            // Log the account deletion for audit
            \Log::info('User account deleted', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'deleted_at' => now()
            ]);

            // Log account deletion before deleting
            AuditLogger::logAuthAction('account_deleted', $user);

            // Delete the user account
            $user->delete();

            // Logout the user
            Auth::logout();

            // Invalidate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')
                ->with('success', 'Your account has been successfully deleted. We\'re sorry to see you go!');

        } catch (\Exception $e) {
            \Log::error('Account deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Account deletion failed. Please try again or contact support.');
        }
    }
}