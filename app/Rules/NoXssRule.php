<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoXssRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        // XSS patterns to detect
        $xssPatterns = [
            // Script tags
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',

            // JavaScript in various contexts
            '/javascript:/i',
            '/vbscript:/i',
            '/data:text\/html/i',

            // Event handlers
            '/on\w+\s*=/i',

            // Iframe and other dangerous tags
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<applet\b/i',
            '/<meta\b/i',
            '/<link\b/i',
            '/<style\b/i',
            '/<form\b/i',

            // Common XSS vectors
            '/expression\s*\(/i',
            '/url\s*\(/i',
            '/@import/i',

            // HTML entities that could be XSS
            '/&#x?[0-9a-f]+;/i',

            // Base64 encoded content that might be malicious
            '/data:.*base64/i',

            // SVG XSS
            '/<svg\b/i',

            // Common bypasses
            '/&lt;script/i',
            '/&gt;.*&lt;/i',

            // Attribute injection
            '/style\s*=.*expression/i',
            '/background.*expression/i',

            // CSS injection
            '/behavior\s*:/i',
            '/-moz-binding/i',

            // XML/XHTML injections
            '/<!\[CDATA\[/i',
            '/<\?xml/i',

            // Markdown XSS (if processing markdown)
            '/!\[.*\]\(javascript:/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail('The :attribute contains potentially harmful content.');
                return;
            }
        }

        // Check for suspicious character sequences
        $suspiciousSequences = [
            '<%',
            '%>',
            '${',
            '#{',
            '{{',
            '}}',
            '[[',
            ']]',
        ];

        foreach ($suspiciousSequences as $sequence) {
            if (strpos($value, $sequence) !== false) {
                $fail('The :attribute contains invalid character sequences.');
                return;
            }
        }

        // Check for HTML tags in general (except in allowed contexts)
        if (!$this->isHtmlAllowed($attribute) && preg_match('/<[^>]+>/', $value)) {
            $fail('The :attribute cannot contain HTML tags.');
            return;
        }

        // Check for URL-encoded XSS attempts
        if (preg_match('/%[0-9a-f]{2}/i', $value)) {
            $decoded = urldecode($value);
            if ($decoded !== $value) {
                // Recursively check decoded content
                $this->validate($attribute, $decoded, $fail);
            }
        }
    }

    /**
     * Check if HTML is allowed for this attribute
     */
    private function isHtmlAllowed(string $attribute): bool
    {
        // Define fields where limited HTML might be allowed
        $htmlAllowedFields = [
            'description',
            'content',
            'bio',
            'about',
            'message',
        ];

        return in_array($attribute, $htmlAllowedFields);
    }
}