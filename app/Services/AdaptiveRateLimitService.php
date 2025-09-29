<?php

namespace App\Services;

use App\Services\CloudflareSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ========================================
 * ADAPTIVE RATE LIMITING SERVICE
 * Dynamic rate limiting based on Cloudflare intelligence
 * Following workinginstruction.md: Separate file for rate limiting functionality
 * ========================================
 */
class AdaptiveRateLimitService
{
    private CloudflareSecurityService $cloudflareService;
    
    // Default rate limits (requests per minute)
    private const DEFAULT_RATE_LIMIT = 30;
    private const HIGH_TRUST_LIMIT = 100;
    private const LIKELY_HUMAN_LIMIT = 60;
    private const SUSPECTED_BOT_LIMIT = 10;
    private const CONFIRMED_BOT_LIMIT = 5;
    
    public function __construct(CloudflareSecurityService $cloudflareService)
    {
        $this->cloudflareService = $cloudflareService;
    }
    
    /**
     * Get adaptive rate limit based on Cloudflare analysis
     * 
     * @param Request $request
     * @return int Requests per minute allowed
     */
    public function getAdaptiveRateLimit(Request $request): int
    {
        // Get Cloudflare security context
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        $botScore = $this->cloudflareService->getBotScore($request);
        $isCloudflareBot = $this->cloudflareService->isCloudflareBot($request);
        
        // Highest trust level - very generous limits
        if ($trustAnalysis['classification'] === 'high_trust') {
            return self::HIGH_TRUST_LIMIT;
        }
        
        // Confirmed bot by Cloudflare - very strict
        if ($isCloudflareBot) {
            return self::CONFIRMED_BOT_LIMIT;
        }
        
        // Bot score based limits
        if ($botScore !== null) {
            if ($botScore < 30) {
                // Likely human - generous limits
                return self::LIKELY_HUMAN_LIMIT;
            } elseif ($botScore > 70) {
                // Likely bot - strict limits
                return self::SUSPECTED_BOT_LIMIT;
            }
        }
        
        // Medium trust - standard limits
        if ($trustAnalysis['classification'] === 'medium_trust') {
            return self::DEFAULT_RATE_LIMIT;
        }
        
        // Low/untrusted - reduced limits
        return self::SUSPECTED_BOT_LIMIT;
    }
    
    /**
     * Check if request should be rate limited
     * 
     * @param Request $request
     * @param string $trackingKey
     * @return array Rate limit status
     */
    public function checkRateLimit(Request $request, string $trackingKey): array
    {
        $limit = $this->getAdaptiveRateLimit($request);
        $currentCount = $this->getCurrentRequestCount($trackingKey);
        $newCount = $currentCount + 1;
        
        // Store updated count
        $this->updateRequestCount($trackingKey, $newCount);
        
        $isExceeded = $newCount > $limit;
        
        return [
            'exceeded' => $isExceeded,
            'current_count' => $newCount,
            'limit' => $limit,
            'remaining' => max(0, $limit - $newCount),
            'reset_time' => now()->addMinute(),
            'tracking_key' => $trackingKey,
            'cloudflare_context' => $this->cloudflareService->getSecurityContext($request),
        ];
    }
    
    /**
     * Get current request count for tracking key
     * 
     * @param string $trackingKey
     * @return int Current request count
     */
    private function getCurrentRequestCount(string $trackingKey): int
    {
        return Cache::get("rate_limit:{$trackingKey}", 0);
    }
    
    /**
     * Update request count with expiration
     * 
     * @param string $trackingKey
     * @param int $count
     * @return void
     */
    private function updateRequestCount(string $trackingKey, int $count): void
    {
        Cache::put("rate_limit:{$trackingKey}", $count, 60); // 1 minute TTL
    }
    
    /**
     * Get rate limit explanation for logging
     * 
     * @param Request $request
     * @return array Rate limit reasoning
     */
    public function getRateLimitExplanation(Request $request): array
    {
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        $botScore = $this->cloudflareService->getBotScore($request);
        $limit = $this->getAdaptiveRateLimit($request);
        
        $factors = [];
        
        if ($trustAnalysis['classification'] === 'high_trust') {
            $factors[] = "High Cloudflare trust level (+{$limit} req/min)";
        } elseif ($trustAnalysis['classification'] === 'medium_trust') {
            $factors[] = "Medium Cloudflare trust level ({$limit} req/min)";
        }
        
        if ($botScore !== null && $botScore < 30) {
            $factors[] = "Low bot score ({$botScore}) - likely human (+generous limits)";
        } elseif ($botScore !== null && $botScore > 70) {
            $factors[] = "High bot score ({$botScore}) - likely bot (-strict limits)";
        }
        
        if ($this->cloudflareService->isCloudflareBot($request)) {
            $factors[] = "Confirmed bot by Cloudflare (-very strict limits)";
        }
        
        return [
            'final_limit' => $limit,
            'trust_classification' => $trustAnalysis['classification'],
            'bot_score' => $botScore,
            'factors' => $factors,
            'reasoning' => implode(', ', $factors),
        ];
    }
    
