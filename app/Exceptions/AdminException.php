<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * ========================================
 * ADMIN EXCEPTION HANDLER
 * Dedicated exception handling for admin operations
 * ========================================
 */
class AdminException extends Exception
{
    /**
     * Exception types
     */
    public const TYPE_VALIDATION = 'validation';
    public const TYPE_AUTHORIZATION = 'authorization';
    public const TYPE_RATE_LIMIT = 'rate_limit';
    public const TYPE_BUSINESS_LOGIC = 'business_logic';
    public const TYPE_EXTERNAL_API = 'external_api';
    public const TYPE_DATABASE = 'database';
    public const TYPE_FILE_OPERATION = 'file_operation';

    /**
     * Exception type
     */
    protected string $type;

    /**
     * Additional context data
     */
    protected array $context;

    /**
     * User-friendly message
     */
    protected string $userMessage;

    /**
     * Whether this exception should be logged
     */
    protected bool $shouldLog;

    /**
     * Create a new admin exception instance
     */
    public function __construct(
        string $message = '',
        string $type = self::TYPE_BUSINESS_LOGIC,
        array $context = [],
        string $userMessage = '',
        bool $shouldLog = true,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->type = $type;
        $this->context = $context;
        $this->userMessage = $userMessage ?: $this->getDefaultUserMessage();
        $this->shouldLog = $shouldLog;
    }

    /**
     * Create validation exception
     */
    public static function validation(string $message, array $context = []): self
    {
        return new self(
            $message,
            self::TYPE_VALIDATION,
            $context,
            'The provided data is invalid. Please check your input and try again.',
            false // Don't log validation errors
        );
    }

    /**
     * Create authorization exception
     */
    public static function unauthorized(string $message, array $context = []): self
    {
        return new self(
            $message,
            self::TYPE_AUTHORIZATION,
            $context,
            'You do not have permission to perform this action.',
            true
        );
    }

    /**
     * Create rate limit exception
     */
    public static function rateLimited(string $message, array $context = []): self
    {
        return new self(
            $message,
            self::TYPE_RATE_LIMIT,
            $context,
            'Too many requests. Please wait before trying again.',
            true
        );
    }

    /**
     * Create business logic exception
     */
    public static function businessLogic(string $message, string $userMessage = '', array $context = []): self
    {
        return new self(
            $message,
            self::TYPE_BUSINESS_LOGIC,
            $context,
            $userMessage,
            true
        );
    }

    /**
     * Create external API exception
     */
    public static function externalApi(string $service, string $message, array $context = []): self
    {
        return new self(
            "External API error [{$service}]: {$message}",
            self::TYPE_EXTERNAL_API,
            array_merge($context, ['service' => $service]),
            'An external service is currently unavailable. Please try again later.',
            true
        );
    }

    /**
     * Create database exception
     */
    public static function database(string $message, array $context = []): self
    {
        return new self(
            $message,
            self::TYPE_DATABASE,
            $context,
            'A database error occurred. Please try again or contact support.',
            true
        );
    }

    /**
     * Create file operation exception
     */
    public static function fileOperation(string $operation, string $message, array $context = []): self
    {
        return new self(
            "File operation error [{$operation}]: {$message}",
            self::TYPE_FILE_OPERATION,
            array_merge($context, ['operation' => $operation]),
            'A file operation failed. Please check your file and try again.',
            true
        );
    }

