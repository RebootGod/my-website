/* ======================================== */
/* ERROR HANDLER COMPONENT */
/* ======================================== */
/* Phase 6.6: Error Handling & User Feedback */
/* Global error handling with retry logic */

class ErrorHandler {
    constructor(options = {}) {
        this.options = {
            maxRetries: options.maxRetries || 3,
            retryDelay: options.retryDelay || 1000,
            timeout: options.timeout || 30000,
            logErrors: options.logErrors !== false,
            showToasts: options.showToasts !== false,
            ...options
        };

        this.errorLog = [];
        this.init();
    }

    init() {
        console.log('🛡️ Error Handler: Initializing...');
        
        this.setupGlobalHandlers();
        this.setupFetchInterceptor();
        
        console.log('✅ Error Handler: Ready');
    }

    /**
     * Setup global error handlers
     */
    setupGlobalHandlers() {
        // Uncaught errors
        window.addEventListener('error', (event) => {
            this.handleError({
                message: event.message,
                stack: event.error?.stack,
                line: event.lineno,
                column: event.colno,
                file: event.filename,
                type: 'runtime'
            });
        });

        // Unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError({
                message: event.reason?.message || 'Promise rejection',
                stack: event.reason?.stack,
                type: 'promise'
            });
        });
    }

    /**
     * Handle error
     */
    handleError(error) {
        // Log error
        if (this.options.logErrors) {
            console.error('❌ Error caught:', error);
            this.logError(error);
        }

        // Get user-friendly message
        const friendlyMessage = this.getFriendlyMessage(error);

        // Show toast notification
        if (this.options.showToasts && window.toastNotification) {
            window.toastNotification.error(friendlyMessage);
        }

        // Report to analytics (placeholder)
        this.reportError(error);
    }

    /**
     * Log error to array
     */
    logError(error) {
        this.errorLog.push({
            ...error,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href
        });

        // Keep only last 50 errors
        if (this.errorLog.length > 50) {
            this.errorLog.shift();
        }
    }

    /**
     * Get user-friendly error message
     */
    getFriendlyMessage(error) {
        const message = error.message || '';

        // Network errors
        if (message.includes('Failed to fetch') || message.includes('Network')) {
            return 'Koneksi internet bermasalah. Silakan cek koneksi Anda.';
        }

        // Timeout errors
        if (message.includes('timeout')) {
            return 'Permintaan memakan waktu terlalu lama. Silakan coba lagi.';
        }

        // 404 errors
        if (error.status === 404) {
            return 'Konten tidak ditemukan.';
        }

        // 403 errors
        if (error.status === 403) {
            return 'Anda tidak memiliki akses ke konten ini.';
        }

        // 401 errors
        if (error.status === 401) {
            return 'Sesi Anda telah habis. Silakan login kembali.';
        }

        // 500 errors
        if (error.status >= 500) {
            return 'Terjadi kesalahan di server. Tim kami akan segera memperbaikinya.';
        }

        // Default message
        return 'Terjadi kesalahan. Silakan coba lagi.';
    }

    /**
     * Setup fetch interceptor
     */
    setupFetchInterceptor() {
        const originalFetch = window.fetch;

        window.fetch = async (...args) => {
            const [url, options = {}] = args;

            // Add timeout
            const timeout = options.timeout || this.options.timeout;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);

            try {
                const response = await originalFetch(url, {
                    ...options,
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                // Handle HTTP errors
                if (!response.ok) {
                    throw {
                        message: `HTTP ${response.status}: ${response.statusText}`,
                        status: response.status,
                        statusText: response.statusText,
                        url: url
                    };
                }

                return response;
            } catch (error) {
                clearTimeout(timeoutId);

                // Check if abort (timeout)
                if (error.name === 'AbortError') {
                    error.message = 'Request timeout';
                }

                // Retry logic
                const retries = options.retries || 0;
                if (retries < this.options.maxRetries && this.shouldRetry(error)) {
                    console.log(`🔄 Retrying request (${retries + 1}/${this.options.maxRetries})...`);
                    
                    await this.delay(this.options.retryDelay * (retries + 1));
                    
                    return window.fetch(url, {
                        ...options,
                        retries: retries + 1
                    });
                }

                // Log and handle error
                this.handleError({
                    message: error.message || 'Fetch error',
                    status: error.status,
                    url: url,
                    type: 'fetch'
                });

                throw error;
            }
        };
    }

    /**
     * Check if error should be retried
     */
    shouldRetry(error) {
        // Retry network errors
        if (error.message?.includes('Failed to fetch')) return true;
        if (error.message?.includes('timeout')) return true;
        if (error.message?.includes('Network')) return true;

        // Retry 5xx errors
        if (error.status >= 500) return true;

        // Don't retry client errors
        return false;
    }

    /**
     * Delay helper
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Fetch with retry
     */
    async fetchWithRetry(url, options = {}) {
        return window.fetch(url, options);
    }

    /**
     * Report error to analytics
     */
    reportError(error) {
        // Placeholder for analytics reporting
        // Could send to Sentry, LogRocket, etc.
        if (this.options.analyticsEndpoint) {
            fetch(this.options.analyticsEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(error)
            }).catch(() => {
                // Silently fail
            });
        }
    }

    /**
     * Get error log
     */
    getErrorLog() {
        return this.errorLog;
    }

    /**
     * Clear error log
     */
    clearErrorLog() {
        this.errorLog = [];
    }

    /**
     * Export error log
     */
    exportErrorLog() {
        const blob = new Blob([JSON.stringify(this.errorLog, null, 2)], {
            type: 'application/json'
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `error-log-${Date.now()}.json`;
        link.click();
        URL.revokeObjectURL(url);
    }
}

// Initialize
const errorHandler = new ErrorHandler({
    maxRetries: 3,
    retryDelay: 1000,
    timeout: 30000
});

// Make globally available
window.errorHandler = errorHandler;

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ErrorHandler;
}
