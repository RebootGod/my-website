<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\SecurityTestingService;
use App\Services\SecurityEventService;
use App\Services\SecurityDashboardService;
use App\Services\CloudflareDashboardService;

class SecurityDashboardController extends Controller
{
    public function __construct(
        private SecurityTestingService $securityTestingService,
        private SecurityEventService $securityEventService,
        private SecurityDashboardService $dashboardService,
        private CloudflareDashboardService $cloudflareDashboardService
    ) {}

    /**
     * Enhanced security dashboard main view with Stage 5 integration
     */
    public function index(Request $request)
    {
        $timeRange = $request->get('hours', 24); // Default 24 hours
        
        // Get comprehensive dashboard data from new services
        $dashboardData = $this->dashboardService->getDashboardData($timeRange);
        $cloudflareData = $this->cloudflareDashboardService->getCloudflareDashboardData($timeRange);
        
        $data = [
            // Enhanced Stage 5 data
            'dashboard_data' => $dashboardData,
            'cloudflare_data' => $cloudflareData,
            'current_request_context' => $this->cloudflareDashboardService->getCurrentRequestContext($request),
            'time_range' => $timeRange,
            
            // Legacy compatibility
            'security_metrics' => $this->getSecurityMetrics(),
            'recent_events' => $this->getRecentSecurityEvents(),
            'threat_summary' => $this->getThreatSummary(),
            'compliance_status' => $this->getComplianceStatus(),
        ];

        return view('admin.security.enhanced-dashboard-v2', $data);
    }

    /**
     * Real-time dashboard updates API endpoint
     */
    public function getRealtimeUpdates(Request $request)
    {
        try {
            $data = [
                'security_updates' => $this->dashboardService->getRealtimeUpdates(),
                'cloudflare_metrics' => $this->cloudflareDashboardService->getRealtimeCloudflareMetrics(),
                'current_context' => $this->cloudflareDashboardService->getCurrentRequestContext($request),
                'timestamp' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enhanced dashboard data API with time range support
     */
    public function getDashboardData(Request $request)
    {
        try {
            $timeRange = $request->get('hours', 24);
            
            $data = [
                'dashboard_data' => $this->dashboardService->getDashboardData($timeRange),
                'cloudflare_data' => $this->cloudflareDashboardService->getCloudflareDashboardData($timeRange),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cloudflare configuration suggestions API
     */
    public function getCloudflareConfigSuggestions(Request $request)
    {
        try {
            $suggestions = $this->cloudflareDashboardService->getConfigurationSuggestions();

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run security tests via web interface (Legacy compatibility)
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
     * Export dashboard data in various formats
     */
    public function exportData(Request $request)
    {
        try {
            $format = $request->get('format', 'json'); // json, csv, excel, pdf
            $hours = $request->get('hours', 24);
            
            // Get dashboard data
            $dashboardData = $this->dashboardService->getDashboardData($hours);
            $cloudflareData = $this->cloudflareDashboardService->getCloudflareDashboardData($hours);
            
            $exportData = [
                'export_info' => [
                    'timestamp' => now()->toISOString(),
                    'time_range_hours' => $hours,
                    'format' => $format,
                ],
                'dashboard_data' => $dashboardData,
                'cloudflare_data' => $cloudflareData,
            ];
            
            switch ($format) {
                case 'json':
                    return response()->json($exportData);
                    
                case 'csv':
                    return $this->exportToCsv($exportData);
                    
                case 'excel':
                    return $this->exportToExcel($exportData);
                    
                case 'pdf':
                    return $this->exportToPdf($exportData);
                    
                default:
                    return response()->json($exportData);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export data to CSV format
     */
    private function exportToCsv($data)
    {
        $filename = 'security-dashboard-' . date('Y-m-d-H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return response()->stream(function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Write CSV headers
            fputcsv($file, ['Metric', 'Value', 'Category', 'Timestamp']);
            
            // Write overview stats
            $overview = $data['dashboard_data']['overview_stats'] ?? [];
            foreach ($overview as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        fputcsv($file, [$key . '_' . $subKey, $subValue, 'overview', now()]);
                    }
                } else {
                    fputcsv($file, [$key, $value, 'overview', now()]);
                }
            }
            
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export data to Excel format (basic CSV with .xlsx extension)
     */
    private function exportToExcel($data)
    {
        // For basic implementation, return enhanced CSV
        return $this->exportToCsv($data);
    }

    /**
     * Export data to PDF format (basic implementation)
     */
    private function exportToPdf($data)
    {
        // For basic implementation, return JSON with PDF mime type
        $filename = 'security-dashboard-' . date('Y-m-d-H-i-s') . '.json';
        
        return response()->json($data)->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
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