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
     * ENHANCED: Sanitize string input based on field type with comprehensive protection
     */
    private function sanitizeString(string $value, string $key): string
    {
        // SECURITY: Don't sanitize password fields to avoid affecting authentication
        if (in_array($key, ['password', 'password_confirmation', 'current_password', '_token'])) {
            return $value;
        }

        // SECURITY: Unicode normalization to prevent bypass attacks
        if (function_exists('normalizer_normalize')) {
            $value = normalizer_normalize($value, Normalizer::FORM_C);
        }

        // SECURITY: Remove null bytes and control characters
        $value = str_replace(["\0", "\r"], '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        // SECURITY: HTML entity decode then re-encode to prevent double encoding bypasses
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // SECURITY: Enhanced malicious pattern removal
        $maliciousPatterns = [
            // Script tags (comprehensive)
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/&lt;script\b[^&]*(?:(?!&lt;\/script&gt;)[^&]*)*&lt;\/script&gt;/mi',
            '/%3Cscript\b[^%]*(?:(?!%3C\/script%3E)[^%]*)*%3C\/script%3E/mi',
            
            // Dangerous tags
            '/<(iframe|object|embed|applet|form|meta|link|style)\b[^>]*>/mi',
            '/&lt;(iframe|object|embed|applet|form|meta|link|style)\b[^&]*&gt;/mi',
            
            // JavaScript protocols
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:\s*text\/html/i',
            '/data\s*:\s*application\/x-javascript/i',
            
            // Event handlers
            '/on\w+\s*=/i',
            '/&\w+;on\w+=/i',
            
            // CSS expressions
            '/expression\s*\(/i',
            '/@import/i',
            '/behavior\s*:/i',
            
            // Template literals and modern JS
            '/\$\{[^}]*\}/i',
            '/`[^`]*\$\{[^}]*\}[^`]*`/i',
            
            // Common XSS bypasses
            '/&\#x?[0-9a-f]+;/i',
            '/\\u[0-9a-f]{4}/i',
            '/\\x[0-9a-f]{2}/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        // SECURITY: Remove suspicious character sequences
        $suspiciousSequences = ['<%', '%>', '${', '#{', '{{', '}}', '[[', ']]'];
        foreach ($suspiciousSequences as $sequence) {
            $value = str_replace($sequence, '', $value);
        }

        // Trim whitespace
        $value = trim($value);

        // SECURITY: Field-specific sanitization
        switch ($key) {
            case 'username':
                // Only allow alphanumeric and underscore
                $value = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
                $value = substr($value, 0, 20);
                break;
                
            case 'email':
                // Basic email sanitization
                $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                $value = substr($value, 0, 255);
                break;
                
            case 'invite_code':
                // Remove special characters except hyphens and underscores
                $value = preg_replace('/[^a-zA-Z0-9_-]/', '', $value);
                $value = substr($value, 0, 50);
                break;
                
            case 'search':
            case 'q':
            case 'query':
            case 'search_term':
                // SECURITY: Enhanced SQL injection prevention for search
                $sqlPatterns = [
                    '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
                    '/[\'";]/',
                    '/--/',
                    '/\/\*.*?\*\//',
                    '/0x[0-9a-f]+/i',
                ];
                foreach ($sqlPatterns as $pattern) {
                    $value = preg_replace($pattern, '', $value);
                }
                $value = substr($value, 0, 255);
                break;
                
            case 'year':
            case 'rating':
            case 'page':
            case 'limit':
            case 'per_page':
            case 'id':
                // Only allow numeric values
                if (!is_numeric($value)) {
                    $value = '';
                } else {
                    $value = (string) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                }
                break;
                
            default:
                // General sanitization for other fields
                $value = substr($value, 0, 1000); // Prevent overly long inputs
                break;
        }

        return $value;
    }
}