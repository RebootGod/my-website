<?php

// ========================================
// 3. AUTH MIDDLEWARE
// ========================================
// File: app/Http/Middleware/CheckUserStatus.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->status !== 'active') {
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'error' => 'Akun Anda telah di-suspend atau di-banned.'
                ]);
            }
        }

        return $next($request);
    }
}