    /**
     * Get exception type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get user-friendly message
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Check if exception should be logged
     */
    public function shouldLog(): bool
    {
        return $this->shouldLog;
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): Response
    {
        // Log the exception if required
        if ($this->shouldLog()) {
            $this->logException($request);
        }

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $this->type,
                'message' => $this->getUserMessage(),
                'code' => $this->getCode() ?: $this->getStatusCode()
            ], $this->getStatusCode());
        }

        // Return view for web requests
        return response()->view('admin.errors.exception', [
            'type' => $this->type,
            'message' => $this->getUserMessage(),
            'title' => $this->getErrorTitle(),
            'canRetry' => $this->canRetry()
        ], $this->getStatusCode());
    }

    /**
     * Log the exception with context
     */
    protected function logException(Request $request): void
    {
        $logLevel = $this->getLogLevel();
        $context = array_merge($this->context, [
            'exception_type' => $this->type,
            'user_id' => auth()->id(),
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'admin_action' => $request->route()?->getName(),
            'timestamp' => now()->toISOString()
        ]);

        Log::log($logLevel, $this->getMessage(), $context);

        // For critical errors, also send to monitoring service
        if (in_array($logLevel, ['error', 'critical', 'alert', 'emergency'])) {
            $this->reportToCriticalMonitoring($context);
        }
    }

    /**
     * Get appropriate log level for exception type
     */
    protected function getLogLevel(): string
    {
        return match ($this->type) {
            self::TYPE_VALIDATION => 'info',
            self::TYPE_AUTHORIZATION => 'warning',
            self::TYPE_RATE_LIMIT => 'warning',
            self::TYPE_BUSINESS_LOGIC => 'info',
            self::TYPE_EXTERNAL_API => 'error',
            self::TYPE_DATABASE => 'error',
            self::TYPE_FILE_OPERATION => 'warning',
            default => 'error'
        };
    }

    /**
     * Get HTTP status code for exception type
     */
    protected function getStatusCode(): int
    {
        return match ($this->type) {
            self::TYPE_VALIDATION => 422,
            self::TYPE_AUTHORIZATION => 403,
            self::TYPE_RATE_LIMIT => 429,
            self::TYPE_BUSINESS_LOGIC => 400,
            self::TYPE_EXTERNAL_API => 502,
            self::TYPE_DATABASE => 500,
            self::TYPE_FILE_OPERATION => 500,
            default => 500
        };
    }

    /**
     * Get default user message for exception type
     */
    protected function getDefaultUserMessage(): string
    {
        return match ($this->type) {
            self::TYPE_VALIDATION => 'The provided data is invalid.',
            self::TYPE_AUTHORIZATION => 'You do not have permission to perform this action.',
            self::TYPE_RATE_LIMIT => 'Too many requests. Please wait before trying again.',
            self::TYPE_BUSINESS_LOGIC => 'The operation could not be completed.',
            self::TYPE_EXTERNAL_API => 'An external service is currently unavailable.',
            self::TYPE_DATABASE => 'A database error occurred.',
            self::TYPE_FILE_OPERATION => 'A file operation failed.',
            default => 'An unexpected error occurred.'
        };
    }

    /**
     * Get error title for display
     */
    protected function getErrorTitle(): string
    {
        return match ($this->type) {
            self::TYPE_VALIDATION => 'Validation Error',
            self::TYPE_AUTHORIZATION => 'Access Denied',
            self::TYPE_RATE_LIMIT => 'Rate Limit Exceeded',
            self::TYPE_BUSINESS_LOGIC => 'Operation Failed',
            self::TYPE_EXTERNAL_API => 'Service Unavailable',
            self::TYPE_DATABASE => 'Database Error',
            self::TYPE_FILE_OPERATION => 'File Operation Failed',
            default => 'System Error'
        };
    }

    /**
     * Check if operation can be retried
     */
    protected function canRetry(): bool
    {
        return !in_array($this->type, [
            self::TYPE_VALIDATION,
            self::TYPE_AUTHORIZATION
        ]);
    }

    /**
     * Report critical errors to monitoring service
     */
    protected function reportToCriticalMonitoring(array $context): void
    {
        // In a real application, this would integrate with services like:
        // - Sentry
        // - Bugsnag
        // - Rollbar
        // - New Relic
        // - Custom monitoring endpoints

        // For now, just log to a separate critical error file
        Log::channel('emergency')->critical('Critical admin error', $context);
    }

    /**
     * Get formatted context for debugging
     */
    public function getFormattedContext(): string
    {
        return json_encode([
            'type' => $this->type,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ], JSON_PRETTY_PRINT);
    }
}