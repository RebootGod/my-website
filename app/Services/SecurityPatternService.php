<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ========================================
 * SECURITY PATTERN ANALYSIS SERVICE
 * Advanced user behavior analysis and business logic security pattern detection
 * Following workinginstruction.md: Separate file for security pattern functionality
 * ========================================
 */
class SecurityPatternService
{
    // Pattern detection thresholds
    private const ACCOUNT_ENUM_THRESHOLD = 10; // Failed login attempts within timeframe
    private const PRIVILEGE_ESCALATION_WINDOW = 300; // 5 minutes
    private const DATA_ACCESS_THRESHOLD = 100; // Records accessed in short time
    private const SUSPICIOUS_PATTERN_WINDOW = 600; // 10 minutes
    
    /**
     * Analyze user behavior patterns for security violations
     * 
     * @param User $user
     * @param Request $request
     * @return array Behavior analysis result
     */
    public function analyzeUserBehavior(User $user, Request $request): array
    {
        $analysis = [
            'user_id' => $user->id,
            'analysis_timestamp' => now()->toISOString(),
            'patterns_detected' => [],
            'risk_score' => 0,
            'recommendations' => [],
        ];
        
        // Check account enumeration attempts
        $accountEnumResult = $this->detectAccountEnumeration($user->email, $request->ip());
        if ($accountEnumResult['detected']) {
            $analysis['patterns_detected'][] = $accountEnumResult;
            $analysis['risk_score'] += 30;
        }
        
        // Check privilege escalation attempts
        $privilegeResult = $this->detectPrivilegeEscalation($user, $request);
        if ($privilegeResult['detected']) {
            $analysis['patterns_detected'][] = $privilegeResult;
            $analysis['risk_score'] += 50;
        }
        
        // Check data access patterns
        $dataAccessResult = $this->analyzeDataAccessPatterns($user);
        if ($dataAccessResult['suspicious']) {
            $analysis['patterns_detected'][] = $dataAccessResult;
            $analysis['risk_score'] += 25;
        }
        
        // Check session anomalies
        $sessionResult = $this->detectSessionAnomalies($user, $request);
        if ($sessionResult['anomalies_detected']) {
            $analysis['patterns_detected'][] = $sessionResult;
            $analysis['risk_score'] += 20;
        }
        
        // Generate recommendations
        $analysis['recommendations'] = $this->generateSecurityRecommendations($analysis);
        $analysis['risk_level'] = $this->calculateRiskLevel($analysis['risk_score']);
        
        return $analysis;
    }
    
    /**
     * Detect account enumeration attempts
     * 
     * @param string $email
     * @param string $ipAddress
     * @return array Detection result
     */
    public function detectAccountEnumeration(string $email, string $ipAddress): array
    {
        $cacheKey = "account_enum:{$ipAddress}";
        $attempts = Cache::get($cacheKey, []);
        
        // Count recent failed login attempts
        $recentAttempts = collect($attempts)->filter(function ($attempt) {
            return $attempt['timestamp'] > now()->subMinutes(15)->timestamp;
        })->count();
        
        $detected = $recentAttempts >= self::ACCOUNT_ENUM_THRESHOLD;
        
        if ($detected) {
            Log::channel('security')->warning('Account Enumeration Detected', [
                'email' => $email,
                'ip_address' => $ipAddress,
                'recent_attempts' => $recentAttempts,
                'threshold' => self::ACCOUNT_ENUM_THRESHOLD,
            ]);
        }
        
        return [
            'pattern_type' => 'account_enumeration',
            'detected' => $detected,
            'attempt_count' => $recentAttempts,
            'threshold' => self::ACCOUNT_ENUM_THRESHOLD,
            'severity' => $detected ? 'high' : 'low',
            'details' => [
                'target_email' => $email,
                'source_ip' => $ipAddress,
                'window_minutes' => 15,
            ],
        ];
    }
    
    /**
     * Track failed login attempt for enumeration detection
     * 
     * @param string $email
     * @param string $ipAddress
     * @return void
     */
    public function trackFailedLoginAttempt(string $email, string $ipAddress): void
    {
        $cacheKey = "account_enum:{$ipAddress}";
        $attempts = Cache::get($cacheKey, []);
        
        $attempts[] = [
            'email' => $email,
            'timestamp' => now()->timestamp,
            'ip_address' => $ipAddress,
        ];
        
        // Keep last 50 attempts
        $attempts = array_slice($attempts, -50);
        
        // Cache for 1 hour
        Cache::put($cacheKey, $attempts, 3600);
    }
    
