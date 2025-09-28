<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use App\Rules\NoXssRule;
use App\Rules\NoSqlInjectionRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    private PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;

        // Apply rate limiting middleware
        $this->middleware('throttle:5,60')->only(['sendResetLink']);
    }

    /**
     * Show forgot password form
     */
    public function showLinkRequestForm()
    {
        // Redirect if already authenticated
        if (auth()->check()) {
            return redirect()->route('home')->with('info', 'Anda sudah login.');
        }

        return view('auth.forgot-password');
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        // Redirect if already authenticated
        if (auth()->check()) {
            return redirect()->route('home')->with('info', 'Anda sudah login.');
        }

        // Advanced rate limiting - per IP and per email
        $ipKey = 'forgot-password-ip:' . $request->ip();
        $executed = RateLimiter::attempt($ipKey, 3, function() {
            return true;
        }, 3600); // 3 attempts per hour per IP

        if (!$executed) {
            $seconds = RateLimiter::availableIn($ipKey);
            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan dari IP ini. Coba lagi dalam ' . ceil($seconds / 60) . ' menit.'
            ])->withInput();
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                new NoXssRule(),
                new NoSqlInjectionRule()
            ]
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        // Sanitize email input
        $email = strtolower(trim($request->email));

        // Security delay to prevent timing attacks
        usleep(random_int(100000, 300000)); // 0.1-0.3 second random delay

        // Additional per-email rate limiting
        $emailKey = 'forgot-password-email:' . $email;
        $emailExecuted = RateLimiter::attempt($emailKey, 2, function() {
            return true;
        }, 3600); // 2 attempts per hour per email

        if (!$emailExecuted) {
            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan untuk email ini. Coba lagi dalam 1 jam.'
            ])->withInput();
        }

        // Get current rate limit status
        $rateLimitStatus = $this->passwordResetService->getRemainingAttempts($email, $request->ip());

        if (!$rateLimitStatus['can_attempt']) {
            return back()->withErrors([
                'email' => 'Batas maksimum percobaan tercapai. Coba lagi dalam ' .
                          ceil($rateLimitStatus['reset_in_seconds'] / 60) . ' menit.'
            ])->withInput();
        }

        try {
            // Send reset email via service
            $result = $this->passwordResetService->sendResetEmail($email, $request->ip());

            if ($result['success']) {
                return back()->with('status', $result['message']);
            } else {
                return back()->withErrors(['email' => $result['message']])->withInput();
            }

        } catch (\Exception $e) {
            // Log error but don't expose to user
            logger()->error('Forgot password error', [
                'email' => $email,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'email' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.'
            ])->withInput();
        }
    }

    /**
     * Get rate limit status (AJAX endpoint)
     * SECURITY: Limited information disclosure
     */
    public function getRateLimitStatus(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        // SECURITY: Additional rate limiting for this endpoint
        $statusKey = 'rate-limit-check:' . $request->ip();
        if (!RateLimiter::attempt($statusKey, 10, function() { return true; }, 60)) {
            return response()->json([
                'can_attempt' => false,
                'message' => 'Terlalu banyak pengecekan status. Coba lagi nanti.'
            ], 429);
        }

        $email = $request->get('email', '');
        $rateLimitStatus = $this->passwordResetService->getRemainingAttempts($email, $request->ip());

        // SECURITY: Return minimal information to prevent information disclosure
        return response()->json([
            'can_attempt' => $rateLimitStatus['can_attempt'],
            'message' => $rateLimitStatus['can_attempt']
                ? 'Anda dapat mengirim reset password.'
                : 'Batas percobaan tercapai. Coba lagi dalam beberapa menit.',
            // REMOVED: Detailed attempt counts and timing information
        ]);
    }

    /**
     * Show password reset statistics (Admin only)
     * SECURITY: Enhanced admin authorization and CSRF protection
     */
    public function showStatistics(Request $request)
    {
        // SECURITY: Enhanced admin permission check
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        // SECURITY: CSRF protection for admin endpoints
        if (!$request->ajax() && $request->isMethod('post')) {
            // For POST requests, CSRF token is automatically validated by middleware
        }

        // SECURITY: Input validation and sanitization
        $days = min(max((int)$request->get('days', 7), 1), 90); // Limit to 1-90 days

        // SECURITY: Rate limiting for admin statistics
        $adminKey = 'admin-stats:' . auth()->id();
        if (!RateLimiter::attempt($adminKey, 20, function() { return true; }, 60)) {
            return response()->json([
                'error' => 'Terlalu banyak permintaan statistik. Coba lagi nanti.'
            ], 429);
        }

        $stats = $this->passwordResetService->getResetStatistics($days);

        // SECURITY: Log admin access to statistics
        logger()->info('Admin accessed password reset statistics', [
            'admin_id' => auth()->id(),
            'admin_username' => auth()->user()->username,
            'days_requested' => $days,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json($stats);
    }

    /**
     * Clean up expired tokens (Console command endpoint)
     */
    public function cleanupExpiredTokens()
    {
        // This should only be called by console commands or scheduled jobs
        if (!app()->runningInConsole()) {
            abort(404);
        }

        $deletedCount = $this->passwordResetService->cleanupExpiredTokens();

        return response()->json([
            'success' => true,
            'deleted_tokens' => $deletedCount,
            'message' => "Cleaned up {$deletedCount} expired tokens."
        ]);
    }
}