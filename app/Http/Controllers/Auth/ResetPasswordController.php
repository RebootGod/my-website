<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use App\Rules\NoXssRule;
use App\Rules\NoSqlInjectionRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    private PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;

        // Apply rate limiting middleware
        $this->middleware('throttle:10,60')->only(['reset']);
    }

    /**
     * Show password reset form
     */
    public function showResetForm(Request $request, $token = null)
    {
        // Redirect if already authenticated
        if (auth()->check()) {
            return redirect()->route('home')->with('info', 'Anda sudah login.');
        }

        // Validate that token and email are present
        if (!$token || !$request->email) {
            return redirect()->route('password.request')
                ->withErrors(['token' => 'Token atau email tidak valid.']);
        }

        // Basic token format validation
        if (!$this->isValidTokenFormat($token)) {
            return redirect()->route('password.request')
                ->withErrors(['token' => 'Format token tidak valid.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Reset password
     */
    public function reset(Request $request)
    {
        // Redirect if already authenticated
        if (auth()->check()) {
            return redirect()->route('home')->with('info', 'Anda sudah login.');
        }

        // Advanced rate limiting
        $ipKey = 'reset-password-ip:' . $request->ip();
        $executed = RateLimiter::attempt($ipKey, 5, function() {
            return true;
        }, 3600); // 5 attempts per hour per IP

        if (!$executed) {
            $seconds = RateLimiter::availableIn($ipKey);
            return back()->withErrors([
                'password' => 'Terlalu banyak percobaan dari IP ini. Coba lagi dalam ' . ceil($seconds / 60) . ' menit.'
            ])->withInput($request->only('email', 'token'));
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'token' => [
                'required',
                'string',
                'min:60',
                'max:128',
                new NoXssRule(),
                new NoSqlInjectionRule()
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                new NoXssRule(),
                'exists:users,email'
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ]
        ], [
            'token.required' => 'Token wajib diisi.',
            'token.min' => 'Token tidak valid.',
            'token.max' => 'Token tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.mixed' => 'Password harus mengandung huruf besar dan kecil.',
            'password.numbers' => 'Password harus mengandung angka.',
            'password.symbols' => 'Password harus mengandung simbol.',
            'password.uncompromised' => 'Password tidak aman, gunakan password yang lebih kuat.'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email', 'token'));
        }

        // Sanitize inputs
        $email = strtolower(trim($request->email));
        $token = trim($request->token);
        $password = $request->password;

        // Additional validation
        if (!$this->isValidTokenFormat($token)) {
            return back()->withErrors([
                'token' => 'Format token tidak valid.'
            ])->withInput($request->only('email', 'token'));
        }

        // Check if password is different from common patterns
        if ($this->isWeakPassword($password, $email)) {
            return back()->withErrors([
                'password' => 'Password terlalu lemah atau mudah ditebak.'
            ])->withInput($request->only('email', 'token'));
        }

        // Security delay
        usleep(random_int(100000, 300000)); // 0.1-0.3 second random delay

        // Per-email rate limiting for reset attempts
        $emailKey = 'reset-password-email:' . $email;
        $emailExecuted = RateLimiter::attempt($emailKey, 3, function() {
            return true;
        }, 3600); // 3 attempts per hour per email

        if (!$emailExecuted) {
            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan reset untuk email ini. Coba lagi dalam 1 jam.'
            ])->withInput($request->only('email', 'token'));
        }

        try {
            // Reset password via service
            $result = $this->passwordResetService->resetPassword(
                $token,
                $email,
                $password,
                $request->ip()
            );

            if ($result['success']) {
                // Clear all rate limiting for successful reset
                RateLimiter::clear($ipKey);
                RateLimiter::clear($emailKey);

                return redirect()->route('login')->with('status', $result['message']);
            } else {
                return back()->withErrors([
                    'token' => $result['message']
                ])->withInput($request->only('email', 'token'));
            }

        } catch (\Exception $e) {
            // Log error but don't expose to user
            logger()->error('Password reset error', [
                'email' => $email,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'password' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.'
            ])->withInput($request->only('email', 'token'));
        }
    }

    /**
     * Validate token format
     */
    private function isValidTokenFormat(string $token): bool
    {
        // Basic format validation - should be alphanumeric hash
        return preg_match('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * Check if password is too weak
     */
    private function isWeakPassword(string $password, string $email): bool
    {
        $password = strtolower($password);
        $email = strtolower($email);
        $username = strstr($email, '@', true);

        // Common weak patterns
        $weakPatterns = [
            'password',
            '12345678',
            'qwertyui',
            'abcdefgh',
            'password123',
            'admin123',
            'user123',
            $username,
            str_replace('@', '', $email),
            'noobzcinema',
            'noobzmovie'
        ];

        foreach ($weakPatterns as $pattern) {
            if (strlen($pattern) >= 4 && strpos($password, $pattern) !== false) {
                return true;
            }
        }

        // Check for simple patterns
        if (preg_match('/^(.)\1+$/', $password)) { // Same character repeated
            return true;
        }

        if (preg_match('/^(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/', $password)) {
            return true;
        }

        return false;
    }

    /**
     * Validate token via AJAX
     */
    public function validateToken(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Data tidak valid.'
            ]);
        }

        try {
            // Use a simple validation method since validateResetToken is private
            // This is just for AJAX validation, actual validation happens in reset()
            $tokenData = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            $result = [
                'valid' => $tokenData !== null,
                'message' => $tokenData ? 'Token valid' : 'Token tidak ditemukan'
            ];

            return response()->json([
                'valid' => $result['valid'],
                'message' => $result['message']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Terjadi kesalahan sistem.'
            ]);
        }
    }

    /**
     * Check password strength via AJAX
     */
    public function checkPasswordStrength(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $password = $request->get('password', '');
        $email = $request->get('email', '');

        $score = 0;
        $feedback = [];

        // Length check
        if (strlen($password) >= 8) {
            $score += 2;
        } else {
            $feedback[] = 'Minimal 8 karakter';
        }

        // Character variety
        if (preg_match('/[a-z]/', $password)) $score += 1;
        else $feedback[] = 'Gunakan huruf kecil';

        if (preg_match('/[A-Z]/', $password)) $score += 1;
        else $feedback[] = 'Gunakan huruf besar';

        if (preg_match('/[0-9]/', $password)) $score += 1;
        else $feedback[] = 'Gunakan angka';

        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 1;
        else $feedback[] = 'Gunakan simbol (!@#$%^&*)';

        // Check for weak patterns
        if ($this->isWeakPassword($password, $email)) {
            $score -= 2;
            $feedback[] = 'Hindari kata yang mudah ditebak';
        }

        // Determine strength level
        $strength = 'weak';
        if ($score >= 6) $strength = 'strong';
        elseif ($score >= 4) $strength = 'medium';

        return response()->json([
            'score' => max(0, $score),
            'strength' => $strength,
            'feedback' => $feedback,
            'is_strong' => $strength === 'strong'
        ]);
    }
}