<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SecurityPatternService;
use App\Services\UserBehaviorAnalyticsService;
use App\Services\DataExfiltrationDetectionService;
use App\Services\EnhancedSecurityEventService;
use App\Services\CloudflareSecurityService;
use App\Services\SecurityEventService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ========================================
 * ENHANCED SECURITY PATTERN MIDDLEWARE
 * Advanced pattern detection with reduced IP-based tracking
 * Following workinginstruction.md: Separate file for enhanced pattern security
 * ========================================
 */
class EnhancedSecurityPatternMiddleware
{
    private SecurityPatternService $patternService;
    private UserBehaviorAnalyticsService $behaviorService;
    private DataExfiltrationDetectionService $exfiltrationService;
    private EnhancedSecurityEventService $enhancedSecurityService;
    private CloudflareSecurityService $cloudflareService;
    
    public function __construct(
        SecurityPatternService $patternService,
        UserBehaviorAnalyticsService $behaviorService,
        DataExfiltrationDetectionService $exfiltrationService,
        EnhancedSecurityEventService $enhancedSecurityService,
        CloudflareSecurityService $cloudflareService
    ) {
        $this->patternService = $patternService;
        $this->behaviorService = $behaviorService;
        $this->exfiltrationService = $exfiltrationService;
        $this->enhancedSecurityService = $enhancedSecurityService;
        $this->cloudflareService = $cloudflareService;
    }
    
