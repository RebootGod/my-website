<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class DiagnoseRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:diagnose {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose route registration and find issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        
        if ($name) {
            $this->info("Searching for route: {$name}");
            $found = false;
            
            foreach (Route::getRoutes() as $route) {
                if ($route->getName() === $name) {
                    $found = true;
                    $this->info("✅ Route found!");
                    $this->table(
                        ['Property', 'Value'],
                        [
                            ['Name', $route->getName()],
                            ['Method', implode('|', $route->methods())],
                            ['URI', $route->uri()],
                            ['Action', $route->getActionName()],
                            ['Middleware', implode(', ', $route->middleware())],
                        ]
                    );
                    break;
                }
            }
            
            if (!$found) {
                $this->error("❌ Route '{$name}' not found!");
                $this->info("Searching for similar routes...");
                
                foreach (Route::getRoutes() as $route) {
                    if (str_contains($route->getName() ?? '', 'track-view')) {
                        $this->line("  - " . $route->getName() . " => " . $route->uri());
                    }
                }
            }
        } else {
            // Show all track-view routes
            $this->info("All track-view routes:");
            
            foreach (Route::getRoutes() as $route) {
                if (str_contains($route->uri(), 'track-view')) {
                    $this->table(
                        ['Name', 'Method', 'URI', 'Middleware'],
                        [[
                            $route->getName(),
                            implode('|', $route->methods()),
                            $route->uri(),
                            implode(', ', $route->middleware())
                        ]]
                    );
                }
            }
        }
        
        return 0;
    }
}
