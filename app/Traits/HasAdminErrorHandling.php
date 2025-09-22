<?php

namespace App\Traits;

use App\Exceptions\AdminException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * ========================================
 * ADMIN ERROR HANDLING TRAIT
 * Consistent error handling for admin controllers
 * ========================================
 */
trait HasAdminErrorHandling
{
    /**
     * Execute operation with comprehensive error handling
     */
    protected function executeAdminOperation(callable $operation, string $operationName = 'admin operation'): mixed
    {
        try {
            return DB::transaction(function () use ($operation) {
                return $operation();
            });

        } catch (ValidationException $e) {
            throw AdminException::validation(
                "Validation failed for {$operationName}",
                ['errors' => $e->errors()]
            );

        } catch (ModelNotFoundException $e) {
            throw AdminException::businessLogic(
                "Resource not found for {$operationName}",
                'The requested item could not be found.',
                ['model' => $e->getModel()]
            );

        } catch (AdminException $e) {
            // Re-throw admin exceptions as-is
            throw $e;

        } catch (\Exception $e) {
            // Handle unexpected exceptions
            Log::error("Unexpected error in {$operationName}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => auth()->id()
            ]);

            throw AdminException::businessLogic(
                "Unexpected error in {$operationName}: " . $e->getMessage(),
                'An unexpected error occurred. Please try again or contact support.',
                ['original_exception' => get_class($e)]
            );
        }
    }

    /**
     * Handle file upload with error handling
     */
    protected function handleFileUpload($file, string $destination, array $allowedTypes = ['jpg', 'jpeg', 'png']): array
    {
        try {
            if (!$file || !$file->isValid()) {
                throw AdminException::fileOperation(
                    'upload',
                    'Invalid file provided',
                    ['file_error' => $file?->getError()]
                );
            }

            $extension = $file->getClientOriginalExtension();
            if (!in_array(strtolower($extension), $allowedTypes)) {
                throw AdminException::fileOperation(
                    'upload',
                    "File type not allowed: {$extension}",
                    [
                        'provided_type' => $extension,
                        'allowed_types' => $allowedTypes
                    ]
                );
            }

            $filename = uniqid() . '.' . $extension;
            $path = $file->storeAs($destination, $filename, 'public');

            if (!$path) {
                throw AdminException::fileOperation(
                    'upload',
                    'Failed to store file',
                    ['destination' => $destination]
                );
            }

            return [
                'success' => true,
                'path' => $path,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ];

        } catch (AdminException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw AdminException::fileOperation(
                'upload',
                'File upload failed: ' . $e->getMessage(),
                ['exception' => get_class($e)]
            );
        }
    }

    /**
     * Handle external API calls with error handling
     */
    protected function handleExternalApiCall(callable $apiCall, string $serviceName): mixed
    {
        try {
            return $apiCall();

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw AdminException::externalApi(
                $serviceName,
                'Connection failed: ' . $e->getMessage(),
                ['type' => 'connection_error']
            );

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode();
            throw AdminException::externalApi(
                $serviceName,
                "Request failed with status {$statusCode}: " . $e->getMessage(),
                [
                    'status_code' => $statusCode,
                    'response_body' => $e->getResponse()?->getBody()?->getContents()
                ]
            );

        } catch (\Exception $e) {
            throw AdminException::externalApi(
                $serviceName,
                'Unexpected API error: ' . $e->getMessage(),
                ['exception' => get_class($e)]
            );
        }
    }

    /**
     * Validate permissions with error handling
     */
    protected function validateAdminPermission(string $action, $resource = null): void
    {
        $user = auth()->user();

        if (!$user) {
            throw AdminException::unauthorized(
                'User not authenticated',
                ['action' => $action]
            );
        }

        // Check if user has required admin role
        if (!$user->isAdmin()) {
            throw AdminException::unauthorized(
                "User {$user->id} attempted {$action} without admin privileges",
                [
                    'action' => $action,
                    'user_role' => $user->role,
                    'resource' => $resource
                ]
            );
        }

        // Check specific permissions if method exists
        if (method_exists($user, 'can') && !$user->can($action, $resource)) {
            throw AdminException::unauthorized(
                "User {$user->id} lacks permission for {$action}",
                [
                    'action' => $action,
                    'resource' => $resource
                ]
            );
        }
    }

    /**
     * Handle bulk operations with error handling
     */
    protected function executeBulkOperation(array $items, callable $operation, string $operationName): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($items)
        ];

        foreach ($items as $item) {
            try {
                $result = $operation($item);
                $results['success'][] = [
                    'item' => $item,
                    'result' => $result
                ];

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'item' => $item,
                    'error' => $e->getMessage()
                ];

                Log::warning("Bulk operation item failed", [
                    'operation' => $operationName,
                    'item' => $item,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // If more than 50% failed, consider it a critical error
        if (count($results['failed']) > count($results['success'])) {
            throw AdminException::businessLogic(
                "Bulk {$operationName} largely failed",
                "The bulk operation encountered too many errors. Please review the items and try again.",
                $results
            );
        }

        return $results;
    }

    /**
     * Log admin action for audit trail
     */
    protected function logAdminAction(string $action, array $context = []): void
    {
        Log::info('Admin action performed', array_merge([
            'admin_id' => auth()->id(),
            'action' => $action,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ], $context));
    }

    /**
     * Return success response with consistent format
     */
    protected function successResponse(string $message, array $data = [], int $status = 200): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        if (request()->expectsJson()) {
            return response()->json($response, $status);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Return error response with consistent format
     */
    protected function errorResponse(string $message, array $errors = [], int $status = 400): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors
            ], $status);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $message)
            ->withErrors($errors);
    }

    /**
     * Handle database constraints violations
     */
    protected function handleDatabaseConstraints(callable $operation, string $operationName): mixed
    {
        try {
            return $operation();

        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;

            // Handle common MySQL constraint violations
            if ($errorCode === 1451) { // Foreign key constraint
                throw AdminException::database(
                    "Cannot delete {$operationName} - it has related records",
                    [
                        'error_code' => $errorCode,
                        'sql_state' => $e->errorInfo[0] ?? null
                    ]
                );
            }

            if ($errorCode === 1062) { // Duplicate entry
                throw AdminException::database(
                    "Duplicate entry for {$operationName}",
                    [
                        'error_code' => $errorCode,
                        'sql_state' => $e->errorInfo[0] ?? null
                    ]
                );
            }

            // Generic database error
            throw AdminException::database(
                "Database error in {$operationName}: " . $e->getMessage(),
                [
                    'error_code' => $errorCode,
                    'sql_state' => $e->errorInfo[0] ?? null
                ]
            );
        }
    }
}