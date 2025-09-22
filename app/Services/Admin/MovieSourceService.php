<?php

namespace App\Services\Admin;

use App\Models\Movie;
use App\Models\MovieSource;
use App\Models\BrokenLinkReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MovieSourceService
{
    /**
     * Get all sources for a movie
     */
    public function getMovieSources(Movie $movie): \Illuminate\Database\Eloquent\Collection
    {
        return $movie->sources()->ordered()->get();
    }

    /**
     * Create new source for movie
     */
    public function createSource(Movie $movie, array $data): MovieSource
    {
        try {
            // Validate quality
            $this->validateQuality($data['quality'] ?? '');

            // Prepare source data
            $sourceData = [
                'movie_id' => $movie->id,
                'source_name' => $data['source_name'],
                'embed_url' => $data['embed_url'],
                'quality' => $data['quality'],
                'priority' => $data['priority'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => auth()->id(),
            ];

            // Check for duplicate URL
            $this->checkDuplicateSource($movie, $sourceData['embed_url']);

            $source = MovieSource::create($sourceData);

            Log::info('Movie source created', [
                'movie_id' => $movie->id,
                'source_id' => $source->id,
                'source_name' => $source->source_name,
                'quality' => $source->quality
            ]);

            return $source;

        } catch (\Exception $e) {
            Log::error('Failed to create movie source', [
                'movie_id' => $movie->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update existing source
     */
    public function updateSource(MovieSource $source, array $data): MovieSource
    {
        try {
            // Validate quality if provided
            if (isset($data['quality'])) {
                $this->validateQuality($data['quality']);
            }

            // Check for duplicate URL if URL is being changed
            if (isset($data['embed_url']) && $data['embed_url'] !== $source->embed_url) {
                $this->checkDuplicateSource($source->movie, $data['embed_url'], $source->id);
            }

            // Prepare update data
            $updateData = array_filter([
                'source_name' => $data['source_name'] ?? null,
                'embed_url' => $data['embed_url'] ?? null,
                'quality' => $data['quality'] ?? null,
                'priority' => $data['priority'] ?? null,
                'is_active' => $data['is_active'] ?? null,
                'updated_by' => auth()->id(),
            ], function ($value) {
                return $value !== null;
            });

            $source->update($updateData);

            Log::info('Movie source updated', [
                'source_id' => $source->id,
                'movie_id' => $source->movie_id,
                'changes' => $updateData
            ]);

            return $source->fresh();

        } catch (\Exception $e) {
            Log::error('Failed to update movie source', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Toggle source active status
     */
    public function toggleSourceStatus(MovieSource $source): MovieSource
    {
        try {
            $newStatus = !$source->is_active;
            
            $source->update([
                'is_active' => $newStatus,
                'updated_by' => auth()->id()
            ]);

            Log::info('Movie source status toggled', [
                'source_id' => $source->id,
                'movie_id' => $source->movie_id,
                'new_status' => $newStatus ? 'active' : 'inactive'
            ]);

            return $source->fresh();

        } catch (\Exception $e) {
            Log::error('Failed to toggle source status', [
                'source_id' => $source->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete source
     */
    public function deleteSource(MovieSource $source): bool
    {
        try {
            $sourceInfo = [
                'source_id' => $source->id,
                'movie_id' => $source->movie_id,
                'source_name' => $source->source_name
            ];

            // Delete related broken link reports
            BrokenLinkReport::where('movie_source_id', $source->id)->delete();

            $source->delete();

            Log::info('Movie source deleted', $sourceInfo);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete movie source', [
                'source_id' => $source->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Migrate main movie embed URL to source
     */
    public function migrateMainEmbedToSource(Movie $movie): array
    {
        try {
            if (!$movie->embed_url) {
                return [
                    'success' => false,
                    'message' => 'No main embed URL found to migrate'
                ];
            }

            // Check if source already exists
            $existingSource = MovieSource::where('movie_id', $movie->id)
                ->where('embed_url', $movie->embed_url)
                ->first();

            if ($existingSource) {
                return [
                    'success' => false,
                    'message' => 'This embed URL already exists as a source'
                ];
            }

            // Create source from main embed URL
            $sourceData = [
                'source_name' => 'Main Server',
                'embed_url' => $movie->embed_url,
                'quality' => $movie->quality ?? 'HD',
                'priority' => 100,
                'is_active' => true
            ];

            $source = $this->createSource($movie, $sourceData);

            Log::info('Main embed URL migrated to source', [
                'movie_id' => $movie->id,
                'source_id' => $source->id,
                'embed_url' => $movie->embed_url
            ]);

            return [
                'success' => true,
                'message' => 'Main embed URL successfully migrated to source',
                'data' => $source
            ];

        } catch (\Exception $e) {
            Log::error('Failed to migrate main embed URL', [
                'movie_id' => $movie->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk update source priorities
     */
    public function updateSourcePriorities(Movie $movie, array $priorityData): array
    {
        try {
            $updated = 0;
            
            foreach ($priorityData as $sourceId => $priority) {
                $source = MovieSource::where('id', $sourceId)
                    ->where('movie_id', $movie->id)
                    ->first();
                
                if ($source) {
                    $source->update([
                        'priority' => (int) $priority,
                        'updated_by' => auth()->id()
                    ]);
                    $updated++;
                }
            }

            Log::info('Bulk source priorities updated', [
                'movie_id' => $movie->id,
                'updated_count' => $updated
            ]);

            return [
                'success' => true,
                'message' => "Updated {$updated} source priorities",
                'updated_count' => $updated
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update source priorities', [
                'movie_id' => $movie->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update priorities: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get source statistics for movie
     */
    public function getSourceStats(Movie $movie): array
    {
        $sources = $movie->sources;
        
        return [
            'total_sources' => $sources->count(),
            'active_sources' => $sources->where('is_active', true)->count(),
            'inactive_sources' => $sources->where('is_active', false)->count(),
            'quality_breakdown' => $sources->groupBy('quality')->map->count(),
            'total_reports' => $sources->sum('report_count'),
            'highest_priority' => $sources->max('priority'),
            'lowest_priority' => $sources->min('priority')
        ];
    }

    /**
     * Reset reports for a source
     */
    public function resetSourceReports(MovieSource $source): bool
    {
        try {
            $source->update([
                'report_count' => 0,
                'updated_by' => auth()->id()
            ]);

            // Mark related broken link reports as fixed
            BrokenLinkReport::where('movie_source_id', $source->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'fixed',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now()
                ]);

            Log::info('Source reports reset', [
                'source_id' => $source->id,
                'movie_id' => $source->movie_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to reset source reports', [
                'source_id' => $source->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate quality value
     */
    protected function validateQuality(string $quality): void
    {
        $allowedQualities = ['CAM', 'TS', 'HD', 'FHD', '4K'];
        
        if (!in_array($quality, $allowedQualities)) {
            throw ValidationException::withMessages([
                'quality' => 'Quality must be one of: ' . implode(', ', $allowedQualities)
            ]);
        }
    }

    /**
     * Check for duplicate source URL
     */
    protected function checkDuplicateSource(Movie $movie, string $embedUrl, ?int $excludeSourceId = null): void
    {
        $query = MovieSource::where('movie_id', $movie->id)
            ->where('embed_url', $embedUrl);
        
        if ($excludeSourceId) {
            $query->where('id', '!=', $excludeSourceId);
        }
        
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'embed_url' => 'This embed URL already exists for this movie'
            ]);
        }
    }

    /**
     * Get available quality options
     */
    public function getQualityOptions(): array
    {
        return [
            'CAM' => 'CAM Quality',
            'TS' => 'TS Quality', 
            'HD' => 'HD Quality',
            'FHD' => 'Full HD Quality',
            '4K' => '4K Quality'
        ];
    }
}