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
                'resources/css/pages/series-detail.css',
                'resources/css/pages/series-detail-v2.css',
                'resources/css/pages/player.css',
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
                'resources/css/components/loading.css',
                'resources/css/components/animations.css',
                'resources/css/components/mobile.css',
                'resources/js/components/search.js',
                'resources/js/components/watchlist.js',
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