<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ========================================
 * BUSINESS LOGIC SECURITY SERVICE
 * Focus security monitoring on sensitive endpoints vs general browsing
 * Following workinginstruction.md: Separate file for business logic security
 * ========================================
 */
class BusinessLogicSecurityService
{
    // Endpoint sensitivity levels
    private const CRITICAL_ENDPOINTS = [
        '/admin', '/api/admin', '/dashboard/admin'
    ];
    
    private const SENSITIVE_ENDPOINTS = [
        '/login', '/register', '/password/reset', '/password/email',
        '/api/auth', '/api/user', '/profile', '/settings'
    ];
    
    private const API_ENDPOINTS = [
        '/api/', '/graphql', '/webhook'
    ];
    
    private const DOWNLOAD_ENDPOINTS = [
        '/download', '/export', '/backup', '/stream'
    ];
    
    private const SEARCH_ENDPOINTS = [
        '/search', '/api/search', '/movies/search', '/series/search'
    ];
    
    /**
     * Determine endpoint security level
     * 
     * @param Request $request
     * @return string Security level (critical|sensitive|api|download|search|browsing)
     */
    public function getEndpointSecurityLevel(Request $request): string
    {
        $path = $request->getPathInfo();
        
        // Check critical endpoints first
        foreach (self::CRITICAL_ENDPOINTS as $endpoint) {
            if (str_starts_with($path, $endpoint)) {
                return 'critical';
            }
        }
        
        // Check sensitive endpoints
        foreach (self::SENSITIVE_ENDPOINTS as $endpoint) {
            if (str_starts_with($path, $endpoint)) {
                return 'sensitive';
            }
        }
        
        // Check API endpoints
        foreach (self::API_ENDPOINTS as $endpoint) {
            if (str_starts_with($path, $endpoint)) {
                return 'api';
            }
        }
        
        // Check download endpoints
        foreach (self::DOWNLOAD_ENDPOINTS as $endpoint) {
            if (str_contains($path, $endpoint)) {
                return 'download';
            }
        }
        
        // Check search endpoints
        foreach (self::SEARCH_ENDPOINTS as $endpoint) {
            if (str_contains($path, $endpoint)) {
                return 'search';
            }
        }
        
        // Default to browsing
        return 'browsing';
    }
    
    /**
     * Check if endpoint requires enhanced monitoring
     * 
     * @param Request $request
     * @return bool Requires enhanced monitoring
     */
    public function requiresEnhancedMonitoring(Request $request): bool
    {
        $securityLevel = $this->getEndpointSecurityLevel($request);
        
        return in_array($securityLevel, ['critical', 'sensitive']);
    }
    
    /**
     * Check if endpoint should have strict rate limiting
     * 
     * @param Request $request
     * @return bool Should apply strict rate limiting
     */
    public function requiresStrictRateLimit(Request $request): bool
    {
        $securityLevel = $this->getEndpointSecurityLevel($request);
        
        return in_array($securityLevel, ['critical', 'sensitive', 'download']);
    }
    
    /**
     * Get monitoring configuration for endpoint
     * 
     * @param Request $request
     * @return array Monitoring configuration
     */
    public function getMonitoringConfig(Request $request): array
    {
        $securityLevel = $this->getEndpointSecurityLevel($request);
        
        switch ($securityLevel) {
            case 'critical':
                return [
                    'monitor_all_requests' => true,
                    'log_request_body' => true,
                    'log_response_headers' => true,
                    'require_authentication' => true,
                    'max_requests_per_minute' => 15,
                    'alert_threshold' => 5,
                    'suspicious_patterns' => $this->getCriticalPatterns(),
                ];
                
            case 'sensitive':
                return [
                    'monitor_all_requests' => true,
                    'log_request_body' => false,
                    'log_response_headers' => true,
                    'require_authentication' => false,
                    'max_requests_per_minute' => 20,
                    'alert_threshold' => 10,
                    'suspicious_patterns' => $this->getSensitivePatterns(),
                ];
                
            case 'api':
                return [
                    'monitor_all_requests' => false,
                    'log_request_body' => false,
                    'log_response_headers' => false,
                    'require_authentication' => false,
                    'max_requests_per_minute' => 30,
                    'alert_threshold' => 50,
                    'suspicious_patterns' => $this->getApiPatterns(),
                ];
                
            case 'download':
                return [
                    'monitor_all_requests' => true,
                    'log_request_body' => false,
                    'log_response_headers' => true,
                    'require_authentication' => false,
                    'max_requests_per_minute' => 5,
                    'alert_threshold' => 3,
                    'suspicious_patterns' => $this->getDownloadPatterns(),
                ];
                
            case 'search':
                return [
                    'monitor_all_requests' => false,
                    'log_request_body' => false,
                    'log_response_headers' => false,
                    'require_authentication' => false,
                    'max_requests_per_minute' => 25,
                    'alert_threshold' => 30,
                    'suspicious_patterns' => $this->getSearchPatterns(),
                ];
                
            default: // browsing
                return [
                    'monitor_all_requests' => false,
                    'log_request_body' => false,
                    'log_response_headers' => false,
                    'require_authentication' => false,
                    'max_requests_per_minute' => 60,
                    'alert_threshold' => 100,
                    'suspicious_patterns' => [],
                ];
        }
    }
    
