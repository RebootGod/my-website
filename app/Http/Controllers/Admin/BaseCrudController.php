<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HandlesExceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Base CRUD Controller
 * Abstract class that provides common CRUD functionality
 * Reduces code duplication between AdminMovieController and AdminSeriesController
 */
abstract class BaseCrudController extends Controller
{
    use HandlesExceptions;

    /**
     * Get the model class name
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the file service instance
     */
    abstract protected function getFileService();

    /**
     * Get the index route name
     */
    abstract protected function getIndexRoute(): string;

    /**
     * Get the view directory
     */
    abstract protected function getViewDirectory(): string;

    /**
     * Get the store request class
     */
    abstract protected function getStoreRequestClass(): string;

    /**
     * Get the update request class
     */
    abstract protected function getUpdateRequestClass(): string;

    /**
     * Get additional relationships to load
     */
    protected function getRelationships(): array
    {
        return ['genres'];
    }

    /**
     * Get additional relationships to count
     */
    protected function getCountRelationships(): array
    {
        return ['views'];
    }

    /**
     * Get the resource name (lowercase)
     */
    protected function getResourceName(): string
    {
        return Str::lower(class_basename($this->getModelClass()));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::with($this->getRelationships())
                           ->withCount($this->getCountRelationships());

        // Apply filters
        $query = $this->applyFilters($query, $request);

        // Apply search
        if ($request->filled('search')) {
            $query = $this->applySearch($query, $request->search);
        }

        // Apply sorting
        $query = $this->applySorting($query, $request);

        // Get paginated results
        $items = $query->paginate(20)->appends($request->query());

        return view($this->getViewDirectory() . '.index', [
            $this->getResourceName() . 's' => $items,
            'request' => $request
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $genres = $this->getGenres();

        return view($this->getViewDirectory() . '.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $requestClass = $this->getStoreRequestClass();
        $request = app($requestClass);

        return $this->handleDatabaseOperation(function() use ($request) {
            $data = $request->validated();

            // Handle file upload
            $data = $this->handleFileUpload($data, $request);

            // Set creator
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            // Create the model
            $modelClass = $this->getModelClass();
            $model = $modelClass::create($data);

            // Sync genres
            if (!empty($data['genre_ids'])) {
                $model->genres()->sync($data['genre_ids']);
            }

            $this->logInfo(class_basename($modelClass) . ' created successfully', [
                'id' => $model->id,
                'title' => $model->title
            ]);

            return $model;

        }, ucfirst($this->getResourceName()) . ' created successfully!',
           'create_' . $this->getResourceName(),
           $this->getIndexRoute());
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::with($this->getRelationships())->findOrFail($id);

        // Get additional stats if file service supports it
        $stats = null;
        if (method_exists($this->getFileService(), 'getStats')) {
            $stats = $this->getFileService()->getStats($model);
        }

        return view($this->getViewDirectory() . '.show', [
            $this->getResourceName() => $model,
            'stats' => $stats
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::with($this->getRelationships())->findOrFail($id);
        $genres = $this->getGenres();

        return view($this->getViewDirectory() . '.edit', [
            $this->getResourceName() => $model,
            'genres' => $genres
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id)
    {
        $requestClass = $this->getUpdateRequestClass();
        $request = app($requestClass);

        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);

        return $this->handleDatabaseOperation(function() use ($request, $model) {
            $data = $request->validated();

            // Handle file upload
            $data = $this->handleFileUpload($data, $request);

            // Set updater
            $data['updated_by'] = auth()->id();

            // Update the model
            $model->update($data);

            // Sync genres
            if (array_key_exists('genre_ids', $data)) {
                $model->genres()->sync($data['genre_ids'] ?? []);
            }

            $this->logInfo(class_basename($model) . ' updated successfully', [
                'id' => $model->id,
                'title' => $model->title
            ]);

            return $model;

        }, ucfirst($this->getResourceName()) . ' updated successfully!',
           'update_' . $this->getResourceName(),
           $this->getIndexRoute());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);

        return $this->handleDatabaseOperation(function() use ($model) {
            $title = $model->title;
            $model->delete();

            $this->logInfo(class_basename($model) . ' deleted successfully', [
                'id' => $model->id,
                'title' => $title
            ]);

            return ['title' => $title];

        }, ucfirst($this->getResourceName()) . ' deleted successfully!',
           'delete_' . $this->getResourceName(),
           $this->getIndexRoute());
    }

    /**
     * Apply filters to the query
     */
    protected function applyFilters($query, Request $request)
    {
        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Genre filter
        if ($request->filled('genre')) {
            $query->byGenre($request->genre);
        }

        // Year filter
        if ($request->filled('year')) {
            $query->byYear($request->year);
        }

        // Active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Featured filter
        if ($request->filled('is_featured')) {
            $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    /**
     * Apply search to the query
     */
    protected function applySearch($query, string $search)
    {
        return $query->search($search);
    }

    /**
     * Apply sorting to the query
     */
    protected function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validate sort parameters
        $allowedSorts = ['title', 'created_at', 'updated_at', 'release_date', 'vote_average', 'popularity'];
        $allowedDirections = ['asc', 'desc'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDirection, $allowedDirections)) {
            $sortDirection = 'desc';
        }

        return $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Handle file upload
     */
    protected function handleFileUpload(array $data, $request): array
    {
        if ($request->hasFile('poster')) {
            $fileService = $this->getFileService();
            $posterResult = $fileService->uploadPoster($request->file('poster'));

            if ($posterResult['success']) {
                $data['poster_path'] = $posterResult['path'];
            } else {
                throw new \Exception('Failed to upload poster: ' . $posterResult['message']);
            }
        }

        return $data;
    }

    /**
     * Get genres for dropdowns
     */
    protected function getGenres()
    {
        return \App\Models\Genre::orderBy('name')->get();
    }

    /**
     * Get bulk actions
     */
    protected function getBulkActions(): array
    {
        return [
            'publish' => 'Publish Selected',
            'draft' => 'Mark as Draft',
            'archive' => 'Archive Selected',
            'activate' => 'Activate Selected',
            'deactivate' => 'Deactivate Selected',
            'delete' => 'Delete Selected'
        ];
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*' => 'integer|exists:' . (new ($this->getModelClass()))->getTable() . ',id'
        ]);

        $action = $request->action;
        $itemIds = $request->items;

        return $this->handleDatabaseOperation(function() use ($action, $itemIds) {
            $modelClass = $this->getModelClass();
            $query = $modelClass::whereIn('id', $itemIds);

            $count = 0;
            switch ($action) {
                case 'publish':
                    $count = $query->update(['status' => 'published']);
                    break;
                case 'draft':
                    $count = $query->update(['status' => 'draft']);
                    break;
                case 'archive':
                    $count = $query->update(['status' => 'archived']);
                    break;
                case 'activate':
                    $count = $query->update(['is_active' => true]);
                    break;
                case 'deactivate':
                    $count = $query->update(['is_active' => false]);
                    break;
                case 'delete':
                    $count = $query->delete();
                    break;
                default:
                    throw new \Exception('Invalid bulk action');
            }

            $this->logInfo('Bulk action performed', [
                'action' => $action,
                'count' => $count,
                'resource' => $this->getResourceName()
            ]);

            return $count;

        }, "Bulk action '{$action}' completed successfully!",
           'bulk_' . $action . '_' . $this->getResourceName(),
           $this->getIndexRoute());
    }
}