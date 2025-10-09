<?php

namespace App\Jobs;

use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GenerateMovieThumbnailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The movie instance.
     */
    protected Movie $movie;

    /**
     * The image URL to process.
     */
    protected string $imageUrl;

    /**
     * The image type (poster or backdrop).
     */
    protected string $imageType;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Thumbnail sizes to generate.
     */
    protected array $thumbnailSizes = [
        'poster' => [
            'small' => ['width' => 185, 'height' => 278],   // w185
            'medium' => ['width' => 342, 'height' => 513],  // w342
            'large' => ['width' => 500, 'height' => 750],   // w500
            'original' => ['width' => 780, 'height' => 1170], // w780
        ],
        'backdrop' => [
            'small' => ['width' => 300, 'height' => 169],   // w300
            'medium' => ['width' => 780, 'height' => 439],  // w780
            'large' => ['width' => 1280, 'height' => 720],  // w1280
            'original' => ['width' => 1920, 'height' => 1080], // original
        ],
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(Movie $movie, string $imageUrl, string $imageType = 'poster')
    {
        $this->movie = $movie;
        $this->imageUrl = $imageUrl;
        $this->imageType = $imageType; // 'poster' or 'backdrop'
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('GenerateMovieThumbnailsJob: Starting thumbnail generation', [
                'movie_id' => $this->movie->id,
                'movie_title' => $this->movie->title,
                'image_type' => $this->imageType,
                'image_url' => $this->imageUrl,
            ]);

            // Validate image type
            if (!in_array($this->imageType, ['poster', 'backdrop'])) {
                throw new \InvalidArgumentException("Invalid image type: {$this->imageType}");
            }

            // Download the original image
            $imageContent = $this->downloadImage($this->imageUrl);
            if (!$imageContent) {
                Log::warning('GenerateMovieThumbnailsJob: Failed to download image', [
                    'movie_id' => $this->movie->id,
                    'image_url' => $this->imageUrl,
                ]);
                return;
            }

            // Generate thumbnails for each size
            $sizes = $this->thumbnailSizes[$this->imageType];
            $generatedCount = 0;

            foreach ($sizes as $sizeName => $dimensions) {
                if ($this->generateThumbnail($imageContent, $sizeName, $dimensions)) {
                    $generatedCount++;
                }
            }

            Log::info('GenerateMovieThumbnailsJob: Thumbnail generation completed', [
                'movie_id' => $this->movie->id,
                'image_type' => $this->imageType,
                'generated_count' => $generatedCount,
                'total_sizes' => count($sizes),
            ]);

        } catch (\Exception $e) {
            Log::error('GenerateMovieThumbnailsJob: Thumbnail generation failed', [
                'movie_id' => $this->movie->id,
                'image_type' => $this->imageType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Download image from URL.
     */
    private function downloadImage(string $url): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (compatible; NoobzCinema/1.0)',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $imageContent = @file_get_contents($url, false, $context);

            if ($imageContent === false) {
                return null;
            }

            // Validate it's an actual image
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            if (!str_starts_with($mimeType, 'image/')) {
                Log::warning('GenerateMovieThumbnailsJob: Invalid image type', [
                    'mime_type' => $mimeType,
                ]);
                return null;
            }

            return $imageContent;

        } catch (\Exception $e) {
            Log::error('GenerateMovieThumbnailsJob: Failed to download image', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate thumbnail for a specific size.
     */
    private function generateThumbnail(string $imageContent, string $sizeName, array $dimensions): bool
    {
        try {
            // Create image manager with GD driver
            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageContent);

            // Resize image maintaining aspect ratio
            $image->scale(width: $dimensions['width'], height: $dimensions['height']);

            // Optimize image quality (85% for good balance)
            $optimized = $image->toJpeg(quality: 85);

            // Generate storage path
            $directory = "thumbnails/movies/{$this->movie->id}/{$this->imageType}";
            $filename = "{$sizeName}.jpg";
            $path = "{$directory}/{$filename}";

            // Store thumbnail
            Storage::disk('public')->put($path, $optimized);

            Log::debug('GenerateMovieThumbnailsJob: Thumbnail generated', [
                'movie_id' => $this->movie->id,
                'size_name' => $sizeName,
                'dimensions' => $dimensions,
                'path' => $path,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::warning('GenerateMovieThumbnailsJob: Failed to generate thumbnail', [
                'movie_id' => $this->movie->id,
                'size_name' => $sizeName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate WebP version of thumbnail (optional, for modern browsers).
     */
    private function generateWebPVersion(string $imageContent, string $sizeName, array $dimensions): bool
    {
        try {
            // Check if WebP is supported
            if (!function_exists('imagewebp')) {
                return false;
            }

            // Create image manager with GD driver
            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageContent);

            // Resize image maintaining aspect ratio
            $image->scale(width: $dimensions['width'], height: $dimensions['height']);

            // Convert to WebP with 85% quality
            $optimized = $image->toWebp(quality: 85);

            // Generate storage path
            $directory = "thumbnails/movies/{$this->movie->id}/{$this->imageType}";
            $filename = "{$sizeName}.webp";
            $path = "{$directory}/{$filename}";

            // Store WebP thumbnail
            Storage::disk('public')->put($path, $optimized);

            Log::debug('GenerateMovieThumbnailsJob: WebP thumbnail generated', [
                'movie_id' => $this->movie->id,
                'size_name' => $sizeName,
                'path' => $path,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::warning('GenerateMovieThumbnailsJob: Failed to generate WebP thumbnail', [
                'movie_id' => $this->movie->id,
                'size_name' => $sizeName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateMovieThumbnailsJob: Job failed permanently', [
            'movie_id' => $this->movie->id,
            'image_type' => $this->imageType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
