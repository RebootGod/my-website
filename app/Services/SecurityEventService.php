<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

/**
 * ========================================
 * SECURITY EVENT LOGGING SERVICE
 * Comprehensive security event monitoring and alerting
 * OWASP A09 - Security Logging & Monitoring Implementation
 * ========================================
 */
class SecurityEventService
{
    // Security Event Types
    const EVENT_BRUTE_FORCE_ATTEMPT = 'brute_force_attempt';
    const EVENT_SUSPICIOUS_LOGIN = 'suspicious_login';
    const EVENT_RATE_LIMIT_HIT = 'rate_limit_hit';
    const EVENT_INJECTION_ATTEMPT = 'injection_attempt';
    const EVENT_XSS_ATTEMPT = 'xss_attempt';
    const EVENT_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const EVENT_PASSWORD_RESET_ABUSE = 'password_reset_abuse';
    const EVENT_SESSION_HIJACKING = 'session_hijacking_attempt';
    const EVENT_ADMIN_ACCESS = 'admin_access';
    const EVENT_PRIVILEGE_ESCALATION = 'privilege_escalation_attempt';
    const EVENT_DATA_EXFILTRATION = 'data_exfiltration_attempt';
    const EVENT_SUSPICIOUS_USER_AGENT = 'suspicious_user_agent';
    
    // Severity Levels
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    /**
     * Log a security event with comprehensive context
     */
    public function logSecurityEvent(
        string $eventType,
        string $severity,
        string $description,
        array $metadata = [],
        ?int $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        bool $requiresAlert = false
    ): void {
        $ipAddress = $ipAddress ?? request()->ip();
        $userAgent = $userAgent ?? request()->userAgent();
        
        // Enrich metadata with security context
        $enrichedMetadata = array_merge($metadata, [
            'event_timestamp' => now()->toISOString(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method(),
            'session_id' => session()->getId(),
            'user_id' => $userId,
            'severity' => $severity,
            'requires_alert' => $requiresAlert,
            'geoip_country' => $this->getCountryFromIP($ipAddress),
        ]);
        
        // Log to UserActivity for database persistence
        UserActivity::create([
            'user_id' => $userId,
            'activity_type' => $eventType,
            'description' => $description,
            'metadata' => $enrichedMetadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'activity_at' => now(),
        ]);
        
        // Log to application logs with structured data
        Log::channel('security')->warning("Security Event: {$eventType}", [
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'metadata' => $enrichedMetadata,
        ]);
        
        // Track suspicious IPs for monitoring
        $this->trackSuspiciousIP($ipAddress, $eventType, $severity);
        
        // Send alerts for critical events
        if ($requiresAlert || $severity === self::SEVERITY_CRITICAL) {
            $this->sendSecurityAlert($eventType, $severity, $description, $enrichedMetadata);
        }
    }
    
    /**
     * Log brute force attempt with escalation tracking
     */
    public function logBruteForceAttempt(string $username, string $ipAddress, string $reason): void
    {
        $attempts = $this->getBruteForceAttempts($ipAddress, $username);
        
        $this->logSecurityEvent(
            self::EVENT_BRUTE_FORCE_ATTEMPT,
            $attempts > 10 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
            "Brute force attempt detected for username '{$username}' from {$ipAddress}",
            [
                'attempted_username' => $username,
                'failure_reason' => $reason,
                'total_attempts' => $attempts,
                'time_window' => '1 hour',
            ],
            null,
            $ipAddress,
            null,
            $attempts > 15 // Alert after 15 attempts
        );
    }
    
    /**
     * Log injection attempt (SQL, XSS, NoSQL, etc.)
     */
    public function logInjectionAttempt(string $injectionType, string $payload, string $field): void
    {
        $this->logSecurityEvent(
            self::EVENT_INJECTION_ATTEMPT,
            self::SEVERITY_HIGH,
            "Injection attempt detected: {$injectionType} in field '{$field}'",
            [
                'injection_type' => $injectionType,
                'payload_sample' => substr($payload, 0, 500), // Limit payload size
                'field_name' => $field,
                'payload_length' => strlen($payload),
                'blocked' => true,
            ],
            auth()->id(),
            null,
            null,
            true // Always alert on injection attempts
        );
    }
    
    /**
     * Log unauthorized access attempt
     */
    public function logUnauthorizedAccess(string $resource, string $action): void
    {
        $this->logSecurityEvent(
            self::EVENT_UNAUTHORIZED_ACCESS,
            self::SEVERITY_MEDIUM,
            "Unauthorized access attempt to {$resource}",
            [
                'resource' => $resource,
                'action' => $action,
                'user_role' => auth()->user()?->role ?? 'guest',
                'required_permission' => $action,
            ],
            auth()->id()
        );
    }
    
    /**
     * Log suspicious login patterns
     */
    public function logSuspiciousLogin(User $user, string $reason): void
    {
        $this->logSecurityEvent(
            self::EVENT_SUSPICIOUS_LOGIN,
            self::SEVERITY_MEDIUM,
            "Suspicious login detected for user '{$user->username}'",
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'suspicion_reason' => $reason,
                'last_login_ip' => $user->last_login_ip,
                'current_ip' => request()->ip(),
                'time_since_last_login' => $user->last_login_at ? 
                    $user->last_login_at->diffInMinutes(now()) . ' minutes' : 'never',
            ],
            $user->id
        );
    }
    
