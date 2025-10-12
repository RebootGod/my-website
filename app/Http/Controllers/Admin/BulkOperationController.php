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
            $this->bulkService->updateProgress($progressKey, [
                'total' => count($request->ids),
                'processed' => 0,
                'success' => 0,
                'failed' => 0,
                'status' => 'processing'
            ]);

            $results = $this->bulkService->bulkRefreshFromTMDB(
                $request->type,
                $request->ids
            );

            // Update final progress
            $this->bulkService->updateProgress($progressKey, [
                'total' => count($request->ids),
                'processed' => count($request->ids),
                'success' => $results['success'],
                'failed' => $results['failed'],
                'status' => 'completed',
                'errors' => $results['errors']
            ]);

            return response()->json([
                'success' => true,
                'results' => $results,
                'progressKey' => $progressKey,
                'message' => "Refreshed {$results['success']} items from TMDB"
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
}
