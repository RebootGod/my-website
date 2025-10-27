<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearTmdbCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:clear-cache {tmdb_id?} {season?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear TMDB cache for specific series/season or all TMDB cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tmdbId = $this->argument('tmdb_id');
        $season = $this->argument('season');

        if ($tmdbId && $season) {
            // Clear specific season cache
            $cacheKey = "tmdb:season:{$tmdbId}:{$season}:id";
            Cache::forget($cacheKey);
            $this->info("Cleared cache for TMDB ID {$tmdbId} Season {$season}");
        } elseif ($tmdbId) {
            // Clear all seasons for this series
            $cleared = 0;
            for ($i = 0; $i <= 50; $i++) {
                $cacheKey = "tmdb:season:{$tmdbId}:{$i}:id";
                if (Cache::forget($cacheKey)) {
                    $cleared++;
                }
            }
            $this->info("Cleared {$cleared} season caches for TMDB ID {$tmdbId}");
        } else {
            // Clear all TMDB cache (dangerous!)
            $this->warn('Clearing ALL TMDB cache...');
            Cache::flush();
            $this->info('All cache cleared!');
        }

        return 0;
    }
}
