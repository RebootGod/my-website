<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContentBulkOperationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Bulk Operation Controller
 * 
 * Handles bulk operations for content management
 * Max 350 lines per workinginstruction.md
 */
class BulkOperationController extends Controller
{
    protected ContentBulkOperationService $bulkService;

    public function __construct(ContentBulkOperationService $bulkService)
    {
        $this->bulkService = $bulkService;
    }

    /**
     * Bulk update metadata
     */
    public function updateMetadata(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:movie,series',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
            'data' => 'required|array',
            'data.title' => 'sometimes|string|max:255',
            'data.description' => 'sometimes|string',
            'data.release_date' => 'sometimes|date',
            'data.status' => 'sometimes|in:published,draft,archived',
            'data.is_featured' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->bulkService->bulkUpdateMetadata(
                $request->type,
                $request->ids,
                $request->data
            );

            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => "Updated {$results['success']} items successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk update metadata failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk refresh from TMDB
     */
    public function refreshTMDB(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:movie,series',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create progress tracking key
            $progressKey = $this->bulkService->createProgressKey('tmdb_refresh', $request->type);
            
            // Initialize progress
            $totalItems = count($request->ids);
            $this->bulkService->updateProgress($progressKey, [
                'total' => $totalItems,
                'processed' => 0,
                'success' => 0,
                'failed' => 0,
                'waiting' => $totalItems,
                'status' => 'processing',
                'current_batch' => 0,
                'total_batches' => (int) ceil($totalItems / 5), // 5 items per batch
                'errors' => []
            ]);

            // Dispatch to queue job (REUSABLE - same as "Refresh All TMDB" button)
            \App\Jobs\RefreshAllTmdbJob::dispatch(
                $request->type,
                $request->ids,
                $progressKey
            )->onConnection('database')->onQueue('default');

            Log::info('Bulk TMDB refresh job dispatched', [
                'type' => $request->type,
                'count' => $totalItems,
                'progress_key' => $progressKey
            ]);

            return response()->json([
                'success' => true,
                'progressKey' => $progressKey,
                'message' => "Processing {$totalItems} items in background. Check progress modal for updates.",
                'totalItems' => $totalItems
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk TMDB refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk TMDB refresh failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk change status
     */
    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:movie,series',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
            'status' => 'required|in:published,draft,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->bulkService->bulkChangeStatus(
                $request->type,
                $request->ids,
                $request->status
            );

            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => "Changed status of {$results['success']} items to {$request->status}"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk status change failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk status change failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:movie,series',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->bulkService->bulkDelete(
                $request->type,
                $request->ids
            );

            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => "Deleted {$results['success']} items successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk toggle featured
     */
    public function toggleFeatured(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:movie,series',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
            'featured' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->bulkService->bulkToggleFeatured(
                $request->type,
                $request->ids,
                $request->featured
            );

            $action = $request->featured ? 'featured' : 'unfeatured';
            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => "Marked {$results['success']} items as {$action}"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk toggle featured failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk toggle featured failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get progress
     */
    public function getProgress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $progress = $this->bulkService->getProgress($request->key);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Progress not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * Refresh ALL items from TMDB (Movies or Series)
     * Uses queue job to prevent timeouts with large datasets
     */
    public function refreshAllTMDB(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:movie,series',
            'status' => 'sometimes|in:published,draft,archived', // Optional: filter by status
            'limit' => 'sometimes|integer|min:1|max:1000', // Optional: limit items to refresh
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get all IDs based on type
            $modelClass = $request->type === 'movie' 
                ? \App\Models\Movie::class 
                : \App\Models\Series::class;

            $query = $modelClass::query();

            // Apply status filter if provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Apply limit if provided, otherwise get all
            if ($request->filled('limit')) {
                $query->limit($request->limit);
            }

            // Get IDs only (efficient)
            $ids = $query->pluck('id')->toArray();

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items found to refresh'
                ], 404);
            }

            Log::info("🚀 Refresh ALL TMDB initiated (Queue Job)", [
                'type' => $request->type,
                'total_items' => count($ids),
                'status_filter' => $request->status ?? 'all',
                'limit' => $request->limit ?? 'no limit',
                'user_id' => auth()->id()
            ]);

            // Create progress tracking key
            $progressKey = $this->bulkService->createProgressKey('tmdb_refresh_all', $request->type);
            
            // Initialize progress - queued status
            $this->bulkService->updateProgress($progressKey, [
                'total' => count($ids),
                'processed' => 0,
                'success' => 0,
                'failed' => 0,
                'status' => 'queued',
                'queued_at' => now()->toISOString()
            ]);

            // Dispatch job to DATABASE queue (explicit connection)
            // Production uses redis as default, but this job needs database queue
            \App\Jobs\RefreshAllTmdbJob::dispatch(
                $request->type,
                $ids,
                $progressKey,
                $request->status,
                $request->limit
            )->onConnection('database')->onQueue('default');

            Log::info("✅ Job dispatched to DATABASE queue", [
                'type' => $request->type,
                'progress_key' => $progressKey,
                'total_items' => count($ids),
                'connection' => 'database',
                'queue' => 'default'
            ]);

            return response()->json([
                'success' => true,
                'progressKey' => $progressKey,
                'totalItems' => count($ids),
                'message' => "Refresh job queued for {$request->type}(s). Processing " . count($ids) . " items in background."
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Refresh ALL TMDB failed to queue', [
                'type' => $request->type ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue refresh job: ' . $e->getMessage()
            ], 500);
        }
    }
}
