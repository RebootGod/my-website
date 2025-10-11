<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ForceRefreshCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:force-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force refresh all Laravel caches (route, config, view, cache)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting force cache refresh...');
        
        // Clear all caches
        $caches = [
            'route:clear' => 'Route cache',
            'config:clear' => 'Config cache',
            'view:clear' => 'View cache',
            'cache:clear' => 'Application cache',
            'clear-compiled' => 'Compiled files',
        ];
        
        foreach ($caches as $command => $label) {
            $this->info("Clearing {$label}...");
            try {
                Artisan::call($command);
                $this->info("✅ {$label} cleared");
            } catch (\Exception $e) {
                $this->error("❌ Failed to clear {$label}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info('🔄 Rebuilding optimized caches...');
        
        // Rebuild caches for production performance
        $rebuilds = [
            'config:cache' => 'Config',
            'route:cache' => 'Route',
            'view:cache' => 'View',
        ];
        
        foreach ($rebuilds as $command => $label) {
            $this->info("Caching {$label}...");
            try {
                Artisan::call($command);
                $this->info("✅ {$label} cached");
            } catch (\Exception $e) {
                $this->error("❌ Failed to cache {$label}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info('🚀 Cache refresh complete!');
        
        // Show route count
        $routes = app('router')->getRoutes()->count();
        $this->info("Total routes registered: {$routes}");
        
        return 0;
    }
}
