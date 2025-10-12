<?php

namespace App\Services;

/**
 * BreadcrumbService - Generates breadcrumb navigation items
 * 
 * Security: XSS prevention via route() helper and HTML escaping in Blade
 * 
 * Usage in controller:
 * $breadcrumbs = BreadcrumbService::generate([
 *     ['label' => 'Movies', 'route' => 'admin.movies.index'],
 *     ['label' => 'Edit Movie']
 * ]);
 * 
 * Usage in view:
 * @include('admin.components.breadcrumbs', ['items' => $breadcrumbs])
 */
class BreadcrumbService
{
    /**
     * Generate breadcrumb items from array
     * 
     * @param array $items Array of breadcrumb items
     *                     Format: [['label' => 'Text', 'route' => 'route.name', 'params' => [], 'icon' => 'fas fa-icon']]
     * @return array Formatted breadcrumb items
     */
    public static function generate(array $items): array
    {
        $breadcrumbs = [];

        foreach ($items as $item) {
            $breadcrumb = [
                'label' => $item['label'] ?? '',
                'icon' => $item['icon'] ?? null,
                'url' => null
            ];

            // Generate URL from route if provided
            if (isset($item['route'])) {
                $params = $item['params'] ?? [];
                try {
                    $breadcrumb['url'] = route($item['route'], $params);
                } catch (\Exception $e) {
                    // Route not found, skip URL
                    $breadcrumb['url'] = null;
                }
            } elseif (isset($item['url'])) {
                $breadcrumb['url'] = $item['url'];
            }

            $breadcrumbs[] = $breadcrumb;
        }

        return $breadcrumbs;
    }

