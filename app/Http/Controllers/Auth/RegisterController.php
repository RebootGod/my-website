<?php

// ========================================
// 2. REGISTER CONTROLLER
// ========================================
// File: app/Http/Controllers/Auth/RegisterController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InviteCode;
use App\Models\UserRegistration;
use App\Rules\StrongPasswordRule;
use App\Rules\NoXssRule;
use App\Rules\NoSqlInjectionRule;
use App\Jobs\SendWelcomeEmailJob;
use App\Notifications\WelcomeNotification;
use App\Notifications\NewUserRegisteredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:20', 'unique:users', 'regex:/^[a-zA-Z0-9_]+$/', new NoXssRule(), new NoSqlInjectionRule()],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', new NoXssRule(), new NoSqlInjectionRule()],
            'password' => ['required', 'string', 'confirmed', new StrongPasswordRule()],
            'invite_code' => ['required', 'string', 'max:50', new NoXssRule(), new NoSqlInjectionRule()],
        ], [
            'username.regex' => 'Username hanya boleh mengandung huruf, angka, dan underscore.',
            'username.unique' => 'Username sudah digunakan.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'invite_code.required' => 'Invite code wajib diisi.',
            'invite_code.max' => 'Invite code terlalu panjang.',
        ]);

        // Sanitize input data to prevent any remaining security issues
        $sanitizedData = [
            'username' => strip_tags(trim($request->username)),
            'email' => filter_var(trim($request->email), FILTER_SANITIZE_EMAIL),
            'invite_code' => strip_tags(trim($request->invite_code)),
        ];

        // Double-check sanitized data
        if (!filter_var($sanitizedData['email'], FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors([
                'email' => 'Format email tidak valid.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }

        // Check invite code validity (case sensitive) using sanitized data
        $inviteCode = InviteCode::whereRaw('BINARY code = ?', [$sanitizedData['invite_code']])->first();

        if (!$inviteCode || !$inviteCode->isValid()) {
            return back()->withErrors([
                'invite_code' => !$inviteCode ? 'Invite code tidak ditemukan.' : 'Invite code sudah expired atau mencapai batas maksimal penggunaan.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }

        DB::beginTransaction();

        try {
            // Create user using sanitized data
            $user = User::create([
                'username' => $sanitizedData['username'],
                'email' => $sanitizedData['email'],
                'password' => Hash::make($request->password),
                'role' => config('app.default_user_role', 'member'),
                'status' => 'active',
            ]);

            // Record registration
            UserRegistration::create([
                'user_id' => $user->id,
                'invite_code_id' => $inviteCode->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Increment invite code usage
            $inviteCode->incrementUsage();

            DB::commit();

            // Auto login after registration
            Auth::login($user);
            $user->updateLastLogin();

            // Log user registration and login activity
            $activityService = app(\App\Services\UserActivityService::class);
            $activityService->logRegistration($user);
            $activityService->logLogin($user);

            // Dispatch welcome email job (queued, non-blocking)
            try {
                SendWelcomeEmailJob::dispatch($user, $sanitizedData['invite_code']);
                Log::info('Welcome email job dispatched', ['user_id' => $user->id]);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch welcome email job', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail registration if email dispatch fails
            }

            // Send welcome notification (queued)
            try {
                $user->notify(new WelcomeNotification($sanitizedData['invite_code']));
                Log::info('Welcome notification dispatched', ['user_id' => $user->id]);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch welcome notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Notify admins of new user registration (queued)
            try {
                $admins = User::whereHas('role', function ($query) {
                    $query->whereIn('name', ['Admin', 'Super Admin']);
                })->get();

                // Fallback to legacy role column if no role-based admins
                if ($admins->isEmpty()) {
                    $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
                }

                foreach ($admins as $admin) {
                    $admin->notify(new NewUserRegisteredNotification($user, $sanitizedData['invite_code']));
                }

                Log::info('Admin notifications dispatched', [
                    'user_id' => $user->id,
                    'admins_count' => $admins->count()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to notify admins', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            return redirect()->route('home')->with('success', 'Selamat datang di Noobz Cinema!');

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }
    }

    public function checkInviteCode(Request $request)
    {
        // SECURITY: CSRF Protection for AJAX requests
        if (!$request->ajax()) {
            abort(404);
        }

        // SECURITY: Rate limiting to prevent brute force attacks
        $key = 'invite-check:' . $request->ip();
        if (!RateLimiter::attempt($key, 5, function() { return true; }, 300)) {
            return response()->json([
                'valid' => false, 
                'message' => 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.',
                'rate_limited' => true
            ], 429);
        }

        // Validate and sanitize the invite code input
        $request->validate([
            'code' => ['required', 'string', 'max:50', new NoXssRule(), new NoSqlInjectionRule()],
        ]);

        $code = strip_tags(trim($request->get('code')));
        
        // SECURITY: Add timing attack protection
        usleep(random_int(200000, 500000)); // 0.2-0.5 second random delay

        $inviteCode = InviteCode::whereRaw('BINARY code = ?', [$code])->first();

        // SECURITY: Generic error messages to prevent enumeration
        if (!$inviteCode || !$inviteCode->isValid()) {
            return response()->json([
                'valid' => false, 
                'message' => 'Invite code tidak valid atau sudah expired.'
            ]);
        }

        return response()->json(['valid' => true, 'message' => 'Invite code valid.']);
    }
}