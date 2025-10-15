<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TMDBImportRequest;
use App\Services\NewTMDBService;
use App\Services\ContentUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewTMDBSeriesController extends Controller
{
    protected $tmdbService;
    protected $contentUploadService;

    public function __construct(NewTMDBService $tmdbService, ContentUploadService $contentUploadService)
    {
        $this->tmdbService = $tmdbService;
        $this->contentUploadService = $contentUploadService;
    }

    public function index()
    {
        return view('admin.series.tmdb-new-index');
    }

    public function search(Request $request)
    {
        // Test response first
        if (!$request->has('query')) {
            return response()->json([
                'success' => false,
                'error' => 'Query parameter is required'
            ], 400);
        }

        $query = $request->get('query');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'error' => 'Query must be at least 2 characters'
            ], 400);
        }

        try {
            $result = $this->tmdbService->searchSeries($query, $request->get('page', 1));

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'results' => $result['data']['results'] ?? [],
                    'total_pages' => $result['data']['total_pages'] ?? 1,
                    'total_results' => $result['data']['total_results'] ?? 0,
                    'current_page' => $result['data']['page'] ?? 1
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Search failed'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Series search error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function popular(Request $request)
    {
        try {
            $result = $this->tmdbService->getPopularSeries($request->get('page', 1));

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'results' => $result['data']['results'] ?? [],
                    'total_pages' => $result['data']['total_pages'] ?? 1,
                    'total_results' => $result['data']['total_results'] ?? 0,
                    'current_page' => $result['data']['page'] ?? 1
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch popular series'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Popular series error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch popular series: ' . $e->getMessage()
            ], 500);
        }
    }

    public function trending(Request $request)
    {
        try {
            $result = $this->tmdbService->getTrendingSeries($request->get('time_window', 'week'));

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'results' => $result['data']['results'] ?? [],
                    'total_pages' => $result['data']['total_pages'] ?? 1,
                    'total_results' => $result['data']['total_results'] ?? 0,
                    'current_page' => $result['data']['page'] ?? 1
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch trending series'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Trending series error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch trending series: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDetails(Request $request, $tmdbId)
    {
        try {
            $result = $this->tmdbService->getSeriesDetails($tmdbId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Series not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Series details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch series details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'tmdb_id' => 'required|integer'
        ]);

        try {
            // Use SeriesTMDBService for import logic
            $seriesTMDBService = new \App\Services\Admin\SeriesTMDBService($this->contentUploadService);
            $result = $seriesTMDBService->importSeries($request->tmdb_id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'series' => $result['data']
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Series import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }
}