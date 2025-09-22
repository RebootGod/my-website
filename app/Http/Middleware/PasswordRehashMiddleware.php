<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PasswordRehashMiddleware
{
    /**
     * Handle an incoming request and check for password rehashing needs.
     * This middleware automatically upgrades password hashes after successful login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only proceed if user is authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if password needs rehashing (only once per session to avoid repeated checks)
        if (!$request->session()->has('password_rehash_checked')) {
            
            // Mark as checked for this session
            $request->session()->put('password_rehash_checked', true);

            // Check if rehashing is needed
            if ($user->needsPasswordRehash()) {
                
                // We can only rehash if we have access to the plain password
                // This will be available during login process via session
                $plainPassword = $request->session()->get('user_plain_password');
                
                if ($plainPassword) {
                    try {
                        // Attempt to rehash the password
                        if ($user->rehashPassword($plainPassword)) {
                            Log::info('Password automatically rehashed for enhanced security', [
                                'user_id' => $user->id,
                                'username' => $user->username,
                                'ip_address' => $request->ip(),
                                'user_agent' => $request->userAgent(),
                                'timestamp' => now()
                            ]);

                            // Optional: Add a flash message to inform user
                            $request->session()->flash('info', 'Your password security has been automatically upgraded.');
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to rehash user password', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                            'ip_address' => $request->ip()
                        ]);
                    }
                    
                    // Clear the plain password from session for security
                    $request->session()->forget('user_plain_password');
                }
            }
        }

        return $next($request);
    }
}