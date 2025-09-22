<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MovieFileService
{
    protected $posterDisk = 'public';
    protected $posterPath = 'movies/posters';
    protected $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    protected $maxFileSize = 5120; // 5MB in KB

    /**
     * Handle poster upload
     */
    public function uploadPoster(UploadedFile $file, ?string $oldPosterPath = null): array
    {
        try {
            // Validate file
            $validation = $this->validatePosterFile($file);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'path' => null
                ];
            }

            // Generate unique filename
            $filename = $this->generatePosterFilename($file);
            $fullPath = $this->posterPath . '/' . $filename;

            // Process and store image
            $processedImage = $this->processImage($file);
            $stored = Storage::disk($this->posterDisk)->put($fullPath, $processedImage);

            if (!$stored) {
                return [
                    'success' => false,
                    'message' => 'Failed to store poster file',
                    'path' => null
                ];
            }

            // Delete old poster if exists
            if ($oldPosterPath) {
                $this->deletePoster($oldPosterPath);
            }

            Log::info('Poster uploaded successfully', [
                'filename' => $filename,
                'path' => $fullPath,
                'size' => $file->getSize()
            ]);

            return [
                'success' => true,
                'message' => 'Poster uploaded successfully',
                'path' => $fullPath,
                'filename' => $filename,
                'url' => Storage::disk($this->posterDisk)->url($fullPath)
            ];

        } catch (\Exception $e) {
            Log::error('Poster upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
                'path' => null
            ];
        }
    }

    /**
     * Delete poster file
     */
    public function deletePoster(string $posterPath): bool
    {
        try {
            if (Storage::disk($this->posterDisk)->exists($posterPath)) {
                $deleted = Storage::disk($this->posterDisk)->delete($posterPath);
                
                if ($deleted) {
                    Log::info('Poster deleted', ['path' => $posterPath]);
                }
                
                return $deleted;
            }
            
            return true; // File doesn't exist, consider it deleted

        } catch (\Exception $e) {
            Log::error('Failed to delete poster', [
                'path' => $posterPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Download and store image from URL (for TMDB posters)
     */
    public function downloadPosterFromUrl(string $imageUrl, string $tmdbPosterPath): array
    {
        try {
            // Build full TMDB URL
            $fullUrl = $this->buildTMDBImageUrl($tmdbPosterPath);
            
            // Download image
            $imageContent = $this->downloadImage($fullUrl);
            
            if (!$imageContent) {
                return [
                    'success' => false,
                    'message' => 'Failed to download image from TMDB',
                    'path' => null
                ];
            }

            // Generate filename
            $filename = $this->generateFilenameFromUrl($tmdbPosterPath);
            $fullPath = $this->posterPath . '/' . $filename;

            // Process and store image
            $processedImage = $this->processImageFromContent($imageContent);
            $stored = Storage::disk($this->posterDisk)->put($fullPath, $processedImage);

            if (!$stored) {
                return [
                    'success' => false,
                    'message' => 'Failed to store downloaded poster',
                    'path' => null
                ];
            }

            Log::info('Poster downloaded from TMDB', [
                'tmdb_path' => $tmdbPosterPath,
                'local_path' => $fullPath,
                'url' => $fullUrl
            ]);

            return [
                'success' => true,
                'message' => 'Poster downloaded successfully',
                'path' => $fullPath,
                'filename' => $filename,
                'url' => Storage::disk($this->posterDisk)->url($fullPath)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to download poster from TMDB', [
                'tmdb_path' => $tmdbPosterPath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage(),
                'path' => null
            ];
        }
    }

    /**
     * Get poster URL
     */
    public function getPosterUrl(?string $posterPath): ?string
    {
        if (!$posterPath) {
            return null;
        }

        try {
            if (Storage::disk($this->posterDisk)->exists($posterPath)) {
                return Storage::disk($this->posterDisk)->url($posterPath);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get poster URL', [
                'path' => $posterPath,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Validate poster file
     */
    protected function validatePosterFile(UploadedFile $file): array
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'message' => 'Uploaded file is not valid'
            ];
        }

        // Check file size
        if ($file->getSize() > ($this->maxFileSize * 1024)) {
            return [
                'valid' => false,
                'message' => 'File size must be less than ' . $this->maxFileSize . 'KB'
            ];
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            return [
                'valid' => false,
                'message' => 'File must be a valid image (JPEG, PNG, or WebP)'
            ];
        }

        // Check if it's actually an image
        try {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                return [
                    'valid' => false,
                    'message' => 'File is not a valid image'
                ];
            }
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Unable to validate image file'
            ];
        }

        return [
            'valid' => true,
            'message' => 'File is valid'
        ];
    }

    /**
     * Generate unique filename for poster
     */
    protected function generatePosterFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        
        return "poster_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Generate filename from URL
     */
    protected function generateFilenameFromUrl(string $tmdbPath): string
    {
        $extension = pathinfo($tmdbPath, PATHINFO_EXTENSION) ?: 'jpg';
        $timestamp = now()->format('YmdHis');
        $hash = substr(md5($tmdbPath), 0, 8);
        
        return "tmdb_{$timestamp}_{$hash}.{$extension}";
    }

    /**
     * Process uploaded image
     */
    protected function processImage(UploadedFile $file): string
    {
        try {
            // Create image instance
            $image = Image::make($file->getPathname());
            
            // Resize if too large (maintain aspect ratio)
            if ($image->width() > 500 || $image->height() > 750) {
                $image->resize(500, 750, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Optimize quality
            $image->encode('jpg', 85);

            return $image->stream()->getContents();

        } catch (\Exception $e) {
            Log::warning('Image processing failed, using original', [
                'error' => $e->getMessage()
            ]);
            
            // Return original file content if processing fails
            return file_get_contents($file->getPathname());
        }
    }

    /**
     * Process image from content
     */
    protected function processImageFromContent(string $imageContent): string
    {
        try {
            // Create image instance from content
            $image = Image::make($imageContent);
            
            // Resize if too large
            if ($image->width() > 500 || $image->height() > 750) {
                $image->resize(500, 750, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Optimize quality
            $image->encode('jpg', 85);

            return $image->stream()->getContents();

        } catch (\Exception $e) {
            Log::warning('Image processing from content failed, using original', [
                'error' => $e->getMessage()
            ]);
            
            return $imageContent;
        }
    }

    /**
     * Build TMDB image URL
     */
    protected function buildTMDBImageUrl(string $posterPath): string
    {
        $baseUrl = 'https://image.tmdb.org/t/p/w500';
        return $baseUrl . $posterPath;
    }

    /**
     * Download image from URL
     */
    protected function downloadImage(string $url): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Movie App Poster Downloader',
                    'follow_location' => true,
                    'max_redirects' => 3
                ]
            ]);

            $content = file_get_contents($url, false, $context);
            
            return $content !== false ? $content : null;

        } catch (\Exception $e) {
            Log::error('Failed to download image', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Clean up orphaned poster files
     */
    public function cleanupOrphanedPosters(): array
    {
        try {
            $allPosters = Storage::disk($this->posterDisk)->files($this->posterPath);
            $usedPosters = \App\Models\Movie::whereNotNull('poster_path')
                ->pluck('poster_path')
                ->toArray();

            $orphanedPosters = array_diff($allPosters, $usedPosters);
            $deletedCount = 0;

            foreach ($orphanedPosters as $posterPath) {
                if ($this->deletePoster($posterPath)) {
                    $deletedCount++;
                }
            }

            Log::info('Poster cleanup completed', [
                'total_posters' => count($allPosters),
                'used_posters' => count($usedPosters),
                'orphaned_posters' => count($orphanedPosters),
                'deleted_count' => $deletedCount
            ]);

            return [
                'success' => true,
                'message' => "Cleaned up {$deletedCount} orphaned poster files",
                'deleted_count' => $deletedCount,
                'orphaned_count' => count($orphanedPosters)
            ];

        } catch (\Exception $e) {
            Log::error('Poster cleanup failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get file size info
     */
    public function getFileSizeInfo(?string $posterPath): array
    {
        if (!$posterPath || !Storage::disk($this->posterDisk)->exists($posterPath)) {
            return [
                'exists' => false,
                'size' => 0,
                'size_human' => '0 B'
            ];
        }

        try {
            $size = Storage::disk($this->posterDisk)->size($posterPath);
            
            return [
                'exists' => true,
                'size' => $size,
                'size_human' => $this->formatFileSize($size)
            ];

        } catch (\Exception $e) {
            return [
                'exists' => false,
                'size' => 0,
                'size_human' => '0 B',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format file size to human readable
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}