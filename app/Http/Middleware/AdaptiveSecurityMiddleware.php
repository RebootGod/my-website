<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AdaptiveRateLimitService;
use App\Services\SessionBasedTrackingService;
use App\Services\BusinessLogicSecurityService;
use App\Services\CloudflareSecurityService;
use App\Services\EnhancedSecurityEventService;
use App\Services\SecurityEventService;
use Illuminate\Support\Facades\Log;

/**
 * ========================================
 * ADAPTIVE SECURITY MIDDLEWARE
 * Unified middleware integrating all Stage 3 adaptive services
 * Following workinginstruction.md: Separate file for unified adaptive security
 * ========================================
 */
class AdaptiveSecurityMiddleware
{
    private AdaptiveRateLimitService $rateLimitService;
    private SessionBasedTrackingService $trackingService;
    private BusinessLogicSecurityService $businessLogicService;
    private CloudflareSecurityService $cloudflareService;
    private EnhancedSecurityEventService $enhancedSecurityService;
    
    public function __construct(
        AdaptiveRateLimitService $rateLimitService,
        SessionBasedTrackingService $trackingService,
        BusinessLogicSecurityService $businessLogicService,
        CloudflareSecurityService $cloudflareService,
        EnhancedSecurityEventService $enhancedSecurityService
    ) {
        $this->rateLimitService = $rateLimitService;
        $this->trackingService = $trackingService;
        $this->businessLogicService = $businessLogicService;
        $this->cloudflareService = $cloudflareService;
        $this->enhancedSecurityService = $enhancedSecurityService;
    }
    
    /**
     * Handle an incoming request with adaptive security
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Step 1: Generate smart tracking key
        $trackingKey = $this->trackingService->generateTrackingKey($request);
        
        // Step 2: Get business logic security level
        $securityLevel = $this->businessLogicService->getEndpointSecurityLevel($request);
        
        // Step 3: Check if this endpoint should bypass security (browsing)
        if ($securityLevel === 'browsing' && $this->shouldMinimallyMonitor($request)) {
            return $this->handleMinimalMonitoring($request, $next, $trackingKey);
        }
        
        // Step 4: Pre-request security analysis
        $securityAnalysis = $this->performPreRequestAnalysis($request, $trackingKey, $securityLevel);
        
        // Step 5: Apply adaptive rate limiting
        $rateLimitResult = $this->applyAdaptiveRateLimit($request, $trackingKey, $securityLevel);
        if ($rateLimitResult['exceeded']) {
            return $this->handleRateLimitExceeded($request, $rateLimitResult);
        }
        
        // Step 6: Business logic violation check
        $businessViolations = $this->businessLogicService->analyzeBusinessLogicViolations($request);
        if ($businessViolations['requires_action']) {
            $this->handleBusinessLogicViolations($request, $businessViolations);
        }
        
        // Step 7: Track user activity
        $this->trackingService->trackUserActivity($request, 'request', [
            'security_level' => $securityLevel,
            'rate_limit_status' => $rateLimitResult,
        ]);
        
        // Step 8: Process request
        $response = $next($request);
        
        // Step 9: Post-request monitoring
        $this->performPostRequestMonitoring($request, $response, $securityAnalysis);
        
        return $response;
    }
    
    /**
     * Check if request should be minimally monitored
     */
    private function shouldMinimallyMonitor(Request $request): bool
    {
        // High-trust Cloudflare users browsing normal pages
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        
        return $trustAnalysis['classification'] === 'high_trust' &&
               $this->rateLimitService->shouldBypassRateLimit($request);
    }
    
    /**
     * Handle minimal monitoring for low-risk browsing
     */
    private function handleMinimalMonitoring(Request $request, Closure $next, string $trackingKey): Response
    {
        // Very light tracking for browsing
        $this->trackingService->trackUserActivity($request, 'browse', [
            'monitoring_level' => 'minimal'
        ]);
        
        // Process request without heavy security checks
        return $next($request);
    }
    
