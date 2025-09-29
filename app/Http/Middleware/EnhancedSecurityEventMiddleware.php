<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\EnhancedSecurityEventService;
use App\Services\SecurityEventService;
use App\Services\CloudflareSecurityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ========================================
 * ENHANCED SECURITY EVENT MONITORING MIDDLEWARE
 * Real-time security event detection with Cloudflare integration
 * Following workinginstruction.md: Separate file for enhancements
 * ========================================
 */
class EnhancedSecurityEventMiddleware
{
    private EnhancedSecurityEventService $enhancedSecurityService;
    private CloudflareSecurityService $cloudflareService;
    
    public function __construct(
        EnhancedSecurityEventService $enhancedSecurityService,
        CloudflareSecurityService $cloudflareService
    ) {
        $this->enhancedSecurityService = $enhancedSecurityService;
        $this->cloudflareService = $cloudflareService;
    }
    
    /**
     * Handle an incoming request with enhanced security monitoring
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pre-request security analysis
        $this->preRequestSecurityCheck($request);
        
        $response = $next($request);
        
        // Post-request monitoring with Cloudflare context
        $this->postRequestSecurityMonitoring($request, $response);
        
        return $response;
    }
    
    /**
     * Pre-request security analysis using Cloudflare data
     */
    private function preRequestSecurityCheck(Request $request): void
    {
        // Get Cloudflare security context
        if (!$this->cloudflareService->isCloudflareProtected($request)) {
            // Log requests not protected by Cloudflare
            Log::channel('security')->info('Request not Cloudflare protected', [
                'ip' => $request->ip(),
                'url' => $request->getPathInfo(),
                'user_agent' => $request->userAgent(),
            ]);
            return;
        }
        
        // Get monitoring recommendations
        $recommendations = $this->enhancedSecurityService->getMonitoringRecommendations(
            $request->ip(),
            $request
        );
        
        // Store recommendations in request for use in post-processing
        $request->attributes->set('security_recommendations', $recommendations);
        
        // Check if IP should be flagged
        if ($this->enhancedSecurityService->shouldFlagIP($request->ip(), $request)) {
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                SecurityEventService::EVENT_SUSPICIOUS_LOGIN,
                SecurityEventService::SEVERITY_MEDIUM,
                'IP flagged for enhanced monitoring despite Cloudflare protection',
                $request,
                ['flagged_reason' => 'High threat score override']
            );
        }
    }
    
    /**
     * Enhanced post-request security monitoring
     */
    private function postRequestSecurityMonitoring(Request $request, Response $response): void
    {
        // Get security recommendations from pre-request analysis
        $recommendations = $request->attributes->get('security_recommendations', []);
        
        // Monitor based on recommendation level
        switch ($recommendations['monitoring_level'] ?? 'standard_monitoring') {
            case 'enhanced_monitoring_required':
                $this->performEnhancedMonitoring($request, $response);
                break;
                
            case 'increased_monitoring':
                $this->performIncreasedMonitoring($request, $response);
                break;
                
            case 'allow_minimal_monitoring':
                $this->performMinimalMonitoring($request, $response);
                break;
                
            default:
                $this->performStandardMonitoring($request, $response);
                break;
        }
        
        // Always check for critical security events
        $this->checkCriticalSecurityEvents($request, $response);
    }
    
    /**
     * Enhanced monitoring for high-risk requests
     */
    private function performEnhancedMonitoring(Request $request, Response $response): void
    {
        // Monitor all aspects with low thresholds
        $this->checkRequestFrequency($request, 15); // Stricter limit: 15 req/min
        $this->checkSuspiciousPatterns($request, true);
        $this->checkUnauthorizedAccess($request, $response);
        $this->checkAdminAccess($request);
        $this->checkAutomationTools($request);
        
        // Log enhanced monitoring activation
        $this->enhancedSecurityService->logEnhancedSecurityEvent(
            'enhanced_monitoring_activated',
            SecurityEventService::SEVERITY_MEDIUM,
            'Enhanced monitoring activated for high-risk IP',
            $request,
            ['monitoring_reason' => 'High threat score']
        );
    }
    
    /**
     * Increased monitoring for medium-risk requests
     */
    private function performIncreasedMonitoring(Request $request, Response $response): void
    {
        // Moderate monitoring with standard thresholds
        $this->checkRequestFrequency($request, 25); // Moderate limit: 25 req/min
        $this->checkSuspiciousPatterns($request, false);
        $this->checkUnauthorizedAccess($request, $response);
        $this->checkAdminAccess($request);
    }
    
    /**
     * Minimal monitoring for high-trust Cloudflare requests
     */
    private function performMinimalMonitoring(Request $request, Response $response): void
    {
        // Only check for critical security events
        $this->checkUnauthorizedAccess($request, $response);
        
        // Relaxed request frequency (trust Cloudflare's protection)
        $this->checkRequestFrequency($request, 60); // Generous limit: 60 req/min
    }
    
    /**
     * Standard monitoring for normal requests
     */
    private function performStandardMonitoring(Request $request, Response $response): void
    {
        // Standard security monitoring
        $this->checkRequestFrequency($request, 30); // Standard limit: 30 req/min
        $this->checkSuspiciousPatterns($request, false);
        $this->checkUnauthorizedAccess($request, $response);
        $this->checkAdminAccess($request);
        $this->checkAutomationTools($request);
    }
    
    /**
     * Check request frequency with dynamic limits
     */
    private function checkRequestFrequency(Request $request, int $limitPerMinute): void
    {
        $ip = $request->ip();
        $key = "request_frequency_{$ip}";
        
        $currentCount = cache()->get($key, 0);
        $newCount = $currentCount + 1;
        
        // Store count with 1-minute expiration
        cache()->put($key, $newCount, 60);
        
        if ($newCount > $limitPerMinute) {
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                SecurityEventService::EVENT_RATE_LIMIT_HIT,
                SecurityEventService::SEVERITY_MEDIUM,
                "Request frequency exceeded: {$newCount} requests per minute (limit: {$limitPerMinute})",
                $request,
                [
                    'current_count' => $newCount,
                    'limit' => $limitPerMinute,
                    'monitoring_level' => 'enhanced',
                ]
            );
        }
    }
    
    /**
     * Check for suspicious URL patterns
     */
    private function checkSuspiciousPatterns(Request $request, bool $strictMode = false): void
    {
        $path = $request->getPathInfo();
        $query = $request->getQueryString();
        
        $suspiciousPatterns = [
            // Standard patterns
            '/\.\.(\/|\\\\)/',  // Directory traversal
            '/union\s+select/i', // SQL injection
            '/<script/i',        // XSS attempts
            '/eval\s*\(/i',      // Code injection
            '/base64_decode/i',  // Obfuscation
            
            // Additional strict patterns
            ...$strictMode ? [
                '/admin/i',          // Admin access attempts
                '/wp-admin/i',       // WordPress admin
                '/phpmyadmin/i',     // Database admin
                '/\.env/i',          // Environment files
            ] : []
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $path . '?' . $query)) {
                $severity = $strictMode ? SecurityEventService::SEVERITY_HIGH : SecurityEventService::SEVERITY_MEDIUM;
                
                $this->enhancedSecurityService->logEnhancedSecurityEvent(
                    SecurityEventService::EVENT_INJECTION_ATTEMPT,
                    $severity,
                    "Suspicious URL pattern detected: {$pattern}",
                    $request,
                    [
                        'pattern' => $pattern,
                        'strict_mode' => $strictMode,
                        'full_url' => $request->fullUrl(),
                    ]
                );
                break;
            }
        }
    }
    
    /**
     * Check for unauthorized access attempts
     */
    private function checkUnauthorizedAccess(Request $request, Response $response): void
    {
        if (in_array($response->getStatusCode(), [401, 403])) {
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                SecurityEventService::EVENT_UNAUTHORIZED_ACCESS,
                SecurityEventService::SEVERITY_MEDIUM,
                "Unauthorized access attempt: {$response->getStatusCode()} response",
                $request,
                [
                    'status_code' => $response->getStatusCode(),
                    'attempted_resource' => $request->getPathInfo(),
                ]
            );
        }
    }
    
    /**
     * Check for admin access attempts
     */
    private function checkAdminAccess(Request $request): void
    {
        if (str_contains($request->getPathInfo(), '/admin')) {
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                SecurityEventService::EVENT_ADMIN_ACCESS,
                SecurityEventService::SEVERITY_HIGH,
                'Admin area access attempt',
                $request,
                [
                    'admin_path' => $request->getPathInfo(),
                    'authenticated' => auth()->check(),
                    'user_id' => auth()->id(),
                ]
            );
        }
    }
    
    /**
     * Check for automation tools and bots
     */
    private function checkAutomationTools(Request $request): void
    {
        $userAgent = $request->userAgent() ?? '';
        
        $automationPatterns = [
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/postman/i',
            '/insomnia/i',
            '/bot/i',
            '/crawler/i',
            '/spider/i',
        ];
        
        foreach ($automationPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                // Check Cloudflare bot classification first
                if (!$this->cloudflareService->isCloudflareBot($request)) {
                    $this->enhancedSecurityService->logEnhancedSecurityEvent(
                        SecurityEventService::EVENT_SUSPICIOUS_USER_AGENT,
                        SecurityEventService::SEVERITY_LOW,
                        'Automation tool detected (not classified by Cloudflare)',
                        $request,
                        [
                            'user_agent' => $userAgent,
                            'pattern_matched' => $pattern,
                            'cf_bot_classified' => false,
                        ]
                    );
                }
                break;
            }
        }
    }
    
    /**
     * Check for critical security events that always need attention
     */
    private function checkCriticalSecurityEvents(Request $request, Response $response): void
    {
        // Check for potential data exfiltration (large responses)
        $contentLength = $response->headers->get('Content-Length', 0);
        if ($contentLength > 10 * 1024 * 1024) { // 10MB threshold
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                SecurityEventService::EVENT_DATA_EXFILTRATION,
                SecurityEventService::SEVERITY_HIGH,
                'Large response detected - potential data exfiltration',
                $request,
                [
                    'response_size_bytes' => $contentLength,
                    'response_size_mb' => round($contentLength / 1024 / 1024, 2),
                ]
            );
        }
        
        // Check for session-related attacks
        if ($request->hasHeader('X-Forwarded-For') && !$this->cloudflareService->isCloudflareProtected($request)) {
            $this->enhancedSecurityService->logEnhancedSecurityEvent(
                SecurityEventService::EVENT_SESSION_HIJACKING,
                SecurityEventService::SEVERITY_MEDIUM,
                'X-Forwarded-For header without Cloudflare protection',
                $request,
                [
                    'x_forwarded_for' => $request->header('X-Forwarded-For'),
                    'direct_ip' => $request->ip(),
                ]
            );
        }
    }
}