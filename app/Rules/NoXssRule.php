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

        // ENHANCED XSS patterns to detect
        $xssPatterns = [
            // Script tags (enhanced)
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<script[^>]*>.*?<\/script>/si',
            '/&lt;script/i',
            '/%3Cscript/i',

            // JavaScript in various contexts (enhanced)
            '/javascript:/i',
            '/vbscript:/i',
            '/data:text\/html/i',
            '/data:application\/x-javascript/i',
            '/data:text\/javascript/i',

            // Event handlers (enhanced)
            '/on\w+\s*=/i',
            '/on(load|error|click|mouse|key|focus|blur|change|submit|resize)\s*=/i',

            // Dangerous HTML tags (enhanced)
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<applet\b/i',
            '/<meta\b/i',
            '/<link\b/i',
            '/<style\b/i',
            '/<form\b/i',
            '/<input\b.*type\s*=\s*["\']?hidden/i',

            // CSS injection vectors
            '/expression\s*\(/i',
            '/url\s*\(/i',
            '/@import/i',
            '/behavior\s*:/i',
            '/-moz-binding/i',
            '/binding\s*:/i',

            // HTML entities that could be XSS (enhanced)
            '/&#x?[0-9a-f]+;/i',
            '/&\w+;.*<.*>/i',

            // Modern XSS vectors
            '/\$\{.*\}/i', // Template literals
            '/`.*\$\{.*\}.*`/i', // Template literal syntax
            '/<\w+.*\son\w+\s*=/i', // Generic event handler detection
            
            // Web Components XSS
            '/<[\w-]+\b[^>]*on\w+/i',
            
            // SVG XSS (enhanced)
            '/<svg\b/i',
            '/<use\b/i',
            '/<foreignObject\b/i',
            
            // CSS data URLs
            '/background\s*:\s*url\s*\(\s*["\']?data:/i',
            '/content\s*:\s*["\'].*<.*>/i',
            
            // DOM clobbering
            '/<\w+\s+name\s*=\s*["\']?(constructor|prototype|__proto__|valueOf)/i',
            
            // JSONP XSS
            '/callback\s*=\s*\w+/i',
            
            // AngularJS XSS
            '/\{\{.*\}\}/i',
            '/ng-\w+\s*=/i',
            
            // React XSS
            '/dangerouslySetInnerHTML/i',
            
            // Common bypasses (enhanced)
            '/&lt;.*&gt;/i',
            '/%3C.*%3E/i',
            '/\\u00[0-9a-f]{2}/i',
            '/\\x[0-9a-f]{2}/i',
            
            // Attribute injection (enhanced)
            '/style\s*=.*expression/i',
            '/background.*expression/i',
            '/href\s*=\s*["\']?javascript:/i',
            '/src\s*=\s*["\']?javascript:/i',
            
            // XML/XHTML injections (enhanced)
            '/<!\[CDATA\[/i',
            '/<\?xml/i',
            '/<!DOCTYPE/i',
            
            // Markdown XSS
            '/!\[.*\]\(javascript:/i',
            '/\[.*\]\(javascript:/i',
            
            // Protocol handlers
            '/mhtml:/i',
            '/jar:/i',
            '/view-source:/i',

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