<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Exception;

/**
 * Automated Security Response Service
 * Account lockout automation, rate limiting, IP blocking, alert escalation
 * OWASP 2024/2025 Security Compliant | Max 300 Lines | WorkingInstruction.md
 */
class AutomatedSecurityResponseService
{
    private ThreatDetectionEngineService $threatEngine;
    private UserActivityNotificationService $notificationService;
    
    // Response thresholds
    private const LOCKOUT_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 30; // minutes
    private const RATE_LIMIT_THRESHOLD = 60; // requests per minute
    private const IP_BLOCK_THRESHOLD = 100; // suspicious activities
    private const ESCALATION_THRESHOLD = 80; // threat score
    
    // Response levels
    private const RESPONSE_WARNING = 1;
    private const RESPONSE_RATE_LIMIT = 2;
    private const RESPONSE_ACCOUNT_LOCK = 3;
    private const RESPONSE_IP_BLOCK = 4;
    private const RESPONSE_ADMIN_ALERT = 5;

    public function __construct(
        ThreatDetectionEngineService $threatEngine,
        UserActivityNotificationService $notificationService
    ) {
        $this->threatEngine = $threatEngine;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute automated security response
     */
    public function executeSecurityResponse(UserActivity $activity, array $threats): array
    {
        try {
            $responses = [];
            $maxThreatScore = $this->calculateMaxThreatScore($threats);
            
            // Determine response level
            $responseLevel = $this->determineResponseLevel($activity, $threats, $maxThreatScore);
            
            // Execute appropriate responses
            switch ($responseLevel) {
                case self::RESPONSE_IP_BLOCK:
                    $responses[] = $this->blockIpAddress($activity);
                    // Fall through to lower levels
                    
                case self::RESPONSE_ACCOUNT_LOCK:
                    $responses[] = $this->lockUserAccount($activity);
                    // Fall through to lower levels
                    
                case self::RESPONSE_RATE_LIMIT:
                    $responses[] = $this->applyRateLimit($activity);
                    // Fall through to lower levels
                    
                case self::RESPONSE_ADMIN_ALERT:
                    $responses[] = $this->sendAdminAlert($activity, $threats);
                    // Fall through to lower levels
                    
                case self::RESPONSE_WARNING:
                    $responses[] = $this->logSecurityWarning($activity, $threats);
                    break;
            }
            
            // Log all responses
            $this->logSecurityResponse($activity, $responses, $maxThreatScore);
            
            return array_filter($responses);
            
        } catch (Exception $e) {
            Log::error('Security response execution failed', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Determine appropriate response level
     */
    private function determineResponseLevel(UserActivity $activity, array $threats, float $threatScore): int
    {
        $user = $activity->user;
        
        // Critical threats require IP blocking
        if ($threatScore >= 0.9 || $this->hasCriticalThreat($threats)) {
            return self::RESPONSE_IP_BLOCK;
        }
        
        // Account takeover attempts require account lockout
        if ($this->hasAccountTakeoverThreat($threats) || $threatScore >= 0.8) {
            return self::RESPONSE_ACCOUNT_LOCK;
        }
        
        // High velocity or bot activity requires rate limiting
        if ($this->hasVelocityThreat($threats) || $this->hasBotThreat($threats)) {
            return self::RESPONSE_RATE_LIMIT;
        }
        
        // Failed login attempts
        if ($activity->activity_type === 'login_failed') {
            $failedAttempts = $this->getRecentFailedAttempts($user->id);
            if ($failedAttempts >= self::LOCKOUT_FAILED_ATTEMPTS) {
                return self::RESPONSE_ACCOUNT_LOCK;
            }
        }
        
        // Medium threats require admin alerts
        if ($threatScore >= 0.5) {
            return self::RESPONSE_ADMIN_ALERT;
        }
        
        return self::RESPONSE_WARNING;
    }

    /**
     * Block IP address
     */
    private function blockIpAddress(UserActivity $activity): array
    {
        $ipAddress = $activity->ip_address;
        $blockKey = "blocked_ip:{$ipAddress}";
        
        // Block IP for 24 hours
        Cache::put($blockKey, [
            'blocked_at' => now(),
            'reason' => 'Automated security response',
            'activity_id' => $activity->id,
            'user_id' => $activity->user_id
        ], now()->addHours(24));
        
        Log::warning('IP address blocked automatically', [
            'ip_address' => $ipAddress,
            'user_id' => $activity->user_id,
            'activity_id' => $activity->id
        ]);
        
        return [
            'action' => 'ip_blocked',
            'ip_address' => $ipAddress,
            'duration' => '24 hours',
            'timestamp' => now()
        ];
    }

    /**
     * Lock user account
     */
    private function lockUserAccount(UserActivity $activity): array
    {
        $user = $activity->user;
        $lockKey = "locked_account:{$user->id}";
        
        // Lock account for specified duration
        Cache::put($lockKey, [
            'locked_at' => now(),
            'reason' => 'Automated security response',
            'activity_id' => $activity->id,
            'unlock_at' => now()->addMinutes(self::LOCKOUT_DURATION)
        ], now()->addMinutes(self::LOCKOUT_DURATION));
        
        // Update user status if necessary
        $user->update([
            'account_locked_at' => now(),
            'account_lock_reason' => 'Automated security response'
        ]);
        
        // Send unlock email to user
        $this->sendAccountLockNotification($user);
        
        Log::warning('User account locked automatically', [
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'duration' => self::LOCKOUT_DURATION . ' minutes'
        ]);
        
        return [
            'action' => 'account_locked',
            'user_id' => $user->id,
            'duration' => self::LOCKOUT_DURATION . ' minutes',
            'unlock_at' => now()->addMinutes(self::LOCKOUT_DURATION),
            'timestamp' => now()
        ];
    }

    /**
     * Apply rate limiting
     */
    private function applyRateLimit(UserActivity $activity): array
    {
        $rateLimitKey = "rate_limit:{$activity->user_id}:{$activity->ip_address}";
        
        // Apply rate limit for 1 hour
        Cache::put($rateLimitKey, [
            'limited_at' => now(),
            'requests_allowed' => 10, // Reduced limit
            'window_minutes' => 60,
            'activity_id' => $activity->id
        ], now()->addHour());
        
        Log::info('Rate limit applied automatically', [
            'user_id' => $activity->user_id,
            'ip_address' => $activity->ip_address,
            'activity_id' => $activity->id
        ]);
        
        return [
            'action' => 'rate_limited',
            'user_id' => $activity->user_id,
            'ip_address' => $activity->ip_address,
            'limit' => '10 requests per hour',
            'duration' => '1 hour',
            'timestamp' => now()
        ];
    }

    /**
     * Send admin alert
     */
    private function sendAdminAlert(UserActivity $activity, array $threats): array
    {
        $alertData = [
            'type' => 'automated_security_alert',
            'severity' => 'high',
            'message' => 'Automated security response triggered',
            'data' => [
                'activity_id' => $activity->id,
                'user_id' => $activity->user_id,
                'threats' => $threats,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent
            ]
        ];
        
        $this->notificationService->sendNotifications($activity->user, [$alertData]);
        
        return [
            'action' => 'admin_alert_sent',
            'alert_type' => 'automated_security_response',
            'threats_count' => count($threats),
            'timestamp' => now()
        ];
    }

    /**
     * Log security warning
     */
    private function logSecurityWarning(UserActivity $activity, array $threats): array
    {
        Log::warning('Security threats detected', [
            'user_id' => $activity->user_id,
            'activity_id' => $activity->id,
            'threats' => $threats,
            'ip_address' => $activity->ip_address
        ]);
        
        return [
            'action' => 'security_warning_logged',
            'threats_count' => count($threats),
            'timestamp' => now()
        ];
    }

    /**
     * Send account lock notification to user
     */
    private function sendAccountLockNotification(User $user): void
    {
        try {
            $data = [
                'user' => $user,
                'unlock_time' => now()->addMinutes(self::LOCKOUT_DURATION),
                'contact_support' => config('app.support_email', 'support@noobzcinema.com')
            ];
            
            Mail::send('emails.account-locked', $data, function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Account Temporarily Locked - Security Alert')
                        ->priority(2);
            });
            
        } catch (Exception $e) {
            Log::error('Failed to send account lock notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if account/IP is currently blocked
     */
    public function isBlocked(string $identifier, string $type = 'ip'): array
    {
        $key = "blocked_{$type}:{$identifier}";
        $blockData = Cache::get($key);
        
        if ($blockData) {
            return [
                'blocked' => true,
                'blocked_at' => $blockData['blocked_at'],
                'reason' => $blockData['reason'],
                'expires_at' => now()->addSeconds(Cache::getStore()->ttl($key))
            ];
        }
        
        return ['blocked' => false];
    }

    /**
     * Manually unblock IP or account
     */
    public function unblock(string $identifier, string $type = 'ip', string $reason = 'Manual override'): bool
    {
        $key = "blocked_{$type}:{$identifier}";
        
        if (Cache::forget($key)) {
            Log::info("Manual unblock executed", [
                'type' => $type,
                'identifier' => $identifier,
                'reason' => $reason,
                'admin_action' => true
            ]);
            return true;
        }
        
        return false;
    }

    /**
     * Get recent failed login attempts
     */
    private function getRecentFailedAttempts(int $userId): int
    {
        return UserActivity::where('user_id', $userId)
            ->where('activity_type', 'login_failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }

    /**
     * Helper methods for threat analysis
     */
    private function calculateMaxThreatScore(array $threats): float
    {
        if (empty($threats)) {
            return 0;
        }
        
        return max(array_column($threats, 'score'));
    }

    private function hasCriticalThreat(array $threats): bool
    {
        foreach ($threats as $threat) {
            if (isset($threat['severity']) && $threat['severity'] === 'critical') {
                return true;
            }
        }
        return false;
    }

    private function hasAccountTakeoverThreat(array $threats): bool
    {
        foreach ($threats as $threat) {
            if (isset($threat['type']) && $threat['type'] === 'account_takeover') {
                return true;
            }
        }
        return false;
    }

    private function hasVelocityThreat(array $threats): bool
    {
        foreach ($threats as $threat) {
            if (isset($threat['type']) && $threat['type'] === 'high_velocity_attack') {
                return true;
            }
        }
        return false;
    }

    private function hasBotThreat(array $threats): bool
    {
        foreach ($threats as $threat) {
            if (isset($threat['type']) && $threat['type'] === 'bot_activity') {
                return true;
            }
        }
        return false;
    }

    private function logSecurityResponse(UserActivity $activity, array $responses, float $threatScore): void
    {
        Log::info('Automated security response executed', [
            'activity_id' => $activity->id,
            'user_id' => $activity->user_id,
            'threat_score' => $threatScore,
            'responses' => $responses,
            'timestamp' => now()
        ]);
    }
}