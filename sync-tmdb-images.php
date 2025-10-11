<?php

/**
 * TMDB Image Database Sync Tool
 * 
 * Syncs database with downloaded TMDB images
 * Use this when files are downloaded but database not updated
 * 
 * Usage: php sync-tmdb-images.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Movie;
use App\Models\Series;
use App\Models\SeriesSeason;
use App\Models\SeriesEpisode;
use Illuminate\Support\Facades\Storage;

// Color helper
class Color {
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
}

function printHeader() {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║         TMDB Image Database Sync Tool v1.0              ║\n";
    echo "║         Sync database with downloaded images            ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
}

function printSection($title) {
    echo Color::CYAN . "📥 {$title}...\n" . Color::RESET;
}

function printSuccess($message) {
    echo Color::GREEN . "   ✓ " . Color::RESET . $message . "\n";
}

function printError($message) {
    echo Color::RED . "   ✗ " . Color::RESET . $message . "\n";
}

function printInfo($message) {
    echo Color::YELLOW . "   ℹ " . Color::RESET . $message . "\n";
}

function syncMoviePosters() {
    printSection("Syncing movie posters");
    
    $files = Storage::disk('public')->files('tmdb_images/posters/movies');
    $updated = 0;
    $skipped = 0;
    
    foreach ($files as $file) {
        // Extract TMDB ID from filename: movie_600129_98cf4189.jpg
        if (preg_match('/movie_(\d+)_[a-f0-9]{8}\.(jpg|png|webp)$/i', $file, $matches)) {
            $tmdbId = $matches[1];
            
            $movie = Movie::where('tmdb_id', $tmdbId)->first();
            
            if ($movie) {
                // Only update if not already set
                if (empty($movie->local_poster_path)) {
                    $movie->update(['local_poster_path' => $file]);
                    $updated++;
                    printSuccess("Updated: {$movie->title} (TMDB: {$tmdbId})");
                } else {
                    $skipped++;
                }
            } else {
                printError("Movie not found: TMDB ID {$tmdbId}");
            }
        }
    }
    
    printInfo("Updated: " . Color::GREEN . $updated . Color::RESET . " | Skipped: {$skipped} | Total files: " . count($files));
    echo "\n";
    
    return $updated;
}

function syncMovieBackdrops() {
    printSection("Syncing movie backdrops");
    
    $files = Storage::disk('public')->files('tmdb_images/backdrops/movies');
    $updated = 0;
    $skipped = 0;
    
    foreach ($files as $file) {
        // Extract TMDB ID from filename: movie_600129_98cf4189.jpg
        if (preg_match('/movie_(\d+)_[a-f0-9]{8}\.(jpg|png|webp)$/i', $file, $matches)) {
            $tmdbId = $matches[1];
            
            $movie = Movie::where('tmdb_id', $tmdbId)->first();
            
            if ($movie) {
                if (empty($movie->local_backdrop_path)) {
                    $movie->update(['local_backdrop_path' => $file]);
                    $updated++;
                    printSuccess("Updated: {$movie->title} (TMDB: {$tmdbId})");
                } else {
                    $skipped++;
                }
            } else {
                printError("Movie not found: TMDB ID {$tmdbId}");
            }
        }
    }
    
    printInfo("Updated: " . Color::GREEN . $updated . Color::RESET . " | Skipped: {$skipped} | Total files: " . count($files));
    echo "\n";
    
    return $updated;
}

function syncSeriesPosters() {
    printSection("Syncing series posters");
    
    $files = Storage::disk('public')->files('tmdb_images/posters/series');
    $updated = 0;
    $skipped = 0;
    
    foreach ($files as $file) {
        // Extract TMDB ID from filename: series_12345_98cf4189.jpg
        if (preg_match('/series_(\d+)_[a-f0-9]{8}\.(jpg|png|webp)$/i', $file, $matches)) {
            $tmdbId = $matches[1];
            
            $series = Series::where('tmdb_id', $tmdbId)->first();
            
            if ($series) {
                if (empty($series->local_poster_path)) {
                    $series->update(['local_poster_path' => $file]);
                    $updated++;
                    printSuccess("Updated: {$series->title} (TMDB: {$tmdbId})");
                } else {
                    $skipped++;
                }
            } else {
                printError("Series not found: TMDB ID {$tmdbId}");
            }
        }
    }
    
    printInfo("Updated: " . Color::GREEN . $updated . Color::RESET . " | Skipped: {$skipped} | Total files: " . count($files));
    echo "\n";
    
    return $updated;
}

function syncSeriesBackdrops() {
    printSection("Syncing series backdrops");
    
    $files = Storage::disk('public')->files('tmdb_images/backdrops/series');
    $updated = 0;
    $skipped = 0;
    
    foreach ($files as $file) {
        // Extract TMDB ID from filename: series_12345_98cf4189.jpg
        if (preg_match('/series_(\d+)_[a-f0-9]{8}\.(jpg|png|webp)$/i', $file, $matches)) {
            $tmdbId = $matches[1];
            
            $series = Series::where('tmdb_id', $tmdbId)->first();
            
            if ($series) {
                if (empty($series->local_backdrop_path)) {
                    $series->update(['local_backdrop_path' => $file]);
                    $updated++;
                    printSuccess("Updated: {$series->title} (TMDB: {$tmdbId})");
                } else {
                    $skipped++;
                }
            } else {
                printError("Series not found: TMDB ID {$tmdbId}");
            }
        }
    }
    
    printInfo("Updated: " . Color::GREEN . $updated . Color::RESET . " | Skipped: {$skipped} | Total files: " . count($files));
    echo "\n";
    
    return $updated;
}

function syncSeasonPosters() {
    printSection("Syncing season posters");
    
    $files = Storage::disk('public')->files('tmdb_images/posters/seasons');
    $updated = 0;
    $skipped = 0;
    
    foreach ($files as $file) {
        // Extract TMDB ID and season number from filename: series_12345_s1_98cf4189.jpg
        if (preg_match('/series_(\d+)_s(\d+)_[a-f0-9]{8}\.(jpg|png|webp)$/i', $file, $matches)) {
            $tmdbId = $matches[1];
            $seasonNumber = $matches[2];
            
            $season = SeriesSeason::whereHas('series', function($q) use ($tmdbId) {
                $q->where('tmdb_id', $tmdbId);
            })->where('season_number', $seasonNumber)->first();
            
            if ($season) {
                if (empty($season->local_poster_path)) {
                    $season->update(['local_poster_path' => $file]);
                    $updated++;
                    printSuccess("Updated: {$season->series->title} S{$seasonNumber} (TMDB: {$tmdbId})");
                } else {
                    $skipped++;
                }
            } else {
                printError("Season not found: TMDB ID {$tmdbId} Season {$seasonNumber}");
            }
        }
    }
    
    printInfo("Updated: " . Color::GREEN . $updated . Color::RESET . " | Skipped: {$skipped} | Total files: " . count($files));
    echo "\n";
    
    return $updated;
}

function syncEpisodeStills() {
    printSection("Syncing episode stills");
    
    $files = Storage::disk('public')->files('tmdb_images/stills/episodes');
    $updated = 0;
    $skipped = 0;
    
    foreach ($files as $file) {
        // Extract info from filename: series_12345_s1e2_98cf4189.jpg
        if (preg_match('/series_(\d+)_s(\d+)e(\d+)_[a-f0-9]{8}\.(jpg|png|webp)$/i', $file, $matches)) {
            $tmdbId = $matches[1];
            $seasonNumber = $matches[2];
            $episodeNumber = $matches[3];
            
            $episode = SeriesEpisode::whereHas('season.series', function($q) use ($tmdbId) {
                $q->where('tmdb_id', $tmdbId);
            })
            ->whereHas('season', function($q) use ($seasonNumber) {
                $q->where('season_number', $seasonNumber);
            })
            ->where('episode_number', $episodeNumber)
            ->first();
            
            if ($episode) {
                if (empty($episode->local_still_path)) {
                    $episode->update(['local_still_path' => $file]);
                    $updated++;
                    printSuccess("Updated: {$episode->season->series->title} S{$seasonNumber}E{$episodeNumber}");
                } else {
                    $skipped++;
                }
            } else {
                printError("Episode not found: TMDB ID {$tmdbId} S{$seasonNumber}E{$episodeNumber}");
            }
        }
    }
    
    printInfo("Updated: " . Color::GREEN . $updated . Color::RESET . " | Skipped: {$skipped} | Total files: " . count($files));
    echo "\n";
    
    return $updated;
}

function printSummary($totalUpdated) {
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║ " . Color::BOLD . "Sync Complete!" . Color::RESET . "                                        ║\n";
    echo "╠════════════════════════════════════════════════════════╣\n";
    printf("║ %-40s %s%5d%s ║\n", "✅ Total records updated", Color::GREEN, $totalUpdated, Color::RESET);
    echo "╚════════════════════════════════════════════════════════╝\n\n";
    
    echo Color::GREEN . "🎉 Done! Check status with: " . Color::BOLD . "php tmdb-image-downloader.php status\n" . Color::RESET;
    echo "\n";
}

// Main execution
try {
    printHeader();
    
    echo Color::YELLOW . "⚠️  This will sync database with downloaded image files.\n" . Color::RESET;
    echo "Continue? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'y') {
        echo Color::RED . "❌ Cancelled.\n\n" . Color::RESET;
        exit(0);
    }
    
    echo "\n" . Color::GREEN . "✅ Starting sync process...\n\n" . Color::RESET;
    
    $totalUpdated = 0;
    
    // Sync all types
    $totalUpdated += syncMoviePosters();
    $totalUpdated += syncMovieBackdrops();
    $totalUpdated += syncSeriesPosters();
    $totalUpdated += syncSeriesBackdrops();
    $totalUpdated += syncSeasonPosters();
    $totalUpdated += syncEpisodeStills();
    
    printSummary($totalUpdated);
    
} catch (Exception $e) {
    echo Color::RED . "\n❌ Error: " . $e->getMessage() . "\n" . Color::RESET;
    echo Color::YELLOW . "\nStack trace:\n" . $e->getTraceAsString() . "\n\n" . Color::RESET;
    exit(1);
}
