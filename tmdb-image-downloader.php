#!/usr/bin/env php
<?php

/**
 * TMDB Image Downloader - Standalone Script
 * 
 * Usage: php tmdb-image-downloader.php [command]
 * 
 * Commands:
 *   test       - Test download 1 image
 *   preview    - Preview what will be downloaded (no actual download)
 *   download   - Download all images
 *   status     - Check download status
 */

// Colors for terminal output
class Color {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const BOLD = "\033[1m";
}

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Movie;
use App\Models\Series;
use App\Models\SeriesSeason;
use App\Models\SeriesEpisode;
use App\Services\TmdbImageDownloadService;
use Illuminate\Support\Facades\Storage;

class TmdbImageDownloaderCli {
    private $service;
    
    public function __construct() {
        $this->service = app(TmdbImageDownloadService::class);
    }
    
    public function run($command = 'help') {
        $this->printHeader();
        
        switch ($command) {
            case 'test':
                $this->testDownload();
                break;
            case 'preview':
                $this->preview();
                break;
            case 'download':
                $this->download();
                break;
            case 'status':
                $this->status();
                break;
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }
    
    private function printHeader() {
        echo Color::CYAN . Color::BOLD . "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║         TMDB Local Image Downloader v1.0                ║\n";
        echo "║         Noobz Cinema - Image Management Tool            ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo Color::RESET . "\n";
    }
    
    private function showHelp() {
        echo Color::YELLOW . "📖 Available Commands:\n" . Color::RESET;
        echo "  " . Color::GREEN . "php tmdb-image-downloader.php test" . Color::RESET . "     - Test download 1 image\n";
        echo "  " . Color::GREEN . "php tmdb-image-downloader.php preview" . Color::RESET . "  - Preview download stats\n";
        echo "  " . Color::GREEN . "php tmdb-image-downloader.php download" . Color::RESET . " - Download all images\n";
        echo "  " . Color::GREEN . "php tmdb-image-downloader.php status" . Color::RESET . "   - Check current status\n";
        echo "\n";
    }
    
    private function testDownload() {
        echo Color::YELLOW . "🧪 Testing Download (1 image)...\n\n" . Color::RESET;
        
        // Find movie with poster_path
        $movie = Movie::whereNotNull('poster_path')
            ->whereNull('local_poster_path')
            ->first();
        
        if (!$movie) {
            echo Color::RED . "❌ No movies found for testing!\n" . Color::RESET;
            return;
        }
        
        echo "📝 Test Movie:\n";
        echo "   ID: " . $movie->id . "\n";
        echo "   Title: " . Color::CYAN . $movie->title . Color::RESET . "\n";
        echo "   TMDB ID: " . $movie->tmdb_id . "\n";
        echo "   Poster Path: " . $movie->poster_path . "\n\n";
        
        echo Color::YELLOW . "⬇️  Downloading poster...\n" . Color::RESET;
        
        // Download
        $startTime = microtime(true);
        $path = $this->service->downloadMoviePoster($movie->poster_path, $movie->tmdb_id);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($path && Storage::disk('public')->exists($path)) {
            $size = Storage::disk('public')->size($path);
            $fullPath = Storage::disk('public')->path($path);
            
            echo Color::GREEN . "✅ SUCCESS!\n\n" . Color::RESET;
            echo "📊 Download Details:\n";
            echo "   Local Path: " . Color::GREEN . $path . Color::RESET . "\n";
            echo "   Full Path: " . $fullPath . "\n";
            echo "   File Size: " . $this->formatBytes($size) . "\n";
            echo "   Duration: " . $duration . "ms\n";
            echo "   Status: " . Color::GREEN . "File exists ✓" . Color::RESET . "\n\n";
            
            // Update database
            $movie->update(['local_poster_path' => $path]);
            echo Color::GREEN . "✅ Database updated!\n" . Color::RESET;
            
        } else {
            echo Color::RED . "❌ FAILED!\n" . Color::RESET;
            echo "   Returned path: " . ($path ?: 'null') . "\n";
            echo "   Check logs: storage/logs/laravel.log\n";
        }
        
        echo "\n";
    }
    
    private function preview() {
        echo Color::YELLOW . "📊 Preview - Calculating statistics...\n\n" . Color::RESET;
        
        // Count pending downloads
        $stats = [
            'movies_posters' => Movie::whereNotNull('poster_path')->whereNull('local_poster_path')->count(),
            'movies_backdrops' => Movie::whereNotNull('backdrop_path')->whereNull('local_backdrop_path')->count(),
            'series_posters' => Series::whereNotNull('poster_path')->whereNull('local_poster_path')->count(),
            'series_backdrops' => Series::whereNotNull('backdrop_path')->whereNull('local_backdrop_path')->count(),
            'seasons_posters' => SeriesSeason::whereNotNull('poster_path')->whereNull('local_poster_path')->count(),
            'episodes_stills' => SeriesEpisode::whereNotNull('still_path')->whereNull('local_still_path')->count(),
        ];
        
        $total = array_sum($stats);
        $estimatedSize = $total * 0.3; // ~300KB per image average
        $estimatedTime = $total * 0.5; // ~500ms per image average
        
        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║ " . Color::BOLD . "Images Pending Download" . Color::RESET . "                              ║\n";
        echo "╠════════════════════════════════════════════════════════╣\n";
        
        foreach ($stats as $category => $count) {
            $label = str_replace('_', ' ', ucwords($category, '_'));
            $color = $count > 0 ? Color::YELLOW : Color::GREEN;
            printf("║ %-40s %s%5d%s ║\n", $label, $color, $count, Color::RESET);
        }
        
        echo "╠════════════════════════════════════════════════════════╣\n";
        printf("║ %-40s %s%5d%s ║\n", Color::BOLD . "TOTAL" . Color::RESET, Color::CYAN . Color::BOLD, $total, Color::RESET);
        echo "╚════════════════════════════════════════════════════════╝\n\n";
        
        echo "📈 Estimates:\n";
        echo "   Storage needed: ~" . $this->formatBytes($estimatedSize * 1024 * 1024) . "\n";
        echo "   Time required: ~" . round($estimatedTime / 60, 1) . " minutes\n\n";
        
        if ($total > 0) {
            echo Color::GREEN . "💡 Ready to download? Run: " . Color::BOLD . "php tmdb-image-downloader.php download\n" . Color::RESET;
        } else {
            echo Color::GREEN . "✅ All images already downloaded!\n" . Color::RESET;
        }
        
        echo "\n";
    }
    
    private function download() {
        echo Color::YELLOW . "🚀 Starting Bulk Download...\n\n" . Color::RESET;
        
        // Ask for confirmation
        echo Color::RED . "⚠️  This will download all pending images.\n" . Color::RESET;
        echo "Continue? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 'y') {
            echo Color::YELLOW . "❌ Cancelled.\n" . Color::RESET;
            return;
        }
        
        echo "\n" . Color::GREEN . "✅ Starting download process...\n\n" . Color::RESET;
        
        $totalDownloaded = 0;
        $totalFailed = 0;
        
        // Download movie posters
        echo Color::CYAN . "📥 Downloading movie posters...\n" . Color::RESET;
        $result = $this->downloadMoviePosters();
        $totalDownloaded += $result['success'];
        $totalFailed += $result['failed'];
        
        // Download movie backdrops
        echo Color::CYAN . "📥 Downloading movie backdrops...\n" . Color::RESET;
        $result = $this->downloadMovieBackdrops();
        $totalDownloaded += $result['success'];
        $totalFailed += $result['failed'];
        
        // Download series posters
        echo Color::CYAN . "📥 Downloading series posters...\n" . Color::RESET;
        $result = $this->downloadSeriesPosters();
        $totalDownloaded += $result['success'];
        $totalFailed += $result['failed'];
        
        // Download series backdrops
        echo Color::CYAN . "📥 Downloading series backdrops...\n" . Color::RESET;
        $result = $this->downloadSeriesBackdrops();
        $totalDownloaded += $result['success'];
        $totalFailed += $result['failed'];
        
        // Final summary
        echo "\n";
        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║ " . Color::BOLD . "Download Complete!" . Color::RESET . "                                   ║\n";
        echo "╠════════════════════════════════════════════════════════╣\n";
        printf("║ %-40s %s%5d%s ║\n", "✅ Successfully downloaded", Color::GREEN, $totalDownloaded, Color::RESET);
        printf("║ %-40s %s%5d%s ║\n", "❌ Failed", Color::RED, $totalFailed, Color::RESET);
        echo "╚════════════════════════════════════════════════════════╝\n\n";
        
        echo Color::GREEN . "🎉 Done! Check status with: " . Color::BOLD . "php tmdb-image-downloader.php status\n" . Color::RESET;
        echo "\n";
    }
    
