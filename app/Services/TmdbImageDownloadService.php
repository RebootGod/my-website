<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service: TMDB Image Download Service
 * 
 * Downloads and stores TMDB images locally to reduce API calls
 * Handles posters, backdrops, and episode stills
 * 
 * Security: File validation, path sanitization, size limits
 * 
 * @package App\Services
 */
class TmdbImageDownloadService
{
    /**
     * TMDB image base URL
     */
    protected string $tmdbImageUrl;

    /**
     * Maximum file size (5MB)
     */
    protected int $maxFileSize = 5242880;

    /**
     * Allowed mime types
     */
    protected array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct()
    {
        $this->tmdbImageUrl = config('services.tmdb.image_url', 'https://image.tmdb.org/t/p');
    }

    /**
     * Download movie poster
     *
     * @param string $posterPath TMDB poster path (e.g., /abc123.jpg)
     * @param int $tmdbId Movie TMDB ID
     * @return string|null Local storage path or null if failed
     */
    public function downloadMoviePoster(string $posterPath, int $tmdbId): ?string
    {
        return $this->downloadImage(
            $posterPath,
            'posters/movies',
            "movie_{$tmdbId}",
            'w500'
        );
    }

    /**
     * Download movie backdrop
     *
     * @param string $backdropPath TMDB backdrop path
     * @param int $tmdbId Movie TMDB ID
     * @return string|null Local storage path or null if failed
     */
    public function downloadMovieBackdrop(string $backdropPath, int $tmdbId): ?string
    {
        return $this->downloadImage(
            $backdropPath,
            'backdrops/movies',
            "movie_{$tmdbId}",
            'original'
        );
    }

    /**
     * Download series poster
     *
     * @param string $posterPath TMDB poster path
     * @param int $tmdbId Series TMDB ID
     * @return string|null Local storage path or null if failed
     */
    public function downloadSeriesPoster(string $posterPath, int $tmdbId): ?string
    {
        return $this->downloadImage(
            $posterPath,
            'posters/series',
            "series_{$tmdbId}",
            'w500'
        );
    }

    /**
     * Download series backdrop
     *
     * @param string $backdropPath TMDB backdrop path
     * @param int $tmdbId Series TMDB ID
     * @return string|null Local storage path or null if failed
     */
    public function downloadSeriesBackdrop(string $backdropPath, int $tmdbId): ?string
    {
        return $this->downloadImage(
            $backdropPath,
            'backdrops/series',
            "series_{$tmdbId}",
            'original'
        );
    }

    /**
     * Download season poster
     *
     * @param string $posterPath TMDB poster path
     * @param int $tmdbId Series TMDB ID
     * @param int $seasonNumber Season number
     * @return string|null Local storage path or null if failed
     */
    public function downloadSeasonPoster(string $posterPath, int $tmdbId, int $seasonNumber): ?string
    {
        return $this->downloadImage(
            $posterPath,
            'posters/seasons',
            "series_{$tmdbId}_s{$seasonNumber}",
            'w500'
        );
    }

    /**
     * Download episode still
     *
     * @param string $stillPath TMDB still path
     * @param int $tmdbId Series TMDB ID
     * @param int $seasonNumber Season number
     * @param int $episodeNumber Episode number
     * @return string|null Local storage path or null if failed
     */
    public function downloadEpisodeStill(string $stillPath, int $tmdbId, int $seasonNumber, int $episodeNumber): ?string
    {
        return $this->downloadImage(
            $stillPath,
            'stills/episodes',
            "series_{$tmdbId}_s{$seasonNumber}e{$episodeNumber}",
            'w500'
        );
    }

