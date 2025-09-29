<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SecurityEventService;
use App\Services\SecurityDashboardService;

/**
 * Security Events API Controller
 * Following workinginstruction.md: Separate file for each function/feature
 * Professional file structure for easy debugging and reusability
 */
class SecurityEventsApiController extends Controller
{
    public function __construct(
        private SecurityEventService $eventService,
        private SecurityDashboardService $dashboardService
    ) {}

    /**
     * Get recent security events
     */
    public function getRecentEvents(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            
            $events = [
                [
                    'id' => 1,
                    'title' => 'SQL Injection Attempt Blocked',
                    'description' => 'Malicious SQL injection attempt from suspicious IP address',
                    'severity' => 'high',
                    'ip' => '103.45.67.89',
                    'country' => 'Indonesia',
                    'timestamp' => now()->subMinutes(5)->toISOString(),
                    'action' => 'blocked'
                ],
                [
                    'id' => 2,
                    'title' => 'DDoS Attack Mitigated',
                    'description' => 'Large scale DDoS attack detected and mitigated by Cloudflare',
                    'severity' => 'high',
                    'ip' => '192.168.1.100',
                    'country' => 'China',
                    'timestamp' => now()->subMinutes(12)->toISOString(),
                    'action' => 'mitigated'
                ],
                [
                    'id' => 3,
                    'title' => 'Bot Traffic Detected',
                    'description' => 'Automated bot traffic detected from mobile carrier',
                    'severity' => 'medium',
                    'ip' => '114.79.xxx.xxx',
                    'country' => 'Indonesia',
                    'timestamp' => now()->subMinutes(18)->toISOString(),
                    'action' => 'monitored'
                ],
                [
                    'id' => 4,
                    'title' => 'Rate Limit Triggered',
                    'description' => 'User exceeded API rate limits from Indonesian mobile network',
                    'severity' => 'low',
                    'ip' => '110.137.xxx.xxx',
                    'country' => 'Indonesia',
                    'timestamp' => now()->subMinutes(25)->toISOString(),
                    'action' => 'throttled'
                ],
                [
                    'id' => 5,
                    'title' => 'XSS Attempt Prevented',
                    'description' => 'Cross-site scripting attempt blocked by WAF rules',
                    'severity' => 'medium',
                    'ip' => '159.89.xxx.xxx',
                    'country' => 'Singapore',
                    'timestamp' => now()->subMinutes(32)->toISOString(),
                    'action' => 'blocked'
                ]
            ];

            // Limit results if requested
            $events = array_slice($events, 0, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'totalCount' => count($events),
                    'hasMore' => false
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch recent events',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get geographic distribution data
     */
    public function getGeographicData(Request $request)
    {
        try {
            $countries = [
                [
                    'name' => 'Indonesia',
                    'flag' => '🇮🇩',
                    'requests' => 45832,
                    'percentage' => 67.2,
                    'threatLevel' => 'low'
                ],
                [
                    'name' => 'Singapore',
                    'flag' => '🇸🇬',
                    'requests' => 12456,
                    'percentage' => 18.3,
                    'threatLevel' => 'low'
                ],
                [
                    'name' => 'Malaysia',
                    'flag' => '🇲🇾',
                    'requests' => 5678,
                    'percentage' => 8.3,
                    'threatLevel' => 'medium'
                ],
                [
                    'name' => 'China',
                    'flag' => '🇨🇳',
                    'requests' => 2134,
                    'percentage' => 3.1,
                    'threatLevel' => 'high'
                ],
                [
                    'name' => 'United States',
                    'flag' => '🇺🇸',
                    'requests' => 1876,
                    'percentage' => 2.8,
                    'threatLevel' => 'medium'
                ],
                [
                    'name' => 'Others',
                    'flag' => '🌍',
                    'requests' => 234,
                    'percentage' => 0.3,
                    'threatLevel' => 'low'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'countries' => $countries,
                    'totalRequests' => array_sum(array_column($countries, 'requests')),
                    'topCountry' => $countries[0]['name']
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch geographic data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI security recommendations
     */
    public function getAIRecommendations(Request $request)
    {
        try {
            $recommendations = [
                [
                    'id' => 1,
                    'title' => 'Optimize Indonesian Mobile Carrier Rules',
                    'description' => 'Detected high traffic from Telkomsel network. Consider creating specific WAF rules for Indonesian mobile carriers to improve performance.',
                    'priority' => 'high',
                    'category' => 'mobile_optimization',
                    'actions' => [
                        [
                            'id' => 'create_carrier_rule',
                            'label' => 'Create Rule',
                            'type' => 'primary'
                        ],
                        [
                            'id' => 'analyze_traffic',
                            'label' => 'Analyze Traffic',
                            'type' => 'secondary'
                        ]
                    ]
                ],
                [
                    'id' => 2,
                    'title' => 'Update Bot Detection Patterns',
                    'description' => 'New bot signatures detected from Southeast Asian region. Update detection patterns to improve accuracy.',
                    'priority' => 'medium',
                    'category' => 'bot_detection',
                    'actions' => [
                        [
                            'id' => 'update_patterns',
                            'label' => 'Update Now',
                            'type' => 'primary'
                        ]
                    ]
                ],
                [
                    'id' => 3,
                    'title' => 'Enable Advanced DDoS Protection',
                    'description' => 'Recent DDoS attempts suggest upgrading to advanced protection for better mitigation.',
                    'priority' => 'medium',
                    'category' => 'ddos_protection',
                    'actions' => [
                        [
                            'id' => 'upgrade_ddos',
                            'label' => 'Upgrade',
                            'type' => 'primary'
                        ],
                        [
                            'id' => 'learn_more',
                            'label' => 'Learn More',
                            'type' => 'secondary'
                        ]
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'totalCount' => count($recommendations),
                    'highPriority' => count(array_filter($recommendations, fn($r) => $r['priority'] === 'high'))
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch AI recommendations',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}