<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Authenticate Telegram Bot API Requests
 * 
 * Security:
 * - Validates Bearer token from bot requests
 * - Prevents unauthorized access to bot endpoints
 * - Rate limiting applied at route level
 * 
 * OWASP: Protected against unauthorized access
 * 
 * @package App\Http\Middleware
 */
class AuthenticateTelegramBot
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get token from Authorization header
        $token = $request->bearerToken();
        
        // Get expected token from environment
        $expectedToken = config('services.telegram_bot.token');
        
        // Validate token exists
        if (!$token || !$expectedToken) {
            \Log::warning('Bot API authentication failed: Missing token', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'has_token' => !empty($token),
                'has_config' => !empty($expectedToken)
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required'
                ]
            ], 401);
        }
        
        // Validate token matches (timing-safe comparison to prevent timing attacks)
        if (!hash_equals($expectedToken, $token)) {
            \Log::warning('Bot API authentication failed: Invalid token', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Invalid authentication token'
                ]
            ], 401);
        }
        
        // Log successful authentication
        \Log::info('Bot API request authenticated', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->path()
        ]);
        
        return $next($request);
    }
}
