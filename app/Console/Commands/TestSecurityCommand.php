<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Movie;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

class TestSecurityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test security implementations: Policies, Authorization, Audit Logging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔒 Testing Security Implementations...');
        $this->newLine();

        // Test 1: Policy Registration
        $this->testPolicyRegistration();

        // Test 2: Authorization Checks
        $this->testAuthorizationChecks();

        // Test 3: Audit Logging
        $this->testAuditLogging();

        // Test 4: Rate Limiting Configuration
        $this->testRateLimitingConfig();

        $this->newLine();
        $this->info('✅ Security testing completed!');
    }

    private function testPolicyRegistration()
    {
        $this->info('1. Testing Policy Registration...');

        $policies = [
            \App\Models\User::class => \App\Policies\UserPolicy::class,
            \App\Models\Movie::class => \App\Policies\MoviePolicy::class,
            \App\Models\Watchlist::class => \App\Policies\WatchlistPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                $this->line("   ✓ {$model} -> {$policy}");
            } else {
                $this->error("   ✗ Missing: {$model} -> {$policy}");
            }
        }
    }

    private function testAuthorizationChecks()
    {
        $this->info('2. Testing Authorization Checks...');

        // Test Gates
        $gates = ['access-admin-panel', 'manage-users', 'super-admin-only'];
        foreach ($gates as $gate) {
            if (Gate::has($gate)) {
                $this->line("   ✓ Gate '{$gate}' is registered");
            } else {
                $this->error("   ✗ Gate '{$gate}' is missing");
            }
        }

        // Test with sample user
        $user = User::first();
        if ($user) {
            $this->line("   ✓ Sample user found: {$user->username}");

            // Test admin gate
            if ($user->isAdmin()) {
                $canAccessAdmin = Gate::forUser($user)->allows('access-admin-panel');
                $this->line("   ✓ Admin user can access admin panel: " . ($canAccessAdmin ? 'YES' : 'NO'));
            }
        } else {
            $this->warn("   ⚠ No users found in database for testing");
        }
    }

    private function testAuditLogging()
    {
        $this->info('3. Testing Audit Logging...');

        // Check if audit_logs table exists
        try {
            $count = AuditLog::count();
            $this->line("   ✓ Audit logs table exists with {$count} records");
        } catch (\Exception $e) {
            $this->error("   ✗ Audit logs table not accessible: " . $e->getMessage());
        }

        // Test audit logger service
        try {
            AuditLogger::log('test', 'Security test log entry');
            $this->line("   ✓ AuditLogger service working");

            // Clean up test log
            AuditLog::where('action', 'test')->where('description', 'Security test log entry')->delete();
        } catch (\Exception $e) {
            $this->error("   ✗ AuditLogger service error: " . $e->getMessage());
        }
    }

    private function testRateLimitingConfig()
    {
        $this->info('4. Testing Rate Limiting Configuration...');

        // Check if routes have rate limiting
        $protectedRoutes = [
            'profile.update.username' => '5,1',
            'profile.update.email' => '3,1',
            'profile.update.password' => '3,1',
            'profile.delete' => '3,1',
            'watchlist.add' => '30,1',
            'movies.report' => '5,60'
        ];

        $routeCollection = app('router')->getRoutes();
        $foundRoutes = 0;

        foreach ($protectedRoutes as $routeName => $expectedLimit) {
            $route = $routeCollection->getByName($routeName);
            if ($route) {
                $middleware = $route->middleware();
                $hasThrottle = collect($middleware)->contains(function ($m) {
                    return str_starts_with($m, 'throttle:');
                });

                if ($hasThrottle) {
                    $this->line("   ✓ Route '{$routeName}' has rate limiting");
                    $foundRoutes++;
                } else {
                    $this->warn("   ⚠ Route '{$routeName}' missing rate limiting");
                }
            } else {
                $this->warn("   ⚠ Route '{$routeName}' not found");
            }
        }

        $this->line("   ✓ Found {$foundRoutes}/" . count($protectedRoutes) . " protected routes");
    }
}