    /**
     * Get suspicious patterns for critical endpoints
     * 
     * @return array Critical endpoint patterns
     */
    private function getCriticalPatterns(): array
    {
        return [
            'sql_injection' => '/union\s+select|drop\s+table|delete\s+from/i',
            'command_injection' => '/;\s*rm\s|;\s*cat\s|;\s*ls\s/i',
            'path_traversal' => '/\.\.(\/|\\\\)/i',
            'admin_brute_force' => '/wp-admin|phpmyadmin|admin\.php/i',
        ];
    }
    
    /**
     * Get suspicious patterns for sensitive endpoints
     * 
     * @return array Sensitive endpoint patterns
     */
    private function getSensitivePatterns(): array
    {
        return [
            'credential_stuffing' => '/password.*123|admin.*admin|test.*test/i',
            'xss_attempt' => '/<script|javascript:|vbscript:/i',
            'csrf_attempt' => '/csrf|xsrf/i',
        ];
    }
    
    /**
     * Get suspicious patterns for API endpoints
     * 
     * @return array API endpoint patterns
     */
    private function getApiPatterns(): array
    {
        return [
            'api_enumeration' => '/\/api\/v\d+\/.*\d{5,}/i',
            'mass_requests' => '/batch|bulk|mass/i',
        ];
    }
    
    /**
     * Get suspicious patterns for download endpoints
     * 
     * @return array Download endpoint patterns
     */
    private function getDownloadPatterns(): array
    {
        return [
            'mass_download' => '/download.*\*|bulk.*download/i',
            'sensitive_files' => '/\.env|config\.php|database\.sqlite/i',
        ];
    }
    
    /**
     * Get suspicious patterns for search endpoints
     * 
     * @return array Search endpoint patterns
     */
    private function getSearchPatterns(): array
    {
        return [
            'search_injection' => '/\*|%|union|select/i',
            'automation' => '/bot|crawler|scraper/i',
        ];
    }
    
    /**
     * Analyze request for business logic violations
     * 
     * @param Request $request
     * @return array Business logic analysis
     */
    public function analyzeBusinessLogicViolations(Request $request): array
    {
        $securityLevel = $this->getEndpointSecurityLevel($request);
        $config = $this->getMonitoringConfig($request);
        $violations = [];
        
        // Check authentication requirements
        if ($config['require_authentication'] && !auth()->check()) {
            $violations[] = [
                'type' => 'authentication_required',
                'severity' => 'high',
                'message' => 'Endpoint requires authentication',
            ];
        }
        
        // Check suspicious patterns
        if (!empty($config['suspicious_patterns'])) {
            $patternViolations = $this->checkSuspiciousPatterns($request, $config['suspicious_patterns']);
            $violations = array_merge($violations, $patternViolations);
        }
        
        // Check business-specific rules
        $businessViolations = $this->checkBusinessRules($request, $securityLevel);
        $violations = array_merge($violations, $businessViolations);
        
        return [
            'security_level' => $securityLevel,
            'violations' => $violations,
            'violation_count' => count($violations),
            'requires_action' => !empty($violations),
            'monitoring_config' => $config,
        ];
    }
    
    /**
     * Check request against suspicious patterns
     * 
     * @param Request $request
     * @param array $patterns
     * @return array Pattern violations
     */
    private function checkSuspiciousPatterns(Request $request, array $patterns): array
    {
        $violations = [];
        $requestContent = $request->getPathInfo() . '?' . $request->getQueryString();
        
        foreach ($patterns as $patternName => $pattern) {
            if (preg_match($pattern, $requestContent)) {
                $violations[] = [
                    'type' => 'suspicious_pattern',
                    'pattern_name' => $patternName,
                    'pattern' => $pattern,
                    'severity' => 'medium',
                    'message' => "Suspicious pattern detected: {$patternName}",
                ];
            }
        }
        
        return $violations;
    }
    
