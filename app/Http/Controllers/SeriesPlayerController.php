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
        // TODO: Re-enable authorization once policy issue is resolved
        // $this->authorize('play', $series);

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

        // Track episode view and series statistics
        if (Auth::check()) {
            $series->incrementViewCount();

            // Log episode view for series statistics
            \App\Models\SeriesEpisodeView::logView($episode->id, Auth::id());

            // Also log series watching activity
            app(\App\Services\UserActivityService::class)->logSeriesWatch(Auth::user(), $series, $episode->id);
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

        return view('series.player', compact(
            'series',
            'episode',
            'currentSeason',
            'seasonEpisodes',
            'previousEpisode',
            'nextEpisode'
        ));
    }

    /**
     * Get episode info for AJAX requests
     */
    public function getEpisodeInfo(Series $series, SeriesEpisode $episode)
    {
        // TODO: Re-enable authorization once policy issue is resolved
        // $this->authorize('play', $series);

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

    /**
     * Track episode view via AJAX (for accurate viewing stats)
     */
    public function trackEpisodeView(Request $request, Series $series, SeriesEpisode $episode)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        // Check if episode belongs to series
        if ($episode->series_id !== $series->id) {
            return response()->json(['success' => false, 'message' => 'Episode not found'], 404);
        }

        // Validate watch duration
        $validated = $request->validate([
            'duration' => 'nullable|integer|min:0|max:86400' // Max 24 hours
        ]);

        // Check if this is a recent duplicate view (within last 5 minutes)
        $recentView = \App\Models\SeriesEpisodeView::where('episode_id', $episode->id)
            ->where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if (!$recentView) {
            // Log new episode view
            $viewData = [
                'episode_id' => $episode->id,
                'user_id' => Auth::id(),
                'viewed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ];

            \App\Models\SeriesEpisodeView::create($viewData);

            // Increment series view count
            $series->incrementViewCount();
        }

        return response()->json(['success' => true]);
    }
}