    /**
     * Detect privilege escalation attempts
     * 
     * @param User $user
     * @param Request $request
     * @return array Detection result
     */
    public function detectPrivilegeEscalation(User $user, Request $request): array
    {
        $cacheKey = "privilege_check:{$user->id}";
        $recentActions = Cache::get($cacheKey, []);
        
        // Look for rapid role/permission changes or admin area access
        $suspiciousActions = collect($recentActions)->filter(function ($action) {
            return in_array($action['type'], [
                'role_change_attempt',
                'admin_area_access',
                'permission_denied',
                'unauthorized_function_call'
            ]) && $action['timestamp'] > now()->subSeconds(self::PRIVILEGE_ESCALATION_WINDOW)->timestamp;
        });
        
        $detected = $suspiciousActions->count() >= 3;
        
        // Check for admin route access without proper role
        $isAdminRoute = str_contains($request->getPathInfo(), '/admin');
        $hasAdminRole = $user->isAdmin() || $user->isSuperAdmin();
        
        if ($isAdminRoute && !$hasAdminRole) {
            $detected = true;
            $this->trackPrivilegeAction($user->id, 'unauthorized_admin_access', [
                'route' => $request->getPathInfo(),
                'user_role' => $user->role,
                'role_name' => is_object($user->role) ? $user->role->name : $user->role,
            ]);
        }
        
        return [
            'pattern_type' => 'privilege_escalation',
            'detected' => $detected,
            'suspicious_action_count' => $suspiciousActions->count(),
            'severity' => $detected ? 'critical' : 'low',
            'details' => [
                'user_id' => $user->id,
                'current_role' => is_object($user->role) ? $user->role->name : $user->role,
                'is_admin' => $user->isAdmin(),
                'is_super_admin' => $user->isSuperAdmin(),
                'suspicious_actions' => $suspiciousActions->toArray(),
                'admin_route_attempt' => $isAdminRoute && !$hasAdminRole,
            ],
        ];
    }
    
    /**
     * Track privilege-related action
     * 
     * @param int $userId
     * @param string $actionType
     * @param array $metadata
     * @return void
     */
    public function trackPrivilegeAction(int $userId, string $actionType, array $metadata = []): void
    {
        $cacheKey = "privilege_check:{$userId}";
        $actions = Cache::get($cacheKey, []);
        
        $actions[] = [
            'type' => $actionType,
            'timestamp' => now()->timestamp,
            'metadata' => $metadata,
        ];
        
        // Keep last 20 actions
        $actions = array_slice($actions, -20);
        
        // Cache for 1 hour
        Cache::put($cacheKey, $actions, 3600);
    }
    
    /**
     * Analyze data access patterns for suspicious behavior
     * 
     * @param User $user
     * @return array Analysis result
     */
    public function analyzeDataAccessPatterns(User $user): array
    {
        $cacheKey = "data_access:{$user->id}";
        $accessLog = Cache::get($cacheKey, []);
        
        $recentAccess = collect($accessLog)->filter(function ($access) {
            return $access['timestamp'] > now()->subMinutes(10)->timestamp;
        });
        
        $totalRecords = $recentAccess->sum('record_count');
        $uniqueResources = $recentAccess->pluck('resource_type')->unique()->count();
        
        // Detect mass data access
        $massAccess = $totalRecords > self::DATA_ACCESS_THRESHOLD;
        
        // Detect resource enumeration
        $resourceEnumeration = $uniqueResources > 10;
        
        // Detect rapid sequential access
        $rapidAccess = $recentAccess->count() > 50;
        
        $suspicious = $massAccess || $resourceEnumeration || $rapidAccess;
        
        return [
            'pattern_type' => 'data_access_analysis',
            'suspicious' => $suspicious,
            'total_records_accessed' => $totalRecords,
            'unique_resources' => $uniqueResources,
            'access_frequency' => $recentAccess->count(),
            'severity' => $suspicious ? 'medium' : 'low',
            'flags' => [
                'mass_access' => $massAccess,
                'resource_enumeration' => $resourceEnumeration,
                'rapid_access' => $rapidAccess,
            ],
            'details' => [
                'user_id' => $user->id,
                'time_window_minutes' => 10,
                'threshold_records' => self::DATA_ACCESS_THRESHOLD,
            ],
        ];
    }
    
    /**
     * Track data access for pattern analysis
     * 
     * @param int $userId
     * @param string $resourceType
     * @param int $recordCount
     * @param array $metadata
     * @return void
     */
    public function trackDataAccess(int $userId, string $resourceType, int $recordCount = 1, array $metadata = []): void
    {
        $cacheKey = "data_access:{$userId}";
        $accessLog = Cache::get($cacheKey, []);
        
        $accessLog[] = [
            'resource_type' => $resourceType,
            'record_count' => $recordCount,
            'timestamp' => now()->timestamp,
            'metadata' => $metadata,
        ];
        
        // Keep last 100 access records
        $accessLog = array_slice($accessLog, -100);
        
        // Cache for 2 hours
        Cache::put($cacheKey, $accessLog, 7200);
    }
    
