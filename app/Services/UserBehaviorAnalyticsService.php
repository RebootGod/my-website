<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use App\Services\SecurityPatternService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ========================================
 * USER BEHAVIOR ANALYTICS SERVICE
 * Advanced user-specific security analytics and behavioral monitoring
 * Following workinginstruction.md: Separate file for user behavior analytics
 * ========================================
 */
class UserBehaviorAnalyticsService
{
    private SecurityPatternService $patternService;
    
    // Analytics thresholds
    private const BEHAVIOR_ANALYSIS_WINDOW = 3600; // 1 hour
    private const ANOMALY_THRESHOLD = 70; // Risk score threshold
    private const LEARNING_PERIOD_DAYS = 7; // Days to establish baseline
    
    public function __construct(SecurityPatternService $patternService)
    {
        $this->patternService = $patternService;
    }
    
    /**
     * Perform comprehensive user behavior analytics
     * 
     * @param User $user
     * @param Request $request
     * @return array Comprehensive analytics result
     */
    public function performBehaviorAnalytics(User $user, Request $request): array
    {
        // Get baseline behavior patterns
        $baseline = $this->getUserBaseline($user);
        
        // Analyze current session behavior
        $currentBehavior = $this->analyzeCurrentBehavior($user, $request);
        
        // Compare against baseline for anomalies
        $anomalies = $this->detectBehaviorAnomalies($baseline, $currentBehavior);
        
        // Analyze authentication patterns
        $authPatterns = $this->analyzeAuthenticationPatterns($user);
        
        // Check for account compromise indicators
        $compromiseIndicators = $this->detectAccountCompromiseIndicators($user, $request);
        
        // Generate behavioral risk score
        $riskScore = $this->calculateBehavioralRiskScore($anomalies, $authPatterns, $compromiseIndicators);
        
        return [
            'user_id' => $user->id,
            'analysis_timestamp' => now()->toISOString(),
            'baseline_established' => !empty($baseline),
            'current_behavior' => $currentBehavior,
            'behavior_anomalies' => $anomalies,
            'authentication_patterns' => $authPatterns,
            'compromise_indicators' => $compromiseIndicators,
            'behavioral_risk_score' => $riskScore,
            'risk_level' => $this->getRiskLevel($riskScore),
            'recommendations' => $this->generateBehaviorRecommendations($riskScore, $anomalies),
        ];
    }
    
    /**
     * Get user baseline behavior patterns
     * 
     * @param User $user
     * @return array Baseline behavior data
     */
    public function getUserBaseline(User $user): array
    {
        $cacheKey = "user_baseline:{$user->id}";
        $baseline = Cache::get($cacheKey);
        
        if ($baseline === null) {
            $baseline = $this->calculateUserBaseline($user);
            // Cache baseline for 24 hours
            Cache::put($cacheKey, $baseline, 86400);
        }
        
        return $baseline;
    }
    
