<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPasswordRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Minimum 8 characters
        if (strlen($value) < 8) {
            $fail('Password harus memiliki minimal 8 karakter.');
            return;
        }

        // Maximum 128 characters (reasonable limit)
        if (strlen($value) > 128) {
            $fail('Password maksimal 128 karakter.');
            return;
        }

        // Must contain at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            $fail('Password harus mengandung minimal 1 huruf kecil (a-z).');
            return;
        }

        // Must contain at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $fail('Password harus mengandung minimal 1 huruf besar (A-Z).');
            return;
        }

        // Must contain at least one digit
        if (!preg_match('/[0-9]/', $value)) {
            $fail('Password harus mengandung minimal 1 angka (0-9).');
            return;
        }

        // Must contain at least one special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $value)) {
            $fail('Password harus mengandung minimal 1 karakter khusus (!@#$%^&* dll).');
            return;
        }

        // Check for common weak patterns
        $weakPatterns = [
            '/(.)\1{3,}/',           // 4 or more repeated characters
            '/123456/',              // Sequential numbers
            '/abcdef/',              // Sequential letters (lowercase)
            '/ABCDEF/',              // Sequential letters (uppercase)
            '/qwerty/i',             // Common keyboard patterns
            '/password/i',           // Contains "password"
            '/admin/i',              // Contains "admin"
            '/user/i',               // Contains "user"
            '/login/i',              // Contains "login"
        ];

        foreach ($weakPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail('Password tidak boleh mengandung pola yang mudah ditebak.');
                return;
            }
        }

        // Check for dictionary words (basic implementation)
        $commonWords = [
            'password', 'admin', 'user', 'login', 'welcome', 'hello',
            'indonesia', 'jakarta', 'bandung', 'surabaya', 'medan',
            'noobz', 'cinema', 'movie', 'film'
        ];

        foreach ($commonWords as $word) {
            if (stripos($value, $word) !== false) {
                $fail('Password tidak boleh mengandung kata yang mudah ditebak.');
                return;
            }
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Password harus memiliki minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan karakter khusus.';
    }

    /**
     * Get password requirements for display
     */
    public static function getRequirements(): array
    {
        return [
            'Minimal 8 karakter',
            'Maksimal 128 karakter', 
            'Minimal 1 huruf kecil (a-z)',
            'Minimal 1 huruf besar (A-Z)',
            'Minimal 1 angka (0-9)',
            'Minimal 1 karakter khusus (!@#$%^&* dll)',
            'Tidak mengandung pola yang mudah ditebak',
            'Tidak mengandung kata umum'
        ];
    }

    /**
     * Generate a sample strong password
     */
    public static function generateSamplePassword(): string
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        
        // Ensure at least one from each category
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Fill the rest randomly (minimum 8 total)
        $allChars = $lowercase . $uppercase . $numbers . $special;
        for ($i = 4; $i < 8; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }
}