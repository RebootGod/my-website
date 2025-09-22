<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MoviePlayerController extends Controller
{
    /**
     * Show movie player page
     */
    public function play(Movie $movie, Request $request, $source = null)
    {
        // Check if movie is published and active
        if (!$movie->isPublished()) {
            abort(404, 'Movie not found');
        }

        // Track movie view
        if (Auth::check()) {
            $movie->incrementViewCount();
        }

        // Get movie with sources and genres - use defensive loading
        $movie->load(['sources' => function($query) {
            $query->where('is_active', true)->orderByDesc('priority');
        }, 'genres']);

        // Use only sources from movie_sources table (unified system)
        $allSources = collect();
        
        // Add all sources from movie_sources table
        foreach ($movie->sources as $index => $source) {
            $sourceObj = (object) [
                'id' => $source->id,
                'movie_id' => $source->movie_id,
                'embed_url' => $source->embed_url,
                'quality' => $source->quality,
                'priority' => $source->priority,
                'is_active' => $source->is_active,
                'name' => $source->source_name,
                'type' => $source->priority == 100 ? 'primary' : 'additional'
            ];
            $allSources->push($sourceObj);
        }
        
        // Sort by priority (highest first)
        $allSources = $allSources->sortByDesc('priority');

        // Get current source based on route parameter, request parameter, or first available
        $sourceId = $source ?? $request->get('source');
        $currentSource = null;
        
        if ($sourceId) {
            // Find by ID in all sources
            $currentSource = $allSources->where('id', $sourceId)->first();
        }
        
        // Fallback to first available source if specific source not found
        if (!$currentSource) {
            $currentSource = $allSources->first();
        }
        
        // Group sources by quality for UI
        $sourcesByQuality = $allSources->groupBy('quality');
        
        // Get best quality available
        $qualities = ['4K', 'FHD', 'HD', 'CAM'];
        $bestQuality = 'HD';
        foreach ($qualities as $quality) {
            if ($sourcesByQuality->has($quality)) {
                $bestQuality = $quality;
                break;
            }
        }
        
        // Get related movies (same genre, excluding current movie)
        $relatedMovies = collect();
        if ($movie->genres->isNotEmpty()) {
            $genreIds = $movie->genres->pluck('id');
            $relatedMovies = Movie::whereHas('genres', function ($query) use ($genreIds) {
                $query->whereIn('genres.id', $genreIds);
            })
            ->where('id', '!=', $movie->id)
            ->published()
            ->inRandomOrder()
            ->limit(5)
            ->get(['id', 'title', 'slug', 'poster_url', 'year', 'rating']);
        }

        // Ensure all required variables are present
        return view('movies.player', compact(
            'movie', 
            'currentSource', 
            'allSources',
            'sourcesByQuality', 
            'bestQuality',
            'relatedMovies'
        ));
    }

    /**
     * Get movie sources for AJAX requests
     */
    public function getSources(Movie $movie)
    {
        // Check if movie is published and active
        if (!$movie->isPublished()) {
            return response()->json(['error' => 'Movie not found'], 404);
        }

        $sources = $movie->sources()->get(['id', 'quality', 'server_name', 'embed_url']);

        // Safe embed_url handling
        $embedUrl = null;
        if ($movie->embed_url) {
            try {
                $embedUrl = decrypt($movie->embed_url);
            } catch (Exception $e) {
                // If decrypt fails, use raw value or null
                $embedUrl = is_string($movie->embed_url) ? $movie->embed_url : null;
            }
        }

        return response()->json([
            'sources' => $sources,
            'movie' => [
                'id' => $movie->id,
                'title' => $movie->title,
                'embed_url' => $embedUrl,
            ]
        ]);
    }
}