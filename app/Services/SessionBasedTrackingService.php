<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ========================================
 * SESSION-BASED TRACKING SERVICE
 * Replace IP-only tracking with session+IP combination for better mobile experience
 * Following workinginstruction.md: Separate file for session tracking functionality
 * ========================================
 */
class SessionBasedTrackingService
{
    /**
     * Generate smart tracking key based on user context
     * 
     * @param Request $request
     * @return string Tracking key for rate limiting/monitoring
     */
    public function generateTrackingKey(Request $request): string
    {
        // For authenticated users, use user ID (most reliable)
        if (auth()->check()) {
            return "user:" . auth()->id();
        }
        
        // For guests with sessions, combine session + IP hash
        $sessionId = session()->getId();
        if ($sessionId) {
            $ipHash = $this->getIPHash($request->ip());
            return "session:{$sessionId}:{$ipHash}";
        }
        
        // Fallback to IP-based tracking (less aggressive than before)
        return "ip:" . $this->getIPHash($request->ip());
    }
    
    /**
     * Generate fingerprint-based tracking key for advanced tracking
     * 
     * @param Request $request
     * @return string Advanced tracking key
     */
    public function generateFingerprintKey(Request $request): string
    {
        $components = [
            'ip_hash' => $this->getIPHash($request->ip()),
            'user_agent_hash' => $this->getUserAgentHash($request->userAgent()),
            'session_id' => session()->getId(),
        ];
        
        // Add user ID if authenticated
        if (auth()->check()) {
            $components['user_id'] = auth()->id();
        }
        
        // Create stable fingerprint
        $fingerprint = md5(json_encode($components));
        
        return "fingerprint:{$fingerprint}";
    }
    
    /**
     * Get tracking context for security analysis
     * 
     * @param Request $request
     * @return array Tracking context information
     */
    public function getTrackingContext(Request $request): array
    {
        return [
            'primary_key' => $this->generateTrackingKey($request),
            'fingerprint_key' => $this->generateFingerprintKey($request),
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
            'ip_hash' => $this->getIPHash($request->ip()),
            'user_agent_hash' => $this->getUserAgentHash($request->userAgent()),
            'user_id' => auth()->id(),
            'is_authenticated' => auth()->check(),
            'tracking_method' => $this->getTrackingMethod($request),
        ];
    }
    
    /**
     * Determine the most appropriate tracking method
     * 
     * @param Request $request
     * @return string Tracking method used
     */
    public function getTrackingMethod(Request $request): string
    {
        if (auth()->check()) {
            return 'user_based';
        }
        
        if (session()->getId()) {
            return 'session_based';
        }
        
        return 'ip_based';
    }
    
    /**
     * Check if tracking key represents same user across requests
     * 
     * @param string $trackingKey1
     * @param string $trackingKey2
     * @return bool True if same user
     */
    public function isSameUser(string $trackingKey1, string $trackingKey2): bool
    {
        // Exact match
        if ($trackingKey1 === $trackingKey2) {
            return true;
        }
        
        // Extract user IDs if both are user-based
        if (str_starts_with($trackingKey1, 'user:') && str_starts_with($trackingKey2, 'user:')) {
            $userId1 = str_replace('user:', '', $trackingKey1);
            $userId2 = str_replace('user:', '', $trackingKey2);
            return $userId1 === $userId2;
        }
        
        return false;
    }
    
    /**
     * Get IP address hash (for privacy and mobile carrier handling)
     * 
     * @param string $ipAddress
     * @return string Hashed IP (first 8 characters for grouping)
     */
    private function getIPHash(string $ipAddress): string
    {
        // Use shorter hash to group mobile carrier IPs together
        return substr(md5($ipAddress . config('app.key')), 0, 8);
    }
    
    /**
     * Get user agent hash for fingerprinting
     * 
     * @param string|null $userAgent
     * @return string Hashed user agent
     */
    private function getUserAgentHash(?string $userAgent): string
    {
        return substr(md5(($userAgent ?? 'unknown') . config('app.key')), 0, 8);
    }
    
    /**
     * Track user activity across sessions
     * 
     * @param Request $request
     * @param string $activityType
     * @param array $metadata
     * @return void
     */
    public function trackUserActivity(Request $request, string $activityType, array $metadata = []): void
    {
        $trackingContext = $this->getTrackingContext($request);
        $cacheKey = "user_activity:{$trackingContext['primary_key']}";
        
        // Get existing activities
        $activities = Cache::get($cacheKey, []);
        
        // Add new activity
        $activities[] = [
            'type' => $activityType,
            'timestamp' => now()->timestamp,
            'url' => $request->getPathInfo(),
            'method' => $request->method(),
            'metadata' => $metadata,
            'tracking_context' => $trackingContext,
        ];
        
        // Keep last 20 activities only
        $activities = array_slice($activities, -20);
        
        // Store with 1 hour TTL
        Cache::put($cacheKey, $activities, 3600);
        
        // Log activity for debugging
        Log::channel('security')->debug('User Activity Tracked', [
            'activity_type' => $activityType,
            'tracking_context' => $trackingContext,
            'total_activities' => count($activities),
        ]);
    }
    
    /**
     * Get user activity history
     * 
     * @param Request $request
     * @return array Recent activity history
     */
    public function getUserActivityHistory(Request $request): array
    {
        $trackingContext = $this->getTrackingContext($request);
        $cacheKey = "user_activity:{$trackingContext['primary_key']}";
        
        return Cache::get($cacheKey, []);
    }
    
