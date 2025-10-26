<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Series;
use App\Models\SeriesSeason;
use App\Models\SeriesEpisode;
use Illuminate\Support\Str;

/**
 * Service: Content Upload Helper
 * 
 * Helper methods for content upload operations
 * Handle duplicate checks and data preparation
 * 
 * Security: SQL injection protected via Eloquent
 * 
 * @package App\Services
 */
class ContentUploadService
{
    /**
     * Check if movie exists by TMDB ID
     *
     * @param int $tmdbId
     * @return array ['exists' => bool, 'movie' => Movie|null]
     */
    public function checkMovieExists(int $tmdbId): array
    {
        $movie = Movie::where('tmdb_id', $tmdbId)->first();

        if ($movie) {
            return [
                'exists' => true,
                'movie' => $movie,
                'details' => [
                    'id' => $movie->id,
                    'title' => $movie->title,
                    'year' => $movie->year,
                    'status' => $movie->status,
                    'url' => route('movies.show', $movie->slug)
                ]
            ];
        }

        return [
            'exists' => false,
            'movie' => null
        ];
    }

    /**
     * Check if series exists by TMDB ID
     *
     * @param int $tmdbId
     * @return array ['exists' => bool, 'series' => Series|null]
     */
    public function checkSeriesExists(int $tmdbId): array
    {
        $series = Series::where('tmdb_id', $tmdbId)->first();

        if ($series) {
            return [
                'exists' => true,
                'series' => $series,
                'details' => [
                    'id' => $series->id,
                    'title' => $series->title,
                    'year' => $series->year,
                    'status' => $series->status,
                    'seasons_count' => $series->seasons()->count(),
                    'url' => route('series.show', $series->slug)
                ]
            ];
        }

        return [
            'exists' => false,
            'series' => null
        ];
    }

    /**
     * Check if season exists
     *
     * @param int $seriesId
     * @param int $seasonNumber
     * @return array
     */
    public function checkSeasonExists(int $seriesId, int $seasonNumber): array
    {
        $season = SeriesSeason::where('series_id', $seriesId)
            ->where('season_number', $seasonNumber)
            ->first();

        if ($season) {
            return [
                'exists' => true,
                'season' => $season,
                'details' => [
                    'id' => $season->id,
                    'season_number' => $season->season_number,
                    'name' => $season->name,
                    'episode_count' => $season->episodes()->count()
                ]
            ];
        }

        return [
            'exists' => false,
            'season' => null
        ];
    }

    /**
     * Check if episode exists
     *
     * @param int $seasonId
     * @param int $episodeNumber
     * @param bool $requireUrls Check if episode has embed_url (default: false)
     * @return array
     */
    public function checkEpisodeExists(int $seasonId, int $episodeNumber, bool $requireUrls = false): array
    {
        $episode = SeriesEpisode::where('season_id', $seasonId)
            ->where('episode_number', $episodeNumber)
            ->first();

        if ($episode) {
            $hasUrls = !empty($episode->embed_url);
            
            // If URLs required and episode has no URLs, treat as needs update
            if ($requireUrls && !$hasUrls) {
                return [
                    'exists' => false,
                    'episode' => $episode,
                    'needs_update' => true,
                    'details' => [
                        'id' => $episode->id,
                        'episode_number' => $episode->episode_number,
                        'name' => $episode->name,
                        'has_embed' => false,
                        'has_download' => !empty($episode->download_url)
                    ]
                ];
            }
            
            return [
                'exists' => true,
                'episode' => $episode,
                'needs_update' => false,
                'details' => [
                    'id' => $episode->id,
                    'episode_number' => $episode->episode_number,
                    'name' => $episode->name,
                    'has_embed' => $hasUrls,
                    'has_download' => !empty($episode->download_url)
                ]
            ];
        }

        return [
            'exists' => false,
            'episode' => null,
            'needs_update' => false
        ];
    }

    /**
     * Update episode URLs (for existing episodes without URLs)
     *
     * @param int $episodeId
     * @param string $embedUrl
     * @param string|null $downloadUrl
     * @return array
     */
    public function updateEpisodeUrls(
        int $episodeId,
        string $embedUrl,
        ?string $downloadUrl = null
    ): array {
        $episode = SeriesEpisode::findOrFail($episodeId);
        
        $episode->update([
            'embed_url' => $embedUrl,
            'download_url' => $downloadUrl,
            'status' => 'published',
            'is_active' => true
        ]);
        
        return [
            'success' => true,
            'episode' => $episode
        ];
    }

