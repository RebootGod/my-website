<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Exceptions\AdminException;

/**
 * Exception Handling Trait
 * Standardizes exception handling across controllers
 * Reduces try-catch boilerplate and ensures consistent error handling
 */
trait HandlesExceptions
{
    /**
     * Handle a standard operation with unified exception handling
     */
    protected function handleOperation(
        callable $operation,
        string $successMessage,
        string $operationName,
        string $redirectRoute = null
    ) {
        try {
            $result = $operation();

            // Log successful operation
            Log::info($operationName . ' successful', [
                'admin_id' => auth()->id(),
                'operation' => $operationName,
                'timestamp' => now()
            ]);

            // Return appropriate response based on request type
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => $result
                ]);
            }

            $redirect = $redirectRoute ? redirect()->route($redirectRoute) : back();
            return $redirect->with('success', $successMessage);

        } catch (AdminException $e) {
            // Handle admin-specific exceptions
            Log::warning($operationName . ' admin exception', [
                'admin_id' => auth()->id(),
                'operation' => $operationName,
                'error' => $e->getMessage(),
                'type' => $e->getType()
            ]);

            return $this->handleAdminException($e);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            Log::info($operationName . ' validation failed', [
                'admin_id' => auth()->id(),
                'operation' => $operationName,
                'errors' => $e->errors()
            ]);

            return $this->handleValidationException($e);

        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database exceptions
            Log::error($operationName . ' database error', [
                'admin_id' => auth()->id(),
                'operation' => $operationName,
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);

            return $this->handleDatabaseException($e, $operationName);

        } catch (\Exception $e) {
            // Handle general exceptions
            Log::error($operationName . ' failed', [
                'admin_id' => auth()->id(),
                'operation' => $operationName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->handleGeneralException($e, $operationName);
        }
    }

    /**
     * Handle API operation with JSON responses
     */
    protected function handleApiOperation(
        callable $operation,
        string $successMessage,
        string $operationName,
        int $successCode = 200
    ): JsonResponse {
        try {
            $result = $operation();

            Log::info($operationName . ' API success', [
                'user_id' => auth()->id(),
                'operation' => $operationName,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => $result
            ], $successCode);

        } catch (AdminException $e) {
            Log::warning($operationName . ' API admin exception', [
                'user_id' => auth()->id(),
                'operation' => $operationName,
                'error' => $e->getMessage(),
                'type' => $e->getType()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
                'error_type' => $e->getType()
            ], $e->getStatusCode());

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::info($operationName . ' API validation failed', [
                'user_id' => auth()->id(),
                'operation' => $operationName,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error($operationName . ' API failed', [
                'user_id' => auth()->id(),
                'operation' => $operationName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle database operations with transaction support
     */
    protected function handleDatabaseOperation(
        callable $operation,
        string $successMessage,
        string $operationName,
        string $redirectRoute = null
    ) {
        return $this->handleOperation(function() use ($operation) {
            return \DB::transaction($operation);
        }, $successMessage, $operationName, $redirectRoute);
    }

    /**
     * Handle file operations
     */
    protected function handleFileOperation(
        callable $operation,
        string $successMessage,
        string $operationName,
        array $allowedExtensions = []
    ) {
        try {
            // Additional file validation if extensions are specified
            if (!empty($allowedExtensions) && request()->hasFile('file')) {
                $file = request()->file('file');
                $extension = $file->getClientOriginalExtension();

                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    throw new \InvalidArgumentException(
                        'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions)
                    );
                }
            }

            return $this->handleOperation($operation, $successMessage, $operationName);

        } catch (\InvalidArgumentException $e) {
            Log::warning($operationName . ' file validation failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Handle admin-specific exceptions
     */
    private function handleAdminException(AdminException $e)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
                'error_type' => $e->getType()
            ], $e->getStatusCode());
        }

        return back()->with('error', $e->getUserMessage());
    }

    /**
     * Handle validation exceptions
     */
    private function handleValidationException(\Illuminate\Validation\ValidationException $e)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        return back()->withInput()->withErrors($e->errors());
    }

    /**
     * Handle database exceptions
     */
    private function handleDatabaseException(\Illuminate\Database\QueryException $e, string $operationName)
    {
        $userMessage = 'A database error occurred. Please try again.';

        // Provide more specific messages for common database errors
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            $userMessage = 'This record already exists.';
        } elseif (str_contains($e->getMessage(), 'foreign key constraint')) {
            $userMessage = 'Cannot delete this record as it is being used elsewhere.';
        } elseif (str_contains($e->getMessage(), 'Data too long')) {
            $userMessage = 'One or more fields contain too much data.';
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $userMessage
            ], 500);
        }

        return back()->with('error', $userMessage);
    }

    /**
     * Handle general exceptions
     */
    private function handleGeneralException(\Exception $e, string $operationName)
    {
        $userMessage = $operationName . ' failed. Please try again or contact support if the problem persists.';

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $userMessage
            ], 500);
        }

        return back()->with('error', $userMessage);
    }

    /**
     * Log an informational message with context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge([
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ], $context));
    }

    /**
     * Log a warning message with context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, array_merge([
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ], $context));
    }

    /**
     * Log an error message with context
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge([
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ], $context));
    }
}