    private function downloadMoviePosters() {
        $movies = Movie::whereNotNull('poster_path')
            ->whereNull('local_poster_path')
            ->get();
        
        $success = 0;
        $failed = 0;
        
        foreach ($movies as $movie) {
            $path = $this->service->downloadMoviePoster($movie->poster_path, $movie->tmdb_id);
            
            if ($path && Storage::disk('public')->exists($path)) {
                $movie->update(['local_poster_path' => $path]);
                $success++;
                echo Color::GREEN . "   ✓ " . Color::RESET . $movie->title . "\n";
            } else {
                $failed++;
                echo Color::RED . "   ✗ " . Color::RESET . $movie->title . "\n";
            }
        }
        
        echo "   Downloaded: " . Color::GREEN . $success . Color::RESET . " | Failed: " . Color::RED . $failed . Color::RESET . "\n\n";
        
        return ['success' => $success, 'failed' => $failed];
    }
    
    private function downloadMovieBackdrops() {
        $movies = Movie::whereNotNull('backdrop_path')
            ->whereNull('local_backdrop_path')
            ->get();
        
        $success = 0;
        $failed = 0;
        
        foreach ($movies as $movie) {
            $path = $this->service->downloadMovieBackdrop($movie->backdrop_path, $movie->tmdb_id);
            
            if ($path && Storage::disk('public')->exists($path)) {
                $movie->update(['local_backdrop_path' => $path]);
                $success++;
                echo Color::GREEN . "   ✓ " . Color::RESET . $movie->title . "\n";
            } else {
                $failed++;
                echo Color::RED . "   ✗ " . Color::RESET . $movie->title . "\n";
            }
        }
        
        echo "   Downloaded: " . Color::GREEN . $success . Color::RESET . " | Failed: " . Color::RED . $failed . Color::RESET . "\n\n";
        
        return ['success' => $success, 'failed' => $failed];
    }
    
