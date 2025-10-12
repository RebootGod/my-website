<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMovieRequest;
use App\Http\Requests\Admin\UpdateMovieRequest;
use App\Http\Requests\Admin\StoreMovieSourceRequest;
use App\Http\Requests\Admin\UpdateMovieSourceRequest;
use App\Http\Requests\Admin\TMDBImportRequest;
use App\Http\Requests\Admin\TMDBBulkImportRequest;
use App\Models\Movie;
use App\Models\MovieSource;
use App\Models\Genre;
use App\Models\BrokenLinkReport;
use App\Models\User;
use App\Models\MovieView;
use App\Notifications\NewMovieAddedNotification;
use App\Services\Admin\MovieTMDBService;
use App\Services\Admin\MovieSourceService;
use App\Services\Admin\MovieFileService;
use App\Services\Admin\MovieReportService;
use App\Traits\HasAdminFiltering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AdminMovieController extends Controller
{
    use HasAdminFiltering;

    protected $tmdbService;
    protected $sourceService;
    protected $fileService;
    protected $reportService;

    public function __construct(
        MovieTMDBService $tmdbService,
        MovieSourceService $sourceService,
        MovieFileService $fileService,
        MovieReportService $reportService
    ) {
        $this->tmdbService = $tmdbService;
        $this->sourceService = $sourceService;
        $this->fileService = $fileService;
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        // Optimized query with eager loading
        $query = Movie::select([
            'id', 'title', 'year', 'quality', 'status',
            'poster_path', 'poster_url', 'local_poster_path', 'view_count', 'rating', 'created_at', 'updated_at', 'description', 'tmdb_id'
        ])->with([
            'genres:id,name',
            'sources:id,movie_id,source_name,is_active'
        ])->withCount(['views', 'sources']);

        // Apply filters using trait
        $query = $this->applySearch($query, $request->search, ['title', 'description']);
        $query = $this->applyStatusFilter($query, $request->status);
        $query = $this->applyDateFilter(
            $query,
            $request->date_from,
            $request->date_to
        );
        
        // Ensure genre_ids is array or null
        $genreIds = $request->genre_ids;
        if ($genreIds && !is_array($genreIds)) {
            $genreIds = [$genreIds];
        }
        $query = $this->applyGenreFilter($query, $genreIds);
        
        $query = $this->applyNumericRangeFilter(
            $query,
            $request->views_from,
            $request->views_to,
            'view_count'
        );

        // Advanced filters - Year Range
        if ($request->filled('year_from')) {
            $query->where('year', '>=', $request->year_from);
        }
        if ($request->filled('year_to')) {
            $query->where('year', '<=', $request->year_to);
        }

        // Advanced filters - Rating Range
        if ($request->filled('rating_from')) {
            $query->where('rating', '>=', $request->rating_from);
        }
        if ($request->filled('rating_to')) {
            $query->where('rating', '<=', $request->rating_to);
        }

        // Advanced filters - Quality
        if ($request->filled('quality')) {
            $query->where('quality', $request->quality);
        }

        // Advanced filters - TMDB Status
        if ($request->has('has_tmdb')) {
            if ($request->has_tmdb == '1') {
                $query->whereNotNull('tmdb_id');
            } elseif ($request->has_tmdb === '0') {
                $query->whereNull('tmdb_id');
            }
        }

        // Apply sorting
        $allowedSorts = ['created_at', 'title', 'year', 'view_count', 'rating', 'updated_at'];
        $query = $this->applySorting(
            $query,
            $request->sort_by ?? 'created_at',
            $request->sort_order ?? 'desc',
            $allowedSorts
        );

        // If count_only requested (AJAX), return count
        if ($request->has('count_only')) {
            return response()->json(['count' => $query->count()]);
        }

        // Get paginated results with optimized query
        $movies = $this->getPaginatedResults($query, 20);

        // Cache genres list to avoid repeated queries
        $genres = Cache::remember('admin:genres_list', 3600, function () {
            return Genre::select(['id', 'name'])->orderBy('name')->get();
        });

        // Build filter summary for display
        $filterSummary = $this->buildFilterSummary($request->only([
            'search', 'status', 'date_from', 'date_to', 'genre_ids', 'year_from', 'year_to', 'rating_from', 'rating_to', 'quality', 'has_tmdb'
        ]));

        return view('admin.movies.index', compact('movies', 'genres', 'filterSummary'));
    }

    public function create()
    {
        $this->authorize('create', Movie::class);

        $genres = Genre::orderBy('name')->get();
        return view('admin.movies.create', compact('genres'));
    }

    public function store(StoreMovieRequest $request)
    {
        $this->authorize('create', Movie::class);

        try {
            $data = $request->validated();
            
            if ($request->hasFile('poster')) {
                $posterResult = $this->fileService->uploadPoster($request->file('poster'));
                if ($posterResult['success']) {
                    $data['poster_path'] = $posterResult['path'];
                } else {
                    return back()->withInput()->withErrors(['poster' => $posterResult['message']]);
                }
            }

            $data['added_by'] = auth()->id();

            $movie = Movie::create($data);

            if (!empty($data['genre_ids'])) {
                $movie->genres()->sync($data['genre_ids']);
            }

            Log::info('Movie created successfully', [
                'movie_id' => $movie->id,
                'title' => $movie->title,
                'admin_id' => auth()->id()
            ]);

            // Dispatch notification to interested users
            try {
                $this->notifyInterestedUsers($movie);
            } catch (\Exception $e) {
                Log::warning('Failed to dispatch movie notifications', [
                    'movie_id' => $movie->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()
                ->route('admin.movies.index')
                ->with('success', 'Movie created successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to create movie', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create movie: ' . $e->getMessage());
        }
    }

    public function show(Movie $movie)
    {
        $movie->load(['genres', 'sources', 'views']);
        $stats = $this->sourceService->getSourceStats($movie);
        
        return view('admin.movies.show', compact('movie', 'stats'));
    }

    public function edit(Movie $movie)
    {
        $this->authorize('update', $movie);

        $movie->load('genres');
        $genres = Genre::orderBy('name')->get();

        return view('admin.movies.edit', compact('movie', 'genres'));
    }

    public function update(UpdateMovieRequest $request, Movie $movie)
    {
        $this->authorize('update', $movie);

        try {
            $data = $request->validated();
            
            if ($request->hasFile('poster')) {
                $posterResult = $this->fileService->uploadPoster(
                    $request->file('poster'),
                    $movie->poster_path
                );
                
                if ($posterResult['success']) {
                    $data['poster_path'] = $posterResult['path'];
                } else {
                    return back()->withInput()->withErrors(['poster' => $posterResult['message']]);
                }
            }

            $movie->update($data);

            if (isset($data['genre_ids'])) {
                $movie->genres()->sync($data['genre_ids']);
            }

            Log::info('Movie updated successfully', [
                'movie_id' => $movie->id,
                'title' => $movie->title,
                'admin_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.movies.index')
                ->with('success', 'Movie updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update movie', [
                'movie_id' => $movie->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update movie: ' . $e->getMessage());
        }
    }

    public function destroy(Movie $movie)
    {
        $this->authorize('delete', $movie);

        try {
            $movieTitle = $movie->title;
            
            if ($movie->poster_path) {
                $this->fileService->deletePoster($movie->poster_path);
            }

            $movie->delete();

            Log::info('Movie deleted successfully', [
                'movie_id' => $movie->id,
                'title' => $movieTitle,
                'admin_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.movies.index')
                ->with('success', 'Movie deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to delete movie', [
                'movie_id' => $movie->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return back()->with('error', 'Failed to delete movie: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Movie $movie)
    {
        $this->authorize('update', $movie);

        try {
            $newStatus = $movie->status === 'published' ? 'draft' : 'published';
            
            $movie->update([
                'status' => $newStatus
            ]);

            Log::info('Movie status toggled', [
                'movie_id' => $movie->id,
                'old_status' => $movie->getOriginal('status'),
                'new_status' => $newStatus,
                'admin_id' => auth()->id()
            ]);

            return back()->with('success', 'Movie status updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to toggle movie status', [
                'movie_id' => $movie->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    public function tmdbSearch(Request $request)
    {
        $this->authorize('create', Movie::class);

        $request->validate([
            'query' => 'required|string|min:2',
            'page' => 'nullable|integer|min:1'
        ]);

        $result = $this->tmdbService->searchMovies(
            $request->query,
            $request->get('page', 1)
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return view('admin.movies.tmdb-search', [
            'searchQuery' => $request->query,
            'searchResults' => $result['data'] ?? [],
            'success' => $result['success'],
            'message' => $result['message'] ?? null
        ]);
    }

    public function tmdbDetails(Request $request)
    {
        $this->authorize('create', Movie::class);

        $request->validate([
            'tmdb_id' => 'required|integer'
        ]);

        $result = $this->tmdbService->getMovieDetails($request->tmdb_id);
        return response()->json($result);
    }

    public function tmdbImport(TMDBImportRequest $request)
    {
        $this->authorize('create', Movie::class);

        try {
            $result = $this->tmdbService->importMovie($request->tmdb_id);
            
            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            if ($request->download_poster && $result['data']->poster_path) {
                $posterResult = $this->fileService->downloadPosterFromUrl(
                    '', 
                    $result['data']->poster_path
                );
                
                if ($posterResult['success']) {
                    $result['data']->update(['poster_path' => $posterResult['path']]);
                }
            }

            return redirect()
                ->route('admin.movies.show', $result['data'])
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('TMDB import failed', [
                'tmdb_id' => $request->tmdb_id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function tmdbBulkImport(TMDBBulkImportRequest $request)
    {
        $this->authorize('create', Movie::class);

        try {
            $result = $this->tmdbService->bulkImportMovies($request->tmdb_ids);
            
            $summary = $result['data']['summary'];
            $message = sprintf(
                'Bulk import completed. Imported: %d, Skipped: %d, Failed: %d',
                $summary['imported_count'],
                $summary['skipped_count'],
                $summary['failed_count']
            );

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Bulk TMDB import failed', [
                'tmdb_ids' => $request->tmdb_ids,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Bulk import failed: ' . $e->getMessage());
        }
    }

    public function sources(Movie $movie)
    {
        $this->authorize('update', $movie);

        $sources = $this->sourceService->getMovieSources($movie);
        $qualityOptions = $this->sourceService->getQualityOptions();
        
        return view('admin.movies.sources', compact('movie', 'sources', 'qualityOptions'));
    }

    public function storeSource(StoreMovieSourceRequest $request, Movie $movie)
    {
        $this->authorize('update', $movie);

        try {
            $this->sourceService->createSource($movie, $request->validated());
            return back()->with('success', 'Source added successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to add source: ' . $e->getMessage());
        }
    }

    public function updateSource(UpdateMovieSourceRequest $request, Movie $movie, MovieSource $source)
    {
        $this->authorize('update', $movie);

        try {
            $this->sourceService->updateSource($source, $request->validated());
            return back()->with('success', 'Source updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update source: ' . $e->getMessage());
        }
    }

    public function toggleSource(Movie $movie, MovieSource $source)
    {
        $this->authorize('update', $movie);

        try {
            $this->sourceService->toggleSourceStatus($source);
            return back()->with('success', 'Source status updated!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update source: ' . $e->getMessage());
        }
    }

    public function destroySource(Movie $movie, MovieSource $source)
    {
        $this->authorize('update', $movie);

        try {
            $this->sourceService->deleteSource($source);
            return back()->with('success', 'Source deleted!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete source: ' . $e->getMessage());
        }
    }

    public function reports(Request $request)
    {
        $filters = $request->only(['status', 'movie_id', 'date_from', 'date_to', 'search']);
        $reports = $this->reportService->getReports($filters);
        $stats = $this->reportService->getReportStats();
        $statusOptions = $this->reportService->getStatusOptions();
        
        return view('admin.reports.index', compact('reports', 'stats', 'statusOptions'));
    }

    public function updateReport(Request $request, BrokenLinkReport $report)
    {
        // Only admin can manage reports
        $this->authorize('create', Movie::class);

        $request->validate([
            'status' => 'required|in:pending,reviewing,fixed,dismissed',
            'admin_note' => 'nullable|string|max:500'
        ]);

        try {
            $this->reportService->updateReportStatus(
                $report,
                $request->status,
                $request->admin_note
            );
            
            return back()->with('success', 'Report status updated!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update report: ' . $e->getMessage());
        }
    }

    public function resetReports(Movie $movie, MovieSource $source)
    {
        $this->authorize('update', $movie);

        try{
            $result = $this->reportService->resetSourceReports($source);
            
            return back()->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reset reports: ' . $e->getMessage());
        }
    }

    public function migrateSource(Movie $movie)
    {
        $this->authorize('update', $movie);

        try {
            $result = $this->sourceService->migrateMainEmbedToSource($movie);
            
            return back()->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Notify interested users about new movie.
     * Users are considered interested if they've watched movies with matching genres.
     */
    private function notifyInterestedUsers(Movie $movie): void
    {
        // Load movie genres
        $movie->load('genres');
        $movieGenreIds = $movie->genres->pluck('id')->toArray();
        
        if (empty($movieGenreIds)) {
            Log::info('NewMovieAddedNotification: No genres for movie, skipping notifications', [
                'movie_id' => $movie->id,
            ]);
            return;
        }

        // Get genre names for notification
        $genreNames = $movie->genres->pluck('name')->toArray();

        // Find users who have watched movies with matching genres
        $interestedUserIds = MovieView::whereHas('movie.genres', function ($query) use ($movieGenreIds) {
            $query->whereIn('genres.id', $movieGenreIds);
        })
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        if (empty($interestedUserIds)) {
            Log::info('NewMovieAddedNotification: No interested users found', [
                'movie_id' => $movie->id,
                'genres' => $genreNames,
            ]);
            return;
        }

        // Get active users
        $users = User::whereIn('id', $interestedUserIds)
            ->where('status', 'active')
            ->get();

        $notifiedCount = 0;
        foreach ($users as $user) {
            try {
                $user->notify(new NewMovieAddedNotification($movie, $genreNames));
                $notifiedCount++;
            } catch (\Exception $e) {
                Log::warning('NewMovieAddedNotification: Failed to notify user', [
                    'user_id' => $user->id,
                    'movie_id' => $movie->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('NewMovieAddedNotification: Notifications dispatched', [
            'movie_id' => $movie->id,
            'movie_title' => $movie->title,
            'genres' => $genreNames,
            'interested_users' => count($interestedUserIds),
            'notified_users' => $notifiedCount,
        ]);
    }
}

