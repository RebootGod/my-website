/* ======================================== */
/* OFFLINE DETECTOR COMPONENT */
/* ======================================== */
/* Phase 6.6: Error Handling & User Feedback */
/* Network status detection and offline handling */

class OfflineDetector {
    constructor(options = {}) {
        this.options = {
            checkInterval: options.checkInterval || 30000, // 30 seconds
            checkUrl: options.checkUrl || '/api/health',
            showBanner: options.showBanner !== false,
            queueRequests: options.queueRequests !== false,
            ...options
        };

        this.isOnline = navigator.onLine;
        this.requestQueue = [];
        this.banner = null;
        
        this.init();
    }

    init() {
        console.log('📡 Offline Detector: Initializing...');
        
        this.setupEventListeners();
        this.createBanner();
        this.startHealthCheck();
        this.updateStatus();
        
        console.log('✅ Offline Detector: Ready');
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        window.addEventListener('online', () => {
            this.handleOnline();
        });

        window.addEventListener('offline', () => {
            this.handleOffline();
        });

        // Monitor connection quality
        if ('connection' in navigator) {
            navigator.connection.addEventListener('change', () => {
                this.handleConnectionChange();
            });
        }
    }

    /**
     * Create offline banner
     */
    createBanner() {
        if (!this.options.showBanner) return;

        this.banner = document.createElement('div');
        this.banner.className = 'offline-banner';
        this.banner.innerHTML = `
            <div class="offline-banner-content">
                <span class="offline-icon">📡</span>
                <div class="offline-text">
                    <strong>Tidak ada koneksi internet</strong>
                    <p>Beberapa fitur mungkin tidak tersedia</p>
                </div>
                <button class="offline-retry-btn">Coba Lagi</button>
            </div>
        `;

        // Inject styles
        this.injectStyles();

        document.body.appendChild(this.banner);

        // Retry button
        const retryBtn = this.banner.querySelector('.offline-retry-btn');
        retryBtn.addEventListener('click', () => {
            this.checkConnection();
        });
    }