    private function downloadSeriesPosters() {
        $series = Series::whereNotNull('poster_path')
            ->whereNull('local_poster_path')
            ->get();
        
        $success = 0;
        $failed = 0;
        
        foreach ($series as $item) {
            $path = $this->service->downloadSeriesPoster($item->poster_path, $item->tmdb_id);
            
            if ($path && Storage::disk('public')->exists($path)) {
                $item->update(['local_poster_path' => $path]);
                $success++;
                echo Color::GREEN . "   ✓ " . Color::RESET . $item->title . "\n";
            } else {
                $failed++;
                echo Color::RED . "   ✗ " . Color::RESET . $item->title . "\n";
            }
        }
        
        echo "   Downloaded: " . Color::GREEN . $success . Color::RESET . " | Failed: " . Color::RED . $failed . Color::RESET . "\n\n";
        
        return ['success' => $success, 'failed' => $failed];
    }
    
    private function downloadSeriesBackdrops() {
        $series = Series::whereNotNull('backdrop_path')
            ->whereNull('local_backdrop_path')
            ->get();
        
        $success = 0;
        $failed = 0;
        
        foreach ($series as $item) {
            $path = $this->service->downloadSeriesBackdrop($item->backdrop_path, $item->tmdb_id);
            
            if ($path && Storage::disk('public')->exists($path)) {
                $item->update(['local_backdrop_path' => $path]);
                $success++;
                echo Color::GREEN . "   ✓ " . Color::RESET . $item->title . "\n";
            } else {
                $failed++;
                echo Color::RED . "   ✗ " . Color::RESET . $item->title . "\n";
            }
        }
        
        echo "   Downloaded: " . Color::GREEN . $success . Color::RESET . " | Failed: " . Color::RED . $failed . Color::RESET . "\n\n";
        
        return ['success' => $success, 'failed' => $failed];
    }
    
