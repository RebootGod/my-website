<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\SecurityTestingService;
use App\Services\SecurityEventService;

class SecurityDashboardController extends Controller
{
    public function __construct(
        private SecurityTestingService $securityTestingService,
        private SecurityEventService $securityEventService
    ) {}

    /**
     * Security dashboard main view
     */
    public function index()
    {
        $data = [
            'security_metrics' => $this->getSecurityMetrics(),
            'recent_events' => $this->getRecentSecurityEvents(),
            'threat_summary' => $this->getThreatSummary(),
            'compliance_status' => $this->getComplianceStatus()
        ];

        return view('admin.security.dashboard', $data);
    }

    /**
     * Run security tests via web interface
     */
    public function runTests(Request $request)
    {
        try {
            $category = $request->get('category');
            
            if ($category && $category !== 'all') {
                $results = $this->runCategoryTest($category);
            } else {
                $results = $this->securityTestingService->runComprehensiveSecurityTest();
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate compliance report
     */
    public function generateReport()
    {
        try {
            $report = $this->securityTestingService->generateComplianceReport();

            return response()->json([
                'success' => true,
                'report' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Security events API
     */
    public function getSecurityEvents(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 50);
        $eventType = $request->get('type');
        $severity = $request->get('severity');

        $events = $this->securityEventService->getSecurityEvents([
            'page' => $page,
            'limit' => $limit,
            'event_type' => $eventType,
            'severity' => $severity,
            'days' => $request->get('days', 7)
        ]);

        return response()->json($events);
    }

    /**
     * Security metrics API
     */
    public function getMetrics()
    {
        return response()->json($this->getSecurityMetrics());
    }

    /**
     * Threat intelligence data
     */
    public function getThreatData()
    {
        return response()->json([
            'high_risk_ips' => $this->securityEventService->getHighRiskIPs(),
            'threat_summary' => $this->getThreatSummary(),
            'attack_patterns' => $this->getAttackPatterns()
        ]);
    }

    private function getSecurityMetrics()
    {
        return Cache::remember('security_metrics', 300, function() {
            $last24Hours = now()->subDay();
            
            return [
                'failed_logins_24h' => $this->securityEventService->getEventCount('failed_login', $last24Hours),
                'injection_attempts_24h' => $this->securityEventService->getEventCount('injection_attempt', $last24Hours),
                'unauthorized_access_24h' => $this->securityEventService->getEventCount('unauthorized_access', $last24Hours),
                'security_score' => $this->getOverallSecurityScore(),
                'active_threats' => $this->securityEventService->getActiveThreatCount(),
                'high_risk_ips' => count($this->securityEventService->getHighRiskIPs())
            ];
        });
    }

    private function getRecentSecurityEvents()
    {
        return $this->securityEventService->getSecurityEvents([
            'limit' => 10,
            'days' => 1
        ]);
    }

    private function getThreatSummary()
    {
        return Cache::remember('threat_summary', 600, function() {
            $last7Days = now()->subWeek();
            
            return [
                'total_threats' => $this->securityEventService->getThreatCount($last7Days),
                'blocked_attacks' => $this->securityEventService->getBlockedAttackCount($last7Days),
                'threat_trend' => $this->securityEventService->getThreatTrend(),
                'top_attack_types' => $this->securityEventService->getTopAttackTypes($last7Days)
            ];
        });
    }

    private function getComplianceStatus()
    {
        $cachedReport = Cache::get('security_compliance_report');
        
        if ($cachedReport) {
            return [
                'status' => $cachedReport['compliance_status'],
                'score' => $cachedReport['overall_score'],
                'last_assessment' => $cachedReport['generated_at'],
                'next_assessment' => $cachedReport['next_assessment_date']
            ];
        }

        return [
            'status' => 'UNKNOWN',
            'score' => null,
            'last_assessment' => null,
            'next_assessment' => now()->addMonths(3)->toISOString()
        ];
    }

    private function getOverallSecurityScore()
    {
        $cachedResults = Cache::get('security_test_results');
        return $cachedResults['overall_score'] ?? null;
    }

    private function getAttackPatterns()
    {
        return Cache::remember('attack_patterns', 3600, function() {
            $last30Days = now()->subMonth();
            
            return [
                'injection_patterns' => $this->securityEventService->getInjectionPatterns($last30Days),
                'brute_force_patterns' => $this->securityEventService->getBruteForcePatterns($last30Days),
                'geographic_threats' => $this->securityEventService->getGeographicThreats($last30Days)
            ];
        });
    }

    private function runCategoryTest($category)
    {
        return match(strtoupper($category)) {
            'A01' => $this->securityTestingService->testAccessControl(),
            'A02' => $this->securityTestingService->testCryptographicSecurity(),
            'A03' => $this->securityTestingService->testInjectionProtection(),
            'A04' => $this->securityTestingService->testSecureDesign(),
            'A05' => $this->securityTestingService->testSecurityConfiguration(),
            'A06' => $this->securityTestingService->testComponentSecurity(),
            'A07' => $this->securityTestingService->testAuthenticationSecurity(),
            'A08' => $this->securityTestingService->testIntegritySecurity(),
            'A09' => $this->securityTestingService->testLoggingMonitoring(),
            'A10' => $this->securityTestingService->testSsrfProtection(),
            default => throw new \InvalidArgumentException('Invalid category')
        };
    }
}