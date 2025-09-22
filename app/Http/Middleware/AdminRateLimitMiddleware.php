<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ========================================
 * ADMIN RATE LIMITING MIDDLEWARE
 * Enhanced rate limiting for admin operations
 * ========================================
 */
class AdminRateLimitMiddleware
{
    /**
     * Rate limit configurations for different operations
     */
    private const RATE_LIMITS = [
        'search' => [
            'max_attempts' => 30,
            'decay_minutes' => 1,
            'message' => 'Too many search requests. Please slow down.'
        ],
        'bulk' => [
            'max_attempts' => 10,
            'decay_minutes' => 5,
            'message' => 'Too many bulk operations. Please wait before trying again.'
        ],
        'destructive' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
            'message' => 'Too many destructive operations. Please wait before trying again.'
        ],
        'api' => [
            'max_attempts' => 100,
            'decay_minutes' => 1,
            'message' => 'Too many API requests. Please wait before trying again.'
        ],
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
            'message' => 'Too many login attempts. Please wait before trying again.'
        ]
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'general'): Response
    {
        $user = $request->user();

        // Skip rate limiting for super admins
        if ($user && $user->role === 'super_admin') {
            return $next($request);
        }

        $identifier = $this->getIdentifier($request, $user);
        $config = self::RATE_LIMITS[$type] ?? self::RATE_LIMITS['api'];

        if ($this->hasExceededRateLimit($identifier, $type, $config)) {
            return $this->handleRateLimitExceeded($request, $user, $type, $config);
        }

        $this->incrementAttempts($identifier, $type, $config);

        $response = $next($request);

        // Log successful operations for monitoring
        $this->logOperation($request, $user, $type);

        return $response;
    }

    /**
     * Get unique identifier for rate limiting
     */
    private function getIdentifier(Request $request, $user): string
    {
        $base = $user ? "user:{$user->id}" : "ip:{$request->ip()}";
        return $base . ":" . $request->route()?->getName();
    }

    /**
     * Check if rate limit has been exceeded
     */
    private function hasExceededRateLimit(string $identifier, string $type, array $config): bool
    {
        $key = "rate_limit:{$type}:{$identifier}";
        $attempts = Cache::get($key, 0);

        return $attempts >= $config['max_attempts'];
    }

    /**
     * Increment rate limit attempts
     */
    private function incrementAttempts(string $identifier, string $type, array $config): void
    {
        $key = "rate_limit:{$type}:{$identifier}";
        $attempts = Cache::get($key, 0) + 1;
        $expiresAt = now()->addMinutes($config['decay_minutes']);

        Cache::put($key, $attempts, $expiresAt);
    }

    /**
     * Handle rate limit exceeded
     */
    private function handleRateLimitExceeded(Request $request, $user, string $type, array $config): Response
    {
        // Log rate limit exceeded
        Log::warning('Admin rate limit exceeded', [
            'user_id' => $user?->id,
            'ip' => $request->ip(),
            'route' => $request->route()?->getName(),
            'type' => $type,
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl()
        ]);

        // For suspicious activity, temporarily block the user
        if ($type === 'destructive' && $user) {
            $this->flagSuspiciousActivity($user, $type);
        }

        // Return appropriate response based on request type
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => $config['message'],
                'retry_after' => $config['decay_minutes'] * 60
            ], 429);
        }

        return response()->view('admin.errors.rate-limit', [
            'message' => $config['message'],
            'retry_after' => $config['decay_minutes']
        ], 429);
    }

    /**
     * Flag suspicious activity
     */
    private function flagSuspiciousActivity($user, string $type): void
    {
        $key = "suspicious_activity:{$user->id}";
        $flags = Cache::get($key, 0) + 1;

        // Temporarily suspend admin access after multiple violations
        if ($flags >= 3) {
            Cache::put("admin_suspended:{$user->id}", true, now()->addHours(1));

            Log::alert('Admin account temporarily suspended for suspicious activity', [
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $type,
                'flags' => $flags
            ]);
        }

        Cache::put($key, $flags, now()->addHours(24));
    }

    /**
     * Log operation for monitoring
     */
    private function logOperation(Request $request, $user, string $type): void
    {
        // Only log significant operations
        if (!in_array($type, ['bulk', 'destructive'])) {
            return;
        }

        Log::info('Admin operation completed', [
            'user_id' => $user?->id,
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'type' => $type,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get remaining attempts for a user
     */
    public static function getRemainingAttempts(Request $request, string $type = 'general'): int
    {
        $user = $request->user();

        if ($user && $user->role === 'super_admin') {
            return 999; // Unlimited for super admins
        }

        $identifier = (new self())->getIdentifier($request, $user);
        $config = self::RATE_LIMITS[$type] ?? self::RATE_LIMITS['api'];
        $key = "rate_limit:{$type}:{$identifier}";
        $attempts = Cache::get($key, 0);

        return max(0, $config['max_attempts'] - $attempts);
    }

    /**
     * Clear rate limit for a user (for testing or admin override)
     */
    public static function clearRateLimit(Request $request, string $type = 'general'): void
    {
        $user = $request->user();
        $identifier = (new self())->getIdentifier($request, $user);
        $key = "rate_limit:{$type}:{$identifier}";

        Cache::forget($key);
    }

    /**
     * Check if user is currently suspended
     */
    public static function isUserSuspended($userId): bool
    {
        return Cache::has("admin_suspended:{$userId}");
    }

    /**
     * Lift suspension for a user
     */
    public static function liftSuspension($userId): void
    {
        Cache::forget("admin_suspended:{$userId}");
        Cache::forget("suspicious_activity:{$userId}");

        Log::info('Admin suspension lifted', [
            'user_id' => $userId,
            'lifted_by' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }
}