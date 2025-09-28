<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeriesRequest;
use App\Http\Requests\Admin\UpdateSeriesRequest;
use App\Http\Requests\Admin\TMDBImportRequest;
use App\Models\Series;
use App\Models\SeriesSeason;
use App\Models\SeriesEpisode;
use App\Models\Genre;
use App\Services\Admin\SeriesTMDBService;
use App\Services\Admin\SeriesFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSeriesController extends Controller
{
    protected $tmdbService;
    protected $fileService;

    public function __construct(
        SeriesTMDBService $tmdbService,
        SeriesFileService $fileService
    ) {
        $this->tmdbService = $tmdbService;
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        $query = Series::with(['genres'])
            ->withCount(['views', 'seasons', 'episodes']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('original_title', 'LIKE', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $series = $query->paginate(20)->appends($request->query());
        $genres = Genre::orderBy('name')->get();

        return view('admin.series.index', compact('series', 'genres'));
    }

    public function create()
    {
        $this->authorize('create', Series::class);

        $genres = Genre::orderBy('name')->get();
        return view('admin.series.create', compact('genres'));
    }

    public function store(StoreSeriesRequest $request)
    {
        $this->authorize('create', Series::class);

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

            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            $series = Series::create($data);

            if (!empty($data['genre_ids'])) {
                $series->genres()->sync($data['genre_ids']);
            }

            Log::info('Series created successfully', [
                'series_id' => $series->id,
                'title' => $series->title,
                'admin_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.series.index')
                ->with('success', 'Series created successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to create series', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create series: ' . $e->getMessage());
        }
    }

    public function show(Series $series)
    {
        $this->authorize('view', $series);

        $series->load(['genres', 'seasons.episodes']);

        return view('admin.series.show', compact('series'));
    }

    public function edit(Series $series)
    {
        $this->authorize('update', $series);

        $series->load('genres');
        $genres = Genre::orderBy('name')->get();

        return view('admin.series.edit', compact('series', 'genres'));
    }

    public function update(UpdateSeriesRequest $request, Series $series)
    {
        $this->authorize('update', $series);

        try {
            $data = $request->validated();
            
            if ($request->hasFile('poster')) {
                $posterResult = $this->fileService->uploadPoster(
                    $request->file('poster'),
                    $series->poster_path
                );
                
                if ($posterResult['success']) {
                    $data['poster_path'] = $posterResult['path'];
                } else {
                    return back()->withInput()->withErrors(['poster' => $posterResult['message']]);
                }
            }

            $data['updated_by'] = auth()->id();
            $series->update($data);

            if (isset($data['genre_ids'])) {
                $series->genres()->sync($data['genre_ids']);
            }

            Log::info('Series updated successfully', [
                'series_id' => $series->id,
                'title' => $series->title,
                'admin_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.series.index')
                ->with('success', 'Series updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update series', [
                'series_id' => $series->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update series: ' . $e->getMessage());
        }
    }

    public function destroy(Series $series)
    {
        $this->authorize('delete', $series);

        try {
            $seriesTitle = $series->title;
            
            if ($series->poster_path) {
                $this->fileService->deletePoster($series->poster_path);
            }

            $series->delete();

            Log::info('Series deleted successfully', [
                'title' => $seriesTitle,
                'admin_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.series.index')
                ->with('success', 'Series deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to delete series', [
                'series_id' => $series->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return back()->with('error', 'Failed to delete series: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Series $series)
    {
        $this->authorize('update', $series);

        try {
            $newStatus = $series->status === 'published' ? 'draft' : 'published';
            
            $series->update([
                'status' => $newStatus,
                'updated_by' => auth()->id()
            ]);

            Log::info('Series status toggled', [
                'series_id' => $series->id,
                'old_status' => $series->getOriginal('status'),
                'new_status' => $newStatus,
                'admin_id' => auth()->id()
            ]);

            return back()->with('success', 'Series status updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to toggle series status', [
                'series_id' => $series->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    public function tmdbSearch(Request $request)
    {
        $this->authorize('create', Series::class);

        $request->validate([
            'query' => 'required|string|min:2',
            'page' => 'nullable|integer|min:1'
        ]);

        $result = $this->tmdbService->searchSeries(
            $request->query,
            $request->get('page', 1)
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return view('admin.series.tmdb-search', [
            'searchQuery' => $request->query,
            'searchResults' => $result['data'] ?? [],
            'success' => $result['success'],
            'message' => $result['message'] ?? null
        ]);
    }

    public function tmdbDetails(Request $request)
    {
        $this->authorize('create', Series::class);

        $request->validate([
            'tmdb_id' => 'required|integer'
        ]);

        $result = $this->tmdbService->getSeriesDetails($request->tmdb_id);
        return response()->json($result);
    }

    public function tmdbImport(TMDBImportRequest $request)
    {
        $this->authorize('create', Series::class);

        try {
            $result = $this->tmdbService->importSeries($request->tmdb_id);

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            return redirect()
                ->route('admin.series.show', $result['data'])
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('TMDB import failed', [
                'tmdb_id' => $request->tmdb_id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function showTmdbImport()
    {
        $this->authorize('create', Series::class);

        return view('admin.series.import_tmdb');
    }

    public function tmdbBulkImport(Request $request)
    {
        $this->authorize('create', Series::class);

        $request->validate([
            'tmdb_ids' => 'required|array',
            'tmdb_ids.*' => 'integer'
        ]);

        try {
            $imported = [];
            $failed = [];
            $skipped = [];

            foreach ($request->tmdb_ids as $tmdbId) {
                $result = $this->tmdbService->importSeries($tmdbId);

                if ($result['success']) {
                    $imported[] = [
                        'tmdb_id' => $tmdbId,
                        'title' => $result['data']['title'],
                        'series_id' => $result['data']['id']
                    ];
                } elseif (str_contains($result['message'], 'already exists')) {
                    $skipped[] = $tmdbId;
                } else {
                    $failed[] = [
                        'tmdb_id' => $tmdbId,
                        'error' => $result['message']
                    ];
                }
            }

            $summary = [
                'imported_count' => count($imported),
                'skipped_count' => count($skipped),
                'failed_count' => count($failed),
                'total_processed' => count($request->tmdb_ids)
            ];

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

    public function storeSeason(Request $request, Series $series)
    {
        $this->authorize('update', $series);

        $request->validate([
            'season_number' => 'required|integer|min:1|unique:series_seasons,season_number,NULL,id,series_id,' . $series->id,
            'name' => 'nullable|string|max:255',
            'overview' => 'nullable|string'
        ]);

        try {
            $season = $series->seasons()->create([
                'season_number' => $request->season_number,
                'name' => $request->name,
                'overview' => $request->overview,
                'is_active' => true
            ]);

            Log::info('Season created successfully', [
                'season_id' => $season->id,
                'series_id' => $series->id,
                'season_number' => $season->season_number,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Season added successfully!',
                'season' => $season
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create season', [
                'series_id' => $series->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to add season: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroySeason(Series $series, SeriesSeason $season)
    {
        $this->authorize('update', $series);

        try {
            // Check if season belongs to the series
            if ($season->series_id !== $series->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Season does not belong to this series'
                ], 403);
            }

            // Count episodes that will be deleted
            $episodeCount = $season->episodes()->count();

            // Delete all episodes in the season first (cascade)
            $season->episodes()->delete();

            // Delete the season
            $season->delete();

            Log::info('Season deleted successfully', [
                'season_id' => $season->id,
                'series_id' => $series->id,
                'season_number' => $season->season_number,
                'episodes_deleted' => $episodeCount,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Season {$season->season_number} and {$episodeCount} episodes deleted successfully!"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete season', [
                'season_id' => $season->id,
                'series_id' => $series->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete season: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeEpisode(Request $request, Series $series)
    {
        $this->authorize('update', $series);

        $request->validate([
            'season_id' => 'required|exists:series_seasons,id',
            'episode_number' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'overview' => 'required|string',
            'runtime' => 'required|integer|min:1',
            'embed_url' => 'required|url',
            'still_path' => 'nullable|url'
        ]);

        try {
            // Check if episode number already exists in this season
            $existingEpisode = SeriesEpisode::where('season_id', $request->season_id)
                ->where('episode_number', $request->episode_number)
                ->first();

            if ($existingEpisode) {
                return response()->json([
                    'success' => false,
                    'error' => 'Episode number already exists in this season.'
                ], 400);
            }

            $episode = SeriesEpisode::create([
                'series_id' => $series->id,
                'season_id' => $request->season_id,
                'episode_number' => $request->episode_number,
                'name' => $request->name,
                'overview' => $request->overview,
                'runtime' => $request->runtime,
                'embed_url' => $request->embed_url,
                'still_path' => $request->still_path,
                'is_active' => true
            ]);

            Log::info('Episode created successfully', [
                'episode_id' => $episode->id,
                'series_id' => $series->id,
                'season_id' => $request->season_id,
                'episode_number' => $episode->episode_number,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Episode added successfully!',
                'episode' => $episode
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create episode', [
                'series_id' => $series->id,
                'season_id' => $request->season_id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to add episode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit an episode (show edit form)
     */
    public function editEpisode(Series $series, SeriesEpisode $episode)
    {
        $this->authorize('update', $series);

        // Verify the episode belongs to this series
        if ($episode->series_id !== $series->id) {
            abort(404, 'Episode not found');
        }

        // Load relationships
        $episode->load('season');
        $series->load('seasons');

        return view('admin.series.episode-edit-modern', compact('series', 'episode'));
    }

    /**
     * Update an episode
     */
    public function updateEpisode(Request $request, Series $series, SeriesEpisode $episode)
    {
        $this->authorize('update', $series);

        // Verify the episode belongs to this series
        if ($episode->series_id !== $series->id) {
            return response()->json([
                'success' => false,
                'error' => 'Episode does not belong to this series.'
            ], 400);
        }

        $request->validate([
            'season_id' => 'required|exists:series_seasons,id',
            'episode_number' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'overview' => 'required|string',
            'runtime' => 'required|integer|min:1',
            'embed_url' => 'required|url',
            'still_path' => 'nullable|url',
            'is_active' => 'boolean'
        ]);

        try {
            // Check if episode number already exists in this season (excluding current episode)
            $existingEpisode = SeriesEpisode::where('season_id', $request->season_id)
                ->where('episode_number', $request->episode_number)
                ->where('id', '!=', $episode->id)
                ->first();

            if ($existingEpisode) {
                return response()->json([
                    'success' => false,
                    'error' => 'Episode number already exists in this season.'
                ], 400);
            }

            // Store old values for logging
            $oldValues = [
                'episode_number' => $episode->episode_number,
                'name' => $episode->name,
                'season_id' => $episode->season_id,
                'runtime' => $episode->runtime,
                'is_active' => $episode->is_active
            ];

            // Update episode
            $episode->update([
                'season_id' => $request->season_id,
                'episode_number' => $request->episode_number,
                'name' => $request->name,
                'overview' => $request->overview,
                'runtime' => $request->runtime,
                'embed_url' => $request->embed_url,
                'still_path' => $request->still_path,
                'is_active' => $request->boolean('is_active', true)
            ]);

            Log::info('Episode updated successfully', [
                'episode_id' => $episode->id,
                'series_id' => $series->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'episode_number' => $episode->episode_number,
                    'name' => $episode->name,
                    'season_id' => $episode->season_id,
                    'runtime' => $episode->runtime,
                    'is_active' => $episode->is_active
                ],
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Episode updated successfully!',
                'episode' => $episode->load('season')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update episode', [
                'episode_id' => $episode->id,
                'series_id' => $series->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update episode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an episode
     */
    public function destroyEpisode(Series $series, SeriesEpisode $episode)
    {
        $this->authorize('update', $series);

        try {
            // Verify the episode belongs to this series
            if ($episode->series_id !== $series->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Episode does not belong to this series.'
                ], 400);
            }

            $episodeNumber = $episode->episode_number;
            $seasonNumber = $episode->season->season_number;

            // Delete the episode
            $episode->delete();

            Log::info('Episode deleted successfully', [
                'episode_id' => $episode->id,
                'series_id' => $series->id,
                'season_number' => $seasonNumber,
                'episode_number' => $episodeNumber,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Episode {$episodeNumber} from Season {$seasonNumber} deleted successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Episode deletion failed', [
                'episode_id' => $episode->id,
                'series_id' => $series->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete episode: ' . $e->getMessage()
            ], 500);
        }
    }
}
