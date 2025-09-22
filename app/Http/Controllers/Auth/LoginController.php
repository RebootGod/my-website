<?php
// ========================================
// 1. LOGIN CONTROLLER
// ========================================
// File: app/Http/Controllers/Auth/LoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');
        $remember = $request->has('remember');

        // Check if user exists and is active
        $user = User::where('username', $credentials['username'])->first();

        if (!$user) {
            return back()->withErrors([
                'username' => 'Username tidak ditemukan.',
            ])->withInput($request->except('password'));
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'username' => 'Akun Anda telah di-suspend atau di-banned.',
            ])->withInput($request->except('password'));
        }

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Store plain password temporarily for potential rehashing
            // This will be used by PasswordRehashMiddleware and then cleared
            $request->session()->put('user_plain_password', $credentials['password']);

            // Update last login info
            $user->updateLastLogin();

            // Log user activity
            app(\App\Services\UserActivityService::class)->logLogin($user);

            $request->session()->regenerate();

            // Log admin login if user is admin
            if ($user->isAdmin()) {
                AdminActionLog::logSecurityAction('admin_login', $user, [
                    'description' => "Admin '{$user->username}' logged into the system",
                    'severity' => 'medium',
                    'is_sensitive' => true,
                    'metadata' => [
                        'username' => $user->username,
                        'user_role' => $user->role,
                        'remember_me' => $remember,
                        'session_id' => session()->getId()
                    ]
                ]);
                
                return redirect()->intended(route('admin.dashboard'));
            }

            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'password' => 'Password salah.',
        ])->withInput($request->except('password'));
    }

    public function logout(Request $request)
    {
        $user = auth()->user();

        // Log user activity
        if ($user) {
            app(\App\Services\UserActivityService::class)->logLogout($user);
        }

        // Log admin logout if user is admin
        if ($user && $user->isAdmin()) {
            AdminActionLog::logSecurityAction('admin_logout', $user, [
                'description' => "Admin '{$user->username}' logged out of the system",
                'severity' => 'low',
                'metadata' => [
                    'username' => $user->username,
                    'user_role' => $user->role,
                    'session_id' => session()->getId(),
                    'logout_time' => now()->toDateTimeString()
                ]
            ]);
        }
        
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}