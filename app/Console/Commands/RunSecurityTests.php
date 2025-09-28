<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SecurityTestingService;
use Illuminate\Support\Facades\Log;

class RunSecurityTests extends Command
{
    protected $signature = 'security:owasp 
                            {--category= : Specific OWASP category to test (A01-A10)}
                            {--report : Generate compliance report}
                            {--format=table : Output format (table, json)}';

    protected $description = 'Run comprehensive OWASP Top 10 2024/2025 compliance tests';

    private SecurityTestingService $securityTestingService;

    public function __construct(SecurityTestingService $securityTestingService)
    {
        parent::__construct();
        $this->securityTestingService = $securityTestingService;
    }

    public function handle()
    {
        $this->info('🔒 Starting OWASP Top 10 2024/2025 Security Testing Suite');
        $this->info('📋 Comprehensive Security Compliance Analysis');
        $this->newLine();

        $startTime = microtime(true);

        try {
            if ($this->option('report')) {
                return $this->generateComplianceReport();
            }

            if ($category = $this->option('category')) {
                return $this->runSpecificCategoryTest($category);
            }

            return $this->runComprehensiveTest();

        } catch (\Exception $e) {
            $this->error('❌ Security testing failed: ' . $e->getMessage());
            Log::channel('security')->error('Security testing command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        } finally {
            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("⏱️  Execution time: {$executionTime}s");
        }
    }

    private function runComprehensiveTest()
    {
        $this->info('🧪 Running comprehensive security test suite...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(10);
        $progressBar->setFormat('verbose');

        $results = $this->securityTestingService->runComprehensiveSecurityTest();
        $progressBar->finish();

        $this->newLine(2);
        $this->displayResults($results);

        return self::SUCCESS;
    }

    private function runSpecificCategoryTest($category)
    {
        $category = strtoupper($category);
        
        if (!in_array($category, ['A01', 'A02', 'A03', 'A04', 'A05', 'A06', 'A07', 'A08', 'A09', 'A10'])) {
            $this->error('❌ Invalid category. Use A01-A10');
            return self::FAILURE;
        }

        $this->info("🧪 Running tests for OWASP {$category}...");
        $this->newLine();

        $result = match($category) {
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
        };

        $this->displayCategoryResult($result);

        return self::SUCCESS;
    }

    private function generateComplianceReport()
    {
        $this->info('📊 Generating security compliance report...');
        $this->newLine();

        $report = $this->securityTestingService->generateComplianceReport();

        if ($this->option('format') === 'json') {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->displayComplianceReport($report);

        return self::SUCCESS;
    }

    private function displayResults($results)
    {
        $this->info('🔍 COMPREHENSIVE SECURITY TEST RESULTS');
        $this->newLine();

        // Overall score display
        $score = $results['overall_score'];
        $riskLevel = $results['risk_level'];
        
        $scoreColor = match($riskLevel) {
            'LOW' => 'green',
            'MEDIUM' => 'yellow',
            'HIGH' => 'red',
            'CRITICAL' => 'red'
        };

        $this->line("🎯 <fg={$scoreColor}>Overall Security Score: {$score}% ({$riskLevel} RISK)</>");
        $this->newLine();

        // Category results table
        $tableData = [];
        foreach ($results['test_results'] as $categoryCode => $category) {
            $status = $category['status'];
            $statusIcon = match($status) {
                'PASS' => '✅',
                'WARN' => '⚠️',
                'FAIL' => '❌'
            };

            $testCount = count($category['tests']);
            $passCount = collect($category['tests'])->where('status', 'PASS')->count();

            $tableData[] = [
                $categoryCode,
                $category['category'],
                "{$statusIcon} {$status}",
                "{$passCount}/{$testCount}",
            ];
        }

        $this->table(['Code', 'OWASP Category', 'Status', 'Tests'], $tableData);
        $this->newLine();

        // Summary recommendations
        if (!empty($results['recommendations'])) {
            $this->info('📋 KEY RECOMMENDATIONS:');
            foreach ($results['recommendations'] as $recommendation) {
                $this->line("   • {$recommendation}");
            }
            $this->newLine();
        }

        // Risk assessment
        $this->displayRiskAssessment($riskLevel, $score);
    }

    private function displayCategoryResult($result)
    {
        $this->info("📊 {$result['category']} Test Results");
        $this->newLine();

        $tableData = [];
        foreach ($result['tests'] as $testName => $test) {
            $statusIcon = match($test['status']) {
                'PASS' => '✅',
                'WARN' => '⚠️',
                'FAIL' => '❌'
            };

            $tableData[] = [
                $testName,
                $test['description'],
                "{$statusIcon} {$test['status']}",
                $test['implementation'] ?? 'N/A'
            ];
        }

        $this->table(['Test', 'Description', 'Status', 'Implementation'], $tableData);
        $this->newLine();

        if (!empty($result['recommendations'])) {
            $this->info('💡 Recommendations:');
            foreach ($result['recommendations'] as $recommendation) {
                $this->line("   • {$recommendation}");
            }
        }
    }

    private function displayComplianceReport($report)
    {
        $this->info('📋 SECURITY COMPLIANCE REPORT');
        $this->line("Report ID: {$report['report_id']}");
        $this->line("Generated: {$report['generated_at']}");
        $this->line("Application: {$report['application']}");
        $this->line("Environment: {$report['environment']}");
        $this->newLine();

        $status = $report['compliance_status'];
        $statusColor = match($status) {
            'COMPLIANT' => 'green',
            'PARTIALLY_COMPLIANT' => 'yellow',
            'NON_COMPLIANT' => 'red'
        };

        $this->line("🎯 <fg={$statusColor}>Compliance Status: {$status}</>");
        $this->line("📊 Overall Score: {$report['overall_score']}%");
        $this->newLine();

        // Executive summary
        $summary = $report['executive_summary'];
        $this->info('📈 EXECUTIVE SUMMARY:');
        $this->line("Security Posture: {$summary['overall_security_posture']}");
        $this->line("Compliance Rating: {$summary['compliance_rating']}");
        $this->newLine();

        $this->info('✅ Key Strengths:');
        foreach ($summary['key_strengths'] as $strength) {
            $this->line("   • {$strength}");
        }
        $this->newLine();

        $this->info('🔧 Areas for Improvement:');
        foreach ($summary['areas_for_improvement'] as $area) {
            $this->line("   • {$area}");
        }
        $this->newLine();

        $this->line("📅 Next Assessment: {$report['next_assessment_date']}");
    }

    private function displayRiskAssessment($riskLevel, $score)
    {
        $this->info('🎯 RISK ASSESSMENT:');

        $riskMessage = match($riskLevel) {
            'LOW' => 'Excellent security posture. Continue monitoring and regular assessments.',
            'MEDIUM' => 'Good security implementation with room for improvement. Address warnings promptly.',
            'HIGH' => 'Security concerns identified. Immediate action required on failed tests.',
            'CRITICAL' => 'Critical security issues found. Urgent remediation required.'
        };

        $this->line("   {$riskMessage}");
        $this->newLine();

        if ($score >= 90) {
            $this->info('🏆 Your application demonstrates strong security practices!');
        } elseif ($score >= 70) {
            $this->comment('⚡ Focus on addressing the identified security gaps.');
        } else {
            $this->error('🚨 Critical security improvements needed before production use.');
        }
    }
}