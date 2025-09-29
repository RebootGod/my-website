<?php
// ========================================
// 1. LOGIN CONTROLLER
// ========================================
// File: app/Http/Controllers/Auth/LoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminActionLog;
use App\Rules\NoXssRule;
use App\Rules\NoSqlInjectionRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

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
        // SECURITY: Rate limiting for brute force protection
        $ipKey = 'login_attempts:' . $request->ip();
        $usernameKey = 'login_attempts_user:' . $request->input('username');
        
        // Check IP-based rate limiting (10 attempts per 15 minutes)
        $executed = RateLimiter::attempt($ipKey, 10, function() {
            return true;
        }, 900); // 15 minutes

        if (!$executed) {
            // SECURITY: Log rate limit hit
            \Log::warning('Login rate limit exceeded for IP', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attempts' => 10
            ]);
            
            $seconds = RateLimiter::availableIn($ipKey);
            return back()->withErrors([
                'username' => 'Terlalu banyak percobaan login dari IP ini. Coba lagi dalam ' . ceil($seconds / 60) . ' menit.'
            ])->withInput($request->only('username'));
        }

        $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[a-zA-Z0-9_]+$/', new NoXssRule(), new NoSqlInjectionRule()],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ], [
            'username.regex' => 'Username hanya boleh mengandung huruf, angka, dan underscore.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 20 karakter.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.max' => 'Password maksimal 128 karakter.',
        ]);

        // Sanitize input data to prevent security issues
        $sanitizedData = [
            'username' => strip_tags(trim($request->username)),
            'password' => $request->password, // Don't sanitize password as it may affect authentication
        ];

        // SECURITY: Username-based rate limiting (5 attempts per username per hour)
        $usernameAttempts = RateLimiter::tooManyAttempts($usernameKey, 5);
        if ($usernameAttempts) {
            // SECURITY: Log brute force attempt
            \Log::warning('Brute force attempt detected', [
                'username' => $sanitizedData['username'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            $seconds = RateLimiter::availableIn($usernameKey);
            return back()->withErrors([
                'username' => 'Terlalu banyak percobaan untuk username ini. Coba lagi dalam ' . ceil($seconds / 60) . ' menit.'
            ])->withInput($request->only('username'));
        }

        $credentials = [
            'username' => $sanitizedData['username'],
            'password' => $sanitizedData['password']
        ];
        $remember = $request->has('remember');

        // Security: Add small delay to prevent timing attacks
        usleep(random_int(100000, 300000)); // 0.1-0.3 second random delay

        // SECURITY: Consistent timing for all authentication paths
        $user = User::where('username', $credentials['username'])->first();
        $authenticationFailed = false;
        $failureReason = 'invalid_credentials';

        if (!$user) {
            $authenticationFailed = true;
            $failureReason = 'user_not_found';
        } elseif ($user->status !== 'active') {
            $authenticationFailed = true;
            $failureReason = 'account_suspended';
        }

        // SECURITY: Add consistent delay regardless of failure reason
        if ($authenticationFailed) {
            // SECURITY: Hit username-based rate limiter on failed attempt
            RateLimiter::hit($usernameKey, 3600); // 1 hour decay

            // Additional delay for failed attempts
            usleep(random_int(100000, 300000));

            // Log failed login attempt
            app(\App\Services\UserActivityService::class)->logFailedLogin(
                $credentials['username'],
                $failureReason,
                $request->ip()
            );

            // SECURITY: Generic error message to prevent user enumeration
            $errorMessage = ($failureReason === 'account_suspended') 
                ? 'Akun Anda telah di-suspend atau di-banned.'
                : 'Username atau password salah.';

            return back()->withErrors([
                'username' => $errorMessage,
            ])->withInput($request->except('password'));
        }

        if (Auth::attempt($credentials, $remember)) {
            // SECURITY: Clear rate limiting on successful login
            RateLimiter::clear($usernameKey);
            
            $user = Auth::user();
            
            // SECURITY: Check for suspicious login patterns
            $this->checkSuspiciousLogin($user, $request);

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

        // SECURITY: Hit username-based rate limiter on wrong password
        RateLimiter::hit($usernameKey, 3600); // 1 hour decay

        // SECURITY: Add delay to prevent timing attacks
        usleep(random_int(100000, 300000));

        // Log failed login attempt
        app(\App\Services\UserActivityService::class)->logFailedLogin(
            $credentials['username'],
            'wrong_password',
            $request->ip()
        );

        return back()->withErrors([
            'password' => 'Username atau password salah.',
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
    
    /**
     * SECURITY: Check for suspicious login patterns
     */
    private function checkSuspiciousLogin(User $user, Request $request): void
    {
        $currentIP = $request->ip();
        $currentUserAgent = $request->userAgent();
        
        // Check for IP address change
        if ($user->last_login_ip && $user->last_login_ip !== $currentIP) {
            \Log::info('IP address change detected', [
                'user_id' => $user->id,
                'username' => $user->username,
                'old_ip' => $user->last_login_ip,
                'new_ip' => $currentIP
            ]);
        }
        
        // Check for unusual time patterns (login outside normal hours)
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 23) {
            \Log::info('Unusual login time detected', [
                'user_id' => $user->id,
                'username' => $user->username,
                'login_hour' => $currentHour
            ]);
        }
        
        // Check for rapid successive logins (potential account sharing)
        if ($user->last_login_at && $user->last_login_at->diffInMinutes(now()) < 2) {
            \Log::warning('Rapid successive login detected', [
                'user_id' => $user->id,
                'username' => $user->username,
                'time_diff' => $user->last_login_at->diffInMinutes(now())
            ]);
        }
        
        // Check for suspicious user agent patterns
        if (empty($currentUserAgent) || strlen($currentUserAgent) < 10) {
            \Log::warning('Suspicious user agent detected', [
                'user_id' => $user->id,
                'username' => $user->username,
                'user_agent' => $currentUserAgent
            ]);
        }
        
        // Check for bot-like user agents
        $botPatterns = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'java'];
        foreach ($botPatterns as $pattern) {
            if (stripos($currentUserAgent, $pattern) !== false) {
                \Log::warning('Bot-like user agent detected', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'user_agent' => $currentUserAgent,
                    'pattern' => $pattern
                ]);
                break;
            }
        }
    }
}