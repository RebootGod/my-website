<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SeriesController extends Controller
{
    public function show(Series $series)
    {
        // Only show published series
        if (!$series->isPublished()) {
            abort(404);
        }

        // Load relationships with proper ordering
        $series->load([
            'genres',
            'seasons' => function($query) {
                $query->orderBy('season_number');
            },
            'seasons.episodes' => function($query) {
                $query->orderBy('episode_number');
            }
        ]);

        // Log series watching activity if user is authenticated
        if (auth()->check()) {
            app(\App\Services\UserActivityService::class)->logSeriesWatch(auth()->user(), $series);
        }

        // Increment view count
        $series->incrementViewCount();

        return view('series.show', compact('series'));
    }

    public function index(Request $request)
    {
        $query = Series::with(['genres', 'seasons'])
            ->published();

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply genre filter
        if ($request->filled('genre')) {
            $genreId = $request->genre;
            $query->whereHas('genres', function($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        // Apply year filter
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        // Apply rating filter
        if ($request->filled('rating')) {
            $query->where('rating', '>=', $request->rating);
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'rating_high':
                $query->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('rating', 'asc');
                break;
            case 'alphabetical':
                $query->orderBy('title', 'asc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $series = $query->paginate(20)->withQueryString();

        return view('series.index', compact('series'));
    }
}
