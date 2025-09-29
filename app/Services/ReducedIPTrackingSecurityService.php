<?php

namespace App\Services;

use App\Services\SecurityEventService;
use App\Services\CloudflareSecurityService;
use App\Services\SecurityPatternService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ========================================
 * REDUCED IP TRACKING SECURITY SERVICE
 * Modified security event service with reduced IP-based tracking
 * Following workinginstruction.md: Separate file for reduced IP tracking
 * ========================================
 */
class ReducedIPTrackingSecurityService
{
    private SecurityEventService $originalSecurityService;
    private CloudflareSecurityService $cloudflareService;
    private SecurityPatternService $patternService;
    
    public function __construct(
        SecurityEventService $originalSecurityService,
        CloudflareSecurityService $cloudflareService,
        SecurityPatternService $patternService
    ) {
        $this->originalSecurityService = $originalSecurityService;
        $this->cloudflareService = $cloudflareService;
        $this->patternService = $patternService;
    }
    
    /**
     * Smart IP tracking with Cloudflare intelligence
     * 
     * @param string $ipAddress
     * @param string $eventType
     * @param string $severity
     * @param Request $request
     * @return void
     */
    public function trackSuspiciousIPIntelligently(string $ipAddress, string $eventType, string $severity, Request $request): void
    {
        // Skip IP tracking if Cloudflare already handled the threat
        if ($this->shouldSkipIPTracking($ipAddress, $eventType, $severity, $request)) {
            return;
        }
        
        // Only track critical events for IP-based monitoring
        if ($severity !== SecurityEventService::SEVERITY_CRITICAL) {
            $this->logReasonForSkippingIPTracking($ipAddress, $eventType, $severity, 'non_critical_event');
            return;
        }
        
        // Use original service for truly critical events
        $this->originalSecurityService->trackSuspiciousIP($ipAddress, $eventType, $severity);
        
        Log::channel('security')->info('IP Tracking Applied (Critical Event Only)', [
            'ip_address' => $ipAddress,
            'event_type' => $eventType,
            'severity' => $severity,
            'reason' => 'critical_event_requires_ip_tracking',
        ]);
    }
    
    /**
     * Determine if IP tracking should be skipped
     * 
     * @param string $ipAddress
     * @param string $eventType
     * @param string $severity
     * @param Request $request
     * @return bool Should skip IP tracking
     */
    private function shouldSkipIPTracking(string $ipAddress, string $eventType, string $severity, Request $request): bool
    {
        // Always skip if Cloudflare provides high trust
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        if ($trustAnalysis['classification'] === 'high_trust') {
            $this->logReasonForSkippingIPTracking($ipAddress, $eventType, $severity, 'high_cloudflare_trust');
            return true;
        }
        
        // Skip if Cloudflare protection is active and bot score is low (likely human)
        if ($this->cloudflareService->isCloudflareProtected($request)) {
            $botScore = $this->cloudflareService->getBotScore($request);
            if ($botScore !== null && $botScore < 50) {
                $this->logReasonForSkippingIPTracking($ipAddress, $eventType, $severity, 'cloudflare_protected_likely_human');
                return true;
            }
        }
        
        // Skip for authenticated users with clean behavioral patterns
        if (auth()->check()) {
            $user = auth()->user();
            $userAnalysis = $this->patternService->analyzeUserBehavior($user, $request);
            
            if ($userAnalysis['risk_level'] === 'minimal' || $userAnalysis['risk_level'] === 'low') {
                $this->logReasonForSkippingIPTracking($ipAddress, $eventType, $severity, 'authenticated_user_low_risk');
                return true;
            }
        }
        
        // Skip for mobile carrier IPs with session-based tracking
        if ($this->isMobileCarrierIP($ipAddress) && session()->getId()) {
            $this->logReasonForSkippingIPTracking($ipAddress, $eventType, $severity, 'mobile_carrier_with_session');
            return true;
        }
        
        return false;
    }
    