    /**
     * Core download method
     *
     * @param string $tmdbPath TMDB image path
     * @param string $directory Storage directory (relative to public/tmdb_images)
     * @param string $filenamePrefix Filename prefix for uniqueness
     * @param string $size Image size (w500, original, etc)
     * @return string|null Local storage path or null if failed
     */
    protected function downloadImage(
        string $tmdbPath,
        string $directory,
        string $filenamePrefix,
        string $size = 'w500'
    ): ?string {
        try {
            // Handle full URLs (extract path from URL)
            if (str_starts_with($tmdbPath, 'http')) {
                // Extract path from full URL: https://image.tmdb.org/t/p/w500/abc123.jpg -> /abc123.jpg
                preg_match('/\/([a-zA-Z0-9]+\.(jpg|png|webp))$/i', $tmdbPath, $matches);
                if (!empty($matches[1])) {
                    $tmdbPath = '/' . $matches[1];
                } else {
                    Log::warning('Cannot extract TMDB path from URL', ['url' => $tmdbPath]);
                    return null;
                }
            }

            // Sanitize TMDB path (security: prevent path traversal)
            $tmdbPath = ltrim($tmdbPath, '/');
            $tmdbPath = str_replace(['..', '\\'], '', $tmdbPath);

            // Construct TMDB URL
            $tmdbUrl = "{$this->tmdbImageUrl}/{$size}/{$tmdbPath}";

            // Download image
            $response = Http::timeout(30)->get($tmdbUrl);

            if (!$response->successful()) {
                Log::warning('TMDB image download failed', [
                    'url' => $tmdbUrl,
                    'status' => $response->status()
                ]);
                return null;
            }

            // Get image content
            $imageContent = $response->body();

            // Security: Validate file size
            if (strlen($imageContent) > $this->maxFileSize) {
                Log::warning('TMDB image too large', [
                    'url' => $tmdbUrl,
                    'size' => strlen($imageContent)
                ]);
                return null;
            }

            // Security: Validate mime type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                Log::warning('TMDB image invalid mime type', [
                    'url' => $tmdbUrl,
                    'mime' => $mimeType
                ]);
                return null;
            }

            // Generate filename with hash for uniqueness
            $extension = pathinfo($tmdbPath, PATHINFO_EXTENSION);
            $hash = substr(md5($imageContent), 0, 8);
            $filename = "{$filenamePrefix}_{$hash}.{$extension}";

            // Full storage path
            $storagePath = "public/tmdb_images/{$directory}/{$filename}";

            // Store image
            Storage::put($storagePath, $imageContent);

            // Return public URL path
            $publicPath = "tmdb_images/{$directory}/{$filename}";

            Log::info('TMDB image downloaded successfully', [
                'tmdb_url' => $tmdbUrl,
                'local_path' => $publicPath,
                'size' => strlen($imageContent)
            ]);

            return $publicPath;

        } catch (\Exception $e) {
            Log::error('TMDB image download exception', [
                'tmdb_path' => $tmdbPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if local image exists
     *
     * @param string $localPath Local storage path
     * @return bool
     */
    public function imageExists(string $localPath): bool
    {
        return Storage::disk('public')->exists($localPath);
    }

    /**
     * Delete local image
     *
     * @param string $localPath Local storage path
     * @return bool
     */
    public function deleteImage(string $localPath): bool
    {
        try {
            if ($this->imageExists($localPath)) {
                return Storage::disk('public')->delete($localPath);
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete local image', [
                'path' => $localPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get storage disk usage
     *
     * @return array Statistics
     */
    public function getStorageStats(): array
    {
        $directories = [
            'posters/movies',
            'posters/series',
            'posters/seasons',
            'backdrops/movies',
            'backdrops/series',
            'stills/episodes'
        ];

        $stats = [];
        $totalSize = 0;
        $totalFiles = 0;

        foreach ($directories as $dir) {
            $path = "public/tmdb_images/{$dir}";
            $files = Storage::files($path);
            $size = 0;

            foreach ($files as $file) {
                $size += Storage::size($file);
            }

            $stats[$dir] = [
                'files' => count($files),
                'size' => $size,
                'size_mb' => round($size / 1024 / 1024, 2)
            ];

            $totalSize += $size;
            $totalFiles += count($files);
        }

        $stats['total'] = [
            'files' => $totalFiles,
            'size' => $totalSize,
            'size_mb' => round($totalSize / 1024 / 1024, 2)
        ];

        return $stats;
    }
}
