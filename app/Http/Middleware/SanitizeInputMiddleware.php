<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInputMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize input data
        $this->sanitizeRequestData($request);

        return $next($request);
    }

    /**
     * Sanitize request data recursively
     */
    private function sanitizeRequestData(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array data
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value, $key);
            }
        }

        return $data;
    }

    /**
     * Sanitize string input based on field type
     */
    private function sanitizeString(string $value, string $key): string
    {
        // Don't sanitize password fields
        if (in_array($key, ['password', 'password_confirmation', 'current_password'])) {
            return $value;
        }

        // Basic XSS prevention
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        // Remove common malicious patterns
        $maliciousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
            '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
        ];

        foreach ($maliciousPatterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        // Trim whitespace
        $value = trim($value);

        // For search fields, allow some special characters but limit length
        if (in_array($key, ['search', 'q', 'query', 'search_term'])) {
            $value = substr($value, 0, 255);
            // Remove SQL injection patterns
            $value = str_replace(['--', ';', '/*', '*/', 'union', 'select', 'drop', 'delete', 'insert', 'update'], '', strtolower($value));
        }

        // For numeric fields, ensure they're actually numeric
        if (in_array($key, ['year', 'rating', 'page', 'limit', 'per_page', 'id'])) {
            if (!is_numeric($value)) {
                $value = '';
            }
        }

        // For email fields, validate format
        if ($key === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                // Don't modify invalid emails here, let validation handle it
            }
        }

        return $value;
    }
}