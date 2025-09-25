<?php

// ========================================
// MOVIE CONTROLLER
// ========================================
// File: app/Http/Controllers/MovieController.php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Genre;
use App\Models\MovieSource;
use App\Models\BrokenLinkReport;
use App\Services\MovieService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::published()->with('genres');
        $result = MovieService::getMovieIndexData($request);
        return view('movies.index', $result);
    }
    
    public function search(Request $request)
    {
        $search = $request->get('search') ?: $request->get('search_alt');
        $result = MovieService::getMovieSearchData($request);
        return view('movies.search', $result);
    }

    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $movies = Movie::published()
            ->where('title', 'like', "%{$query}%")
            ->select('id', 'title', 'year', 'poster_path', 'slug')
            ->limit(5)
            ->get();
        
        $results = $movies->map(function($movie) {
            return [
                'id' => $movie->id,
                'title' => $movie->title,
                'year' => $movie->year,
                'poster' => $movie->poster_url,
                'url' => route('movies.show', $movie->slug)
            ];
        });
        
        return response()->json($results);
    }

    public function popularSearches()
    {
        // You can implement search tracking table for real data
        // For now, return static popular searches
        $searches = [
            'Action Movies',
            'Comedy 2024', 
            'Marvel',
            'Horror',
            'Top Rated'
        ];
        
        return response()->json($searches);
    }
    
    public function show(Movie $movie)
    {
        // Only show published movies to non-admin users
        if (!$movie->isPublished() && (!Auth::check() || !Auth::user()->isAdmin())) {
            abort(404);
        }

        $movie->load('genres');

        // Log movie watching activity if user is authenticated
        if (Auth::check()) {
            app(\App\Services\UserActivityService::class)->logMovieWatch(Auth::user(), $movie);
        }

        // Get related movies with caching (cached for 1 hour)
        $relatedMovies = Cache::remember("movie:related:{$movie->id}", 3600, function() use ($movie) {
            return Movie::published()
                ->where('id', '!=', $movie->id)
                ->whereHas('genres', function ($query) use ($movie) {
                    $query->whereIn('genres.id', $movie->genres->pluck('id'));
                })
                ->with('genres') // Eager load to prevent N+1
                ->take(5)
                ->get();
        });

        return view('movies.show', compact('movie', 'relatedMovies'));
    }
    
    public function genre(Genre $genre)
    {
        $movies = Movie::published()
            ->with('genres')
            ->byGenre($genre->slug)
            ->latest()
            ->paginate(20);
            
        $genres = Genre::orderBy('name')->get();
        
        return view('movies.genre', compact('movies', 'genre', 'genres'));
    }
    
    /**
     * Enhanced play method with multiple sources and quality selection
     */
    public function play(Request $request, Movie $movie)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please login to watch movies.');
        }
        
        // Only show published movies to non-admin
        if (!$movie->isPublished() && !Auth::user()->isAdmin()) {
            abort(404);
        }
        
        // Get all active sources ordered by priority and quality
        $sources = $movie->sources()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderByDesc('quality')
            ->get();
        
        // If no sources, try to use main embed_url
        if ($sources->isEmpty() && $movie->embed_url) {
            // Create temporary source object for backward compatibility
            $tempSource = new MovieSource([
                'movie_id' => $movie->id,
                'source_name' => 'Main Server',
                'embed_url' => $movie->embed_url,
                'quality' => $movie->quality,
                'is_active' => true,
                'priority' => 0
            ]);
            $sources = collect([$tempSource]);
        }
        
        // No sources available at all
        if ($sources->isEmpty()) {
            return redirect()->route('movies.show', $movie->slug)
                ->with('error', 'Sorry, no video sources available for this movie.');
        }
        
        // Get requested source or best available
        $sourceId = $request->get('source');
        $currentSource = null;
        
        if ($sourceId) {
            $currentSource = $sources->firstWhere('id', $sourceId);
        }
        
        // If no specific source requested or not found, get best quality
        if (!$currentSource) {
            // Sort by quality priority (4K > FHD > HD > TS > CAM)
            $qualityOrder = ['4K' => 5, 'FHD' => 4, 'HD' => 3, 'TS' => 2, 'CAM' => 1];
            $currentSource = $sources->sortByDesc(function ($source) use ($qualityOrder) {
                return $qualityOrder[$source->quality] ?? 0;
            })->first();
        }
        
        // Group sources by quality for selector
        $sourcesByQuality = $sources->groupBy('quality');
        
        // Get best available quality
        $bestQuality = $sources->pluck('quality')->unique()->sortByDesc(function ($quality) {
            $order = ['4K' => 5, 'FHD' => 4, 'HD' => 3, 'TS' => 2, 'CAM' => 1];
            return $order[$quality] ?? 0;
        })->first();
        
        // Get related movies
        $relatedMovies = Movie::published()
            ->where('id', '!=', $movie->id)
            ->whereHas('genres', function ($query) use ($movie) {
                $query->whereIn('genres.id', $movie->genres->pluck('id'));
            })
            ->inRandomOrder()
            ->limit(5)
            ->get();
        
        // Log movie view immediately for statistics tracking
        if (Auth::check()) {
            \App\Models\MovieView::logView($movie->id, Auth::id());
            $movie->increment('view_count');
        }
        
        $result = MovieService::getMoviePlayerData($request, $movie);
        return view('movies.player', $result);
    }

    /**
     * Report broken link or issue
     */
    public function reportIssue(Request $request, Movie $movie)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Please login to report issues.'], 401);
        }
        
        $validated = $request->validate([
            'source_id' => 'nullable|exists:movie_sources,id',
            'issue_type' => 'required|in:not_loading,wrong_movie,poor_quality,no_audio,no_subtitle,buffering,other',
            'description' => 'nullable|string|max:500'
        ]);
        
        // Check if user already reported this in last 24 hours
        $existingReport = BrokenLinkReport::where('movie_id', $movie->id)
            ->where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subDay())
            ->first();
        
        if ($existingReport) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reported an issue for this movie recently.'
            ], 429);
        }
        
        // Create report
        $report = BrokenLinkReport::create([
            'movie_id' => $movie->id,
            'movie_source_id' => $validated['source_id'] ?? null,
            'user_id' => Auth::id(),
            'issue_type' => $validated['issue_type'],
            'description' => $validated['description'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'pending'
        ]);
        
        // Increment report count on source if specified
        if ($validated['source_id']) {
            $source = MovieSource::find($validated['source_id']);
            if ($source) {
                $source->increment('report_count');
                
                // Auto-disable if too many reports (10+)
                if ($source->report_count >= 10) {
                    $source->update([
                        'is_active' => false,
                        'notes' => 'Auto-disabled due to multiple reports at ' . now()
                    ]);
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Thank you for reporting! We will check this issue soon.'
        ]);
    }

    /**
     * Track movie view via AJAX (for accurate viewing stats)
     */
    public function trackView(Request $request, Movie $movie)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        // Validate watch duration
        $validated = $request->validate([
            'duration' => 'nullable|integer|min:0|max:86400' // Max 24 hours
        ]);

        // Check if this is a recent duplicate view (within last 5 minutes)
        $recentView = \App\Models\MovieView::where('movie_id', $movie->id)
            ->where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if (!$recentView) {
            // Log new view with duration if provided
            $viewData = [
                'movie_id' => $movie->id,
                'user_id' => Auth::id(),
                'watched_at' => now(),
                'ip_address' => $request->ip()
            ];

            if (isset($validated['duration'])) {
                $viewData['watch_duration'] = $validated['duration'];
            }

            \App\Models\MovieView::create($viewData);

            // Increment movie view count
            $movie->increment('view_count');
        } elseif (isset($validated['duration'])) {
            // Update existing view with duration
            $recentView->update(['watch_duration' => $validated['duration']]);
        }

        return response()->json(['success' => true]);
    }

}