    private function status() {
        echo Color::YELLOW . "📊 Current Status...\n\n" . Color::RESET;
        
        // Movies
        $moviesTotal = Movie::count();
        $moviesWithPoster = Movie::whereNotNull('local_poster_path')->count();
        $moviesWithBackdrop = Movie::whereNotNull('local_backdrop_path')->count();
        
        // Series
        $seriesTotal = Series::count();
        $seriesWithPoster = Series::whereNotNull('local_poster_path')->count();
        $seriesWithBackdrop = Series::whereNotNull('local_backdrop_path')->count();
        
        // Seasons
        $seasonsTotal = SeriesSeason::count();
        $seasonsWithPoster = SeriesSeason::whereNotNull('local_poster_path')->count();
        
        // Episodes
        $episodesTotal = SeriesEpisode::count();
        $episodesWithStill = SeriesEpisode::whereNotNull('local_still_path')->count();
        
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║ " . Color::BOLD . "Download Progress Status" . Color::RESET . "                                     ║\n";
        echo "╠════════════════════════════════════════════════════════════════╣\n";
        
        $this->printProgressRow("Movies (Posters)", $moviesWithPoster, $moviesTotal);
        $this->printProgressRow("Movies (Backdrops)", $moviesWithBackdrop, $moviesTotal);
        $this->printProgressRow("Series (Posters)", $seriesWithPoster, $seriesTotal);
        $this->printProgressRow("Series (Backdrops)", $seriesWithBackdrop, $seriesTotal);
        $this->printProgressRow("Seasons (Posters)", $seasonsWithPoster, $seasonsTotal);
        $this->printProgressRow("Episodes (Stills)", $episodesWithStill, $episodesTotal);
        
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";
        
        // Storage usage
        if (Storage::disk('public')->exists('tmdb_images')) {
            echo "💾 Storage Usage:\n";
            $stats = $this->service->getStorageStats();
            echo "   Total Files: " . Color::CYAN . $stats['total']['files'] . Color::RESET . "\n";
            echo "   Total Size: " . Color::CYAN . $stats['total']['size_mb'] . " MB" . Color::RESET . "\n\n";
        }
    }
    
    private function printProgressRow($label, $current, $total) {
        $percentage = $total > 0 ? round(($current / $total) * 100, 1) : 0;
        $bar = $this->getProgressBar($percentage);
        
        $color = $percentage == 100 ? Color::GREEN : ($percentage > 50 ? Color::YELLOW : Color::RED);
        
        printf("║ %-25s %s%s%s %5d/%d (%5.1f%%) ║\n", 
            $label, 
            $color, 
            $bar, 
            Color::RESET,
            $current, 
            $total, 
            $percentage
        );
    }
    
    private function getProgressBar($percentage, $width = 20) {
        $filled = round(($percentage / 100) * $width);
        $empty = $width - $filled;
        return str_repeat('█', $filled) . str_repeat('░', $empty);
    }
    
    private function formatBytes($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}

// Run CLI
$command = $argv[1] ?? 'help';
$cli = new TmdbImageDownloaderCli();
$cli->run($command);
