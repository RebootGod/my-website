<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Series;
use App\Models\User;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GlobalSearchController - Admin panel global search
 * 
 * Search across:
 * - Movies (title, description)
 * - Series (title, description)
 * - Users (name, email)
 * - Episodes (title)
 * 
 * Security: SQL Injection prevention via query builder
 * OWASP: Input validation, output escaping
 */
class GlobalSearchController extends Controller
{
    /**
     * Maximum results per category
     */
    const MAX_RESULTS_PER_CATEGORY = 5;

    /**
     * Minimum search query length
     */
    const MIN_QUERY_LENGTH = 2;

    /**
     * Global search endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');
            
            // Validate query length
            if (strlen($query) < self::MIN_QUERY_LENGTH) {
                return response()->json([
                    'results' => [],
                    'message' => 'Query too short'
                ]);
            }

            // Sanitize query (prevent SQL injection)
            $query = trim($query);
            $searchTerm = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';

            $results = [];

            // Search Movies
            try {
                $movies = $this->searchMovies($searchTerm);
                $results = array_merge($results, $movies);
            } catch (\Exception $e) {
                \Log::error('Movie search error: ' . $e->getMessage());
            }

            // Search Series
            try {
                $series = $this->searchSeries($searchTerm);
                $results = array_merge($results, $series);
            } catch (\Exception $e) {
                \Log::error('Series search error: ' . $e->getMessage());
            }

            // Search Users (super admin only for privacy)
            if (auth()->check() && auth()->user()->isSuperAdmin()) {
                try {
                    $users = $this->searchUsers($searchTerm);
                    $results = array_merge($results, $users);
                } catch (\Exception $e) {
                    \Log::error('User search error: ' . $e->getMessage());
                }
            }

            // Note: Episodes search disabled (model doesn't exist yet)
            // TODO: Create Episode model and re-enable

            return response()->json([
                'results' => $results,
                'total' => count($results),
                'query' => $query
            ]);
        } catch (\Exception $e) {
            \Log::error('Global search error: ' . $e->getMessage());
            return response()->json([
                'results' => [],
                'error' => 'Search failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Search movies
     * 
     * @param string $searchTerm
     * @return array
     */
    protected function searchMovies($searchTerm)
    {
        $movies = Movie::where('title', 'like', $searchTerm)
            ->orWhere('description', 'like', $searchTerm)
            ->limit(self::MAX_RESULTS_PER_CATEGORY)
            ->get(['id', 'title', 'release_date', 'poster_url']);

        return $movies->map(function ($movie) {
            $year = 'N/A';
            if ($movie->release_date) {
                try {
                    $year = $movie->release_date->format('Y');
                } catch (\Exception $e) {
                    $year = 'N/A';
                }
            }

            return [
                'type' => 'movie',
                'title' => $movie->title,
                'subtitle' => 'Movie • ' . $year,
                'url' => route('admin.movies.edit', $movie->id),
                'icon' => 'fas fa-film',
                'meta' => [
                    'id' => $movie->id,
                    'poster' => $movie->poster_url
                ]
            ];
        })->toArray();
    }

    /**
     * Search series
     * 
     * @param string $searchTerm
     * @return array
     */
    protected function searchSeries($searchTerm)
    {
        $series = Series::where('title', 'like', $searchTerm)
            ->orWhere('description', 'like', $searchTerm)
            ->limit(self::MAX_RESULTS_PER_CATEGORY)
            ->get(['id', 'title', 'release_date', 'poster_url']);

        return $series->map(function ($series) {
            $year = 'N/A';
            if ($series->release_date) {
                try {
                    $year = $series->release_date->format('Y');
                } catch (\Exception $e) {
                    $year = 'N/A';
                }
            }

            return [
                'type' => 'series',
                'title' => $series->title,
                'subtitle' => 'Series • ' . $year,
                'url' => route('admin.series.edit', $series->id),
                'icon' => 'fas fa-tv',
                'meta' => [
                    'id' => $series->id,
                    'poster' => $series->poster_url
                ]
            ];
        })->toArray();
    }

    /**
     * Search users
     * 
     * @param string $searchTerm
     * @return array
     */
    protected function searchUsers($searchTerm)
    {
        $users = User::where('name', 'like', $searchTerm)
            ->orWhere('email', 'like', $searchTerm)
            ->limit(self::MAX_RESULTS_PER_CATEGORY)
            ->get(['id', 'name', 'email', 'created_at']);

        return $users->map(function ($user) {
            return [
                'type' => 'user',
                'title' => $user->name,
                'subtitle' => 'User • ' . $user->email,
                'url' => route('admin.users.edit', $user->id),
                'icon' => 'fas fa-user',
                'meta' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'joined' => $user->created_at->diffForHumans()
                ]
            ];
        })->toArray();
    }

    /**
     * Search episodes
     * 
     * @param string $searchTerm
     * @return array
     */
    protected function searchEpisodes($searchTerm)
    {
        $episodes = Episode::where('title', 'like', $searchTerm)
            ->with(['season.series:id,title'])
            ->limit(self::MAX_RESULTS_PER_CATEGORY)
            ->get(['id', 'title', 'episode_number', 'season_id']);

        return $episodes->map(function ($episode) {
            $seriesTitle = $episode->season->series->title ?? 'Unknown Series';
            
            return [
                'type' => 'episode',
                'title' => $episode->title,
                'subtitle' => "Episode {$episode->episode_number} • {$seriesTitle}",
                'url' => route('admin.episodes.edit', $episode->id),
                'icon' => 'fas fa-play-circle',
                'meta' => [
                    'id' => $episode->id,
                    'episode_number' => $episode->episode_number,
                    'series' => $seriesTitle
                ]
            ];
        })->toArray();
    }

    /**
     * Get search suggestions (autocomplete)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $searchTerm = trim($query) . '%';

        // Get top 10 suggestions
        $suggestions = collect();

        // Movie titles
        $movieTitles = Movie::where('title', 'like', $searchTerm)
            ->limit(5)
            ->pluck('title');
        $suggestions = $suggestions->merge($movieTitles);

        // Series titles
        $seriesTitles = Series::where('title', 'like', $searchTerm)
            ->limit(5)
            ->pluck('title');
        $suggestions = $suggestions->merge($seriesTitles);

        return response()->json([
            'suggestions' => $suggestions->unique()->take(10)->values()
        ]);
    }
}
