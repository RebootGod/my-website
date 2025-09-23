<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Log an audit event
     */
    public static function log(
        string $action,
        string $description,
        $model = null,
        array $oldValues = null,
        array $newValues = null,
        Request $request = null
    ): void {
        $request = $request ?: request();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
        ]);
    }

    /**
     * Log user management actions
     */
    public static function logUserAction(string $action, $user, array $oldValues = null, array $newValues = null): void
    {
        $descriptions = [
            'created' => "Created user: {$user->username} ({$user->email})",
            'updated' => "Updated user: {$user->username} ({$user->email})",
            'deleted' => "Deleted user: {$user->username} ({$user->email})",
            'role_changed' => "Changed role for user: {$user->username}",
            'password_reset' => "Reset password for user: {$user->username}",
            'banned' => "Banned user: {$user->username}",
            'unbanned' => "Unbanned user: {$user->username}",
        ];

        self::log(
            $action,
            $descriptions[$action] ?? "User action: {$action}",
            $user,
            $oldValues,
            $newValues
        );
    }

    /**
     * Log movie management actions
     */
    public static function logMovieAction(string $action, $movie, array $oldValues = null, array $newValues = null): void
    {
        $descriptions = [
            'created' => "Created movie: {$movie->title}",
            'updated' => "Updated movie: {$movie->title}",
            'deleted' => "Deleted movie: {$movie->title}",
            'published' => "Published movie: {$movie->title}",
            'unpublished' => "Unpublished movie: {$movie->title}",
        ];

        self::log(
            $action,
            $descriptions[$action] ?? "Movie action: {$action}",
            $movie,
            $oldValues,
            $newValues
        );
    }

    /**
     * Log authentication events
     */
    public static function logAuthAction(string $action, $user = null, string $description = null): void
    {
        $user = $user ?: Auth::user();
        $descriptions = [
            'login' => "User logged in: {$user?->username}",
            'logout' => "User logged out: {$user?->username}",
            'failed_login' => "Failed login attempt",
            'password_changed' => "Password changed for user: {$user?->username}",
            'account_deleted' => "Account deleted: {$user?->username}",
        ];

        self::log(
            $action,
            $description ?: ($descriptions[$action] ?? "Auth action: {$action}"),
            $user
        );
    }

    /**
     * Log admin panel access
     */
    public static function logAdminAccess(string $page): void
    {
        self::log(
            'admin_access',
            "Accessed admin panel: {$page}",
        );
    }

    /**
     * Log security events
     */
    public static function logSecurityEvent(string $event, string $description, $relatedModel = null): void
    {
        self::log(
            "security_{$event}",
            $description,
            $relatedModel
        );
    }
}