    /**
     * Generate unique slug
     *
     * @param string $title
     * @param int|null $year
     * @param string $model Movie or Series class
     * @return string
     */
    public function generateSlug(string $title, ?int $year, string $model): string
    {
        // Validate title is not empty
        $title = trim($title);
        if (empty($title)) {
            // Fallback: use model name + year + random string
            $baseSlug = strtolower(class_basename($model)) . '-' . ($year ?? 'unknown') . '-' . Str::random(6);
        } else {
            $baseSlug = Str::slug($title);
            
            // Additional check: if slug is still empty after Str::slug (edge case)
            if (empty($baseSlug)) {
                $baseSlug = strtolower(class_basename($model)) . '-' . ($year ?? 'unknown') . '-' . Str::random(6);
            } else if ($year) {
                $baseSlug .= '-' . $year;
            }
        }

        $slug = $baseSlug;
        $counter = 1;

        // Check uniqueness
        while ($model::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Prepare movie data from TMDB response
     *
     * @param array $tmdbData
     * @param string $embedUrl
     * @param string|null $downloadUrl
     * @return array
     */
    public function prepareMovieData(array $tmdbData, string $embedUrl, ?string $downloadUrl, string $status = 'published'): array
    {
        $year = null;
        if (!empty($tmdbData['release_date'])) {
            $year = (int) substr($tmdbData['release_date'], 0, 4);
        }

        return [
            'tmdb_id' => $tmdbData['id'],
            'imdb_id' => $tmdbData['imdb_id'] ?? null,
            'title' => $tmdbData['title'],
            'slug' => $this->generateSlug($tmdbData['title'], $year, Movie::class),
            'description' => $tmdbData['overview'] ?? null,
            'embed_url' => $embedUrl,
            'download_url' => $downloadUrl,
            'poster_path' => $tmdbData['poster_path'] ?? null,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? null,
            'year' => $year,
            'duration' => $tmdbData['runtime'] ?? null,
            'rating' => $tmdbData['vote_average'] ?? 0,
            'quality' => 'HD',
            'status' => $status,  // Use parameter instead of hardcoded 'draft'
            'view_count' => 0,
        ];
    }

    /**
     * Prepare series data from TMDB response
     *
     * @param array $tmdbData
     * @return array
     */
    public function prepareSeriesData(array $tmdbData, string $status = 'published'): array
    {
        $year = null;
        if (!empty($tmdbData['first_air_date'])) {
            $year = (int) substr($tmdbData['first_air_date'], 0, 4);
        }

        return [
            'tmdb_id' => $tmdbData['id'],
            'title' => $tmdbData['name'],
            'slug' => $this->generateSlug($tmdbData['name'], $year, Series::class),
            'description' => $tmdbData['overview'] ?? null,
            'poster_url' => $tmdbData['poster_path'] 
                ? 'https://image.tmdb.org/t/p/w500' . $tmdbData['poster_path'] 
                : null,
            'backdrop_url' => $tmdbData['backdrop_path']
                ? 'https://image.tmdb.org/t/p/original' . $tmdbData['backdrop_path']
                : null,
            'year' => $year,
            'rating' => $tmdbData['vote_average'] ?? 0,
            'status' => $status,  // Use parameter instead of hardcoded 'draft'
        ];
    }

    /**
     * Prepare season data from TMDB response
     *
     * @param array $tmdbData
     * @param int $seriesId
     * @return array
     */
    public function prepareSeasonData(array $tmdbData, int $seriesId): array
    {
        return [
            'series_id' => $seriesId,
            'tmdb_id' => $tmdbData['id'] ?? null,
            'season_number' => $tmdbData['season_number'],
            'name' => $tmdbData['name'] ?? "Season {$tmdbData['season_number']}",
            'overview' => $tmdbData['overview'] ?? null,
            'poster_path' => $tmdbData['poster_path'] ?? null,
            'air_date' => $tmdbData['air_date'] ?? null,
            'episode_count' => $tmdbData['episode_count'] ?? 0,
            'is_active' => true,
        ];
    }

    /**
     * Prepare episode data from TMDB response
     *
     * @param array $tmdbData
     * @param int $seriesId
     * @param int $seasonId
     * @param string $embedUrl
     * @param string|null $downloadUrl
     * @return array
     */
    public function prepareEpisodeData(
        array $tmdbData, 
        int $seriesId, 
        int $seasonId, 
        string $embedUrl, 
        ?string $downloadUrl
    ): array {
        return [
            'series_id' => $seriesId,
            'season_id' => $seasonId,
            'tmdb_id' => $tmdbData['id'] ?? null,
            'episode_number' => $tmdbData['episode_number'],
            'name' => $tmdbData['name'] ?? "Episode {$tmdbData['episode_number']}",
            'overview' => $tmdbData['overview'] ?? null,
            'still_path' => $tmdbData['still_path'] ?? null,
            'air_date' => $tmdbData['air_date'] ?? null,
            'runtime' => $tmdbData['runtime'] ?? null,
            'vote_average' => $tmdbData['vote_average'] ?? 0,
            'vote_count' => $tmdbData['vote_count'] ?? 0,
            'embed_url' => $embedUrl,
            'download_url' => $downloadUrl,
            'is_active' => true,
        ];
    }
}