    /**
     * Detect session anomalies
     * 
     * @param User $user
     * @param Request $request
     * @return array Detection result
     */
    public function detectSessionAnomalies(User $user, Request $request): array
    {
        $anomalies = [];
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();
        
        // Get user's typical session patterns
        $sessionHistory = $this->getUserSessionHistory($user->id);
        
        // Check for user agent changes
        if ($this->isUserAgentAnomalous($user->id, $userAgent, $sessionHistory)) {
            $anomalies[] = [
                'type' => 'user_agent_change',
                'current' => $userAgent,
                'expected_pattern' => $this->getMostCommonUserAgent($sessionHistory),
                'severity' => 'medium',
            ];
        }
        
        // Check for geographic anomalies (if available)
        if ($this->isGeographicAnomalous($user->id, $ipAddress, $sessionHistory)) {
            $anomalies[] = [
                'type' => 'geographic_anomaly',
                'current_ip' => $ipAddress,
                'severity' => 'high',
            ];
        }
        
        // Check for time-based anomalies
        if ($this->isTimeAnomalous($user->id, $sessionHistory)) {
            $anomalies[] = [
                'type' => 'unusual_access_time',
                'current_time' => now()->format('H:i'),
                'typical_hours' => $this->getTypicalAccessHours($sessionHistory),
                'severity' => 'low',
            ];
        }
        
        return [
            'pattern_type' => 'session_anomalies',
            'anomalies_detected' => !empty($anomalies),
            'anomaly_count' => count($anomalies),
            'anomalies' => $anomalies,
            'severity' => $this->getMaxAnomalySeverity($anomalies),
        ];
    }
    
    /**
     * Track user session for anomaly detection
     * 
     * @param int $userId
     * @param Request $request
     * @return void
     */
    public function trackUserSession(int $userId, Request $request): void
    {
        $cacheKey = "session_history:{$userId}";
        $history = Cache::get($cacheKey, []);
        
        $history[] = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->timestamp,
            'hour' => (int) now()->format('H'),
            'request_path' => $request->getPathInfo(),
        ];
        
        // Keep last 50 sessions
        $history = array_slice($history, -50);
        
