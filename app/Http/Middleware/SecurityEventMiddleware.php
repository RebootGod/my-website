<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SecurityEventService;
use Illuminate\Support\Facades\Auth;

/**
 * ========================================
 * SECURITY EVENT MONITORING MIDDLEWARE
 * Real-time security event detection and logging
 * ========================================
 */
class SecurityEventMiddleware
{
    /**
     * Handle an incoming request and monitor for security events
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Monitor after request processing
        $this->monitorSecurityEvents($request, $response);
        
        return $response;
    }
    
    /**
     * Monitor and log various security events
     */
    private function monitorSecurityEvents(Request $request, Response $response): void
    {
        $securityService = app(SecurityEventService::class);
        
        // Monitor for unauthorized access attempts (403/401 responses)
        if (in_array($response->getStatusCode(), [401, 403])) {
            $securityService->logUnauthorizedAccess(
                $request->getPathInfo(),
                $request->method()
            );
        }
        
        // Monitor for suspicious URL patterns
        $this->checkSuspiciousUrls($request, $securityService);
        
        // Monitor for admin access
        $this->monitorAdminAccess($request, $securityService);
        
        // Monitor for potential data exfiltration
        $this->checkDataExfiltration($request, $response, $securityService);
        
        // Monitor for suspicious request patterns
        $this->checkSuspiciousRequests($request, $securityService);
    }
    
    /**
     * Check for suspicious URL patterns
     */
    private function checkSuspiciousUrls(Request $request, SecurityEventService $securityService): void
    {
        $url = $request->getPathInfo();
        
        // Common attack patterns in URLs
        $suspiciousPatterns = [
            '/\.\./i', // Directory traversal
            '/etc\/passwd/i', // Linux system files
            '/proc\/self/i', // Process information
            '/\.env/i', // Environment files
            '/wp-admin/i', // WordPress admin (wrong platform)
            '/phpMyAdmin/i', // Database admin tools
            '/admin\.php/i', // Generic admin files
            '/config\.php/i', // Config files
            '/backup/i', // Backup files
            '/\.git/i', // Git repositories
            '/_debug/i', // Debug endpoints
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                $securityService->logSecurityEvent(
                    SecurityEventService::EVENT_SUSPICIOUS_LOGIN,
                    SecurityEventService::SEVERITY_MEDIUM,
                    "Suspicious URL access attempt: {$url}",
                    [
                        'url' => $url,
                        'pattern_matched' => $pattern,
                        'method' => $request->method(),
                    ]
                );
                break;
            }
        }
    }
    
    /**
     * Monitor admin access and actions
     */
    private function monitorAdminAccess(Request $request, SecurityEventService $securityService): void
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            $url = $request->getPathInfo();
            
            // Log admin actions
            if (str_starts_with($url, '/admin')) {
                $action = $this->extractAdminAction($url, $request->method());
                
                $securityService->logAdminAccess(
                    Auth::user(),
                    $action,
                    $url
                );
            }
        }
    }
    
    /**
     * Check for potential data exfiltration
     */
    private function checkDataExfiltration(Request $request, Response $response, SecurityEventService $securityService): void
    {
        // Monitor large response sizes (potential data dumps)
        $responseSize = strlen($response->getContent());
        
        if ($responseSize > 1024 * 1024) { // 1MB threshold
            $securityService->logSecurityEvent(
                SecurityEventService::EVENT_DATA_EXFILTRATION,
                SecurityEventService::SEVERITY_MEDIUM,
                "Large response detected - potential data exfiltration",
                [
                    'response_size' => $responseSize,
                    'url' => $request->getPathInfo(),
                    'method' => $request->method(),
                ]
            );
        }
        
        // Monitor for bulk data requests
        $bulkDataPatterns = [
            '/export/i',
            '/download/i',
            '/backup/i',
            '/dump/i',
            '/all/i',
        ];
        
        $url = $request->getPathInfo();
        foreach ($bulkDataPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                $securityService->logSecurityEvent(
                    SecurityEventService::EVENT_DATA_EXFILTRATION,
                    SecurityEventService::SEVERITY_LOW,
                    "Bulk data request detected: {$url}",
                    [
                        'url' => $url,
                        'pattern_matched' => $pattern,
                        'response_size' => $responseSize,
                    ]
                );
                break;
            }
        }
    }
    
    /**
     * Check for suspicious request patterns
     */
    private function checkSuspiciousRequests(Request $request, SecurityEventService $securityService): void
    {
        $userAgent = $request->userAgent();
        
        // Check for suspicious user agents
        if (empty($userAgent) || strlen($userAgent) < 5) {
            $securityService->logSecurityEvent(
                SecurityEventService::EVENT_SUSPICIOUS_USER_AGENT,
                SecurityEventService::SEVERITY_LOW,
                "Suspicious user agent detected",
                [
                    'user_agent' => $userAgent,
                    'url' => $request->getPathInfo(),
                ]
            );
        }
        
        // Check for automation tools
        $automationTools = [
            'curl', 'wget', 'python-requests', 'postman', 'insomnia',
            'burp', 'nikto', 'sqlmap', 'nmap', 'masscan', 'zap'
        ];
        
        foreach ($automationTools as $tool) {
            if (stripos($userAgent, $tool) !== false) {
                $securityService->logSecurityEvent(
                    SecurityEventService::EVENT_SUSPICIOUS_USER_AGENT,
                    SecurityEventService::SEVERITY_MEDIUM,
                    "Automation tool detected: {$tool}",
                    [
                        'tool' => $tool,
                        'user_agent' => $userAgent,
                        'url' => $request->getPathInfo(),
                    ]
                );
                break;
            }
        }
        
        // Monitor request frequency (rapid requests from same IP)
        $this->monitorRequestFrequency($request, $securityService);
    }
    
    /**
     * Monitor request frequency for DOS/DDOS detection
     */
    private function monitorRequestFrequency(Request $request, SecurityEventService $securityService): void
    {
        $ip = $request->ip();
        $cacheKey = "request_freq:{$ip}";
        
        $requests = cache()->get($cacheKey, []);
        $now = time();
        
        // Remove requests older than 1 minute
        $requests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        });
        
        // Add current request
        $requests[] = $now;
        
        // Cache for 2 minutes
        cache()->put($cacheKey, $requests, now()->addMinutes(2));
        
        // Alert if more than 30 requests per minute
        if (count($requests) > 30) {
            $securityService->logSecurityEvent(
                SecurityEventService::EVENT_RATE_LIMIT_HIT,
                SecurityEventService::SEVERITY_HIGH,
                "High request frequency detected - possible DOS attack",
                [
                    'requests_per_minute' => count($requests),
                    'threshold' => 30,
                    'url' => $request->getPathInfo(),
                ],
                Auth::id(),
                $ip,
                $request->userAgent(),
                true // Requires alert
            );
        }
    }
    
    /**
     * Extract admin action from URL and method
     */
    private function extractAdminAction(string $url, string $method): string
    {
        $parts = explode('/', trim($url, '/'));
        
        if (count($parts) >= 2) {
            $resource = $parts[1] ?? 'unknown';
            
            switch ($method) {
                case 'GET':
                    return "View {$resource}";
                case 'POST':
                    return "Create {$resource}";
                case 'PUT':
                case 'PATCH':
                    return "Update {$resource}";
                case 'DELETE':
                    return "Delete {$resource}";
                default:
                    return "Access {$resource}";
            }
        }
        
        return 'Admin access';
    }
}