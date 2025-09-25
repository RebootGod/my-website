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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
        // Validate and sanitize the invite code input
        $request->validate([
            'code' => ['required', 'string', 'max:50', new NoXssRule(), new NoSqlInjectionRule()],
        ]);

        $code = strip_tags(trim($request->get('code')));
        $inviteCode = InviteCode::whereRaw('BINARY code = ?', [$code])->first();

        if (!$inviteCode) {
            return response()->json(['valid' => false, 'message' => 'Invite code tidak ditemukan.']);
        }

        if (!$inviteCode->isValid()) {
            return response()->json(['valid' => false, 'message' => 'Invite code expired atau sudah mencapai limit.']);
        }

        return response()->json(['valid' => true, 'message' => 'Invite code valid.']);
    }
}