        // Cache for 7 days
        Cache::put($cacheKey, $history, 604800);
    }
    
    /**
     * Get user session history for analysis
     * 
     * @param int $userId
     * @return array Session history
     */
    private function getUserSessionHistory(int $userId): array
    {
        return Cache::get("session_history:{$userId}", []);
    }
    
    /**
     * Check if user agent is anomalous
     * 
     * @param int $userId
     * @param string $currentUserAgent
     * @param array $sessionHistory
     * @return bool Is anomalous
     */
    private function isUserAgentAnomalous(int $userId, string $currentUserAgent, array $sessionHistory): bool
    {
        if (empty($sessionHistory)) {
            return false;
        }
        
        $recentUserAgents = collect($sessionHistory)
            ->where('timestamp', '>', now()->subDays(7)->timestamp)
            ->pluck('user_agent')
            ->filter()
            ->unique();
        
        // If user has been using multiple user agents recently, current one might be normal
        if ($recentUserAgents->count() > 3) {
            return false;
        }
        
        // Check if current user agent is completely different from recent ones
        return !$recentUserAgents->contains($currentUserAgent);
    }
    
    /**
     * Check if IP address represents geographic anomaly
     * 
     * @param int $userId
     * @param string $currentIp
     * @param array $sessionHistory
     * @return bool Is geographic anomaly
     */
    private function isGeographicAnomalous(int $userId, string $currentIp, array $sessionHistory): bool
    {
        // Simple IP-based geographic check
        // In production, this would use GeoIP service
        if (empty($sessionHistory)) {
            return false;
        }
        
        $recentIps = collect($sessionHistory)
            ->where('timestamp', '>', now()->subDays(1)->timestamp)
            ->pluck('ip_address')
            ->unique();
        
        // If IP is completely new within 24 hours, flag as potential anomaly
        return !$recentIps->contains($currentIp) && $recentIps->count() > 0;
    }
    
    /**
     * Check if access time is anomalous
     * 
     * @param int $userId
     * @param array $sessionHistory
     * @return bool Is time anomaly
     */
    private function isTimeAnomalous(int $userId, array $sessionHistory): bool
    {
        if (count($sessionHistory) < 5) {
            return false; // Not enough data
        }
        
        $currentHour = (int) now()->format('H');
        $typicalHours = $this->getTypicalAccessHours($sessionHistory);
        
        // If user typically doesn't access at this hour, flag as anomaly
        return !in_array($currentHour, $typicalHours);
    }
    
    /**
     * Get most common user agent from history
     * 
     * @param array $sessionHistory
     * @return string Most common user agent
     */
    private function getMostCommonUserAgent(array $sessionHistory): string
    {
        if (empty($sessionHistory)) {
            return 'unknown';
        }
        
        return collect($sessionHistory)
            ->pluck('user_agent')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first() ?? 'unknown';
    }
    
    /**
     * Get typical access hours for user
     * 
     * @param array $sessionHistory
     * @return array Typical hours
     */
    private function getTypicalAccessHours(array $sessionHistory): array
    {
        if (empty($sessionHistory)) {
            return range(8, 22); // Default business hours
        }
        
        return collect($sessionHistory)
            ->pluck('hour')
            ->countBy()
            ->filter(function ($count) {
                return $count >= 2; // Hours with at least 2 accesses
            })
            ->keys()
            ->toArray();
    }
    
    /**
     * Get maximum severity from anomalies
     * 
     * @param array $anomalies
     * @return string Maximum severity
     */
    private function getMaxAnomalySeverity(array $anomalies): string
    {
        if (empty($anomalies)) {
            return 'none';
        }
        
        $severityLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        
        $maxSeverity = collect($anomalies)
            ->pluck('severity')
            ->map(function ($severity) use ($severityLevels) {
                return $severityLevels[$severity] ?? 0;
            })
            ->max();
        
        return array_search($maxSeverity, $severityLevels) ?: 'low';
    }
    
    /**
     * Generate security recommendations based on analysis
     * 
     * @param array $analysis
     * @return array Recommendations
     */
    private function generateSecurityRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        foreach ($analysis['patterns_detected'] as $pattern) {
            switch ($pattern['pattern_type']) {
                case 'account_enumeration':
                    if ($pattern['detected']) {
                        $recommendations[] = 'Implement CAPTCHA for login attempts';
                        $recommendations[] = 'Consider temporary IP-based rate limiting';
                    }
                    break;
                    
                case 'privilege_escalation':
                    if ($pattern['detected']) {
                        $recommendations[] = 'Review user permissions immediately';
                        $recommendations[] = 'Enable multi-factor authentication';
                        $recommendations[] = 'Audit recent role changes';
                    }
                    break;
                    
                case 'data_access_analysis':
                    if ($pattern['suspicious']) {
                        $recommendations[] = 'Monitor data export activities';
                        $recommendations[] = 'Implement data access quotas';
                        $recommendations[] = 'Review data access permissions';
                    }
                    break;
                    
                case 'session_anomalies':
                    if ($pattern['anomalies_detected']) {
                        $recommendations[] = 'Verify user identity through additional authentication';
                        $recommendations[] = 'Monitor session for unusual activity';
                    }
                    break;
            }
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Calculate risk level from risk score
     * 
     * @param int $riskScore
     * @return string Risk level
     */
    private function calculateRiskLevel(int $riskScore): string
    {
        if ($riskScore >= 80) {
            return 'critical';
        } elseif ($riskScore >= 60) {
            return 'high';
        } elseif ($riskScore >= 40) {
            return 'medium';
        } elseif ($riskScore >= 20) {
            return 'low';
        } else {
            return 'minimal';
        }
    }
    
    /**
     * Get comprehensive pattern analysis for user
     * 
     * @param User $user
     * @param Request $request
     * @return array Comprehensive analysis
     */
    public function getComprehensiveAnalysis(User $user, Request $request): array
    {
        $behaviorAnalysis = $this->analyzeUserBehavior($user, $request);
        
        return [
            'user_analysis' => $behaviorAnalysis,
            'session_tracking' => $this->getUserSessionHistory($user->id),
            'recommendations' => $behaviorAnalysis['recommendations'],
            'action_required' => $behaviorAnalysis['risk_level'] !== 'minimal',
            'monitoring_level' => $this->getRecommendedMonitoringLevel($behaviorAnalysis['risk_level']),
        ];
    }
    
    /**
     * Get recommended monitoring level based on risk
     * 
     * @param string $riskLevel
     * @return string Monitoring level
     */
    private function getRecommendedMonitoringLevel(string $riskLevel): string
    {
        return match ($riskLevel) {
            'critical' => 'maximum_monitoring',
            'high' => 'enhanced_monitoring',
            'medium' => 'increased_monitoring',
            'low' => 'standard_monitoring',
            default => 'minimal_monitoring',
        };
    }
}