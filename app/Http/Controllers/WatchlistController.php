<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Models\Movie;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    /**
     * Display user's watchlist
     */
    public function index()
    {
        $watchlist = Watchlist::where('user_id', Auth::id())
            ->with(['movie.genres', 'series.genres'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('user.watchlist.index', compact('watchlist'));
    }

    /**
     * Add movie to watchlist
     */
    public function add(Movie $movie)
    {
        $this->authorize('create', Watchlist::class);
        try {
            $exists = Watchlist::where('user_id', Auth::id())
                ->where('movie_id', $movie->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movie is already in your watchlist.',
                ]);
            }

            Watchlist::create([
                'user_id' => Auth::id(),
                'movie_id' => $movie->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movie added to watchlist successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add movie to watchlist.',
            ], 500);
        }
    }

    /**
     * Add series to watchlist
     */
    public function addSeries(Series $series)
    {
        try {
            $this->authorize('create', Watchlist::class);
            
            $exists = Watchlist::where('user_id', Auth::id())
                ->where('series_id', $series->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Series is already in your watchlist.',
                ]);
            }

            Watchlist::create([
                'user_id' => Auth::id(),
                'series_id' => $series->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Series added to watchlist successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to add series to watchlist', [
                'user_id' => Auth::id(),
                'series_id' => $series->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add series to watchlist: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove movie from watchlist
     */
    public function remove($movieId)
    {
        // Find the watchlist item first to authorize against it
        $watchlistItem = Watchlist::where('user_id', Auth::id())
            ->where('movie_id', $movieId)
            ->first();

        if ($watchlistItem) {
            $this->authorize('delete', $watchlistItem);
        }
        try {
            $movie = Movie::find($movieId);

            if (!$movie) {
                return back()->with('error', 'Film tidak ditemukan.');
            }

            if (!$watchlistItem) {
                return back()->with('error', 'Film tidak ada di watchlist Anda.');
            }

            $watchlistItem->delete();
            
            return back()->with('success', 'Film berhasil dihapus dari watchlist.');
            
        } catch (\Exception $e) {
            \Log::error("Error removing from watchlist: " . $e->getMessage());
            
            return back()->with('error', 'Gagal menghapus film dari watchlist.');
        }
    }

    /**
     * Remove series from watchlist
     */
    public function removeSeries($seriesId)
    {
        $watchlistItem = Watchlist::where('user_id', Auth::id())
            ->where('series_id', $seriesId)
            ->first();

        if ($watchlistItem) {
            $this->authorize('delete', $watchlistItem);
        }
        
        try {
            $series = Series::find($seriesId);

            if (!$series) {
                return back()->with('error', 'Series not found.');
            }

            if (!$watchlistItem) {
                return back()->with('error', 'Series is not in your watchlist.');
            }

            $watchlistItem->delete();
            
            return back()->with('success', 'Series removed from watchlist successfully.');
            
        } catch (\Exception $e) {
            \Log::error("Error removing series from watchlist: " . $e->getMessage());
            
            return back()->with('error', 'Failed to remove series from watchlist.');
        }
    }

    /**
     * Check if movie is in user's watchlist
     */
    public function check(Movie $movie)
    {
        $inWatchlist = Watchlist::where('user_id', Auth::id())
            ->where('movie_id', $movie->id)
            ->exists();

        return response()->json([
            'in_watchlist' => $inWatchlist,
        ]);
    }

    /**
     * Check if series is in user's watchlist
     */
    public function checkSeries(Series $series)
    {
        $inWatchlist = Watchlist::where('user_id', Auth::id())
            ->where('series_id', $series->id)
            ->exists();

        return response()->json([
            'in_watchlist' => $inWatchlist,
        ]);
    }
}