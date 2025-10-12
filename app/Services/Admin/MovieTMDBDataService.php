<?php

namespace App\Services\Admin;

use App\Models\Movie;
use App\Models\Genre;
use Illuminate\Support\Str;

/**
 * Service: Movie TMDB Data Preparation
 * 
 * Handles data transformation and genre syncing for TMDB movie imports
 * Extracted from MovieTMDBService to comply with 300-line limit
 */
class MovieTMDBDataService
{
    /**
     * Prepare TMDB movie data for database insertion
     */
    public function prepareTMDBMovieData(array $tmdbData): array
    {
        return [
            'tmdb_id' => $tmdbData['tmdb_id'],
            'title' => $tmdbData['title'],
            'slug' => Str::slug($tmdbData['title']),
            'overview' => $tmdbData['description'] ?? '',
            'description' => $tmdbData['description'] ?? '',
            'release_date' => $tmdbData['release_date'] ?
                \Carbon\Carbon::parse($tmdbData['release_date']) : null,
            'year' => $tmdbData['year'] ?? null,
            'runtime' => $tmdbData['duration'] ?? null,
            'poster_path' => $tmdbData['poster_path'] ?? null,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? null,
            'rating' => $tmdbData['rating'] ?? 0,
            'vote_count' => $tmdbData['vote_count'] ?? 0,
            'popularity' => $tmdbData['popularity'] ?? 0,
            'language' => $tmdbData['original_language'] ?? 'en',
            'original_title' => $tmdbData['original_title'] ?? $tmdbData['title'],
            'status' => 'published',
            'is_featured' => false,
            'added_by' => auth()->id(),
        ];
    }

    /**
     * Sync movie genres from TMDB data
     */
    public function syncMovieGenres(Movie $movie, array $tmdbGenres): void
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

        // Sync genres to movie
        $movie->genres()->sync($genreIds);
    }

    /**
     * Dispatch image download jobs for movie poster and backdrop
     */
    public function dispatchImageDownloads(Movie $movie, array $movieData): void
    {
        // Dispatch image download jobs for poster and backdrop
        if (!empty($movieData['poster_path'])) {
            \App\Jobs\DownloadTmdbImageJob::dispatch(
                'movie',
                $movie->id,
                'poster',
                $movieData['poster_path']
            );
        }

        if (!empty($movieData['backdrop_path'])) {
            \App\Jobs\DownloadTmdbImageJob::dispatch(
                'movie',
                $movie->id,
                'backdrop',
                $movieData['backdrop_path']
            );
        }
    }
}