    /**
     * Perform comprehensive pre-request security analysis
     */
    private function performPreRequestAnalysis(Request $request, string $trackingKey, string $securityLevel): array
    {
        return [
            'tracking_context' => $this->trackingService->getTrackingContext($request),
            'cloudflare_context' => $this->cloudflareService->getSecurityContext($request),
            'trust_analysis' => $this->cloudflareService->analyzeTrustLevel($request),
            'behavior_analysis' => $this->trackingService->analyzeUserBehavior($request),
            'security_level' => $securityLevel,
            'monitoring_config' => $this->businessLogicService->getMonitoringConfig($request),
            'timestamp' => now()->toISOString(),
        ];
    }
    
    /**
     * Apply adaptive rate limiting based on multiple factors
     */
    private function applyAdaptiveRateLimit(Request $request, string $trackingKey, string $securityLevel): array
    {
        // Check if should bypass rate limiting entirely
        if ($this->rateLimitService->shouldBypassRateLimit($request)) {
            return [
                'exceeded' => false,
                'bypassed' => true,
                'reason' => 'High trust Cloudflare user',
            ];
        }
        
        // Get endpoint-specific limit
        $endpointLimit = $this->rateLimitService->getEndpointSpecificLimit($request, $securityLevel);
        
        // Apply rate limiting with endpoint-specific limits
        $rateLimitResult = $this->rateLimitService->checkRateLimit($request, $trackingKey);
        
        // Override limit with endpoint-specific if lower
        if ($endpointLimit < $rateLimitResult['limit']) {
            $rateLimitResult['limit'] = $endpointLimit;
            $rateLimitResult['remaining'] = max(0, $endpointLimit - $rateLimitResult['current_count']);
            $rateLimitResult['exceeded'] = $rateLimitResult['current_count'] > $endpointLimit;
        }
        
        return $rateLimitResult;
    }
    
    /**
     * Handle rate limit exceeded scenario
     */
    private function handleRateLimitExceeded(Request $request, array $rateLimitResult): Response
    {
        // Log rate limit exceeded event
        $this->enhancedSecurityService->logEnhancedSecurityEvent(
            SecurityEventService::EVENT_RATE_LIMIT_HIT,
            SecurityEventService::SEVERITY_MEDIUM,
            'Adaptive rate limit exceeded',
            $request,
            [
                'rate_limit_result' => $rateLimitResult,
                'adaptive_explanation' => $this->rateLimitService->getRateLimitExplanation($request),
            ]
        );
        
        // Return rate limit response
        return response()->json([
            'message' => 'Rate limit exceeded',
            'limit' => $rateLimitResult['limit'],
            'current' => $rateLimitResult['current_count'],
            'reset_time' => $rateLimitResult['reset_time']->toISOString(),
        ], 429)->header('Retry-After', 60);
    }
    
    /**
     * Handle business logic violations
     */
    private function handleBusinessLogicViolations(Request $request, array $violations): void
    {
        foreach ($violations['violations'] as $violation) {
            $severity = match ($violation['severity']) {
                'high' => SecurityEventService::SEVERITY_HIGH,
                'medium' => SecurityEventService::SEVERITY_MEDIUM,
                default => SecurityEventService::SEVERITY_LOW,
            };
            
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                'business_logic_violation',
                $severity,
                $violation['message'],
                $request,
                [
                    'violation_details' => $violation,
                    'all_violations' => $violations,
                ]
            );
        }
        
