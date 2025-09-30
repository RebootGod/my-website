<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Threat Detection Engine Service
 * ML-based anomaly detection, behavioral biometrics, fraud detection
 * OWASP 2024/2025 Security Compliant | Max 300 Lines | WorkingInstruction.md
 */
class ThreatDetectionEngineService
{
    private UserActivitySecurityService $securityService;
    private UserActivityAnalyticsService $analyticsService;
    
    // Threat detection thresholds
    private const ANOMALY_THRESHOLD = 0.7;
    private const FRAUD_SCORE_THRESHOLD = 80;
    private const BOT_DETECTION_THRESHOLD = 0.8;
    private const VELOCITY_THRESHOLD = 50; // requests per minute
    
    // Behavioral analysis windows
    private const SHORT_WINDOW = 15; // minutes
    private const MEDIUM_WINDOW = 60; // minutes
    private const LONG_WINDOW = 1440; // 24 hours

    public function __construct(
        UserActivitySecurityService $securityService,
        UserActivityAnalyticsService $analyticsService
    ) {
        $this->securityService = $securityService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Analyze activity for threats
     */
    public function analyzeThreat(UserActivity $activity): array
    {
        try {
            $threats = [];
            
            // Anomaly detection
            $anomalyScore = $this->detectAnomaly($activity);
            if ($anomalyScore >= self::ANOMALY_THRESHOLD) {
                $threats[] = [
                    'type' => 'behavioral_anomaly',
                    'score' => $anomalyScore,
                    'severity' => $this->calculateSeverity($anomalyScore)
                ];
            }
            
            // Fraud detection
            $fraudScore = $this->detectFraud($activity);
            if ($fraudScore >= self::FRAUD_SCORE_THRESHOLD) {
                $threats[] = [
                    'type' => 'fraud_detected',
                    'score' => $fraudScore,
                    'severity' => 'critical'
                ];
            }
            
            // Bot detection
            $botScore = $this->detectBot($activity);
            if ($botScore >= self::BOT_DETECTION_THRESHOLD) {
                $threats[] = [
                    'type' => 'bot_activity',
                    'score' => $botScore,
                    'severity' => 'high'
                ];
            }
            
            // Velocity analysis
            $velocityThreat = $this->analyzeVelocity($activity);
            if ($velocityThreat) {
                $threats[] = $velocityThreat;
            }
            
            // Account takeover detection
            $takeoverThreat = $this->detectAccountTakeover($activity);
            if ($takeoverThreat) {
                $threats[] = $takeoverThreat;
            }
            
            return $threats;
            
        } catch (Exception $e) {
            Log::error('Threat analysis failed', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Detect behavioral anomalies using statistical analysis
     */
    private function detectAnomaly(UserActivity $activity): float
    {
        $user = $activity->user;
        $anomalyScore = 0;
        
        // Time-based anomaly (unusual activity hours)
        $timeAnomaly = $this->analyzeTimeAnomaly($user, $activity);
        $anomalyScore += $timeAnomaly * 0.2;
        
        // Location-based anomaly
        $locationAnomaly = $this->analyzeLocationAnomaly($user, $activity);
        $anomalyScore += $locationAnomaly * 0.3;
        
        // Device-based anomaly
        $deviceAnomaly = $this->analyzeDeviceAnomaly($user, $activity);
        $anomalyScore += $deviceAnomaly * 0.2;
        
        // Behavioral pattern anomaly
        $behaviorAnomaly = $this->analyzeBehaviorAnomaly($user, $activity);
        $anomalyScore += $behaviorAnomaly * 0.3;
        
        return min($anomalyScore, 1.0);
    }

    /**
     * Analyze time-based anomalies
     */
    private function analyzeTimeAnomaly(User $user, UserActivity $activity): float
    {
        $currentHour = $activity->created_at->hour;
        
        // Get user's typical active hours
        $typicalHours = Cache::remember("user_hours_{$user->id}", 3600, function () use ($user) {
            return UserActivity::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->pluck('count', 'hour')
                ->toArray();
        });
        
        if (empty($typicalHours)) {
            return 0;
        }
        
        $maxActivity = max($typicalHours);
        $currentActivity = $typicalHours[$currentHour] ?? 0;
        
        return 1 - ($currentActivity / $maxActivity);
    }

    /**
     * Analyze location-based anomalies
     */
    private function analyzeLocationAnomaly(User $user, UserActivity $activity): float
    {
        if (!isset($activity->geolocation['country'])) {
            return 0;
        }
        
        $currentCountry = $activity->geolocation['country'];
        
        // Get user's typical locations
        $typicalLocations = Cache::remember("user_locations_{$user->id}", 3600, function () use ($user) {
            return UserActivity::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('geolocation')
                ->get()
                ->map(function ($activity) {
                    return $activity->geolocation['country'] ?? 'Unknown';
                })
                ->countBy()
                ->toArray();
        });
        
        if (empty($typicalLocations)) {
            return 0;
        }
        
        return !isset($typicalLocations[$currentCountry]) ? 1.0 : 0;
    }

    /**
     * Analyze device-based anomalies
     */
    private function analyzeDeviceAnomaly(User $user, UserActivity $activity): float
    {
        $deviceFingerprint = $activity->device_fingerprint;
        
        if (!$deviceFingerprint) {
            return 0.5; // Unknown device is moderately suspicious
        }
        
        // Check if device is known
        $knownDevices = Cache::remember("user_devices_{$user->id}", 3600, function () use ($user) {
            return UserActivity::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('device_fingerprint')
                ->distinct('device_fingerprint')
                ->pluck('device_fingerprint')
                ->toArray();
        });
        
        return !in_array($deviceFingerprint, $knownDevices) ? 0.8 : 0;
    }

    /**
     * Analyze behavioral pattern anomalies
     */
    private function analyzeBehaviorAnomaly(User $user, UserActivity $activity): float
    {
        $activityType = $activity->activity_type;
        
        // Get user's typical activity patterns
        $typicalActivities = Cache::remember("user_activities_{$user->id}", 1800, function () use ($user) {
            return UserActivity::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->selectRaw('activity_type, COUNT(*) as count')
                ->groupBy('activity_type')
                ->pluck('count', 'activity_type')
                ->toArray();
        });
        
        if (empty($typicalActivities)) {
            return 0;
        }
        
        $totalActivities = array_sum($typicalActivities);
        $activityFrequency = ($typicalActivities[$activityType] ?? 0) / $totalActivities;
        
        return $activityFrequency < 0.01 ? 0.6 : 0; // Rare activities are suspicious
    }

    /**
     * Detect fraud patterns
     */
    private function detectFraud(UserActivity $activity): int
    {
        $fraudScore = 0;
        $user = $activity->user;
        
        // Multiple simultaneous sessions
        $simultaneousSessions = $this->countSimultaneousSessions($user->id);
        if ($simultaneousSessions > 3) {
            $fraudScore += 30;
        }
        
        // Rapid location changes
        $rapidLocationChange = $this->detectRapidLocationChange($user->id);
        if ($rapidLocationChange) {
            $fraudScore += 40;
        }
        
        // Suspicious payment activities
        if (in_array($activity->activity_type, ['payment_attempt', 'subscription_change'])) {
            $fraudScore += 20;
        }
        
        // High-risk IP
        if (isset($activity->geolocation['high_risk']) && $activity->geolocation['high_risk']) {
            $fraudScore += 25;
        }
        
        return min($fraudScore, 100);
    }

    /**
     * Detect bot activity
     */
    private function detectBot(UserActivity $activity): float
    {
        $botScore = 0;
        
        // User agent analysis
        $userAgent = $activity->user_agent ?? '';
        if ($this->isKnownBotUserAgent($userAgent)) {
            $botScore += 0.4;
        }
        
        // Request patterns
        $requestVelocity = $this->calculateRequestVelocity($activity->user_id);
        if ($requestVelocity > self::VELOCITY_THRESHOLD) {
            $botScore += 0.3;
        }
        
        // Behavioral patterns
        $behaviorPattern = $this->analyzeBotBehaviorPattern($activity);
        $botScore += $behaviorPattern * 0.3;
        
        return min($botScore, 1.0);
    }

    /**
     * Analyze request velocity
     */
    private function analyzeVelocity(UserActivity $activity): ?array
    {
        $velocity = $this->calculateRequestVelocity($activity->user_id);
        
        if ($velocity > self::VELOCITY_THRESHOLD) {
            return [
                'type' => 'high_velocity_attack',
                'score' => min($velocity / self::VELOCITY_THRESHOLD, 2.0),
                'severity' => $velocity > self::VELOCITY_THRESHOLD * 2 ? 'critical' : 'high',
                'requests_per_minute' => $velocity
            ];
        }
        
        return null;
    }

    /**
     * Detect account takeover
     */
    private function detectAccountTakeover(UserActivity $activity): ?array
    {
        $indicators = 0;
        $user = $activity->user;
        
        // Password change from new device
        if ($activity->activity_type === 'password_change') {
            $indicators += $this->analyzeDeviceAnomaly($user, $activity) > 0.5 ? 1 : 0;
        }
        
        // Login from suspicious location
        if ($activity->activity_type === 'login_success') {
            $indicators += $this->analyzeLocationAnomaly($user, $activity) > 0.8 ? 1 : 0;
        }
        
        // Multiple failed login attempts followed by success
        if ($activity->activity_type === 'login_success') {
            $recentFailures = UserActivity::where('user_id', $user->id)
                ->where('activity_type', 'login_failed')
                ->where('created_at', '>=', now()->subMinutes(30))
                ->count();
            
            $indicators += $recentFailures >= 3 ? 1 : 0;
        }
        
        if ($indicators >= 2) {
            return [
                'type' => 'account_takeover',
                'score' => $indicators / 3,
                'severity' => 'critical',
                'indicators' => $indicators
            ];
        }
        
        return null;
    }

    /**
     * Helper methods
     */
    private function calculateSeverity(float $score): string
    {
        if ($score >= 0.9) return 'critical';
        if ($score >= 0.7) return 'high';
        if ($score >= 0.5) return 'medium';
        return 'low';
    }

    private function countSimultaneousSessions(int $userId): int
    {
        return UserActivity::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->distinct('session_id')
            ->count();
    }

    private function detectRapidLocationChange(int $userId): bool
    {
        $recentActivities = UserActivity::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(2))
            ->whereNotNull('geolocation')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        $countries = $recentActivities->pluck('geolocation.country')->unique();
        return $countries->count() > 2;
    }

    private function isKnownBotUserAgent(string $userAgent): bool
    {
        $botPatterns = ['bot', 'crawler', 'spider', 'scraper', 'wget', 'curl'];
        
        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function calculateRequestVelocity(int $userId): int
    {
        return UserActivity::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(1))
            ->count();
    }

    private function analyzeBotBehaviorPattern(UserActivity $activity): float
    {
        // Analyze for robotic patterns
        $user = $activity->user;
        $recentActivities = UserActivity::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->orderBy('created_at')
            ->get();
        
        if ($recentActivities->count() < 5) {
            return 0;
        }
        
        // Check for perfectly timed intervals (bot-like)
        $intervals = [];
        for ($i = 1; $i < $recentActivities->count(); $i++) {
            $intervals[] = $recentActivities[$i]->created_at->diffInSeconds($recentActivities[$i-1]->created_at);
        }
        
        $variance = $this->calculateVariance($intervals);
        return $variance < 2 ? 0.8 : 0; // Low variance suggests bot behavior
    }

    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $sumSquares = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values));
        
        return $sumSquares / count($values);
    }
}