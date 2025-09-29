<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SecurityDashboardService;
use App\Services\CloudflareDashboardService;

/**
 * Security Charts API Controller
 * Following workinginstruction.md: Separate file for each function/feature
 * Professional file structure for easy debugging and reusability
 */
class SecurityChartsApiController extends Controller
{
    public function __construct(
        private SecurityDashboardService $dashboardService,
        private CloudflareDashboardService $cloudflareDashboardService
    ) {}

    /**
     * Get chart data based on chart type and options
     */
    public function getChartData(Request $request)
    {
        try {
            $chartType = $request->get('chart');
            $options = $request->except(['chart']);

            switch ($chartType) {
                case 'threatTimeline':
                    return $this->getThreatTimelineData($options);
                case 'responseTime':
                    return $this->getResponseTimeData($options);
                case 'geoDistribution':
                    return $this->getGeoDistributionData($options);
                case 'eventDistribution':
                    return $this->getEventDistributionData($options);
                case 'performance':
                    return $this->getPerformanceData($options);
                case 'attackPattern':
                    return $this->getAttackPatternData($options);
                default:
                    throw new \InvalidArgumentException('Invalid chart type');
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch chart data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance metrics data
     */
    public function getPerformanceData(Request $request)
    {
        try {
            $timeframe = $request->get('timeframe', '24h');
            
            $data = [
                'response_time' => 85,
                'throughput' => 92,
                'success_rate' => 99.5,
                'error_rate' => 0.5,
                'uptime' => 99.9,
                'resource_usage' => 68,
                'cache_hit_rate' => 87,
                'bandwidth_usage' => 73
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'timeframe' => $timeframe,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch performance data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Cloudflare statistics
     */
    public function getCloudflareStats(Request $request)
    {
        try {
            $stats = [
                'requests_total' => 125467,
                'requests_cached' => 87234,
                'requests_uncached' => 38233,
                'bandwidth_saved' => '2.3 GB',
                'threats_blocked' => 1567,
                'countries_served' => 45,
                'edge_response_time' => 23, // ms
                'origin_response_time' => 156, // ms
                'cache_hit_ratio' => 69.5, // percentage
                'ssl_requests' => 100, // percentage
                'bot_traffic' => 12.3, // percentage
                'ddos_attacks_mitigated' => 23
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch Cloudflare stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate threat timeline data
     */
    private function getThreatTimelineData($options)
    {
        $period = $options['period'] ?? '24h';
        
        // Generate sample time labels
        $labels = [];
        $highRisk = [];
        $mediumRisk = [];
        $lowRisk = [];

        $hours = $period === '7d' ? 168 : ($period === '30d' ? 720 : 24);
        $step = $period === '7d' ? 6 : ($period === '30d' ? 24 : 1);

        for ($i = 0; $i < $hours; $i += $step) {
            $labels[] = now()->subHours($hours - $i)->format($step > 1 ? 'M j' : 'H:i');
            $highRisk[] = rand(0, 5);
            $mediumRisk[] = rand(2, 15);
            $lowRisk[] = rand(5, 25);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'high_risk' => $highRisk,
                'medium_risk' => $mediumRisk,
                'low_risk' => $lowRisk
            ]
        ]);
    }

    /**
     * Generate response time data
     */
    private function getResponseTimeData($options)
    {
        $data = [
            'firewall' => rand(15, 35),
            'ddos_protection' => rand(8, 25),
            'bot_detection' => rand(20, 45),
            'rate_limiting' => rand(5, 15),
            'geo_blocking' => rand(10, 30)
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Generate geographic distribution data
     */
    private function getGeoDistributionData($options)
    {
        $data = [
            'countries' => ['Indonesia', 'Singapore', 'Malaysia', 'Thailand', 'Philippines', 'Others'],
            'percentages' => [67.2, 18.3, 8.3, 3.1, 2.8, 0.3]
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Generate event distribution data
     */
    private function getEventDistributionData($options)
    {
        $data = [
            'bot_attacks' => rand(25, 40),
            'ddos_attempts' => rand(15, 25),
            'sql_injection' => rand(8, 15),
            'xss_attempts' => rand(10, 20),
            'brute_force' => rand(5, 12),
            'other' => rand(3, 8)
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Generate attack pattern data
     */
    private function getAttackPatternData($options)
    {
        $filters = $options['filters'] ?? [];
        
        $labels = [];
        $attempts = [];
        $blocked = [];

        for ($i = 23; $i >= 0; $i--) {
            $time = now()->subHours($i);
            $labels[] = $time->format('H:i');
            
            $attemptCount = rand(10, 50);
            $attempts[] = $attemptCount;
            $blocked[] = rand(round($attemptCount * 0.85), $attemptCount);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'timestamps' => $labels,
                'attempts' => $attempts,
                'blocked' => $blocked,
                'filters_applied' => $filters
            ]
        ]);
    }
}