<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard Widget Service
 * 
 * Manages dashboard widgets, layouts, and user preferences
 * Max 350 lines per workinginstruction.md
 * 
 * Features:
 * - Widget management (available widgets list)
 * - User widget preferences (save/load)
 * - Widget ordering and visibility
 * - Layout customization
 */
class DashboardWidgetService
{
    /**
     * Available widgets configuration
     */
    protected array $availableWidgets = [
        'stats-movies' => [
            'id' => 'stats-movies',
            'name' => 'Total Movies',
            'description' => 'Display total movies count',
            'component' => 'admin.widgets.stat-card',
            'category' => 'stats',
            'icon' => 'fas fa-film',
            'defaultOrder' => 1,
            'defaultVisible' => true,
            'size' => 'small'
        ],
        'stats-users' => [
            'id' => 'stats-users',
            'name' => 'Total Users',
            'description' => 'Display total users count',
            'component' => 'admin.widgets.stat-card',
            'category' => 'stats',
            'icon' => 'fas fa-users',
            'defaultOrder' => 2,
            'defaultVisible' => true,
            'size' => 'small'
        ],
        'stats-active-users' => [
            'id' => 'stats-active-users',
            'name' => 'Active Users',
            'description' => 'Display active users count',
            'component' => 'admin.widgets.stat-card',
            'category' => 'stats',
            'icon' => 'fas fa-user-check',
            'defaultOrder' => 3,
            'defaultVisible' => true,
            'size' => 'small'
        ],
        'stats-series' => [
            'id' => 'stats-series',
            'name' => 'Total Series',
            'description' => 'Display total series count',
            'component' => 'admin.widgets.stat-card',
            'category' => 'stats',
            'icon' => 'fas fa-tv',
            'defaultOrder' => 4,
            'defaultVisible' => true,
            'size' => 'small'
        ],
        'stats-invite-codes' => [
            'id' => 'stats-invite-codes',
            'name' => 'Invite Codes',
            'description' => 'Display invite codes count',
            'component' => 'admin.widgets.stat-card',
            'category' => 'stats',
            'icon' => 'fas fa-ticket-alt',
            'defaultOrder' => 5,
            'defaultVisible' => true,
            'size' => 'small'
        ],
        'stats-pending-reports' => [
            'id' => 'stats-pending-reports',
            'name' => 'Pending Reports',
            'description' => 'Display pending reports count',
            'component' => 'admin.widgets.stat-card',
            'category' => 'stats',
            'icon' => 'fas fa-exclamation-triangle',
            'defaultOrder' => 6,
            'defaultVisible' => true,
            'size' => 'small'
        ],
        'chart-content-growth' => [
            'id' => 'chart-content-growth',
            'name' => 'Content Growth',
            'description' => 'Display content growth chart',
            'component' => 'admin.widgets.chart-widget',
            'category' => 'charts',
            'icon' => 'fas fa-chart-line',
            'defaultOrder' => 7,
            'defaultVisible' => true,
            'size' => 'large'
        ],
        'chart-user-activity' => [
            'id' => 'chart-user-activity',
            'name' => 'User Activity',
            'description' => 'Display user activity chart',
            'component' => 'admin.widgets.chart-widget',
            'category' => 'charts',
            'icon' => 'fas fa-chart-bar',
            'defaultOrder' => 8,
            'defaultVisible' => true,
            'size' => 'large'
        ],
        'top-content' => [
            'id' => 'top-content',
            'name' => 'Top Content',
            'description' => 'Display top movies and series',
            'component' => 'admin.widgets.top-content',
            'category' => 'content',
            'icon' => 'fas fa-trophy',
            'defaultOrder' => 9,
            'defaultVisible' => true,
            'size' => 'medium'
        ],
        'activity-feed' => [
            'id' => 'activity-feed',
            'name' => 'Activity Feed',
            'description' => 'Display recent activity',
            'component' => 'admin.widgets.activity-feed',
            'category' => 'content',
            'icon' => 'fas fa-stream',
            'defaultOrder' => 10,
            'defaultVisible' => true,
            'size' => 'medium'
        ],
        'quick-actions' => [
            'id' => 'quick-actions',
            'name' => 'Quick Actions',
            'description' => 'Display quick action buttons',
            'component' => 'admin.widgets.quick-actions',
            'category' => 'actions',
            'icon' => 'fas fa-bolt',
            'defaultOrder' => 11,
            'defaultVisible' => true,
            'size' => 'full'
        ]
    ];