        // Log to business logic service as well
        $this->businessLogicService->logBusinessLogicEvent($request, $violations);
    }
    
    /**
     * Perform post-request monitoring
     */
    private function performPostRequestMonitoring(Request $request, Response $response, array $securityAnalysis): void
    {
        $securityLevel = $securityAnalysis['security_level'];
        $monitoringConfig = $securityAnalysis['monitoring_config'];
        
        // Monitor based on security level and configuration
        if ($monitoringConfig['monitor_all_requests']) {
            $this->performComprehensiveMonitoring($request, $response, $securityAnalysis);
        }
        
        // Check for suspicious response patterns
        $this->checkResponsePatterns($request, $response, $securityAnalysis);
        
        // Update user behavior tracking
        $this->updateBehaviorTracking($request, $response, $securityAnalysis);
        
        // Check if user should be flagged
        $flagAnalysis = $this->trackingService->shouldFlagUser($request);
        if ($flagAnalysis['should_flag']) {
            $this->handleUserFlagging($request, $flagAnalysis, $securityAnalysis);
        }
    }
    
    /**
     * Perform comprehensive monitoring for sensitive endpoints
     */
    private function performComprehensiveMonitoring(Request $request, Response $response, array $securityAnalysis): void
    {
        $monitoringData = [
            'url' => $request->getPathInfo(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'security_analysis' => $securityAnalysis,
            'response_size' => strlen($response->getContent()),
        ];
        
        // Log to dedicated monitoring channel
        Log::channel('security')->info('Comprehensive Request Monitor', $monitoringData);
        
        // Check for specific response status codes
        if (in_array($response->getStatusCode(), [401, 403, 404, 500])) {
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                'suspicious_response_code',
                SecurityEventService::SEVERITY_MEDIUM,
                "Suspicious response code: {$response->getStatusCode()}",
                $request,
                $monitoringData
            );
        }
    }
    
    /**
     * Check response patterns for security issues
     */
    private function checkResponsePatterns(Request $request, Response $response, array $securityAnalysis): void
    {
        $contentLength = strlen($response->getContent());
        
        // Check for unusually large responses (potential data exfiltration)
        if ($contentLength > 5 * 1024 * 1024) { // 5MB threshold
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                SecurityEventService::EVENT_DATA_EXFILTRATION,
                SecurityEventService::SEVERITY_HIGH,
                'Large response detected',
                $request,
                [
                    'response_size_bytes' => $contentLength,
                    'response_size_mb' => round($contentLength / 1024 / 1024, 2),
                ]
            );
        }
        
        // Check for error patterns that might indicate probing
        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
            $behaviorAnalysis = $securityAnalysis['behavior_analysis'];
            
            // Multiple 4xx errors might indicate scanning
            if ($behaviorAnalysis['pattern'] === 'rapid_browsing' && 
                $behaviorAnalysis['risk_level'] === 'high') {
                $this->enhancedSecurityService->logEnhancedSecurityEvent(
                    'potential_scanning_activity',
                    SecurityEventService::SEVERITY_MEDIUM,
                    'Multiple 4xx responses with rapid browsing pattern',
                    $request,
                    [
                        'status_code' => $response->getStatusCode(),
                        'behavior_analysis' => $behaviorAnalysis,
                    ]
                );
            }
        }
    }
    
    /**
     * Update behavior tracking with response context
     */
    private function updateBehaviorTracking(Request $request, Response $response, array $securityAnalysis): void
    {
        $this->trackingService->trackUserActivity($request, 'response', [
            'status_code' => $response->getStatusCode(),
            'security_level' => $securityAnalysis['security_level'],
            'trust_classification' => $securityAnalysis['trust_analysis']['classification'],
            'response_size' => strlen($response->getContent()),
        ]);
    }
    
    /**
     * Handle user flagging based on behavior analysis
     */
    private function handleUserFlagging(Request $request, array $flagAnalysis, array $securityAnalysis): void
    {
        $this->enhancedSecurityService->logEnhancedSecurityEvent(
            'user_behavior_flagged',
            SecurityEventService::SEVERITY_HIGH,
            'User flagged based on behavior analysis',
            $request,
            [
                'flag_analysis' => $flagAnalysis,
                'security_analysis' => $securityAnalysis,
                'recommended_actions' => [
                    'Increase monitoring level',
                    'Apply stricter rate limits',
                    'Review recent activity',
                ],
            ]
        );
    }
    
    /**
     * Get comprehensive security status for request
     */
    public function getSecurityStatus(Request $request): array
    {
        $trackingKey = $this->trackingService->generateTrackingKey($request);
        $securityLevel = $this->businessLogicService->getEndpointSecurityLevel($request);
        
        return [
            'tracking_key' => $trackingKey,
            'security_level' => $securityLevel,
            'cloudflare_status' => $this->cloudflareService->getSecurityContext($request),
            'trust_analysis' => $this->cloudflareService->analyzeTrustLevel($request),
            'rate_limit_stats' => $this->rateLimitService->getRateLimitStats($request, $trackingKey),
            'behavior_analysis' => $this->trackingService->analyzeUserBehavior($request),
            'business_recommendations' => $this->businessLogicService->getSecurityRecommendations($request),
        ];
    }
}