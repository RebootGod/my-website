<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the admin panel functionality
    |
    */

    /**
     * Security Settings
     */
    'security' => [
        /**
         * Maximum login attempts before account lockout
         */
        'max_login_attempts' => env('ADMIN_MAX_LOGIN_ATTEMPTS', 5),

        /**
         * Account lockout duration in minutes
         */
        'lockout_duration' => env('ADMIN_LOCKOUT_DURATION', 15),

        /**
         * Session timeout in minutes (0 = no timeout)
         */
        'session_timeout' => env('ADMIN_SESSION_TIMEOUT', 120),

        /**
         * Force password reset after days (0 = never)
         */
        'password_expiry_days' => env('ADMIN_PASSWORD_EXPIRY_DAYS', 90),

        /**
         * Require two-factor authentication
         */
        'require_2fa' => env('ADMIN_REQUIRE_2FA', false),

        /**
         * IP whitelist for admin access (empty = all IPs allowed)
         */
        'ip_whitelist' => array_filter(explode(',', env('ADMIN_IP_WHITELIST', ''))),

        /**
         * Enable admin activity logging
         */
        'log_activity' => env('ADMIN_LOG_ACTIVITY', true),

        /**
         * Secure headers for admin pages
         */
        'security_headers' => [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ],
    ],

    /**
     * Rate Limiting Settings
     */
    'rate_limits' => [
        /**
         * Search operations per minute
         */
        'search' => [
            'max_attempts' => env('ADMIN_SEARCH_RATE_LIMIT', 30),
            'decay_minutes' => 1,
        ],

        /**
         * Bulk operations per 5 minutes
         */
        'bulk' => [
            'max_attempts' => env('ADMIN_BULK_RATE_LIMIT', 10),
            'decay_minutes' => 5,
        ],

        /**
         * Destructive operations per 15 minutes
         */
        'destructive' => [
            'max_attempts' => env('ADMIN_DESTRUCTIVE_RATE_LIMIT', 5),
            'decay_minutes' => 15,
        ],

        /**
         * API calls per minute
         */
        'api' => [
            'max_attempts' => env('ADMIN_API_RATE_LIMIT', 100),
            'decay_minutes' => 1,
        ],
    ],

    /**
     * Bulk Operations Settings
     */
    'bulk_operations' => [
        /**
         * Maximum items per bulk operation
         */
        'max_items' => env('ADMIN_BULK_MAX_ITEMS', 100),

        /**
         * Timeout for bulk operations in seconds
         */
        'timeout' => env('ADMIN_BULK_TIMEOUT', 300),

        /**
         * Enable bulk operation confirmations
         */
        'require_confirmation' => env('ADMIN_BULK_REQUIRE_CONFIRMATION', true),

        /**
         * Allowed bulk actions per resource
         */
        'allowed_actions' => [
            'movies' => ['publish', 'draft', 'archive', 'delete', 'update_quality'],
            'series' => ['publish', 'draft', 'archive', 'delete'],
            'users' => ['activate', 'deactivate', 'promote', 'demote', 'delete'],
            'invite_codes' => ['activate', 'deactivate', 'delete'],
        ],
    ],

    /**
     * File Upload Settings
     */
    'uploads' => [
        /**
         * Maximum file size in MB
         */
        'max_file_size' => env('ADMIN_MAX_FILE_SIZE', 10),

        /**
         * Allowed image types
         */
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],

        /**
         * Allowed document types
         */
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'txt'],

        /**
         * Upload directories
         */
        'directories' => [
            'movies' => 'uploads/movies',
            'series' => 'uploads/series',
            'users' => 'uploads/users',
            'temp' => 'uploads/temp',
        ],

        /**
         * Image processing settings
         */
        'image_processing' => [
            'poster_dimensions' => [300, 450],
            'thumbnail_dimensions' => [150, 225],
            'quality' => 85,
            'format' => 'webp',
        ],
    ],

    /**
     * Cache Settings
     */
    'cache' => [
        /**
         * Dashboard statistics cache duration in seconds
         */
        'dashboard_stats' => env('ADMIN_CACHE_DASHBOARD_STATS', 1800), // 30 minutes

        /**
         * Search results cache duration in seconds
         */
        'search_results' => env('ADMIN_CACHE_SEARCH_RESULTS', 300), // 5 minutes

        /**
         * User permissions cache duration in seconds
         */
        'user_permissions' => env('ADMIN_CACHE_USER_PERMISSIONS', 3600), // 1 hour

        /**
         * Navigation menu cache duration in seconds
         */
        'navigation' => env('ADMIN_CACHE_NAVIGATION', 86400), // 24 hours
    ],

    /**
     * External API Settings
     */
    'external_apis' => [
        /**
         * TMDB API settings
         */
        'tmdb' => [
            'timeout' => env('TMDB_TIMEOUT', 30),
            'retry_attempts' => env('TMDB_RETRY_ATTEMPTS', 3),
            'cache_duration' => env('TMDB_CACHE_DURATION', 3600),
            'rate_limit' => env('TMDB_RATE_LIMIT', 40), // requests per 10 seconds
        ],

        /**
         * Other external services
         */
        'image_processing' => [
            'timeout' => env('IMAGE_PROCESSING_TIMEOUT', 60),
            'retry_attempts' => env('IMAGE_PROCESSING_RETRY_ATTEMPTS', 2),
        ],
    ],

    /**
     * Monitoring & Logging Settings
     */
    'monitoring' => [
        /**
         * Enable performance monitoring
         */
        'performance_monitoring' => env('ADMIN_PERFORMANCE_MONITORING', true),

        /**
         * Log slow queries (in milliseconds)
         */
        'slow_query_threshold' => env('ADMIN_SLOW_QUERY_THRESHOLD', 1000),

        /**
         * Enable error tracking
         */
        'error_tracking' => env('ADMIN_ERROR_TRACKING', true),

        /**
         * Error notification settings
         */
        'error_notifications' => [
            'email' => env('ADMIN_ERROR_EMAIL'),
            'slack_webhook' => env('ADMIN_ERROR_SLACK_WEBHOOK'),
            'threshold' => env('ADMIN_ERROR_THRESHOLD', 10), // errors per hour
        ],

        /**
         * Activity log retention in days
         */
        'log_retention_days' => env('ADMIN_LOG_RETENTION_DAYS', 90),
    ],

    /**
     * Pagination Settings
     */
    'pagination' => [
        /**
         * Default items per page
         */
        'default_per_page' => env('ADMIN_DEFAULT_PER_PAGE', 20),

        /**
         * Maximum items per page
         */
        'max_per_page' => env('ADMIN_MAX_PER_PAGE', 100),

        /**
         * Available per page options
         */
        'per_page_options' => [10, 20, 50, 100],
    ],

    /**
     * UI/UX Settings
     */
    'ui' => [
        /**
         * Default admin theme
         */
        'theme' => env('ADMIN_THEME', 'dark'),

        /**
         * Enable animations
         */
        'animations' => env('ADMIN_ANIMATIONS', true),

        /**
         * Chart refresh interval in seconds
         */
        'chart_refresh_interval' => env('ADMIN_CHART_REFRESH_INTERVAL', 30),

        /**
         * Activity feed refresh interval in seconds
         */
        'activity_refresh_interval' => env('ADMIN_ACTIVITY_REFRESH_INTERVAL', 30),

        /**
         * Toast notification duration in milliseconds
         */
        'toast_duration' => env('ADMIN_TOAST_DURATION', 5000),
    ],

    /**
     * Development Settings
     */
    'development' => [
        /**
         * Enable debug mode for admin
         */
        'debug' => env('ADMIN_DEBUG', false),

        /**
         * Show query information
         */
        'show_queries' => env('ADMIN_SHOW_QUERIES', false),

        /**
         * Enable admin toolbar
         */
        'toolbar' => env('ADMIN_TOOLBAR', false),

        /**
         * Mock external APIs in development
         */
        'mock_external_apis' => env('ADMIN_MOCK_EXTERNAL_APIS', false),
    ],

    /**
     * Feature Flags
     */
    'features' => [
        /**
         * Enable advanced search
         */
        'advanced_search' => env('ADMIN_FEATURE_ADVANCED_SEARCH', true),

        /**
         * Enable bulk operations
         */
        'bulk_operations' => env('ADMIN_FEATURE_BULK_OPERATIONS', true),

        /**
         * Enable data visualization
         */
        'data_visualization' => env('ADMIN_FEATURE_DATA_VISUALIZATION', true),

        /**
         * Enable real-time updates
         */
        'realtime_updates' => env('ADMIN_FEATURE_REALTIME_UPDATES', false),

        /**
         * Enable API access
         */
        'api_access' => env('ADMIN_FEATURE_API_ACCESS', true),

        /**
         * Enable export functionality
         */
        'export' => env('ADMIN_FEATURE_EXPORT', true),

        /**
         * Enable import functionality
         */
        'import' => env('ADMIN_FEATURE_IMPORT', true),
    ],
];