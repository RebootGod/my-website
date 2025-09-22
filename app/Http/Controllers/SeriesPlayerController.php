<?php

namespace App\Http\Controllers;

use App\Models\Series;
use App\Models\SeriesEpisode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeriesPlayerController extends Controller
{
    /**
     * Show episode player page
     */
    public function playEpisode(Series $series, SeriesEpisode $episode, Request $request)
    {
        // Check if series is published and active
        if (!$series->isPublished()) {
            abort(404, 'Series not found');
        }

        // Check if episode belongs to the series and is active
        if ($episode->series_id !== $series->id || !$episode->is_active) {
            abort(404, 'Episode not found');
        }

        // Check if episode has embed URL
        if (!$episode->embed_url) {
            abort(404, 'Episode not available');
        }

        // Track episode view
        if (Auth::check()) {
            $series->incrementViewCount();
            // You can also track episode-specific views if needed
        }

        // Load relationships
        $series->load(['genres', 'seasons.episodes' => function($query) {
            $query->where('is_active', true)->orderBy('season_id')->orderBy('episode_number');
        }]);

        $episode->load('season');

        // Get current season
        $currentSeason = $episode->season;

        // Get all episodes in current season for navigation
        $seasonEpisodes = $currentSeason->episodes()
            ->where('is_active', true)
            ->orderBy('episode_number')
            ->get();

        // Get next and previous episodes in the season
        $currentIndex = $seasonEpisodes->search(function($ep) use ($episode) {
            return $ep->id === $episode->id;
        });

        $previousEpisode = $currentIndex > 0 ? $seasonEpisodes[$currentIndex - 1] : null;
        $nextEpisode = $currentIndex < $seasonEpisodes->count() - 1 ? $seasonEpisodes[$currentIndex + 1] : null;

        // If no next episode in current season, check next season
        if (!$nextEpisode) {
            $nextSeason = $series->seasons()
                ->where('season_number', '>', $currentSeason->season_number)
                ->orderBy('season_number')
                ->first();

            if ($nextSeason) {
                $nextEpisode = $nextSeason->episodes()
                    ->where('is_active', true)
                    ->orderBy('episode_number')
                    ->first();
            }
        }

        // Get related series (same genre, excluding current series)
        $relatedSeries = collect();
        if ($series->genres->isNotEmpty()) {
            $genreIds = $series->genres->pluck('id');
            $relatedSeries = Series::whereHas('genres', function ($query) use ($genreIds) {
                $query->whereIn('genres.id', $genreIds);
            })
            ->where('id', '!=', $series->id)
            ->published()
            ->inRandomOrder()
            ->limit(5)
            ->get(['id', 'title', 'poster_path', 'poster_url', 'year', 'rating']);
        }

        return view('series.player', compact(
            'series',
            'episode',
            'currentSeason',
            'seasonEpisodes',
            'previousEpisode',
            'nextEpisode',
            'relatedSeries'
        ));
    }

    /**
     * Get episode info for AJAX requests
     */
    public function getEpisodeInfo(Series $series, SeriesEpisode $episode)
    {
        // Check if series is published and episode belongs to series
        if (!$series->isPublished() || $episode->series_id !== $series->id) {
            return response()->json(['error' => 'Episode not found'], 404);
        }

        $episode->load('season');

        return response()->json([
            'episode' => [
                'id' => $episode->id,
                'name' => $episode->name,
                'episode_number' => $episode->episode_number,
                'overview' => $episode->overview,
                'runtime' => $episode->runtime,
                'formatted_runtime' => $episode->getFormattedRuntime(),
                'embed_url' => $episode->embed_url,
                'season' => [
                    'id' => $episode->season->id,
                    'season_number' => $episode->season->season_number,
                    'name' => $episode->season->name
                ]
            ],
            'series' => [
                'id' => $series->id,
                'title' => $series->title,
            ]
        ]);
    }
}