    /**
     * Get all available widgets
     * 
     * @return array
     */
    public function getAvailableWidgets(): array
    {
        return $this->availableWidgets;
    }

    /**
     * Get widgets by category
     * 
     * @param string $category
     * @return array
     */
    public function getWidgetsByCategory(string $category): array
    {
        return array_filter($this->availableWidgets, function($widget) use ($category) {
            return $widget['category'] === $category;
        });
    }

    /**
     * Get user widget preferences
     * 
     * @param int|null $userId
     * @return array
     */
    public function getUserWidgetPreferences(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return $this->getDefaultLayout();
        }

        $cacheKey = "dashboard_widgets_user_{$userId}";
        
        return Cache::remember($cacheKey, 3600, function() use ($userId) {
            // In production, this would fetch from database
            // For now, return default layout
            return $this->getDefaultLayout();
        });
    }

    /**
     * Save user widget preferences
     * 
     * @param array $preferences
     * @param int|null $userId
     * @return bool
     */
    public function saveUserWidgetPreferences(array $preferences, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return false;
        }

        // Validate preferences
        $validated = $this->validatePreferences($preferences);
        
        $cacheKey = "dashboard_widgets_user_{$userId}";
        Cache::put($cacheKey, $validated, 3600);
        
        // In production, this would save to database
        // For Phase 2.3, using cache is sufficient
        
        return true;
    }

    /**
     * Get default widget layout
     * 
     * @return array
     */
    protected function getDefaultLayout(): array
    {
        $layout = [];
        
        foreach ($this->availableWidgets as $widget) {
            $layout[] = [
                'id' => $widget['id'],
                'order' => $widget['defaultOrder'],
                'visible' => $widget['defaultVisible'],
                'size' => $widget['size']
            ];
        }

        // Sort by order
        usort($layout, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $layout;
    }

    /**
     * Validate widget preferences
     * 
     * @param array $preferences
     * @return array
     */
    protected function validatePreferences(array $preferences): array
    {
        $validated = [];
        $validWidgetIds = array_keys($this->availableWidgets);

        foreach ($preferences as $pref) {
            // Must have id
            if (!isset($pref['id'])) {
                continue;
            }

            // Must be valid widget ID
            if (!in_array($pref['id'], $validWidgetIds)) {
                continue;
            }

            $validated[] = [
                'id' => $pref['id'],
                'order' => isset($pref['order']) ? (int) $pref['order'] : 0,
                'visible' => isset($pref['visible']) ? (bool) $pref['visible'] : true,
                'size' => isset($pref['size']) && in_array($pref['size'], ['small', 'medium', 'large', 'full']) 
                    ? $pref['size'] 
                    : $this->availableWidgets[$pref['id']]['size']
            ];
        }

        return $validated;
    }

    /**
     * Reset user widget preferences to default
     * 
     * @param int|null $userId
     * @return bool
     */
    public function resetUserWidgetPreferences(?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return false;
        }

        $cacheKey = "dashboard_widgets_user_{$userId}";
        Cache::forget($cacheKey);
        
        return true;
    }

    /**
     * Get widget configuration by ID
     * 
     * @param string $widgetId
     * @return array|null
     */
    public function getWidgetConfig(string $widgetId): ?array
    {
        return $this->availableWidgets[$widgetId] ?? null;
    }

    /**
     * Check if widget exists
     * 
     * @param string $widgetId
     * @return bool
     */
    public function widgetExists(string $widgetId): bool
    {
        return isset($this->availableWidgets[$widgetId]);
    }

    /**
     * Get widgets for rendering
     * 
     * @param int|null $userId
     * @return array
     */
    public function getWidgetsForRendering(?int $userId = null): array
    {
        $preferences = $this->getUserWidgetPreferences($userId);
        $widgets = [];

        foreach ($preferences as $pref) {
            if (!$pref['visible']) {
                continue;
            }

            $config = $this->getWidgetConfig($pref['id']);
            if (!$config) {
                continue;
            }

            $widgets[] = array_merge($config, [
                'order' => $pref['order'],
                'currentSize' => $pref['size']
            ]);
        }

        // Sort by order
        usort($widgets, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $widgets;
    }
}