    /**
     * Log reason for skipping IP tracking
     * 
     * @param string $ipAddress
     * @param string $eventType
     * @param string $severity
     * @param string $reason
     * @return void
     */
    private function logReasonForSkippingIPTracking(string $ipAddress, string $eventType, string $severity, string $reason): void
    {
        Log::channel('security')->debug('IP Tracking Skipped (Smart Decision)', [
            'ip_address' => $ipAddress,
            'event_type' => $eventType,
            'severity' => $severity,
            'skip_reason' => $reason,
            'alternative_tracking' => 'user_behavior_based',
        ]);
    }
    
    /**
     * Check if IP belongs to known mobile carrier
     * 
     * @param string $ipAddress
     * @return bool Is mobile carrier IP
     */
    private function isMobileCarrierIP(string $ipAddress): bool
    {
        // Indonesian mobile carrier IP ranges (simplified)
        $mobileCarrierRanges = [
            '114.10.', '110.138.', '180.243.', // Telkomsel
            '202.3.', '103.47.', '36.66.',     // Indosat
            '103.8.', '103.23.', '118.96.',    // XL Axiata
        ];
        
        foreach ($mobileCarrierRanges as $range) {
            if (str_starts_with($ipAddress, $range)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enhanced threat scoring with reduced IP emphasis
     * 
     * @param string $eventType
     * @param string $severity
     * @param Request $request
     * @return int Adjusted threat score
     */
    public function calculateReducedIPThreatScore(string $eventType, string $severity, Request $request): int
    {
        // Get base threat score from original service
        $baseScore = $this->getBaseThreatScore($eventType, $severity);
        
        // Apply Cloudflare intelligence reduction
        $cfReduction = $this->calculateCloudflareThreatReduction($request);
        
        // Apply user behavior context
        $behaviorReduction = $this->calculateBehaviorThreatReduction($request);
        
        // Apply session context reduction
        $sessionReduction = $this->calculateSessionThreatReduction($request);
        
        $adjustedScore = max(0, $baseScore - $cfReduction - $behaviorReduction - $sessionReduction);
        
        Log::channel('security')->debug('Threat Score Calculation (Reduced IP Emphasis)', [
            'event_type' => $eventType,
            'severity' => $severity,
            'base_score' => $baseScore,
            'cloudflare_reduction' => $cfReduction,
            'behavior_reduction' => $behaviorReduction,
            'session_reduction' => $sessionReduction,
            'final_score' => $adjustedScore,
        ]);
        
        return $adjustedScore;
    }
    
    /**
     * Get base threat score from event type and severity
     * 
     * @param string $eventType
     * @param string $severity
     * @return int Base threat score
     */
    private function getBaseThreatScore(string $eventType, string $severity): int
    {
        $severityScores = [
            SecurityEventService::SEVERITY_CRITICAL => 50,
            SecurityEventService::SEVERITY_HIGH => 35,
            SecurityEventService::SEVERITY_MEDIUM => 20,
            SecurityEventService::SEVERITY_LOW => 10,
        ];
        
        $eventTypeScores = [
            SecurityEventService::EVENT_BRUTE_FORCE_ATTEMPT => 30,
            SecurityEventService::EVENT_INJECTION_ATTEMPT => 40,
            SecurityEventService::EVENT_UNAUTHORIZED_ACCESS => 25,
            SecurityEventService::EVENT_ADMIN_ACCESS => 35,
            SecurityEventService::EVENT_DATA_EXFILTRATION => 45,
        ];
        
        return ($severityScores[$severity] ?? 10) + ($eventTypeScores[$eventType] ?? 5);
    }
    
    /**
     * Calculate threat reduction based on Cloudflare analysis
     * 
     * @param Request $request
     * @return int Threat score reduction
     */
    private function calculateCloudflareThreatReduction(Request $request): int
    {
        $reduction = 0;
        
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        
        switch ($trustAnalysis['classification']) {
            case 'high_trust':
                $reduction += 40;
                break;
            case 'medium_trust':
                $reduction += 20;
                break;
        }
        
        // Additional reduction for Cloudflare protection
        if ($this->cloudflareService->isCloudflareProtected($request)) {
            $reduction += 15;
        }
        
        // Bot score based reduction
        $botScore = $this->cloudflareService->getBotScore($request);
        if ($botScore !== null && $botScore < 30) {
            $reduction += 25; // Very likely human
        }
        
        return $reduction;
    }
    
    /**
     * Calculate threat reduction based on user behavior
     * 
     * @param Request $request
     * @return int Threat score reduction
     */
    private function calculateBehaviorThreatReduction(Request $request): int
    {
        $reduction = 0;
        
        if (auth()->check()) {
            $user = auth()->user();
            
            // Long-standing user account
            if ($user->created_at < now()->subMonths(6)) {
                $reduction += 15;
            }
            
            // User with established behavioral baseline
            $baseline = Cache::get("user_baseline:{$user->id}");
            if ($baseline && !isset($baseline['insufficient_data'])) {
                $reduction += 20;
            }
            
            // User with admin role and good track record
            if ($user->hasRole('admin')) {
                $adminActions = Cache::get("admin_actions:{$user->id}", []);
                $recentBadActions = collect($adminActions)
                    ->where('timestamp', '>', now()->subWeek()->timestamp)
                    ->where('risk_level', 'high')
                    ->count();
                
                if ($recentBadActions === 0) {
                    $reduction += 25;
                }
            }
        }
        
        return $reduction;
    }
    
    /**
     * Calculate threat reduction based on session context
     * 
     * @param Request $request
     * @return int Threat score reduction
     */
    private function calculateSessionThreatReduction(Request $request): int
    {
        $reduction = 0;
        
        // Established session (not first request)
        $sessionId = session()->getId();
        if ($sessionId) {
            $sessionAge = Cache::get("session_age:{$sessionId}");
            if ($sessionAge && $sessionAge > 300) { // 5+ minutes
                $reduction += 10;
            }
        }
        
        // Mobile carrier IP with session tracking
        if ($this->isMobileCarrierIP($request->ip()) && $sessionId) {
            $reduction += 20;
        }
        
        return $reduction;
    }
    
    /**
     * Enhanced flagging logic with reduced IP emphasis
     * 
     * @param string $ipAddress
     * @param Request $request
     * @return bool Should flag IP
     */
    public function shouldFlagIPWithReducedEmphasis(string $ipAddress, Request $request): bool
    {
        // Never flag high-trust Cloudflare users
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        if ($trustAnalysis['classification'] === 'high_trust') {
            return false;
        }
        
        // Don't flag mobile carrier IPs with session tracking
        if ($this->isMobileCarrierIP($ipAddress) && session()->getId()) {
            return false;
        }
        
        // Don't flag authenticated users with good behavior
        if (auth()->check()) {
            $user = auth()->user();
            $userAnalysis = $this->patternService->analyzeUserBehavior($user, $request);
            
            if ($userAnalysis['risk_level'] !== 'high' && $userAnalysis['risk_level'] !== 'critical') {
                return false;
            }
        }
        
        // Calculate adjusted threat score
        $threatScore = $this->calculateReducedIPThreatScore('suspicious_activity', 'medium', $request);
        
        // Much higher threshold for IP flagging
        return $threatScore >= 80; // Increased from typical 60
    }
    
    /**
     * Alternative tracking methods when IP tracking is skipped
     * 
     * @param string $eventType
     * @param string $severity
     * @param Request $request
     * @return void
     */
    public function useAlternativeTracking(string $eventType, string $severity, Request $request): void
    {
        // Use session-based tracking
        if (session()->getId()) {
            $this->trackBySession($eventType, $severity, $request);
        }
        
        // Use user-based tracking if authenticated
        if (auth()->check()) {
            $this->trackByUser($eventType, $severity, $request);
        }
        
        // Use fingerprint-based tracking
        $this->trackByFingerprint($eventType, $severity, $request);
    }
    
    /**
     * Track security events by session
     * 
     * @param string $eventType
     * @param string $severity
     * @param Request $request
     * @return void
     */
    private function trackBySession(string $eventType, string $severity, Request $request): void
    {
        $sessionId = session()->getId();
        $cacheKey = "session_security:{$sessionId}";
        
        $events = Cache::get($cacheKey, []);
        $events[] = [
            'event_type' => $eventType,
            'severity' => $severity,
            'timestamp' => now()->timestamp,
            'ip_address' => $request->ip(),
        ];
        
        // Keep last 20 events per session
        $events = array_slice($events, -20);
        
        Cache::put($cacheKey, $events, 3600); // 1 hour
    }
    
    /**
     * Track security events by authenticated user
     * 
     * @param string $eventType
     * @param string $severity
     * @param Request $request
     * @return void
     */
    private function trackByUser(string $eventType, string $severity, Request $request): void
    {
        $userId = auth()->id();
        $this->patternService->trackPrivilegeAction($userId, $eventType, [
            'severity' => $severity,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
    
    /**
     * Track security events by browser/device fingerprint
     * 
     * @param string $eventType
     * @param string $severity
     * @param Request $request
     * @return void
     */
    private function trackByFingerprint(string $eventType, string $severity, Request $request): void
    {
        $fingerprint = $this->generateDeviceFingerprint($request);
        $cacheKey = "fingerprint_security:{$fingerprint}";
        
        $events = Cache::get($cacheKey, []);
        $events[] = [
            'event_type' => $eventType,
            'severity' => $severity,
            'timestamp' => now()->timestamp,
        ];
        
        // Keep last 15 events per fingerprint
        $events = array_slice($events, -15);
        
        Cache::put($cacheKey, $events, 7200); // 2 hours
    }
    
    /**
     * Generate device fingerprint for tracking
     * 
     * @param Request $request
     * @return string Device fingerprint
     */
    private function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            'user_agent' => md5($request->userAgent() ?? ''),
            'ip_prefix' => substr($request->ip(), 0, strrpos($request->ip(), '.')), // IP subnet
            'accept_language' => md5($request->header('Accept-Language') ?? ''),
        ];
        
        return md5(json_encode($components));
    }
    
    /**
     * Get comprehensive tracking summary
     * 
     * @param Request $request
     * @return array Tracking summary
     */
    public function getTrackingSummary(Request $request): array
    {
        return [
            'ip_tracking_enabled' => !$this->shouldSkipIPTracking($request->ip(), 'test', 'medium', $request),
            'alternative_tracking_methods' => [
                'session_based' => session()->getId() !== null,
                'user_based' => auth()->check(),
                'fingerprint_based' => true,
            ],
            'cloudflare_context' => $this->cloudflareService->getSecurityContext($request),
            'tracking_reasons' => $this->getTrackingReasons($request),
        ];
    }
    
    /**
     * Get reasons for current tracking decisions
     * 
     * @param Request $request
     * @return array Tracking decision reasons
     */
    private function getTrackingReasons(Request $request): array
    {
        $reasons = [];
        
        if ($this->cloudflareService->isCloudflareProtected($request)) {
            $reasons[] = 'cloudflare_protection_active';
        }
        
        if (auth()->check()) {
            $reasons[] = 'user_authenticated';
        }
        
        if ($this->isMobileCarrierIP($request->ip())) {
            $reasons[] = 'mobile_carrier_ip_detected';
        }
        
        if (session()->getId()) {
            $reasons[] = 'session_tracking_available';
        }
        
        return $reasons;
    }
}