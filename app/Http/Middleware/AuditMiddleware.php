<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log successful admin actions (2xx responses)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->logAdminAction($request);
        }

        return $response;
    }

    /**
     * Log admin actions
     */
    private function logAdminAction(Request $request): void
    {
        $route = $request->route();
        if (!$route) return;

        $routeName = $route->getName();
        $method = $request->method();
        $url = $request->url();

        // Skip logging for read-only operations
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return;
        }

        // Map route patterns to descriptions
        $actionDescriptions = [
            // User management
            'admin.users.store' => 'Created new user',
            'admin.users.update' => 'Updated user profile',
            'admin.users.destroy' => 'Deleted user account',
            'admin.users.bulk' => 'Performed bulk user operation',

            // Movie management
            'admin.movies.store' => 'Created new movie',
            'admin.movies.update' => 'Updated movie details',
            'admin.movies.destroy' => 'Deleted movie',
            'admin.movies.bulk' => 'Performed bulk movie operation',

            // Series management
            'admin.series.store' => 'Created new series',
            'admin.series.update' => 'Updated series details',
            'admin.series.destroy' => 'Deleted series',

            // TMDB operations
            'admin.tmdb.import' => 'Imported content from TMDB',
            'admin.tmdb.search' => 'Searched TMDB database',

            // Settings
            'admin.settings.update' => 'Updated system settings',
            'admin.config.update' => 'Updated configuration',
        ];

        $description = $actionDescriptions[$routeName] ?? "Admin action: {$method} {$url}";

        // Get additional context from request
        $context = [
            'route' => $routeName,
            'method' => $method,
            'parameters' => $route->parameters(),
        ];

        // Add form data for context (excluding sensitive fields)
        $formData = $request->except(['password', 'password_confirmation', '_token', '_method']);
        if (!empty($formData)) {
            $context['form_data'] = $formData;
        }

        AuditLogger::log(
            'admin_action',
            $description,
            null,
            null,
            $context,
            $request
        );
    }
}