    /**
     * Inject CSS styles
     */
    injectStyles() {
        if (document.getElementById('offline-detector-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'offline-detector-styles';
        styles.textContent = `
            .offline-banner {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: #fff;
                padding: 16px 20px;
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                transform: translateY(-100%);
                transition: transform 0.3s ease;
            }

            .offline-banner.show {
                transform: translateY(0);
            }

            .offline-banner-content {
                display: flex;
                align-items: center;
                gap: 16px;
                max-width: 1200px;
                margin: 0 auto;
            }

            .offline-icon {
                font-size: 32px;
            }

            .offline-text {
                flex: 1;
            }

            .offline-text strong {
                display: block;
                font-size: 16px;
                margin-bottom: 4px;
            }

            .offline-text p {
                margin: 0;
                font-size: 13px;
                opacity: 0.9;
            }

            .offline-retry-btn {
                padding: 10px 20px;
                background: rgba(255, 255, 255, 0.2);
                color: #fff;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .offline-retry-btn:hover {
                background: rgba(255, 255, 255, 0.3);
            }

            .offline-indicator {
                position: fixed;
                bottom: 20px;
                left: 20px;
                padding: 12px 20px;
                background: #1a1a1a;
                border: 2px solid #333;
                border-radius: 50px;
                display: flex;
                align-items: center;
                gap: 10px;
                z-index: 999;
                animation: slideInLeft 0.3s ease;
            }

            @keyframes slideInLeft {
                from {
                    transform: translateX(-100px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            .offline-indicator .status-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #10b981;
                animation: pulse 2s ease-in-out infinite;
            }

            .offline-indicator.offline .status-dot {
                background: #ef4444;
            }

            .offline-indicator .status-text {
                font-size: 13px;
                font-weight: 600;
                color: #fff;
            }

            @keyframes pulse {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0.5;
                }
            }

            @media (max-width: 768px) {
                .offline-banner {
                    padding: 12px 16px;
                }

                .offline-icon {
                    font-size: 24px;
                }

                .offline-text strong {
                    font-size: 14px;
                }

                .offline-text p {
                    font-size: 12px;
                }

                .offline-retry-btn {
                    padding: 8px 16px;
                    font-size: 13px;
                }

                .offline-indicator {
                    bottom: 16px;
                    left: 16px;
                }
            }
        `;

        document.head.appendChild(styles);
    }

    /**
     * Handle online event
     */
    handleOnline() {
        this.isOnline = true;
        this.updateStatus();
        
        console.log('✅ Connection restored');
        
        // Process queued requests
        this.processQueue();

        // Hide banner
        if (this.banner) {
            this.banner.classList.remove('show');
        }

        // Show success notification
        if (window.toastNotification) {
            window.toastNotification.success('Koneksi internet dipulihkan');
        }

        // Announce to screen reader
        if (window.ariaLabels) {
            window.ariaLabels.announce('Koneksi internet dipulihkan', 'polite');
        }
    }

    /**
     * Handle offline event
     */
    handleOffline() {
        this.isOnline = false;
        this.updateStatus();
        
        console.log('❌ Connection lost');

        // Show banner
        if (this.banner) {
            this.banner.classList.add('show');
        }

        // Show error notification
        if (window.toastNotification) {
            window.toastNotification.error('Koneksi internet terputus');
        }

        // Announce to screen reader
        if (window.ariaLabels) {
            window.ariaLabels.announce('Koneksi internet terputus', 'assertive');
        }
    }

    /**
     * Handle connection change
     */
    handleConnectionChange() {
        if (!navigator.connection) return;

        const connection = navigator.connection;
        const type = connection.effectiveType;
        const downlink = connection.downlink;

        console.log(`📡 Connection: ${type}, Speed: ${downlink} Mbps`);

        // Warn about slow connection
        if (type === 'slow-2g' || type === '2g') {
            if (window.toastNotification) {
                window.toastNotification.warning('Koneksi internet lambat');
            }
        }
    }

    /**
     * Check connection
     */
    async checkConnection() {
        try {
            const response = await fetch(this.options.checkUrl, {
                method: 'HEAD',
                cache: 'no-cache'
            });

            if (response.ok) {
                if (!this.isOnline) {
                    this.handleOnline();
                }
            } else {
                if (this.isOnline) {
                    this.handleOffline();
                }
            }
        } catch (error) {
            if (this.isOnline) {
                this.handleOffline();
            }
        }
    }

    /**
     * Start health check
     */
    startHealthCheck() {
        setInterval(() => {
            this.checkConnection();
        }, this.options.checkInterval);
    }

    /**
     * Update status indicator
     */
    updateStatus() {
        // Emit custom event
        window.dispatchEvent(new CustomEvent('connectionchange', {
            detail: { isOnline: this.isOnline }
        }));
    }

    /**
     * Queue request for later
     */
    queueRequest(request) {
        if (!this.options.queueRequests) return;

        this.requestQueue.push(request);
        console.log(`📥 Request queued (${this.requestQueue.length} total)`);
    }

    /**
     * Process queued requests
     */
    async processQueue() {
        if (this.requestQueue.length === 0) return;

        console.log(`📤 Processing ${this.requestQueue.length} queued requests...`);

        const requests = [...this.requestQueue];
        this.requestQueue = [];

        for (const request of requests) {
            try {
                await request();
            } catch (error) {
                console.error('Failed to process queued request:', error);
            }
        }
    }

    /**
     * Get connection info
     */
    getConnectionInfo() {
        const info = {
            online: this.isOnline,
            type: 'unknown',
            downlink: 0,
            rtt: 0
        };

        if (navigator.connection) {
            info.type = navigator.connection.effectiveType;
            info.downlink = navigator.connection.downlink;
            info.rtt = navigator.connection.rtt;
        }

        return info;
    }
}

// Initialize
const offlineDetector = new OfflineDetector();

// Make globally available
window.offlineDetector = offlineDetector;

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OfflineDetector;
}