    /**
     * Check business-specific security rules
     * 
     * @param Request $request
     * @param string $securityLevel
     * @return array Business rule violations
     */
    private function checkBusinessRules(Request $request, string $securityLevel): array
    {
        $violations = [];
        
        // Critical endpoint specific rules
        if ($securityLevel === 'critical') {
            // Admin endpoints should only be accessed during business hours (optional)
            if ($this->isOutsideBusinessHours() && !$this->isEmergencyAccess($request)) {
                $violations[] = [
                    'type' => 'business_hours_violation',
                    'severity' => 'medium',
                    'message' => 'Admin access outside business hours',
                ];
            }
        }
        
        // Download endpoint specific rules
        if ($securityLevel === 'download') {
            // Check for rapid sequential downloads
            if ($this->isRapidDownloadAttempt($request)) {
                $violations[] = [
                    'type' => 'rapid_download_attempt',
                    'severity' => 'high',
                    'message' => 'Rapid sequential download detected',
                ];
            }
        }
        
        // API endpoint specific rules
        if ($securityLevel === 'api') {
            // Check for API abuse patterns
            if ($this->isApiAbusePattern($request)) {
                $violations[] = [
                    'type' => 'api_abuse_pattern',
                    'severity' => 'medium',
                    'message' => 'API abuse pattern detected',
                ];
            }
        }
        
        return $violations;
    }
    
    /**
     * Check if current time is outside business hours
     * 
     * @return bool Outside business hours
     */
    private function isOutsideBusinessHours(): bool
    {
        // Simple business hours check (9 AM - 6 PM)
        $hour = (int) now()->format('H');
        return $hour < 9 || $hour > 18;
    }
    
    /**
     * Check if this is emergency access
     * 
     * @param Request $request
     * @return bool Is emergency access
     */
    private function isEmergencyAccess(Request $request): bool
    {
        // Check for emergency access patterns or authenticated super admin
        return auth()->check() && 
               auth()->user()->hasRole('super_admin') &&
               $request->hasHeader('X-Emergency-Access');
    }
    
    /**
     * Check for rapid download attempts
     * 
     * @param Request $request
     * @return bool Is rapid download attempt
     */
    private function isRapidDownloadAttempt(Request $request): bool
    {
        // Implementation would check cache for recent download requests
        // This is a placeholder for the business logic
        return false;
    }
    
    /**
     * Check for API abuse patterns
     * 
     * @param Request $request
     * @return bool Is API abuse pattern
     */
    private function isApiAbusePattern(Request $request): bool
    {
        // Check for patterns like:
        // - Same endpoint called rapidly
        // - Suspicious parameter patterns
        // - Missing or invalid API key
        return false;
    }
    
    /**
     * Get security recommendations for endpoint
     * 
     * @param Request $request
     * @return array Security recommendations
     */
    public function getSecurityRecommendations(Request $request): array
    {
        $securityLevel = $this->getEndpointSecurityLevel($request);
        $analysis = $this->analyzeBusinessLogicViolations($request);
        
        $recommendations = [];
        
        // Based on security level
        switch ($securityLevel) {
            case 'critical':
                $recommendations[] = 'Implement multi-factor authentication';
                $recommendations[] = 'Log all access attempts';
                $recommendations[] = 'Monitor for privilege escalation';
                break;
                
            case 'sensitive':
                $recommendations[] = 'Implement CSRF protection';
                $recommendations[] = 'Rate limit aggressively';
                $recommendations[] = 'Monitor for brute force attacks';
                break;
                
            case 'download':
                $recommendations[] = 'Implement download quotas';
                $recommendations[] = 'Monitor bandwidth usage';
                $recommendations[] = 'Check file access permissions';
                break;
        }
        
        // Based on violations
        if ($analysis['violation_count'] > 0) {
            $recommendations[] = 'Increase monitoring level';
            $recommendations[] = 'Consider temporary rate limiting';
        }
        
        return [
            'security_level' => $securityLevel,
            'recommendations' => $recommendations,
            'analysis' => $analysis,
        ];
    }
    
    /**
     * Log business logic security event
     * 
     * @param Request $request
     * @param array $analysis
     * @return void
     */
    public function logBusinessLogicEvent(Request $request, array $analysis): void
    {
        if ($analysis['requires_action']) {
            Log::channel('security')->warning('Business Logic Security Violation', [
                'endpoint' => $request->getPathInfo(),
                'method' => $request->method(),
                'security_level' => $analysis['security_level'],
                'violations' => $analysis['violations'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
}