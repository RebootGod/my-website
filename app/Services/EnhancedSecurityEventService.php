<?php

namespace App\Services;

use App\Services\SecurityEventService;
use App\Services\CloudflareSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ========================================
 * ENHANCED SECURITY EVENT SERVICE
 * SecurityEventService enhancement with Cloudflare integration
 * Following workinginstruction.md: Separate file for enhancements
 * ========================================
 */
class EnhancedSecurityEventService
{
    private SecurityEventService $securityEventService;
    private CloudflareSecurityService $cloudflareService;
    
    public function __construct(
        SecurityEventService $securityEventService,
        CloudflareSecurityService $cloudflareService
    ) {
        $this->securityEventService = $securityEventService;
        $this->cloudflareService = $cloudflareService;
    }
    
    /**
     * Enhanced IP threat scoring with Cloudflare context
     * 
     * @param string $ipAddress
     * @param Request $request
     * @return array Enhanced threat analysis
     */
    public function calculateEnhancedThreatScore(string $ipAddress, Request $request): array
    {
        // Get Cloudflare security context
        $cfContext = $this->cloudflareService->getSecurityContext($request);
        $trustAnalysis = $this->cloudflareService->analyzeTrustLevel($request);
        
        // Get base threat score from existing system
        $baseThreatScore = $this->getBaseThreatScore($ipAddress);
        
        // Adjust threat score based on Cloudflare analysis
        $adjustedScore = $this->adjustThreatScoreWithCloudflare(
            $baseThreatScore,
            $cfContext,
            $trustAnalysis
        );
        
        return [
            'ip_address' => $ipAddress,
            'base_threat_score' => $baseThreatScore,
            'adjusted_threat_score' => $adjustedScore,
            'cloudflare_context' => $cfContext,
            'trust_analysis' => $trustAnalysis,
            'adjustment_factors' => $this->getAdjustmentFactors($cfContext, $trustAnalysis),
            'final_classification' => $this->getFinalThreatClassification($adjustedScore),
            'recommended_action' => $this->getRecommendedAction($adjustedScore, $trustAnalysis),
        ];
    }
    
    /**
     * Get base threat score from existing SecurityEventService logic
     * 
     * @param string $ipAddress
     * @return int Base threat score
     */
    private function getBaseThreatScore(string $ipAddress): int
    {
        // Get existing threat data from cache/database
        $cacheKey = "ip_threat_score_{$ipAddress}";
        $cachedScore = Cache::get($cacheKey, 0);
        
        // Get recent security events for this IP
        $recentEvents = Cache::get("ip_events_{$ipAddress}", []);
        
        // Calculate base score from existing system logic
        $baseScore = $cachedScore;
        
        // Add points for recent security events
        foreach ($recentEvents as $event) {
            switch ($event['severity']) {
                case 'critical':
                    $baseScore += 50;
                    break;
                case 'high':
                    $baseScore += 25;
                    break;
                case 'medium':
                    $baseScore += 10;
                    break;
                case 'low':
                    $baseScore += 5;
                    break;
            }
        }
        
        return min(100, max(0, $baseScore));
    }
    
    /**
     * Adjust threat score based on Cloudflare analysis
     * 
     * @param int $baseScore
     * @param array $cfContext
     * @param array $trustAnalysis
     * @return int Adjusted threat score
     */
    private function adjustThreatScoreWithCloudflare(int $baseScore, array $cfContext, array $trustAnalysis): int
    {
        $adjustedScore = $baseScore;
        
        // Major reduction if Cloudflare trust is high
        if ($trustAnalysis['classification'] === 'high_trust') {
            $adjustedScore = max(0, $adjustedScore - 40);
        } elseif ($trustAnalysis['classification'] === 'medium_trust') {
            $adjustedScore = max(0, $adjustedScore - 20);
        }
        
        // Reduce score for Cloudflare protection
        if ($cfContext['cf_protected']) {
            $adjustedScore = max(0, $adjustedScore - 15);
        }
        
        // Adjust based on bot score
        if ($cfContext['cf_bot_score'] !== null) {
            if ($cfContext['cf_bot_score'] < 30) {
                // Likely human, reduce threat
                $adjustedScore = max(0, $adjustedScore - 25);
            } elseif ($cfContext['cf_bot_score'] > 70) {
                // Likely bot, but Cloudflare already handled malicious bots
                $adjustedScore = max(0, $adjustedScore - 10);
            }
        }
        
        // Consider Cloudflare threat score
        if ($cfContext['cf_threat_score'] !== null) {
            if ($cfContext['cf_threat_score'] < 20) {
                $adjustedScore = max(0, $adjustedScore - 20);
            } elseif ($cfContext['cf_threat_score'] > 50) {
                $adjustedScore = min(100, $adjustedScore + 15);
            }
        }
        
        return $adjustedScore;
    }
    
    /**
     * Get factors that influenced score adjustment
     * 
     * @param array $cfContext
     * @param array $trustAnalysis
     * @return array Adjustment factors
     */
    private function getAdjustmentFactors(array $cfContext, array $trustAnalysis): array
    {
        $factors = [];
        
        if ($cfContext['cf_protected']) {
            $factors[] = 'Cloudflare protection active (-15 points)';
        }
        
        if ($trustAnalysis['classification'] === 'high_trust') {
            $factors[] = 'High trust classification (-40 points)';
        } elseif ($trustAnalysis['classification'] === 'medium_trust') {
            $factors[] = 'Medium trust classification (-20 points)';
        }
        
        if ($cfContext['cf_bot_score'] !== null && $cfContext['cf_bot_score'] < 30) {
            $factors[] = "Low bot score ({$cfContext['cf_bot_score']}) - likely human (-25 points)";
        }
        
        if ($cfContext['cf_threat_score'] !== null && $cfContext['cf_threat_score'] < 20) {
            $factors[] = "Low Cloudflare threat score ({$cfContext['cf_threat_score']}) (-20 points)";
        }
        
        return $factors;
    }
    
