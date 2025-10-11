import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',

                // Design System v2.0 (Foundation)
                'resources/css/design-system.css',
                'resources/css/utilities.css',

                // Layout styles and scripts (Phase 2)
                'resources/css/layouts/navigation.css',
                'resources/css/layouts/app.css',
                'resources/js/layouts/app.js',

                // Admin styles and scripts
                'resources/css/admin/admin-core.css',
                'resources/css/admin/admin-mobile.css',
                'resources/css/admin/admin-tables.css',
                'resources/css/admin/admin-dashboard.css',
                'resources/css/admin/admin-components.css',
                'resources/js/admin/admin-core.js',
                'resources/js/admin/admin-mobile.js',
                'resources/js/admin/admin-bulk.js',
                'resources/js/admin/admin-charts.js',

                // Page-specific styles and scripts
                'resources/css/pages/home.css',
                'resources/css/pages/movie-detail.css',
                'resources/css/pages/movie-detail-v2.css',
                'resources/css/pages/movie-detail-v3.css',
                'resources/css/pages/series-detail.css',
                'resources/css/pages/series-detail-v2.css',
                'resources/css/pages/series-player.css',
                'resources/css/pages/watchlist.css',
                'resources/css/pages/player.css',
                'resources/css/pages/player-v3.css',
                'resources/js/pages/home.js',
                'resources/js/pages/movie-detail.js',
                'resources/js/pages/series-detail.js',
                'resources/js/pages/detail-share.js',
                'resources/js/pages/player.js',

                // Component styles and scripts (v2.0)
                'resources/css/components/buttons.css',
                'resources/css/components/cards.css',
                'resources/css/components/movie-cards.css',
                'resources/css/components/skeleton-loading.css',
                'resources/css/components/mobile-filters.css',
                'resources/css/components/share-modal.css',
                'resources/css/components/player-controls-v2.css',
                'resources/css/components/player-mobile.css',
                'resources/css/components/skeleton-loader.css',  // Phase 6.1
                'resources/css/components/loading-states.css', // Phase 6.1
                'resources/css/components/micro-interactions.css', // Phase 6.2
                'resources/css/components/accessibility.css', // Phase 6.4
                'resources/css/components/feedback-modal.css', // Phase 6.6
                'resources/css/components/loading.css',
                'resources/css/components/animations.css',
                'resources/css/components/mobile.css',
                'resources/js/components/search.js',
                'resources/js/components/watchlist.js',
                'resources/js/components/player-gestures.js',
                'resources/js/components/page-transitions.js', // Phase 6.1
                'resources/js/components/toast-notifications.js', // Phase 6.2
                'resources/js/components/scroll-animations.js', // Phase 6.2
                'resources/js/components/lazy-load.js', // Phase 6.3
                'resources/js/components/performance-monitor.js', // Phase 6.3
                'resources/js/components/cache-strategy.js', // Phase 6.3
                'resources/js/components/keyboard-nav.js', // Phase 6.4
                'resources/js/components/aria-labels.js', // Phase 6.4
                'resources/js/components/error-handler.js', // Phase 6.6
                'resources/js/components/offline-detector.js', // Phase 6.6
            ],
            refresh: true,
        }),
    ],
    
    // Optional: Optimize build for production
    build: {
        sourcemap: process.env.NODE_ENV !== 'production', // Disable source maps in production
        rollupOptions: {
            output: {
                // Organize compiled assets by type
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    if (/css/i.test(extType)) {
                        return `css/[name]-[hash][extname]`;
                    }
                    return `${extType}/[name]-[hash][extname]`;
                },
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
            },
        },
        // Split vendor libraries for better caching
        chunkSizeWarningLimit: 1000,
    },
    
    // Development server configuration
    server: {
        hmr: {
            overlay: true
        },
        watch: {
            usePolling: true
        }
    }
});