    /**
     * Get recommended rate limit for endpoint type
     * 
     * @param Request $request
     * @param string $endpointType
     * @return int Recommended rate limit
     */
    public function getEndpointSpecificLimit(Request $request, string $endpointType): int
    {
        $baseLimit = $this->getAdaptiveRateLimit($request);
        
        // Adjust based on endpoint sensitivity
        switch ($endpointType) {
            case 'login':
                return min($baseLimit, 10); // Max 10 login attempts per minute
                
            case 'admin':
                return min($baseLimit, 15); // Max 15 admin requests per minute
                
            case 'api':
                return min($baseLimit, 20); // Max 20 API calls per minute
                
            case 'download':
                return min($baseLimit, 5); // Max 5 downloads per minute
                
            case 'search':
                return min($baseLimit, 25); // Max 25 searches per minute
                
            case 'browsing':
                return $baseLimit; // Full adaptive limit for normal browsing
                
            default:
                return $baseLimit;
        }
    }
    
    /**
     * Check if IP should bypass rate limiting
     * 
     * @param Request $request
     * @return bool Should bypass rate limiting
     */
    public function shouldBypassRateLimit(Request $request): bool
    {
        // Bypass for very high trust Cloudflare users
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        
        if ($trustAnalysis['classification'] === 'high_trust' && 
            $trustAnalysis['trust_score'] >= 90) {
            return true;
        }
        
        // Bypass for authenticated admin users with high trust
        if (auth()->check() && 
            auth()->user()->hasRole('admin') && 
            $trustAnalysis['trust_score'] >= 80) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Log rate limit event with adaptive context
     * 
     * @param Request $request
     * @param array $rateLimitStatus
     * @return void
     */
    public function logRateLimitEvent(Request $request, array $rateLimitStatus): void
    {
        $explanation = $this->getRateLimitExplanation($request);
        
        Log::channel('security')->info('Adaptive Rate Limit Applied', [
            'ip' => $request->ip(),
            'url' => $request->getPathInfo(),
            'rate_limit_status' => $rateLimitStatus,
            'adaptive_explanation' => $explanation,
            'cloudflare_context' => $rateLimitStatus['cloudflare_context'],
            'timestamp' => now()->toISOString(),
        ]);
    }
    
    /**
     * Get rate limiting statistics
     * 
     * @param Request $request
     * @param string $trackingKey
     * @return array Rate limiting stats
     */
    public function getRateLimitStats(Request $request, string $trackingKey): array
    {
        $currentCount = $this->getCurrentRequestCount($trackingKey);
        $limit = $this->getAdaptiveRateLimit($request);
        $percentage = $limit > 0 ? ($currentCount / $limit) * 100 : 0;
        
        return [
            'current_requests' => $currentCount,
            'rate_limit' => $limit,
            'usage_percentage' => round($percentage, 2),
            'remaining_requests' => max(0, $limit - $currentCount),
            'status' => $this->getRateLimitStatus($percentage),
            'cloudflare_trust' => $this->cloudflareService->analyzeTrustLevel($request)['classification'],
        ];
    }
    
    /**
     * Get rate limit status based on usage percentage
     * 
     * @param float $percentage
     * @return string Status level
     */
    private function getRateLimitStatus(float $percentage): string
    {
        if ($percentage >= 100) {
            return 'exceeded';
        } elseif ($percentage >= 80) {
            return 'warning';
        } elseif ($percentage >= 60) {
            return 'moderate';
        } else {
            return 'normal';
        }
    }
    
    /**
     * Clear rate limit for tracking key (admin function)
     * 
     * @param string $trackingKey
     * @return bool Success status
     */
    public function clearRateLimit(string $trackingKey): bool
    {
        return Cache::forget("rate_limit:{$trackingKey}");
    }
    
    /**
     * Get adaptive rate limiting configuration
     * 
     * @return array Configuration settings
     */
    public function getConfiguration(): array
    {
        return [
            'default_limit' => self::DEFAULT_RATE_LIMIT,
            'high_trust_limit' => self::HIGH_TRUST_LIMIT,
            'likely_human_limit' => self::LIKELY_HUMAN_LIMIT,
            'suspected_bot_limit' => self::SUSPECTED_BOT_LIMIT,
            'confirmed_bot_limit' => self::CONFIRMED_BOT_LIMIT,
            'window_minutes' => 1,
            'cache_ttl_seconds' => 60,
        ];
    }
}