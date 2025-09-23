<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoSqlInjectionRule implements ValidationRule
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

        // Convert to lowercase for case-insensitive checking
        $lowerValue = strtolower($value);

        // SQL injection patterns to detect
        $sqlPatterns = [
            // SQL keywords
            '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',

            // SQL operators and functions
            '/\b(and|or|not|like|in|exists|having|group\s+by|order\s+by)\b/i',

            // SQL comment patterns
            '/--/',
            '/\/\*.*?\*\//',

            // SQL injection characters
            '/[\'";]/',

            // Hex encoding attempts
            '/0x[0-9a-f]+/i',

            // UNION-based injection
            '/union\s+(all\s+)?select/i',

            // Boolean-based injection
            '/(\s+(and|or)\s+)?[\'"]?\w*[\'"]?\s*(=|!=|<>|>|<)\s*[\'"]?\w*[\'"]?(\s+(and|or)\s+)?/i',

            // Time-based injection
            '/\b(sleep|benchmark|waitfor|delay)\s*\(/i',

            // Error-based injection
            '/\b(extractvalue|updatexml|exp)\s*\(/i',

            // Stored procedures
            '/\bsp_\w+/i',
            '/\bxp_\w+/i',

            // Database functions
            '/\b(version|user|database|schema|table_name|column_name)\s*\(/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail('The :attribute contains potentially malicious content.');
                return;
            }
        }

        // Check for multiple consecutive special characters (common in injection)
        if (preg_match('/[\'";]{2,}/', $value)) {
            $fail('The :attribute contains invalid character sequences.');
            return;
        }

        // Check for suspicious URL-encoded content
        if (preg_match('/%[0-9a-f]{2}/i', $value)) {
            $decoded = urldecode($value);
            if ($decoded !== $value) {
                // Recursively check decoded content
                $this->validate($attribute, $decoded, $fail);
            }
        }
    }
}