    /**
     * Log admin access with enhanced monitoring
     */
    public function logAdminAccess(User $user, string $action, string $resource = null): void
    {
        $this->logSecurityEvent(
            self::EVENT_ADMIN_ACCESS,
            self::SEVERITY_MEDIUM,
            "Admin access: {$user->username} performed '{$action}'" . 
            ($resource ? " on {$resource}" : ""),
            [
                'admin_user_id' => $user->id,
                'admin_username' => $user->username,
                'admin_role' => $user->role,
                'action' => $action,
                'resource' => $resource,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ],
            $user->id
        );
    }
    
    /**
     * Track and analyze IP reputation
     */
    private function trackSuspiciousIP(string $ipAddress, string $eventType, string $severity): void
    {
        $cacheKey = "suspicious_ip:{$ipAddress}";
        $data = Cache::get($cacheKey, [
            'events' => [],
            'score' => 0,
            'first_seen' => now()->toISOString(),
        ]);
        
        // Add new event
        $data['events'][] = [
            'type' => $eventType,
            'severity' => $severity,
            'timestamp' => now()->toISOString(),
        ];
        
        // Calculate threat score
        $data['score'] += $this->calculateThreatScore($eventType, $severity);
        $data['last_seen'] = now()->toISOString();
        
        // Cache for 24 hours
        Cache::put($cacheKey, $data, now()->addHours(24));
        
        // Auto-block highly suspicious IPs
        if ($data['score'] >= 100) {
            $this->flagHighRiskIP($ipAddress, $data);
        }
    }
    
    /**
     * Calculate threat score based on event type and severity
     */
    private function calculateThreatScore(string $eventType, string $severity): int
    {
        $baseScores = [
            self::EVENT_INJECTION_ATTEMPT => 25,
            self::EVENT_BRUTE_FORCE_ATTEMPT => 10,
            self::EVENT_UNAUTHORIZED_ACCESS => 15,
            self::EVENT_XSS_ATTEMPT => 20,
            self::EVENT_RATE_LIMIT_HIT => 5,
        ];
        
        $severityMultipliers = [
            self::SEVERITY_LOW => 1,
            self::SEVERITY_MEDIUM => 2,
            self::SEVERITY_HIGH => 3,
            self::SEVERITY_CRITICAL => 5,
        ];
        
        $baseScore = $baseScores[$eventType] ?? 5;
        $multiplier = $severityMultipliers[$severity] ?? 1;
        
        return $baseScore * $multiplier;
    }
    
    /**
     * Get brute force attempt count for IP/username combination
     */
    private function getBruteForceAttempts(string $ipAddress, string $username): int
    {
        return UserActivity::where('activity_type', UserActivity::TYPE_LOGIN_FAILED)
            ->where('ip_address', $ipAddress)
            ->whereJsonContains('metadata->attempted_username', $username)
            ->where('activity_at', '>=', now()->subHour())
            ->count();
    }
    