    /**
     * Calculate user baseline from historical data
     * 
     * @param User $user
     * @return array Calculated baseline
     */
    private function calculateUserBaseline(User $user): array
    {
        $startDate = now()->subDays(self::LEARNING_PERIOD_DAYS);
        
        // Get user activities for baseline calculation
        $activities = UserActivity::where('user_id', $user->id)
            ->where('activity_at', '>=', $startDate)
            ->get();
        
        if ($activities->isEmpty()) {
            return ['insufficient_data' => true];
        }
        
        return [
            'activity_count' => $activities->count(),
            'typical_hours' => $this->getTypicalActivityHours($activities),
            'common_activities' => $this->getCommonActivityTypes($activities),
            'average_session_duration' => $this->getAverageSessionDuration($activities),
            'typical_ip_patterns' => $this->getTypicalIPPatterns($activities),
            'device_patterns' => $this->getDevicePatterns($activities),
            'geographic_patterns' => $this->getGeographicPatterns($activities),
            'access_frequency' => $this->getAccessFrequency($activities),
            'baseline_established_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Analyze current behavior session
     * 
     * @param User $user
     * @param Request $request
     * @return array Current behavior analysis
     */
    public function analyzeCurrentBehavior(User $user, Request $request): array
    {
        $sessionStart = now()->subHour(); // Last hour of activity
        
        $currentActivities = UserActivity::where('user_id', $user->id)
            ->where('activity_at', '>=', $sessionStart)
            ->get();
        
        return [
            'session_duration_minutes' => 60, // Fixed 1-hour window
            'activity_count' => $currentActivities->count(),
            'activity_types' => $currentActivities->pluck('activity_type')->unique()->values(),
            'current_hour' => (int) now()->format('H'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_frequency' => $this->calculateRequestFrequency($user, $sessionStart),
            'data_access_volume' => $this->calculateDataAccessVolume($currentActivities),
            'feature_usage' => $this->analyzeFeatureUsage($currentActivities),
        ];
    }
    
    /**
     * Detect behavior anomalies against baseline
     * 
     * @param array $baseline
     * @param array $currentBehavior
     * @return array Detected anomalies
     */
    public function detectBehaviorAnomalies(array $baseline, array $currentBehavior): array
    {
        if (isset($baseline['insufficient_data'])) {
            return ['baseline_insufficient' => true];
        }
        
        $anomalies = [];
        
        // Check activity frequency anomaly
        if ($this->isActivityFrequencyAnomalous($baseline, $currentBehavior)) {
            $anomalies[] = [
                'type' => 'activity_frequency_anomaly',
                'severity' => 'medium',
                'current' => $currentBehavior['activity_count'],
                'expected_range' => $baseline['access_frequency'],
            ];
        }
        
        // Check time-based anomaly
        if ($this->isTimeAnomalous($baseline, $currentBehavior)) {
            $anomalies[] = [
                'type' => 'unusual_access_time',
                'severity' => 'low',
                'current_hour' => $currentBehavior['current_hour'],
                'typical_hours' => $baseline['typical_hours'],
            ];
        }
        
        // Check activity type anomaly
        if ($this->isActivityTypeAnomalous($baseline, $currentBehavior)) {
            $anomalies[] = [
                'type' => 'unusual_activity_pattern',
                'severity' => 'medium',
                'current_activities' => $currentBehavior['activity_types'],
                'common_activities' => $baseline['common_activities'],
            ];
        }
        
        // Check data access volume anomaly
        if ($this->isDataAccessAnomalous($baseline, $currentBehavior)) {
            $anomalies[] = [
                'type' => 'unusual_data_access_volume',
                'severity' => 'high',
                'current_volume' => $currentBehavior['data_access_volume'],
                'typical_volume' => $baseline['access_frequency']['data_volume'] ?? 'unknown',
            ];
        }
        
        return $anomalies;
    }
    
    /**
     * Analyze authentication patterns
     * 
     * @param User $user
     * @return array Authentication pattern analysis
     */
    public function analyzeAuthenticationPatterns(User $user): array
    {
        $cacheKey = "auth_patterns:{$user->id}";
        $recentAuth = Cache::get($cacheKey, []);
        
        $analysis = [
            'login_frequency' => count($recentAuth),
            'failed_attempts' => $this->countFailedAttempts($recentAuth),
            'success_rate' => $this->calculateSuccessRate($recentAuth),
            'device_consistency' => $this->analyzeDeviceConsistency($recentAuth),
            'location_consistency' => $this->analyzeLocationConsistency($recentAuth),
            'suspicious_patterns' => [],
        ];
        
        // Detect suspicious authentication patterns
        if ($analysis['failed_attempts'] > 5) {
            $analysis['suspicious_patterns'][] = 'high_failed_attempts';
        }
        
        if ($analysis['success_rate'] < 0.7 && count($recentAuth) > 10) {
            $analysis['suspicious_patterns'][] = 'low_success_rate';
        }
        
        if (!$analysis['device_consistency']) {
            $analysis['suspicious_patterns'][] = 'inconsistent_devices';
        }
        
        if (!$analysis['location_consistency']) {
            $analysis['suspicious_patterns'][] = 'inconsistent_locations';
        }
        
        return $analysis;
    }
    
    /**
     * Track authentication attempt for pattern analysis
     * 
     * @param int $userId
     * @param bool $successful
     * @param Request $request
     * @return void
     */
    public function trackAuthenticationAttempt(int $userId, bool $successful, Request $request): void
    {
        $cacheKey = "auth_patterns:{$userId}";
        $authHistory = Cache::get($cacheKey, []);
        
        $authHistory[] = [
            'successful' => $successful,
            'timestamp' => now()->timestamp,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'hour' => (int) now()->format('H'),
        ];
        
        // Keep last 100 authentication attempts
        $authHistory = array_slice($authHistory, -100);
        
        // Cache for 7 days
        Cache::put($cacheKey, $authHistory, 604800);
        
        // Track failed attempts for enumeration detection
        if (!$successful) {
            $user = User::find($userId);
            if ($user) {
                $this->patternService->trackFailedLoginAttempt($user->email, $request->ip());
            }
        }
    }
    
    /**
     * Detect account compromise indicators
     * 
     * @param User $user
     * @param Request $request
     * @return array Compromise indicators
     */
    public function detectAccountCompromiseIndicators(User $user, Request $request): array
    {
        $indicators = [];
        
        // Check for password changes
        $recentPasswordChange = $this->hasRecentPasswordChange($user);
        if ($recentPasswordChange) {
            $indicators[] = [
                'type' => 'recent_password_change',
                'severity' => 'medium',
                'details' => 'Password changed recently - monitor for compromise',
            ];
        }
        
        // Check for role/permission changes
        $recentRoleChange = $this->hasRecentRoleChange($user);
        if ($recentRoleChange) {
            $indicators[] = [
                'type' => 'recent_role_change',
                'severity' => 'high',
                'details' => 'User role or permissions changed recently',
            ];
        }
        
        // Check for multiple concurrent sessions
        $concurrentSessions = $this->detectConcurrentSessions($user);
        if ($concurrentSessions['detected']) {
            $indicators[] = [
                'type' => 'concurrent_sessions',
                'severity' => 'medium',
                'details' => 'Multiple active sessions detected',
                'session_count' => $concurrentSessions['count'],
            ];
        }
        
        // Check for unusual administrative actions
        $adminActions = $this->detectUnusualAdminActions($user);
        if (!empty($adminActions)) {
            $indicators[] = [
                'type' => 'unusual_admin_actions',
                'severity' => 'high',
                'details' => 'Unusual administrative actions performed',
                'actions' => $adminActions,
            ];
        }
        
        return $indicators;
    }
    
    /**
     * Calculate behavioral risk score
     * 
     * @param array $anomalies
     * @param array $authPatterns
     * @param array $compromiseIndicators
     * @return int Risk score (0-100)
     */
    public function calculateBehavioralRiskScore(array $anomalies, array $authPatterns, array $compromiseIndicators): int
    {
        $score = 0;
        
        // Add points for anomalies
        foreach ($anomalies as $anomaly) {
            switch ($anomaly['severity'] ?? 'low') {
                case 'high':
                    $score += 25;
                    break;
                case 'medium':
                    $score += 15;
                    break;
                case 'low':
                    $score += 5;
                    break;
            }
        }
        
        // Add points for authentication issues
        if (in_array('high_failed_attempts', $authPatterns['suspicious_patterns'] ?? [])) {
            $score += 20;
        }
        if (in_array('low_success_rate', $authPatterns['suspicious_patterns'] ?? [])) {
            $score += 15;
        }
        if (in_array('inconsistent_devices', $authPatterns['suspicious_patterns'] ?? [])) {
            $score += 10;
        }
        
        // Add points for compromise indicators
        foreach ($compromiseIndicators as $indicator) {
            switch ($indicator['severity']) {
                case 'high':
                    $score += 30;
                    break;
                case 'medium':
                    $score += 20;
                    break;
                case 'low':
                    $score += 10;
                    break;
            }
        }
        
        return min(100, max(0, $score));
    }
    
    /**
     * Generate behavior-based recommendations
     * 
     * @param int $riskScore
     * @param array $anomalies
     * @return array Recommendations
     */
    private function generateBehaviorRecommendations(int $riskScore, array $anomalies): array
    {
        $recommendations = [];
        
        if ($riskScore >= 70) {
            $recommendations[] = 'Immediately verify user identity';
            $recommendations[] = 'Consider forced re-authentication';
            $recommendations[] = 'Enable enhanced monitoring';
        } elseif ($riskScore >= 40) {
            $recommendations[] = 'Increase session monitoring';
            $recommendations[] = 'Review recent user activities';
            $recommendations[] = 'Consider additional authentication factors';
        } elseif ($riskScore >= 20) {
            $recommendations[] = 'Monitor user behavior patterns';
            $recommendations[] = 'Log security events for review';
        }
        
        // Specific recommendations based on anomalies
        foreach ($anomalies as $anomaly) {
            switch ($anomaly['type']) {
                case 'unusual_access_time':
                    $recommendations[] = 'Verify access during unusual hours';
                    break;
                case 'unusual_data_access_volume':
                    $recommendations[] = 'Review data access permissions';
                    $recommendations[] = 'Monitor for data exfiltration';
                    break;
                case 'activity_frequency_anomaly':
                    $recommendations[] = 'Check for automated/scripted access';
                    break;
            }
        }
        
        return array_unique($recommendations);
    }
    
    // Helper methods for behavior analysis
    private function getTypicalActivityHours(Collection $activities): array
    {
        return $activities->pluck('activity_at')
            ->map(fn($date) => Carbon::parse($date)->hour)
            ->countBy()
            ->sortDesc()
            ->take(8) // Top 8 hours
            ->keys()
            ->toArray();
    }
    
    private function getCommonActivityTypes($activities): array
    {
        return $activities->pluck('activity_type')
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->keys()
            ->toArray();
    }
    
    private function getAverageSessionDuration($activities): int
    {
        // Simplified calculation - in practice would track actual sessions
        return 30; // 30 minutes average
    }
    
    private function getTypicalIPPatterns($activities): array
    {
        return $activities->pluck('ip_address')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->keys()
            ->toArray();
    }
    
    private function getDevicePatterns($activities): array
    {
        return $activities->pluck('user_agent')
            ->filter()
            ->map(fn($ua) => substr(md5($ua), 0, 8)) // Simplified device fingerprint
            ->countBy()
            ->keys()
            ->toArray();
    }
    
    private function getGeographicPatterns($activities): array
    {
        // Placeholder - would use GeoIP in production
        return ['ID', 'SG', 'US'];
    }
    
    private function getAccessFrequency($activities): array
    {
        $dailyAccess = $activities->groupBy(fn($activity) => Carbon::parse($activity->activity_at)->format('Y-m-d'))
            ->map(fn($day) => $day->count());
        
        return [
            'daily_average' => $dailyAccess->avg(),
            'daily_min' => $dailyAccess->min(),
            'daily_max' => $dailyAccess->max(),
            'data_volume' => $activities->count(),
        ];
    }
    
    private function calculateRequestFrequency(User $user, Carbon $since): int
    {
        return UserActivity::where('user_id', $user->id)
            ->where('activity_at', '>=', $since)
            ->count();
    }
    
    private function calculateDataAccessVolume($activities): int
    {
        // Count activities that involve data access
        return $activities->whereIn('activity_type', [
            'data_view', 'data_export', 'search_performed', 'api_call'
        ])->count();
    }
    
    private function analyzeFeatureUsage($activities): array
    {
        return $activities->pluck('activity_type')
            ->countBy()
            ->toArray();
    }
    
    private function isActivityFrequencyAnomalous(array $baseline, array $current): bool
    {
        $expected = $baseline['access_frequency']['daily_average'] ?? 50;
        $currentFreq = $current['activity_count'] * 24; // Extrapolate hourly to daily
        
        // Anomalous if 3x higher than expected
        return $currentFreq > ($expected * 3);
    }
    
    private function isTimeAnomalous(array $baseline, array $current): bool
    {
        return !in_array($current['current_hour'], $baseline['typical_hours'] ?? []);
    }
    
    private function isActivityTypeAnomalous(array $baseline, array $current): bool
    {
        $commonActivities = $baseline['common_activities'] ?? [];
        $currentActivities = $current['activity_types'] ?? [];
        
        // Check if current activities are mostly uncommon
        $uncommonCount = collect($currentActivities)
            ->filter(fn($activity) => !in_array($activity, $commonActivities))
            ->count();
        
        return $uncommonCount > (count($currentActivities) / 2);
    }
    
    private function isDataAccessAnomalous(array $baseline, array $current): bool
    {
        $expectedVolume = $baseline['access_frequency']['data_volume'] ?? 10;
        $currentVolume = $current['data_access_volume'];
        
        // Anomalous if 5x higher than baseline
        return $currentVolume > ($expectedVolume * 5);
    }
    
    private function countFailedAttempts(array $authHistory): int
    {
        return collect($authHistory)->where('successful', false)->count();
    }
    
    private function calculateSuccessRate(array $authHistory): float
    {
        if (empty($authHistory)) return 1.0;
        
        $successful = collect($authHistory)->where('successful', true)->count();
        return $successful / count($authHistory);
    }
    
    private function analyzeDeviceConsistency(array $authHistory): bool
    {
        $devices = collect($authHistory)->pluck('user_agent')->unique();
        return $devices->count() <= 3; // Allow up to 3 different devices
    }
    
    private function analyzeLocationConsistency(array $authHistory): bool
    {
        $ips = collect($authHistory)->pluck('ip_address')->unique();
        return $ips->count() <= 5; // Allow up to 5 different IP addresses
    }
    
    private function hasRecentPasswordChange(User $user): bool
    {
        // Check if password was changed in last 24 hours
        return $user->updated_at > now()->subDay() && 
               $user->wasChanged('password');
    }
    
    private function hasRecentRoleChange(User $user): bool
    {
        // Simplified check - would need role change audit trail in production
        return Cache::has("role_change:{$user->id}");
    }
    
    private function detectConcurrentSessions(User $user): array
    {
        // Simplified concurrent session detection
        $sessionKey = "user_sessions:{$user->id}";
        $sessions = Cache::get($sessionKey, []);
        
        $activeSessions = collect($sessions)->where('last_activity', '>', now()->subMinutes(15)->timestamp);
        
        return [
            'detected' => $activeSessions->count() > 1,
            'count' => $activeSessions->count(),
        ];
    }
    
    private function detectUnusualAdminActions(User $user): array
    {
        if (!$user->hasRole('admin')) {
            return [];
        }
        
        // Check for admin actions in cache
        $adminActions = Cache::get("admin_actions:{$user->id}", []);
        
        // Filter for unusual/high-risk actions
        return collect($adminActions)
            ->where('timestamp', '>', now()->subHour()->timestamp)
            ->where('risk_level', 'high')
            ->toArray();
    }
    
    private function getRiskLevel(int $score): string
    {
        if ($score >= 80) return 'critical';
        if ($score >= 60) return 'high';  
        if ($score >= 40) return 'medium';
        if ($score >= 20) return 'low';
        return 'minimal';
    }
}