<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\User;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * ========================================
 * BULK OPERATIONS CONTROLLER
 * Handles bulk operations for admin entities
 * ========================================
 */
class BulkOperationsController extends Controller
{
    /**
     * Handle bulk operations for movies
     */
    public function movies(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in([
                'publish', 'draft', 'archive', 'delete', 'update_quality'
            ])],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer', 'exists:movies,id'],
            'quality' => ['nullable', 'string', Rule::in(['360p', '480p', '720p', '1080p', '4K'])],
        ]);

        try {
            $result = $this->executeMovieBulkAction(
                $validated['action'],
                $validated['items'],
                $validated['quality'] ?? null
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Movie bulk operation failed', [
                'action' => $validated['action'],
                'items' => $validated['items'],
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle bulk operations for users
     */
    public function users(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in([
                'activate', 'deactivate', 'promote', 'demote', 'delete'
            ])],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer', 'exists:users,id'],
        ]);

        try {
            $result = $this->executeUserBulkAction(
                $validated['action'],
                $validated['items']
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('User bulk operation failed', [
                'action' => $validated['action'],
                'items' => $validated['items'],
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle bulk operations for series
     */
    public function series(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in([
                'publish', 'draft', 'archive', 'delete'
            ])],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer', 'exists:series,id'],
        ]);

        try {
            $result = $this->executeSeriesBulkAction(
                $validated['action'],
                $validated['items']
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Series bulk operation failed', [
                'action' => $validated['action'],
                'items' => $validated['items'],
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute movie bulk action
     */
    private function executeMovieBulkAction(string $action, array $movieIds, ?string $quality = null): array
    {
        $updatedMovies = [];

        DB::transaction(function () use ($action, $movieIds, $quality, &$updatedMovies) {
            switch ($action) {
                case 'publish':
                    Movie::whereIn('id', $movieIds)->update([
                        'status' => 'published',
                        'updated_at' => now()
                    ]);
                    $updatedMovies = Movie::whereIn('id', $movieIds)
                        ->select(['id', 'status', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'draft':
                    Movie::whereIn('id', $movieIds)->update([
                        'status' => 'draft',
                        'updated_at' => now()
                    ]);
                    $updatedMovies = Movie::whereIn('id', $movieIds)
                        ->select(['id', 'status', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'archive':
                    Movie::whereIn('id', $movieIds)->update([
                        'status' => 'archived',
                        'updated_at' => now()
                    ]);
                    $updatedMovies = Movie::whereIn('id', $movieIds)
                        ->select(['id', 'status', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'update_quality':
                    if ($quality) {
                        Movie::whereIn('id', $movieIds)->update([
                            'quality' => $quality,
                            'updated_at' => now()
                        ]);
                        $updatedMovies = Movie::whereIn('id', $movieIds)
                            ->select(['id', 'quality', 'updated_at'])
                            ->get()
                            ->toArray();
                    }
                    break;

                case 'delete':
                    // Soft delete if available, otherwise hard delete
                    Movie::whereIn('id', $movieIds)->delete();
                    break;
            }
        });

        $count = count($movieIds);
        $messages = [
            'publish' => "Successfully published {$count} movie(s)",
            'draft' => "Successfully moved {$count} movie(s) to draft",
            'archive' => "Successfully archived {$count} movie(s)",
            'update_quality' => "Successfully updated quality for {$count} movie(s)",
            'delete' => "Successfully deleted {$count} movie(s)"
        ];

        return [
            'success' => true,
            'message' => $messages[$action] ?? 'Bulk operation completed',
            'updated_items' => $updatedMovies,
            'reload' => $action === 'delete'
        ];
    }

    /**
     * Execute user bulk action
     */
    private function executeUserBulkAction(string $action, array $userIds): array
    {
        $updatedUsers = [];

        DB::transaction(function () use ($action, $userIds, &$updatedUsers) {
            // Prevent action on super admin users
            $superAdmins = User::whereIn('id', $userIds)
                ->where('role', 'super_admin')
                ->pluck('id')
                ->toArray();

            if (!empty($superAdmins)) {
                throw new \Exception('Cannot perform bulk operations on super admin users');
            }

            // Prevent self-action
            if (in_array(auth()->id(), $userIds)) {
                throw new \Exception('Cannot perform bulk operations on your own account');
            }

            switch ($action) {
                case 'activate':
                    User::whereIn('id', $userIds)->update([
                        'status' => 'active',
                        'updated_at' => now()
                    ]);
                    $updatedUsers = User::whereIn('id', $userIds)
                        ->select(['id', 'status', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'deactivate':
                    User::whereIn('id', $userIds)->update([
                        'status' => 'inactive',
                        'updated_at' => now()
                    ]);
                    $updatedUsers = User::whereIn('id', $userIds)
                        ->select(['id', 'status', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'promote':
                    // Only promote members to moderators
                    User::whereIn('id', $userIds)
                        ->where('role', 'member')
                        ->update([
                            'role' => 'moderator',
                            'updated_at' => now()
                        ]);
                    $updatedUsers = User::whereIn('id', $userIds)
                        ->select(['id', 'role', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'demote':
                    // Only demote moderators to members
                    User::whereIn('id', $userIds)
                        ->where('role', 'moderator')
                        ->update([
                            'role' => 'member',
                            'updated_at' => now()
                        ]);
                    $updatedUsers = User::whereIn('id', $userIds)
                        ->select(['id', 'role', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'delete':
                    User::whereIn('id', $userIds)->delete();
                    break;
            }
        });

        $count = count($userIds);
        $messages = [
            'activate' => "Successfully activated {$count} user(s)",
            'deactivate' => "Successfully deactivated {$count} user(s)",
            'promote' => "Successfully promoted {$count} user(s)",
            'demote' => "Successfully demoted {$count} user(s)",
            'delete' => "Successfully deleted {$count} user(s)"
        ];

        return [
            'success' => true,
            'message' => $messages[$action] ?? 'Bulk operation completed',
            'updated_items' => $updatedUsers,
            'reload' => $action === 'delete'
        ];
    }

    /**
     * Execute series bulk action
     */
    private function executeSeriesBulkAction(string $action, array $seriesIds): array
    {
        $updatedSeries = [];

        DB::transaction(function () use ($action, $seriesIds, &$updatedSeries) {
            switch ($action) {
                case 'publish':
                    Series::whereIn('id', $seriesIds)->update([
                        'status' => 'published',
                        'updated_at' => now()
                    ]);
                    $updatedSeries = Series::whereIn('id', $seriesIds)
                        ->select(['id', 'status', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'draft':
                    Series::whereIn('id', $seriesIds)->update([
                        'status' => 'draft',
                        'updated_at' => now()
                    ]);
                    $updatedSeries = Series::whereIn('id', $seriesIds)
                        ->select(['id', 'status', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'archive':
                    Series::whereIn('id', $seriesIds)->update([
                        'status' => 'archived',
                        'updated_at' => now()
                    ]);
                    $updatedSeries = Series::whereIn('id', $seriesIds)
                        ->select(['id', 'status', 'updated_at'])
                        ->get()
                        ->toArray();
                    break;

                case 'delete':
                    Series::whereIn('id', $seriesIds)->delete();
                    break;
            }
        });

        $count = count($seriesIds);
        $messages = [
            'publish' => "Successfully published {$count} series",
            'draft' => "Successfully moved {$count} series to draft",
            'archive' => "Successfully archived {$count} series",
            'delete' => "Successfully deleted {$count} series"
        ];

        return [
            'success' => true,
            'message' => $messages[$action] ?? 'Bulk operation completed',
            'updated_items' => $updatedSeries,
            'reload' => $action === 'delete'
        ];
    }
}