    /**
     * Get country from IP (basic implementation)
     */
    private function getCountryFromIP(string $ipAddress): ?string
    {
        // Basic IP geolocation - in production, use proper GeoIP service
        if (str_starts_with($ipAddress, '192.168.') || 
            str_starts_with($ipAddress, '10.') || 
            str_starts_with($ipAddress, '172.') ||
            $ipAddress === '127.0.0.1') {
            return 'local';
        }
        
        return null; // Implement proper GeoIP service in production
    }
    
    /**
     * Flag high-risk IP for enhanced monitoring
     */
    private function flagHighRiskIP(string $ipAddress, array $data): void
    {
        Log::channel('security')->critical("High-risk IP detected", [
            'ip_address' => $ipAddress,
            'threat_score' => $data['score'],
            'event_count' => count($data['events']),
            'first_seen' => $data['first_seen'],
            'recommendation' => 'Consider IP blocking or enhanced monitoring',
        ]);
        
        // Cache high-risk IP list
        $highRiskIPs = Cache::get('high_risk_ips', []);
        $highRiskIPs[$ipAddress] = [
            'score' => $data['score'],
            'flagged_at' => now()->toISOString(),
        ];
        Cache::put('high_risk_ips', $highRiskIPs, now()->addDays(7));
    }
    
    /**
     * Send security alert (email, Slack, etc.)
     */
    private function sendSecurityAlert(string $eventType, string $severity, string $description, array $metadata): void
    {
        // Log alert for now - in production, implement email/Slack notifications
        Log::channel('security')->alert("Security Alert Required", [
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'metadata' => $metadata,
            'alert_timestamp' => now()->toISOString(),
        ]);
    }
    
    /**
     * Get security dashboard data
     */
    public function getSecurityDashboardData(): array
    {
        return [
            'failed_logins_24h' => $this->getFailedLoginCount(24),
            'injection_attempts_24h' => $this->getInjectionAttemptCount(24),
            'suspicious_ips' => $this->getSuspiciousIPs(),
            'high_risk_events' => $this->getHighRiskEvents(),
            'security_score' => $this->calculateSecurityScore(),
        ];
    }
    
    /**
     * Get failed login count for specified hours
     */
    private function getFailedLoginCount(int $hours): int
    {
        return UserActivity::where('activity_type', UserActivity::TYPE_LOGIN_FAILED)
            ->where('activity_at', '>=', now()->subHours($hours))
            ->count();
    }
    
    /**
     * Get injection attempt count
     */
    private function getInjectionAttemptCount(int $hours): int
    {
        return UserActivity::where('activity_type', self::EVENT_INJECTION_ATTEMPT)
            ->where('activity_at', '>=', now()->subHours($hours))
            ->count();
    }
    
    /**
     * Get list of suspicious IPs
     */
    private function getSuspiciousIPs(): array
    {
        return Cache::get('high_risk_ips', []);
    }
    
    /**
     * Get high-risk security events
     */
    private function getHighRiskEvents(): array
    {
        return UserActivity::whereIn('activity_type', [
                self::EVENT_INJECTION_ATTEMPT,
                self::EVENT_UNAUTHORIZED_ACCESS,
                self::EVENT_BRUTE_FORCE_ATTEMPT,
            ])
            ->where('activity_at', '>=', now()->subHours(24))
            ->orderBy('activity_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }
    
    /**
     * Calculate overall security score
     */
    private function calculateSecurityScore(): int
    {
        $baseScore = 100;
        
        // Deduct points for recent security events
        $recentEvents = UserActivity::where('activity_at', '>=', now()->subHours(24))
            ->whereIn('activity_type', [
                self::EVENT_INJECTION_ATTEMPT,
                self::EVENT_BRUTE_FORCE_ATTEMPT,
                self::EVENT_UNAUTHORIZED_ACCESS,
            ])
            ->count();
        
        return max(0, $baseScore - ($recentEvents * 5));
    }
}