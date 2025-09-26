<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ========================================
 * PASSWORD RESET SERVICE
 * Secure password reset functionality with rate limiting
 * ========================================
 */
class PasswordResetService
{
    const RATE_LIMIT_ATTEMPTS = 5; // Max attempts per hour
    const RATE_LIMIT_DURATION = 3600; // 1 hour in seconds
    const TOKEN_EXPIRY_HOURS = 1; // Token expires in 1 hour
    const CACHE_DURATION = 1800; // 30 minutes

    private UserActivityService $activityService;

    public function __construct(UserActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Send password reset email
     */
    public function sendResetEmail(string $email, string $ipAddress): array
    {
        // Rate limiting check
        if (!$this->checkRateLimit($email, $ipAddress)) {
            $this->activityService->logActivity(
                null,
                'password_reset_blocked',
                "Rate limit exceeded for password reset attempt: {$email}",
                [
                    'email' => $email,
                    'ip_address' => $ipAddress,
                    'reason' => 'rate_limit_exceeded'
                ],
                $ipAddress
            );

            return [
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 1 jam.',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ];
        }

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Log failed attempt but don't reveal user doesn't exist
            $this->activityService->logActivity(
                null,
                'password_reset_failed',
                "Password reset attempted for non-existent email: {$email}",
                [
                    'email' => $email,
                    'ip_address' => $ipAddress,
                    'reason' => 'user_not_found'
                ],
                $ipAddress
            );

            // Increment rate limit counter even for invalid emails
            $this->incrementRateLimit($email, $ipAddress);

            // Always return success to prevent email enumeration
            return [
                'success' => true,
                'message' => 'Jika email terdaftar, link reset password telah dikirim.',
                'debug_info' => config('app.debug') ? 'User not found' : null
            ];
        }

        // Check if user account is active
        if ($user->status !== 'active') {
            $this->activityService->logActivity(
                $user->id,
                'password_reset_blocked',
                "Password reset blocked for suspended/banned user: {$user->username}",
                [
                    'email' => $email,
                    'username' => $user->username,
                    'user_status' => $user->status,
                    'ip_address' => $ipAddress
                ],
                $ipAddress
            );

            $this->incrementRateLimit($email, $ipAddress);

            return [
                'success' => false,
                'message' => 'Akun tidak aktif. Hubungi administrator.',
                'error_code' => 'ACCOUNT_SUSPENDED'
            ];
        }

        // Generate secure token
        $token = $this->generateSecureToken();

        // Store token in database
        $this->storeResetToken($email, $token);

        // Log successful reset request
        $this->activityService->logActivity(
            $user->id,
            'password_reset_requested',
            "Password reset requested for user: {$user->username}",
            [
                'email' => $email,
                'username' => $user->username,
                'ip_address' => $ipAddress,
                'token_created' => now()
            ],
            $ipAddress
        );

        // Increment rate limit counter
        $this->incrementRateLimit($email, $ipAddress);

        // Send email notification (will be handled by notification system)
        $user->notify(new \App\Notifications\ResetPasswordNotification($token));

        return [
            'success' => true,
            'message' => 'Link reset password telah dikirim ke email Anda.',
            'expires_in' => self::TOKEN_EXPIRY_HOURS . ' jam'
        ];
    }

    /**
     * Reset password using token
     */
    public function resetPassword(string $token, string $email, string $newPassword, string $ipAddress): array
    {
        // Validate token
        $tokenData = $this->validateResetToken($token, $email);

        if (!$tokenData['valid']) {
            return [
                'success' => false,
                'message' => $tokenData['message'],
                'error_code' => $tokenData['error_code']
            ];
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan.',
                'error_code' => 'USER_NOT_FOUND'
            ];
        }

        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
            'remember_token' => null // Invalidate all remember tokens
        ]);

        // Delete used token
        $this->deleteResetToken($email);

        // Log successful password reset
        $this->activityService->logActivity(
            $user->id,
            'password_reset_success',
            "Password successfully reset for user: {$user->username}",
            [
                'email' => $email,
                'username' => $user->username,
                'ip_address' => $ipAddress,
                'reset_at' => now()
            ],
            $ipAddress
        );

        // Clear rate limiting for this email after successful reset
        $this->clearRateLimit($email, $ipAddress);

        return [
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            'user' => [
                'username' => $user->username,
                'email' => $user->email
            ]
        ];
    }

    /**
     * Generate cryptographically secure token
     */
    private function generateSecureToken(): string
    {
        return hash('sha256', Str::random(60) . time() . random_bytes(32));
    }

    /**
     * Store password reset token
     */
    private function storeResetToken(string $email, string $token): void
    {
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );
    }

    /**
     * Validate reset token
     */
    private function validateResetToken(string $token, string $email): array
    {
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$tokenData) {
            $this->activityService->logActivity(
                null,
                'password_reset_failed',
                "Invalid token used for password reset: {$email}",
                [
                    'email' => $email,
                    'reason' => 'token_not_found',
                    'attempted_at' => now()
                ]
            );

            return [
                'valid' => false,
                'message' => 'Token tidak valid atau sudah digunakan.',
                'error_code' => 'INVALID_TOKEN'
            ];
        }

        // Check if token has expired
        $createdAt = Carbon::parse($tokenData->created_at);
        if ($createdAt->addHours(self::TOKEN_EXPIRY_HOURS)->isPast()) {
            // Delete expired token
            $this->deleteResetToken($email);

            $this->activityService->logActivity(
                null,
                'password_reset_failed',
                "Expired token used for password reset: {$email}",
                [
                    'email' => $email,
                    'reason' => 'token_expired',
                    'created_at' => $tokenData->created_at,
                    'attempted_at' => now()
                ]
            );

            return [
                'valid' => false,
                'message' => 'Token sudah expired. Silakan request reset password baru.',
                'error_code' => 'TOKEN_EXPIRED'
            ];
        }

        // Verify token hash
        if (!Hash::check($token, $tokenData->token)) {
            $this->activityService->logActivity(
                null,
                'password_reset_failed',
                "Invalid token hash for password reset: {$email}",
                [
                    'email' => $email,
                    'reason' => 'token_hash_mismatch',
                    'attempted_at' => now()
                ]
            );

            return [
                'valid' => false,
                'message' => 'Token tidak valid.',
                'error_code' => 'INVALID_TOKEN'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Token valid',
            'token_data' => $tokenData
        ];
    }

    /**
     * Delete reset token after use
     */
    private function deleteResetToken(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit(string $email, string $ipAddress): bool
    {
        $emailKey = "pwd_reset_email:" . $email;
        $ipKey = "pwd_reset_ip:" . $ipAddress;

        $emailAttempts = Cache::get($emailKey, 0);
        $ipAttempts = Cache::get($ipKey, 0);

        return $emailAttempts < self::RATE_LIMIT_ATTEMPTS && $ipAttempts < self::RATE_LIMIT_ATTEMPTS;
    }

    /**
     * Increment rate limit counters
     */
    private function incrementRateLimit(string $email, string $ipAddress): void
    {
        $emailKey = "pwd_reset_email:" . $email;
        $ipKey = "pwd_reset_ip:" . $ipAddress;

        Cache::put($emailKey, Cache::get($emailKey, 0) + 1, self::RATE_LIMIT_DURATION);
        Cache::put($ipKey, Cache::get($ipKey, 0) + 1, self::RATE_LIMIT_DURATION);
    }

    /**
     * Clear rate limiting after successful reset
     */
    private function clearRateLimit(string $email, string $ipAddress): void
    {
        $emailKey = "pwd_reset_email:" . $email;
        $ipKey = "pwd_reset_ip:" . $ipAddress;

        Cache::forget($emailKey);
        Cache::forget($ipKey);
    }

    /**
     * Get remaining attempts for rate limiting
     */
    public function getRemainingAttempts(string $email, string $ipAddress): array
    {
        $emailKey = "pwd_reset_email:" . $email;
        $ipKey = "pwd_reset_ip:" . $ipAddress;

        $emailAttempts = Cache::get($emailKey, 0);
        $ipAttempts = Cache::get($ipKey, 0);

        $emailTtl = Cache::store()->getRedis()->ttl($emailKey);
        $ipTtl = Cache::store()->getRedis()->ttl($ipKey);

        return [
            'email_attempts_remaining' => max(0, self::RATE_LIMIT_ATTEMPTS - $emailAttempts),
            'ip_attempts_remaining' => max(0, self::RATE_LIMIT_ATTEMPTS - $ipAttempts),
            'reset_in_seconds' => max($emailTtl, $ipTtl),
            'can_attempt' => $this->checkRateLimit($email, $ipAddress)
        ];
    }

    /**
     * Clean up expired tokens (to be called by scheduler)
     */
    public function cleanupExpiredTokens(): int
    {
        $expiredTime = now()->subHours(self::TOKEN_EXPIRY_HOURS);

        return DB::table('password_reset_tokens')
            ->where('created_at', '<', $expiredTime)
            ->delete();
    }

    /**
     * Get password reset statistics
     */
    public function getResetStatistics(int $days = 7): array
    {
        $cacheKey = "pwd_reset_stats_{$days}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($days) {
            $startDate = now()->subDays($days);

            return [
                'total_requests' => $this->activityService->getActivitiesByType('password_reset_requested', 1000)->where('activity_at', '>=', $startDate)->count(),
                'successful_resets' => $this->activityService->getActivitiesByType('password_reset_success', 1000)->where('activity_at', '>=', $startDate)->count(),
                'failed_attempts' => $this->activityService->getActivitiesByType('password_reset_failed', 1000)->where('activity_at', '>=', $startDate)->count(),
                'blocked_attempts' => $this->activityService->getActivitiesByType('password_reset_blocked', 1000)->where('activity_at', '>=', $startDate)->count(),
                'active_tokens' => DB::table('password_reset_tokens')->count(),
                'period_days' => $days
            ];
        });
    }
}