<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\SecurityEventService;

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

        // ENHANCED SQL injection patterns to detect
        $sqlPatterns = [
            // SQL keywords
            '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute|truncate|replace)\b/i',

            // SQL operators and functions
            '/\b(and|or|not|like|in|exists|having|group\s+by|order\s+by|limit|offset|into|from|where)\b/i',

            // SQL comment patterns
            '/--/',
            '/\/\*.*?\*\//',
            '/#.*/',
            '/;.*--/',

            // SQL injection characters and sequences
            '/[\'";]/',
            '/\|\|/',
            '/&&/',

            // Hex and binary encoding attempts
            '/0x[0-9a-f]+/i',
            '/\bchar\s*\(/i',
            '/\bascii\s*\(/i',

            // UNION-based injection (enhanced)
            '/union\s+(all\s+)?select/i',
            '/\bunion\b.*\bselect\b/i',

            // Boolean-based injection (enhanced)
            '/(\s+(and|or)\s+)?[\'"]?\w*[\'"]?\s*(=|!=|<>|>|<|>=|<=)\s*[\'"]?\w*[\'"]?(\s+(and|or)\s+)?/i',
            '/\b(true|false)\b\s*(=|!=)/i',
            '/\d+\s*(=|!=)\s*\d+/i',

            // Time-based injection (enhanced)
            '/\b(sleep|benchmark|waitfor|delay|pg_sleep)\s*\(/i',
            '/\bif\s*\(\s*\w+\s*,\s*sleep\s*\(/i',

            // Error-based injection (enhanced)
            '/\b(extractvalue|updatexml|exp|floor|rand|count|group_concat)\s*\(/i',
            '/\b(convert|cast|try_convert)\s*\(/i',

            // Stored procedures (enhanced)
            '/\b(sp_|xp_|fn_|sys\.)\w+/i',

            // Database functions (enhanced)
            '/\b(version|user|database|schema|table_name|column_name|information_schema|current_user|session_user)\s*(\(|\b)/i',

            // NoSQL injection patterns
            '/\{\s*["\']?\$\w+["\']?\s*:/i',
            '/\{\s*["\']?\$ne["\']?\s*:/i',
            '/\{\s*["\']?\$regex["\']?\s*:/i',
            '/\{\s*["\']?\$where["\']?\s*:/i',
            '/\{\s*["\']?\$gt["\']?\s*:/i',
            '/\{\s*["\']?\$lt["\']?\s*:/i',

            // LDAP injection patterns
            '/\*\)\(.*=\*\)\)\(\|\(/i',
            '/\(\|\(\w+=\*\)/i',

            // XML injection patterns
            '/<!--\s*#\s*exec/i',
            '/<!ENTITY/i',
            '/<!\[CDATA\[/i',

            // Advanced bypass patterns
            '/\bCONCAT\s*\(/i',
            '/\bSUBSTRING\s*\(/i',
            '/\bLEFT\s*\(/i',
            '/\bRIGHT\s*\(/i',
            '/\bMID\s*\(/i',
            '/\bLEN\s*\(/i',
            '/\bCHAR_LENGTH\s*\(/i',

            // Encoding bypass attempts
            '/&#x?[0-9a-f]+;.*[\'";]/i',
            '/%[0-9a-f]{2}.*[\'";]/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                // SECURITY: Log SQL injection attempt
                app(SecurityEventService::class)->logInjectionAttempt(
                    'SQL Injection',
                    $value,
                    $attribute
                );
                
                $fail('The :attribute contains potentially malicious content.');
                return;
            }
        }

        // Check for multiple consecutive special characters (common in injection)
        if (preg_match('/[\'";]{2,}/', $value)) {
            // SECURITY: Log suspicious character sequence
            app(SecurityEventService::class)->logInjectionAttempt(
                'Character Sequence Injection',
                $value,
                $attribute
            );
            
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