    /**
     * Generate breadcrumbs for Movies section
     * 
     * @param string $action Action type: 'index', 'create', 'edit', 'show'
     * @param mixed $movie Movie model instance (for edit/show)
     * @return array
     */
    public static function forMovies(string $action, $movie = null): array
    {
        $items = [];

        switch ($action) {
            case 'index':
                $items = [
                    ['label' => 'Movies', 'icon' => 'fas fa-film']
                ];
                break;

            case 'create':
                $items = [
                    ['label' => 'Movies', 'route' => 'admin.movies.index', 'icon' => 'fas fa-film'],
                    ['label' => 'Add New Movie', 'icon' => 'fas fa-plus']
                ];
                break;

            case 'edit':
                $movieTitle = $movie ? substr($movie->title, 0, 30) : 'Edit';
                $items = [
                    ['label' => 'Movies', 'route' => 'admin.movies.index', 'icon' => 'fas fa-film'],
                    ['label' => 'Edit: ' . $movieTitle, 'icon' => 'fas fa-edit']
                ];
                break;

            case 'show':
                $movieTitle = $movie ? substr($movie->title, 0, 30) : 'View';
                $items = [
                    ['label' => 'Movies', 'route' => 'admin.movies.index', 'icon' => 'fas fa-film'],
                    ['label' => $movieTitle, 'icon' => 'fas fa-eye']
                ];
                break;

            case 'sources':
                $movieTitle = $movie ? substr($movie->title, 0, 30) : 'Sources';
                $items = [
                    ['label' => 'Movies', 'route' => 'admin.movies.index', 'icon' => 'fas fa-film'],
                    ['label' => $movieTitle, 'route' => 'admin.movies.show', 'params' => [$movie->id], 'icon' => 'fas fa-eye'],
                    ['label' => 'Manage Sources', 'icon' => 'fas fa-link']
                ];
                break;
        }

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for Series section
     * 
     * @param string $action Action type
     * @param mixed $series Series model instance
     * @param mixed $season Season model instance
     * @param mixed $episode Episode model instance
     * @return array
     */
    public static function forSeries(string $action, $series = null, $season = null, $episode = null): array
    {
        $items = [];

        switch ($action) {
            case 'index':
                $items = [
                    ['label' => 'Series', 'icon' => 'fas fa-tv']
                ];
                break;

            case 'create':
                $items = [
                    ['label' => 'Series', 'route' => 'admin.series.index', 'icon' => 'fas fa-tv'],
                    ['label' => 'Add New Series', 'icon' => 'fas fa-plus']
                ];
                break;

            case 'edit':
                $seriesTitle = $series ? substr($series->title, 0, 30) : 'Edit';
                $items = [
                    ['label' => 'Series', 'route' => 'admin.series.index', 'icon' => 'fas fa-tv'],
                    ['label' => 'Edit: ' . $seriesTitle, 'icon' => 'fas fa-edit']
                ];
                break;

            case 'show':
                $seriesTitle = $series ? substr($series->title, 0, 30) : 'View';
                $items = [
                    ['label' => 'Series', 'route' => 'admin.series.index', 'icon' => 'fas fa-tv'],
                    ['label' => $seriesTitle, 'icon' => 'fas fa-eye']
                ];
                break;

            case 'season':
                $seriesTitle = $series ? substr($series->title, 0, 25) : 'Series';
                $seasonNumber = $season ? 'Season ' . $season->season_number : 'Season';
                $items = [
                    ['label' => 'Series', 'route' => 'admin.series.index', 'icon' => 'fas fa-tv'],
                    ['label' => $seriesTitle, 'route' => 'admin.series.show', 'params' => [$series->id]],
                    ['label' => $seasonNumber, 'icon' => 'fas fa-folder']
                ];
                break;

            case 'episode':
                $seriesTitle = $series ? substr($series->title, 0, 20) : 'Series';
                $seasonNumber = $season ? 'S' . $season->season_number : 'Season';
                $episodeNumber = $episode ? 'E' . $episode->episode_number : 'Episode';
                $items = [
                    ['label' => 'Series', 'route' => 'admin.series.index', 'icon' => 'fas fa-tv'],
                    ['label' => $seriesTitle, 'route' => 'admin.series.show', 'params' => [$series->id]],
                    ['label' => $seasonNumber . ' ' . $episodeNumber, 'icon' => 'fas fa-video']
                ];
                break;
        }

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for Users section
     */
    public static function forUsers(string $action, $user = null): array
    {
        $items = [];

        switch ($action) {
            case 'index':
                $items = [
                    ['label' => 'Users', 'icon' => 'fas fa-users']
                ];
                break;

            case 'show':
            case 'edit':
                $userName = $user ? substr($user->name, 0, 30) : 'User';
                $actionLabel = $action === 'edit' ? 'Edit' : 'View';
                $items = [
                    ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'fas fa-users'],
                    ['label' => $actionLabel . ': ' . $userName, 'icon' => 'fas fa-user']
                ];
                break;
        }

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for Invite Codes section
     */
    public static function forInviteCodes(string $action): array
    {
        $items = [];

        switch ($action) {
            case 'index':
                $items = [
                    ['label' => 'Invite Codes', 'icon' => 'fas fa-ticket-alt']
                ];
                break;

            case 'create':
                $items = [
                    ['label' => 'Invite Codes', 'route' => 'admin.invite-codes.index', 'icon' => 'fas fa-ticket-alt'],
                    ['label' => 'Generate Code', 'icon' => 'fas fa-plus']
                ];
                break;
        }

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for Reports section
     */
    public static function forReports(string $action, $report = null): array
    {
        $items = [];

        switch ($action) {
            case 'index':
                $items = [
                    ['label' => 'Reports', 'icon' => 'fas fa-exclamation-triangle']
                ];
                break;

            case 'show':
                $reportId = $report ? '#' . $report->id : 'Report';
                $items = [
                    ['label' => 'Reports', 'route' => 'admin.reports.index', 'icon' => 'fas fa-exclamation-triangle'],
                    ['label' => 'Report ' . $reportId, 'icon' => 'fas fa-eye']
                ];
                break;
        }

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for Logs section
     */
    public static function forLogs(string $action, $log = null): array
    {
        $items = [];

        switch ($action) {
            case 'index':
                $items = [
                    ['label' => 'Admin Logs', 'icon' => 'fas fa-clipboard-list']
                ];
                break;

            case 'show':
                $logId = $log ? '#' . $log->id : 'Log';
                $items = [
                    ['label' => 'Admin Logs', 'route' => 'admin.logs.index', 'icon' => 'fas fa-clipboard-list'],
                    ['label' => 'Log ' . $logId, 'icon' => 'fas fa-eye']
                ];
                break;
        }

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for TMDB Import section
     */
    public static function forTMDB(string $type = 'movies'): array
    {
        $label = $type === 'movies' ? 'Import Movies' : 'Import Series';
        $icon = $type === 'movies' ? 'fas fa-film' : 'fas fa-tv';

        $items = [
            ['label' => $label, 'icon' => $icon],
            ['label' => 'TMDB Import', 'icon' => 'fas fa-download']
        ];

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for User Activity section
     */
    public static function forUserActivity(string $action = 'index'): array
    {
        $items = [
            ['label' => 'User Activity', 'icon' => 'fas fa-chart-line']
        ];

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for Ban History section
     */
    public static function forBanHistory(string $action = 'index'): array
    {
        $items = [
            ['label' => 'Ban History', 'icon' => 'fas fa-gavel']
        ];

        return self::generate($items);
    }

    /**
     * Generate breadcrumbs for Roles & Permissions section
     */
    public static function forRoles(string $action, $role = null): array
    {
        $items = [];

        switch ($action) {
            case 'index':
                $items = [
                    ['label' => 'Roles & Permissions', 'icon' => 'fas fa-shield-alt']
                ];
                break;

            case 'create':
                $items = [
                    ['label' => 'Roles & Permissions', 'route' => 'admin.roles.index', 'icon' => 'fas fa-shield-alt'],
                    ['label' => 'Create Role', 'icon' => 'fas fa-plus']
                ];
                break;

            case 'edit':
                $roleName = $role ? $role->name : 'Role';
                $items = [
                    ['label' => 'Roles & Permissions', 'route' => 'admin.roles.index', 'icon' => 'fas fa-shield-alt'],
                    ['label' => 'Edit: ' . $roleName, 'icon' => 'fas fa-edit']
                ];
                break;
        }

        return self::generate($items);
    }
}
