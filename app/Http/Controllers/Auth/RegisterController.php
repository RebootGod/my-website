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
            'username' => 'required|string|min:3|max:20|unique:users|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', new StrongPasswordRule()],
            'invite_code' => 'required|string',
        ], [
            'username.regex' => 'Username hanya boleh mengandung huruf, angka, dan underscore.',
            'username.unique' => 'Username sudah digunakan.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'invite_code.required' => 'Invite code wajib diisi.',
        ]);

        // Check invite code validity (case sensitive)
        $inviteCode = InviteCode::whereRaw('BINARY code = ?', [$request->invite_code])->first();

        if (!$inviteCode || !$inviteCode->isValid()) {
            return back()->withErrors([
                'invite_code' => !$inviteCode ? 'Invite code tidak ditemukan.' : 'Invite code sudah expired atau mencapai batas maksimal penggunaan.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }

        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
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
        $code = $request->get('code');
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