<?php

namespace App\Services\Admin;

use App\Models\Series;
use App\Models\Genre;
use App\Services\TMDBService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SeriesTMDBService
{
    protected $tmdbService;

    public function __construct()
    {
        $this->tmdbService = new TMDBService();
    }

    /**
     * Search series on TMDB
     */
    public function searchSeries(string $query, int $page = 1): array
    {
        try {
            $results = $this->tmdbService->searchTv($query, $page);

            if (!$results['success']) {
                return [
                    'success' => false,
                    'message' => 'TMDB search failed: ' . ($results['error'] ?? 'Unknown error'),
                    'data' => []
                ];
            }

            // Check which series already exist in our database
            $tmdbIds = collect($results['data']['results'])->pluck('id')->toArray();
            $existingIds = Series::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();

            // Mark existing series
            $resultsWithStatus = collect($results['data']['results'])->map(function ($series) use ($existingIds) {
                $series['exists_in_db'] = in_array($series['id'], $existingIds);
                return $series;
            });

            return [
                'success' => true,
                'data' => [
                    'results' => $resultsWithStatus->toArray(),
                    'total_results' => $results['data']['total_results'] ?? 0,
                    'total_pages' => $results['data']['total_pages'] ?? 1,
                    'page' => $page
                ]
            ];

        } catch (\Exception $e) {
            Log::error('TMDB search error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get series details from TMDB
     */
    public function getSeriesDetails(int $tmdbId): array
    {
        try {
            $result = $this->tmdbService->getTvDetails($tmdbId);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch series details: ' . ($result['error'] ?? 'Unknown error'),
                    'data' => null
                ];
            }

            // Check if series already exists
            $existingSeries = Series::where('tmdb_id', $tmdbId)->first();

            $seriesData = $result['data'];
            $seriesData['exists_in_db'] = $existingSeries !== null;
            $seriesData['existing_series'] = $existingSeries;

            return [
                'success' => true,
                'data' => $seriesData
            ];

        } catch (\Exception $e) {
            Log::error('TMDB details error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch details: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Import series from TMDB
     */
    public function importSeries(int $tmdbId): array
    {
        try {
            // Check if series already exists
            if (Series::where('tmdb_id', $tmdbId)->exists()) {
                return [
                    'success' => false,
                    'message' => 'Series already exists in database',
                    'data' => null
                ];
            }

            // Get series details from TMDB
            $tmdbResult = $this->getSeriesDetails($tmdbId);

            if (!$tmdbResult['success']) {
                return $tmdbResult;
            }

            $tmdbData = $tmdbResult['data'];

            // Prepare series data (only the fields you specified)
            $seriesData = $this->prepareTMDBSeriesData($tmdbData);

            // Create series
            $series = Series::create($seriesData);

            // Sync genres
            $this->syncSeriesGenres($series, $tmdbData['genres'] ?? []);

            Log::info('Series imported from TMDB', [
                'tmdb_id' => $tmdbId,
                'series_id' => $series->id,
                'title' => $series->title
            ]);

            return [
                'success' => true,
                'message' => 'Series imported successfully',
                'data' => $series->load('genres')
            ];

        } catch (\Exception $e) {
            Log::error('TMDB import error: ' . $e->getMessage(), ['tmdb_id' => $tmdbId]);
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Prepare TMDB series data for database insertion
     */
    protected function prepareTMDBSeriesData(array $tmdbData): array
    {
        return [
            'tmdb_id' => $tmdbData['id'],
            'title' => $tmdbData['name'] ?? $tmdbData['title'],
            'description' => $tmdbData['overview'] ?? '',
            'poster_path' => $tmdbData['poster_path'] ?? null,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? null,
            'year' => $tmdbData['first_air_date'] ?
                Carbon::parse($tmdbData['first_air_date'])->year : null,
            'rating' => $tmdbData['vote_average'] ?? 0,
            'status' => 'published',
            'poster_url' => $tmdbData['poster_path'] ?
                'https://image.tmdb.org/t/p/w500' . $tmdbData['poster_path'] : null,
            'backdrop_url' => $tmdbData['backdrop_path'] ?
                'https://image.tmdb.org/t/p/w1280' . $tmdbData['backdrop_path'] : null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'is_active' => true,
        ];
    }

    /**
     * Sync series genres from TMDB data
     */
    protected function syncSeriesGenres(Series $series, array $tmdbGenres): void
    {
        $genreIds = [];

        foreach ($tmdbGenres as $tmdbGenre) {
            // Find or create genre
            $genre = Genre::firstOrCreate(
                ['tmdb_id' => $tmdbGenre['id']],
                ['name' => $tmdbGenre['name']]
            );

            $genreIds[] = $genre->id;
        }

        // Sync genres to series
        $series->genres()->sync($genreIds);
    }

    /**
     * Get popular series from TMDB
     */
    public function getPopularSeries(int $page = 1): array
    {
        try {
            $results = $this->tmdbService->getPopularTv($page);

            if (!$results['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch popular series',
                    'data' => []
                ];
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('TMDB popular series error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch popular series: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Validate TMDB ID
     */
    public function validateTMDBId(int $tmdbId): bool
    {
        $result = $this->getSeriesDetails($tmdbId);
        return $result['success'];
    }
}