    /**
     * Analyze user behavior patterns
     * 
     * @param Request $request
     * @return array Behavior analysis
     */
    public function analyzeUserBehavior(Request $request): array
    {
        $activities = $this->getUserActivityHistory($request);
        $trackingContext = $this->getTrackingContext($request);
        
        if (empty($activities)) {
            return [
                'pattern' => 'new_user',
                'risk_level' => 'unknown',
                'activity_count' => 0,
                'tracking_method' => $trackingContext['tracking_method'],
            ];
        }
        
        $activityCount = count($activities);
        $recentActivities = array_slice($activities, -10); // Last 10 activities
        $timeSpan = $this->getActivityTimeSpan($recentActivities);
        $uniqueUrls = $this->getUniqueUrls($recentActivities);
        
        return [
            'pattern' => $this->detectBehaviorPattern($recentActivities, $timeSpan),
            'risk_level' => $this->calculateRiskLevel($recentActivities, $timeSpan, $uniqueUrls),
            'activity_count' => $activityCount,
            'recent_activity_count' => count($recentActivities),
            'time_span_minutes' => $timeSpan,
            'unique_urls' => count($uniqueUrls),
            'tracking_method' => $trackingContext['tracking_method'],
            'is_authenticated' => $trackingContext['is_authenticated'],
        ];
    }
    
    /**
     * Detect behavior pattern from activities
     * 
     * @param array $activities
     * @param int $timeSpanMinutes
     * @return string Behavior pattern
     */
    private function detectBehaviorPattern(array $activities, int $timeSpanMinutes): string
    {
        $activityCount = count($activities);
        
        if ($activityCount === 0) {
            return 'inactive';
        }
        
        if ($timeSpanMinutes < 5 && $activityCount > 15) {
            return 'rapid_browsing';
        }
        
        if ($timeSpanMinutes < 2 && $activityCount > 10) {
            return 'suspicious_rapid';
        }
        
        if ($activityCount > 5 && $timeSpanMinutes > 30) {
            return 'normal_browsing';
        }
        
        return 'moderate_activity';
    }
    
    /**
     * Calculate risk level based on behavior
     * 
     * @param array $activities
     * @param int $timeSpanMinutes
     * @param array $uniqueUrls
     * @return string Risk level
     */
    private function calculateRiskLevel(array $activities, int $timeSpanMinutes, array $uniqueUrls): string
    {
        $activityCount = count($activities);
        $uniqueUrlCount = count($uniqueUrls);
        
        // High risk indicators
        if ($timeSpanMinutes < 1 && $activityCount > 20) {
            return 'high'; // Too many requests too fast
        }
        
        if ($uniqueUrlCount === 1 && $activityCount > 15) {
            return 'high'; // Repeated requests to same URL
        }
        
        // Medium risk indicators
        if ($timeSpanMinutes < 5 && $activityCount > 10) {
            return 'medium'; // Moderately fast browsing
        }
        
        if ($uniqueUrlCount < 3 && $activityCount > 10) {
            return 'medium'; // Limited URL diversity
        }
        
        // Low risk - normal behavior
        return 'low';
    }
    
    /**
     * Get activity time span in minutes
     * 
     * @param array $activities
     * @return int Time span in minutes
     */
    private function getActivityTimeSpan(array $activities): int
    {
        if (count($activities) < 2) {
            return 0;
        }
        
        $timestamps = array_column($activities, 'timestamp');
        $minTime = min($timestamps);
        $maxTime = max($timestamps);
        
        return (int) ceil(($maxTime - $minTime) / 60);
    }
    
    /**
     * Get unique URLs from activities
     * 
     * @param array $activities
     * @return array Unique URLs
     */
    private function getUniqueUrls(array $activities): array
    {
        return array_unique(array_column($activities, 'url'));
    }
    
    /**
     * Check if user should be flagged based on behavior
     * 
     * @param Request $request
     * @return array Flag recommendation
     */
    public function shouldFlagUser(Request $request): array
    {
        $behavior = $this->analyzeUserBehavior($request);
        $trackingContext = $this->getTrackingContext($request);
        
        $shouldFlag = false;
        $reasons = [];
        
        // Flag based on risk level
        if ($behavior['risk_level'] === 'high') {
            $shouldFlag = true;
            $reasons[] = 'High risk behavior pattern detected';
        }
        
        // Flag rapid browsing patterns
        if ($behavior['pattern'] === 'suspicious_rapid') {
            $shouldFlag = true;
            $reasons[] = 'Suspicious rapid browsing detected';
        }
        
        // Don't flag authenticated users as aggressively
        if ($trackingContext['is_authenticated'] && $behavior['risk_level'] !== 'high') {
            $shouldFlag = false;
            $reasons[] = 'Authenticated user - reduced flagging threshold';
        }
        
        return [
            'should_flag' => $shouldFlag,
            'reasons' => $reasons,
            'behavior_analysis' => $behavior,
            'tracking_context' => $trackingContext,
        ];
    }
    
    /**
     * Clear user tracking data (privacy/admin function)
     * 
     * @param Request $request
     * @return bool Success status
     */
    public function clearUserTracking(Request $request): bool
    {
        $trackingContext = $this->getTrackingContext($request);
        $cacheKey = "user_activity:{$trackingContext['primary_key']}";
        
        return Cache::forget($cacheKey);
    }
}