    /**
     * Get final threat classification
     * 
     * @param int $score
     * @return string Classification
     */
    private function getFinalThreatClassification(int $score): string
    {
        if ($score >= 80) {
            return 'critical_threat';
        } elseif ($score >= 60) {
            return 'high_threat';
        } elseif ($score >= 40) {
            return 'medium_threat';
        } elseif ($score >= 20) {
            return 'low_threat';
        } else {
            return 'minimal_threat';
        }
    }
    
    /**
     * Get recommended action based on threat analysis
     * 
     * @param int $score
     * @param array $trustAnalysis
     * @return string Recommended action
     */
    private function getRecommendedAction(int $score, array $trustAnalysis): string
    {
        // High trust with Cloudflare - allow with minimal monitoring
        if ($trustAnalysis['classification'] === 'high_trust' && $score < 30) {
            return 'allow_minimal_monitoring';
        }
        
        // Medium trust - normal monitoring
        if ($trustAnalysis['classification'] === 'medium_trust' && $score < 50) {
            return 'allow_normal_monitoring';
        }
        
        // Low trust or higher score - enhanced monitoring
        if ($score >= 60) {
            return 'enhanced_monitoring_required';
        } elseif ($score >= 40) {
            return 'increased_monitoring';
        } else {
            return 'standard_monitoring';
        }
    }
    
    /**
     * Enhanced security event logging with Cloudflare context
     * 
     * @param string $eventType
     * @param string $severity
     * @param string $description
     * @param Request $request
     * @param array $additionalMetadata
     * @return void
     */
    public function logEnhancedSecurityEvent(
        string $eventType,
        string $severity,
        string $description,
        Request $request,
        array $additionalMetadata = []
    ): void {
        // Get enhanced threat analysis
        $ipAddress = $request->ip();
        $threatAnalysis = $this->calculateEnhancedThreatScore($ipAddress, $request);
        
        // Merge metadata with Cloudflare analysis
        $enhancedMetadata = array_merge($additionalMetadata, [
            'cloudflare_analysis' => $threatAnalysis,
            'original_ip' => $ipAddress,
            'cf_real_ip' => $threatAnalysis['cloudflare_context']['cf_real_ip'],
            'threat_adjustment' => [
                'base_score' => $threatAnalysis['base_threat_score'],
                'adjusted_score' => $threatAnalysis['adjusted_threat_score'],
                'factors' => $threatAnalysis['adjustment_factors'],
            ]
        ]);
        
        // Use original SecurityEventService with enhanced metadata
        $this->securityEventService->logSecurityEvent(
            $eventType,
            $severity,
            $description,
            $enhancedMetadata,
            auth()->id(),
            $threatAnalysis['cloudflare_context']['cf_real_ip'] ?? $ipAddress,
            $request->userAgent(),
            $threatAnalysis['final_classification'] === 'critical_threat'
        );
        
        // Log Cloudflare analysis separately for debugging
        $this->cloudflareService->logSecurityAnalysis($request, [
            'event_type' => $eventType,
            'threat_analysis' => $threatAnalysis,
        ]);
    }
    
    /**
     * Check if IP should be flagged based on enhanced analysis
     * 
     * @param string $ipAddress
     * @param Request $request
     * @return bool Should flag IP
     */
    public function shouldFlagIP(string $ipAddress, Request $request): bool
    {
        $analysis = $this->calculateEnhancedThreatScore($ipAddress, $request);
        
        // Don't flag high-trust Cloudflare-protected requests
        if ($analysis['trust_analysis']['classification'] === 'high_trust' && 
            $analysis['adjusted_threat_score'] < 40) {
            return false;
        }
        
        // Flag based on adjusted score
        return $analysis['adjusted_threat_score'] >= 60;
    }
    
    /**
     * Get smart monitoring recommendations for IP
     * 
     * @param string $ipAddress
     * @param Request $request
     * @return array Monitoring recommendations
     */
    public function getMonitoringRecommendations(string $ipAddress, Request $request): array
    {
        $analysis = $this->calculateEnhancedThreatScore($ipAddress, $request);
        
        return [
            'ip_address' => $ipAddress,
            'monitoring_level' => $analysis['recommended_action'],
            'threat_score' => $analysis['adjusted_threat_score'],
            'cloudflare_protected' => $analysis['cloudflare_context']['cf_protected'],
            'trust_level' => $analysis['trust_analysis']['classification'],
            'recommendations' => $this->generateMonitoringRecommendations($analysis),
        ];
    }
    
    /**
     * Generate specific monitoring recommendations
     * 
     * @param array $analysis
     * @return array Specific recommendations
     */
    private function generateMonitoringRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        if ($analysis['cloudflare_context']['cf_protected']) {
            $recommendations[] = 'Leverage Cloudflare edge security - reduce application-layer checks';
        }
        
        if ($analysis['trust_analysis']['classification'] === 'high_trust') {
            $recommendations[] = 'High trust level - focus on behavior patterns rather than IP blocking';
        }
        
        if ($analysis['adjusted_threat_score'] < 30) {
            $recommendations[] = 'Low threat score - minimal monitoring sufficient';
        } elseif ($analysis['adjusted_threat_score'] > 70) {
            $recommendations[] = 'High threat score - implement enhanced monitoring';
        }
        
        return $recommendations;
    }
}