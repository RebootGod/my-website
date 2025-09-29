<?php

namespace App\Console\Commands;

use App\Rules\NoSqlInjectionRule;
use App\Rules\NoXssRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class TestInjectionProtectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-injection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SQL injection and XSS protection mechanisms';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🛡️ Testing Injection Protection Mechanisms...');
        $this->newLine();

        // Test SQL Injection Protection
        $this->testSqlInjectionProtection();

        // Test XSS Protection
        $this->testXssProtection();

        // Test Middleware
        $this->testMiddleware();

        $this->newLine();
        $this->info('✅ Injection protection testing completed!');
    }

    private function testSqlInjectionProtection()
    {
        $this->info('1. Testing SQL Injection Protection...');

        $sqlInjectionPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "1' UNION SELECT * FROM users --",
            "admin'--",
            "1; INSERT INTO users (username) VALUES ('hacker'); --",
            "' OR 1=1#",
            "'; EXEC xp_cmdshell('dir'); --",
            "' AND (SELECT * FROM (SELECT COUNT(*),CONCAT(version(),FLOOR(RAND(0)*2))x FROM information_schema.tables GROUP BY x)a) --",
            "1' AND SLEEP(5) --",
            "' UNION ALL SELECT NULL,NULL,NULL,version() --",
        ];

        $rule = new NoSqlInjectionRule();
        $passed = 0;
        $total = count($sqlInjectionPayloads);

        foreach ($sqlInjectionPayloads as $payload) {
            $validator = Validator::make(['input' => $payload], [
                'input' => [$rule]
            ]);

            if ($validator->fails()) {
                $passed++;
                $this->line("   ✓ Blocked: " . substr($payload, 0, 30) . "...");
            } else {
                $this->error("   ✗ Missed: " . substr($payload, 0, 30) . "...");
            }
        }

        $this->line("   📊 SQL Injection Protection: {$passed}/{$total} payloads blocked");
    }

    private function testXssProtection()
    {
        $this->info('2. Testing XSS Protection...');

        $xssPayloads = [
            "<script>alert('XSS')</script>",
            "<img src=x onerror=alert('XSS')>",
            "<iframe src=javascript:alert('XSS')></iframe>",
            "<svg onload=alert('XSS')>",
            "<body onload=alert('XSS')>",
            "javascript:alert('XSS')",
            "<script src='http://evil.com/xss.js'></script>",
            "<link rel=stylesheet href=javascript:alert('XSS')>",
            "<meta http-equiv=refresh content='0;url=javascript:alert(`XSS`)'>",
            "<object data=javascript:alert('XSS')>",
            "<embed src=javascript:alert('XSS')>",
            "<form><button formaction=javascript:alert('XSS')>",
            "<input onfocus=alert('XSS') autofocus>",
            "<select onfocus=alert('XSS') autofocus>",
            "<textarea onfocus=alert('XSS') autofocus>",
            "<keygen onfocus=alert('XSS') autofocus>",
            "<video><source onerror=alert('XSS')>",
            "<audio src=x onerror=alert('XSS')>",
            "<details open ontoggle=alert('XSS')>",
            "<marquee onstart=alert('XSS')>",
        ];

        $rule = new NoXssRule();
        $passed = 0;
        $total = count($xssPayloads);

        foreach ($xssPayloads as $payload) {
            $validator = Validator::make(['input' => $payload], [
                'input' => [$rule]
            ]);

            if ($validator->fails()) {
                $passed++;
                $this->line("   ✓ Blocked: " . substr($payload, 0, 30) . "...");
            } else {
                $this->error("   ✗ Missed: " . substr($payload, 0, 30) . "...");
            }
        }

        $this->line("   📊 XSS Protection: {$passed}/{$total} payloads blocked");
    }

    private function testMiddleware()
    {
        $this->info('3. Testing Security Middleware...');

        // Check if middleware are registered
        $middlewareAliases = app('router')->getMiddleware();

        $requiredMiddleware = [
            'sanitize.input' => \App\Http\Middleware\SanitizeInputMiddleware::class,
        ];

        foreach ($requiredMiddleware as $alias => $class) {
            if (isset($middlewareAliases[$alias]) && $middlewareAliases[$alias] === $class) {
                $this->line("   ✓ Middleware '{$alias}' is registered");
            } else {
                $this->error("   ✗ Middleware '{$alias}' is missing");
            }
        }

        // Test security headers
        $this->testSecurityHeaders();
    }

    private function testSecurityHeaders()
    {
        $this->line('   Testing Security Headers...');

        $requiredHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        // Create a test response with security headers
        // $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware();
        $request = \Illuminate\Http\Request::create('/test');

        $response = response('test');
        // Security headers middleware removed - test skipped

        $headersSet = 0;
        foreach ($requiredHeaders as $header => $expectedValue) {
            if ($response->headers->has($header)) {
                $actualValue = $response->headers->get($header);
                if ($actualValue === $expectedValue) {
                    $this->line("     ✓ Header '{$header}': {$actualValue}");
                    $headersSet++;
                } else {
                    $this->warn("     ⚠ Header '{$header}': Expected '{$expectedValue}', got '{$actualValue}'");
                }
            } else {
                $this->error("     ✗ Header '{$header}' is missing");
            }
        }

        $total = count($requiredHeaders);
        $this->line("   📊 Security Headers: {$headersSet}/{$total} headers correctly set");
    }
}