    /**
     * Handle request with advanced pattern detection
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip pattern analysis for unauthenticated users on non-sensitive endpoints
        if (!Auth::check() && !$this->requiresUnauthenticatedAnalysis($request)) {
            return $this->handleUnauthenticatedRequest($request, $next);
        }
        
        $user = Auth::user();
        
        // Pre-request analysis
        $securityAnalysis = $this->performPreRequestAnalysis($user, $request);
        
        // Handle high-risk users with immediate action
        if ($this->requiresImmediateAction($securityAnalysis)) {
            return $this->handleHighRiskUser($user, $request, $securityAnalysis);
        }
        
        // Track user session and behavior
        $this->trackUserActivity($user, $request);
        
        // Process request
        $response = $next($request);
        
        // Post-request analysis
        $this->performPostRequestAnalysis($user, $request, $response, $securityAnalysis);
        
        return $response;
    }
    
    /**
     * Check if unauthenticated request requires pattern analysis
     */
    private function requiresUnauthenticatedAnalysis(Request $request): bool
    {
        $sensitiveEndpoints = ['/login', '/register', '/password', '/api/auth'];
        
        foreach ($sensitiveEndpoints as $endpoint) {
            if (str_starts_with($request->getPathInfo(), $endpoint)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Handle unauthenticated requests with minimal analysis
     */
    private function handleUnauthenticatedRequest(Request $request, Closure $next): Response
    {
        // Only check for obvious attack patterns
        if ($this->hasObviousAttackPattern($request)) {
            $this->logUnauthenticatedThreat($request);
        }
        
        // Check Cloudflare trust for basic filtering
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        if ($trustAnalysis['classification'] === 'untrusted') {
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                'untrusted_unauthenticated_access',
                SecurityEventService::SEVERITY_MEDIUM,
                'Untrusted unauthenticated access attempt',
                $request
            );
        }
        
        return $next($request);
    }
    
    /**
     * Perform comprehensive pre-request security analysis
     */
    private function performPreRequestAnalysis($user, Request $request): array
    {
        if (!$user) {
            return ['analysis_skipped' => 'unauthenticated'];
        }
        
        // Security pattern analysis
        $patternAnalysis = $this->patternService->analyzeUserBehavior($user, $request);
        
        // Behavioral analytics
        $behaviorAnalysis = $this->behaviorService->performBehaviorAnalytics($user, $request);
        
        // Data exfiltration detection
        $exfiltrationAnalysis = $this->exfiltrationService->analyzeDataExfiltration($user, $request);
        
        // Cloudflare context
        $cloudflareContext = $this->cloudflareService->getSecurityContext($request);
        
        // Combined risk assessment
        $combinedRisk = $this->calculateCombinedRiskScore([
            'pattern_risk' => $patternAnalysis['risk_score'] ?? 0,
            'behavior_risk' => $behaviorAnalysis['behavioral_risk_score'] ?? 0,
            'exfiltration_risk' => $exfiltrationAnalysis['risk_score'] ?? 0,
        ]);
        
        return [
            'user_id' => $user->id,
            'pattern_analysis' => $patternAnalysis,
            'behavior_analysis' => $behaviorAnalysis,
            'exfiltration_analysis' => $exfiltrationAnalysis,
            'cloudflare_context' => $cloudflareContext,
            'combined_risk_score' => $combinedRisk['score'],
            'combined_risk_level' => $combinedRisk['level'],
            'analysis_timestamp' => now()->toISOString(),
        ];
    }
    
    /**
     * Check if user requires immediate action
     */
    private function requiresImmediateAction(array $analysis): bool
    {
        if (isset($analysis['analysis_skipped'])) {
            return false;
        }
        
        // Critical risk level requires immediate action
        if ($analysis['combined_risk_level'] === 'critical') {
            return true;
        }
        
        // Specific high-risk indicators
        if ($analysis['exfiltration_analysis']['immediate_action_required'] ?? false) {
            return true;
        }
        
        // Pattern-based critical indicators
        $criticalPatterns = collect($analysis['pattern_analysis']['patterns_detected'] ?? [])
            ->where('severity', 'critical')
            ->isNotEmpty();
        
        return $criticalPatterns;
    }
    
    /**
     * Handle high-risk user with immediate action
     */
    private function handleHighRiskUser($user, Request $request, array $analysis): Response
    {
        // Log critical security event
        $this->enhancedSecurityService->logEnhancedSecurityEvent(
            'critical_user_behavior_detected',
            SecurityEventService::SEVERITY_CRITICAL,
            'User flagged for critical security risk - immediate action required',
            $request,
            [
                'user_analysis' => $analysis,
                'immediate_actions_taken' => [
                    'request_blocked',
                    'security_team_alerted',
                    'enhanced_monitoring_enabled'
                ],
            ]
        );
        
        // Return security block response
        return response()->json([
            'message' => 'Access temporarily restricted for security review',
            'support_contact' => 'security@example.com',
            'incident_id' => uniqid('SEC_', true),
        ], 423); // 423 Locked
    }
    
    /**
     * Track user activity across all services
     */
    private function trackUserActivity($user, Request $request): void
    {
        if (!$user) return;
        
        // Track in pattern service
        $this->patternService->trackUserSession($user->id, $request);
        
        // Track authentication if login-related
        if ($this->isAuthenticationRequest($request)) {
            $successful = $request->method() === 'POST' ? null : true; // Will be determined later
            $this->behaviorService->trackAuthenticationAttempt($user->id, $successful ?? true, $request);
        }
        
        // Track data access
        if ($this->isDataAccessRequest($request)) {
            $resourceType = $this->getResourceType($request);
            $this->exfiltrationService->trackDataAccess($user->id, $resourceType);
        }
        
        // Track API usage
        if ($this->isAPIRequest($request)) {
            $this->exfiltrationService->trackAPIUsage(
                $user->id,
                $request->getPathInfo(),
                0, // Response size will be tracked in post-request
                200 // Default status, will be updated
            );
        }
    }
    
    /**
     * Perform post-request analysis
     */
    private function performPostRequestAnalysis($user, Request $request, Response $response, array $preAnalysis): void
    {
        if (!$user || isset($preAnalysis['analysis_skipped'])) {
            return;
        }
        
        // Track response context for data exfiltration analysis
        $responseContext = [
            'size_bytes' => strlen($response->getContent()),
            'status_code' => $response->getStatusCode(),
            'content_type' => $response->headers->get('Content-Type'),
            'content_disposition' => $response->headers->get('Content-Disposition'),
        ];
        
        // Update data exfiltration tracking with response data
        if ($this->isDataAccessRequest($request)) {
            $this->exfiltrationService->trackRapidAccess($user->id, 1);
            
            if ($this->isDownloadRequest($request, $responseContext)) {
                $this->exfiltrationService->trackDownloadActivity(
                    $user->id,
                    $this->getResourceType($request),
                    $responseContext['size_bytes']
                );
            }
        }
        
        // Track search activity
        if ($this->isSearchRequest($request)) {
            $searchTerm = $request->get('q') ?? $request->get('search') ?? 'unknown';
            $this->exfiltrationService->trackSearchActivity($user->id, $searchTerm, 0);
        }
        
        // Re-analyze for post-request patterns
        $postAnalysis = $this->exfiltrationService->analyzeDataExfiltration($user, $request, $responseContext);
        
        // Check if new risks emerged
        if ($postAnalysis['risk_score'] > ($preAnalysis['exfiltration_analysis']['risk_score'] ?? 0)) {
            $this->handleEmergingRisk($user, $request, $preAnalysis, $postAnalysis);
        }
        
        // Log comprehensive analysis
        $this->logComprehensiveAnalysis($user, $request, $response, $preAnalysis, $postAnalysis);
    }
    
    /**
     * Handle emerging security risks identified post-request
     */
    private function handleEmergingRisk($user, Request $request, array $preAnalysis, array $postAnalysis): void
    {
        $riskIncrease = $postAnalysis['risk_score'] - ($preAnalysis['exfiltration_analysis']['risk_score'] ?? 0);
        
        if ($riskIncrease >= 30) { // Significant risk increase
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                'emerging_security_risk_detected',
                SecurityEventService::SEVERITY_HIGH,
                'Significant security risk increase detected post-request',
                $request,
                [
                    'pre_analysis' => $preAnalysis,
                    'post_analysis' => $postAnalysis,
                    'risk_increase' => $riskIncrease,
                ]
            );
        }
    }
    
    /**
     * Log comprehensive security analysis
     */
    private function logComprehensiveAnalysis($user, Request $request, Response $response, array $preAnalysis, array $postAnalysis): void
    {
        // Only log if there are significant security findings
        $shouldLog = ($preAnalysis['combined_risk_score'] ?? 0) > 20 ||
                    ($postAnalysis['risk_score'] ?? 0) > 20 ||
                    !empty($preAnalysis['pattern_analysis']['patterns_detected'] ?? []);
        
        if ($shouldLog) {
            Log::channel('security')->info('Comprehensive Security Pattern Analysis', [
                'user_id' => $user->id,
                'request_path' => $request->getPathInfo(),
                'request_method' => $request->method(),
                'response_status' => $response->getStatusCode(),
                'pre_request_analysis' => $preAnalysis,
                'post_request_analysis' => $postAnalysis,
                'cloudflare_protected' => $this->cloudflareService->isCloudflareProtected($request),
                'analysis_timestamp' => now()->toISOString(),
            ]);
        }
    }
    
    /**
     * Calculate combined risk score from multiple analyses
     */
    private function calculateCombinedRiskScore(array $risks): array
    {
        // Weighted risk calculation
        $weights = [
            'pattern_risk' => 0.4,
            'behavior_risk' => 0.3,
            'exfiltration_risk' => 0.3,
        ];
        
        $weightedScore = 0;
        foreach ($risks as $type => $score) {
            $weightedScore += ($score * ($weights[$type] ?? 0.33));
        }
        
        $combinedScore = min(100, max(0, $weightedScore));
        
        return [
            'score' => (int) $combinedScore,
            'level' => $this->getRiskLevel($combinedScore),
            'component_scores' => $risks,
            'weights_used' => $weights,
        ];
    }
    
    // Helper methods for request classification
    private function hasObviousAttackPattern(Request $request): bool
    {
        $attackPatterns = [
            '/union\s+select/i',
            '/<script/i',
            '/\.\.\/\.\.\//',
            '/eval\s*\(/i',
            '/base64_decode/i',
        ];
        
        $content = $request->getPathInfo() . '?' . $request->getQueryString();
        
        foreach ($attackPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function logUnauthenticatedThreat(Request $request): void
    {
        Log::channel('security')->warning('Unauthenticated Attack Pattern Detected', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->getPathInfo(),
            'query' => $request->getQueryString(),
            'method' => $request->method(),
        ]);
    }
    
    private function isAuthenticationRequest(Request $request): bool
    {
        return str_contains($request->getPathInfo(), '/login') ||
               str_contains($request->getPathInfo(), '/auth') ||
               str_contains($request->getPathInfo(), '/register');
    }
    
    private function isDataAccessRequest(Request $request): bool
    {
        $dataEndpoints = ['/api/', '/movies', '/series', '/search', '/admin'];
        
        foreach ($dataEndpoints as $endpoint) {
            if (str_contains($request->getPathInfo(), $endpoint)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isAPIRequest(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/');
    }
    
    private function isSearchRequest(Request $request): bool
    {
        return str_contains($request->getPathInfo(), '/search') ||
               $request->has('q') ||
               $request->has('search');
    }
    
    private function isDownloadRequest(Request $request, array $responseContext): bool
    {
        return str_contains($request->getPathInfo(), '/download') ||
               str_contains($request->getPathInfo(), '/export') ||
               (isset($responseContext['content_disposition']) && 
                str_contains($responseContext['content_disposition'], 'attachment'));
    }
    
    private function getResourceType(Request $request): string
    {
        $path = $request->getPathInfo();
        
        if (str_contains($path, '/movies')) return 'movies';
        if (str_contains($path, '/series')) return 'series';
        if (str_contains($path, '/users')) return 'users';
        if (str_contains($path, '/admin')) return 'admin_data';
        if (str_contains($path, '/api/')) return 'api_data';
        
        return 'general';
    }
    
    private function getRiskLevel(float $score): string
    {
        if ($score >= 80) return 'critical';
        if ($score >= 60) return 'high';
        if ($score >= 40) return 'medium';
        if ($score >= 20) return 'low';
        return 'minimal';
    }
    
    /**
     * Get security analysis summary for current request
     */
    public function getSecuritySummary(Request $request): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [
                'authenticated' => false,
                'cloudflare_status' => $this->cloudflareService->getSecurityContext($request),
            ];
        }
        
        return [
            'authenticated' => true,
            'user_id' => $user->id,
            'pattern_analysis' => $this->patternService->getComprehensiveAnalysis($user, $request),
            'behavior_baseline' => $this->behaviorService->getUserBaseline($user),
            'cloudflare_status' => $this->cloudflareService->getSecurityContext($request),
            'current_risk_factors' => $this->identifyCurrentRiskFactors($user, $request),
        ];
    }
    
    /**
     * Identify current risk factors for user
     */
    private function identifyCurrentRiskFactors($user, Request $request): array
    {
        $riskFactors = [];
        
        // Check recent security events
        $recentEvents = Cache::get("user_security:{$user->id}", []);
        if (count($recentEvents) > 5) {
            $riskFactors[] = 'multiple_recent_security_events';
        }
        
        // Check Cloudflare trust
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        if ($trustAnalysis['classification'] !== 'high_trust') {
            $riskFactors[] = 'low_cloudflare_trust';
        }
        
        // Check for admin access
        if (str_contains($request->getPathInfo(), '/admin') && !$user->hasRole('admin')) {
            $riskFactors[] = 'unauthorized_admin_access_attempt';
        }
        
        return $riskFactors;
    }
}