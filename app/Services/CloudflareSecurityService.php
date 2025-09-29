<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ========================================
 * CLOUDFLARE SECURITY SERVICE
 * Cloudflare security header integration and analysis
 * Following workinginstruction.md: Separate file for each function
 * ========================================
 */
class CloudflareSecurityService
{
    /**
     * Get Cloudflare bot detection score (1-100)
     * Lower scores indicate more human-like behavior
     * 
     * @param Request $request
     * @return int|null Bot score or null if not available
     */
    public function getBotScore(Request $request): ?int
    {
        $score = $request->headers->get('CF-Bot-Management-Score');
        
        if ($score !== null && is_numeric($score)) {
            return (int) $score;
        }
        
        return null;
    }
    
    /**
     * Check if Cloudflare classified request as bot
     * 
     * @param Request $request
     * @return bool True if classified as bot
     */
    public function isCloudflareBot(Request $request): bool
    {
        $botManagement = $request->headers->get('CF-Bot-Management');
        return strtolower($botManagement) === 'bot';
    }
    
    /**
     * Get Cloudflare threat score
     * Higher scores indicate more threatening behavior
     * 
     * @param Request $request
     * @return int|null Threat score or null if not available
     */
    public function getThreatScore(Request $request): ?int
    {
        $score = $request->headers->get('CF-Threat-Score');
        
        if ($score !== null && is_numeric($score)) {
            return (int) $score;
        }
        
        return null;
    }
    
    /**
     * Get visitor's country from Cloudflare
     * 
     * @param Request $request
     * @return string|null Country code or null if not available
     */
    public function getVisitorCountry(Request $request): ?string
    {
        return $request->headers->get('CF-IPCountry');
    }
    
    /**
     * Check if request is protected by Cloudflare
     * 
     * @param Request $request
     * @return bool True if Cloudflare headers present
     */
    public function isCloudflareProtected(Request $request): bool
    {
        return $request->headers->has('CF-Ray') || 
               $request->headers->has('CF-Visitor') ||
               $request->headers->has('CF-Connecting-IP');
    }
    
    /**
     * Get Cloudflare Ray ID for request tracking
     * 
     * @param Request $request
     * @return string|null Ray ID or null if not available
     */
    public function getRayId(Request $request): ?string
    {
        return $request->headers->get('CF-Ray');
    }
    
    /**
     * Get visitor's real IP from Cloudflare
     * 
     * @param Request $request
     * @return string|null Real IP or null if not available
     */
    public function getVisitorRealIP(Request $request): ?string
    {
        return $request->headers->get('CF-Connecting-IP');
    }
    
    /**
     * Check if visitor is using HTTPS through Cloudflare
     * 
     * @param Request $request
     * @return bool True if HTTPS connection
     */
    public function isHttpsVisitor(Request $request): bool
    {
        $visitor = $request->headers->get('CF-Visitor');
        
        if ($visitor) {
            $decoded = json_decode($visitor, true);
            return isset($decoded['scheme']) && $decoded['scheme'] === 'https';
        }
        
        return false;
    }
    
    /**
     * Get comprehensive Cloudflare context for security analysis
     * 
     * @param Request $request
     * @return array Cloudflare security context
     */
    public function getSecurityContext(Request $request): array
    {
        return [
            'cf_protected' => $this->isCloudflareProtected($request),
            'cf_bot_score' => $this->getBotScore($request),
            'cf_is_bot' => $this->isCloudflareBot($request),
            'cf_threat_score' => $this->getThreatScore($request),
            'cf_country' => $this->getVisitorCountry($request),
            'cf_ray_id' => $this->getRayId($request),
            'cf_real_ip' => $this->getVisitorRealIP($request),
            'cf_https' => $this->isHttpsVisitor($request),
            'cf_headers_available' => $this->getAvailableHeaders($request),
        ];
    }
    
    /**
     * Get list of available Cloudflare headers for debugging
     * 
     * @param Request $request
     * @return array Available CF headers
     */
    public function getAvailableHeaders(Request $request): array
    {
        $cfHeaders = [];
        
        foreach ($request->headers->all() as $name => $values) {
            if (str_starts_with(strtoupper($name), 'CF-')) {
                $cfHeaders[$name] = $values[0] ?? null;
            }
        }
        
        return $cfHeaders;
    }
    
    /**
     * Log Cloudflare security analysis for debugging
     * 
     * @param Request $request
     * @param array $context Additional context
     * @return void
     */
    public function logSecurityAnalysis(Request $request, array $context = []): void
    {
        $analysis = array_merge($this->getSecurityContext($request), $context);
        
        Log::channel('security')->info('Cloudflare Security Analysis', [
            'url' => $request->getPathInfo(),
            'method' => $request->method(),
            'cloudflare_analysis' => $analysis,
            'timestamp' => now()->toISOString(),
        ]);
    }
    
    /**
     * Determine if request should be trusted based on Cloudflare analysis
     * 
     * @param Request $request
     * @return array Trust analysis result
     */
    public function analyzeTrustLevel(Request $request): array
    {
        $context = $this->getSecurityContext($request);
        $trustScore = 50; // Neutral starting point
        $reasons = [];
        
        // Increase trust for Cloudflare protection
        if ($context['cf_protected']) {
            $trustScore += 20;
            $reasons[] = 'Cloudflare protected';
        }
        
        // Analyze bot score
        if ($context['cf_bot_score'] !== null) {
            if ($context['cf_bot_score'] < 30) {
                $trustScore += 25;
                $reasons[] = 'Low bot score (likely human)';
            } elseif ($context['cf_bot_score'] > 70) {
                $trustScore -= 30;
                $reasons[] = 'High bot score (likely automated)';
            }
        }
        
        // Check for explicit bot classification
        if ($context['cf_is_bot']) {
            $trustScore -= 40;
            $reasons[] = 'Explicitly classified as bot';
        }
        
        // Analyze threat score
        if ($context['cf_threat_score'] !== null && $context['cf_threat_score'] > 50) {
            $trustScore -= 20;
            $reasons[] = 'High threat score';
        }
        
        // Ensure score bounds
        $trustScore = max(0, min(100, $trustScore));
        
        return [
            'trust_score' => $trustScore,
            'classification' => $this->getTrustClassification($trustScore),
            'reasons' => $reasons,
            'cloudflare_context' => $context,
        ];
    }
    
    /**
     * Get trust classification based on score
     * 
     * @param int $score Trust score (0-100)
     * @return string Classification level
     */
    private function getTrustClassification(int $score): string
    {
        if ($score >= 80) {
            return 'high_trust';
        } elseif ($score >= 60) {
            return 'medium_trust';
        } elseif ($score >= 40) {
            return 'low_trust';
        } else {
            return 'untrusted';
        }
    }
}