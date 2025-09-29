/**
 * ========================================
 * SECURITY DASHBOARD CORE JS
 * Core functionality for Enhanced Security Dashboard
 * Following workinginstruction.md: Modular JavaScript architecture
 * ========================================
 */

class SecurityDashboard {
    constructor() {
        this.isInitialized = false;
        this.refreshInterval = null;
        this.refreshRate = 30000; // 30 seconds
        this.charts = {};
        this.eventListeners = [];
        
        // Bind methods to preserve context
        this.init = this.init.bind(this);
        this.refresh = this.refresh.bind(this);
        this.handleRefreshClick = this.handleRefreshClick.bind(this);
        this.handleError = this.handleError.bind(this);
    }

    /**
     * Initialize the dashboard
     */
    async init() {
        if (this.isInitialized) return;
        
        try {
            console.log('🔒 Initializing Enhanced Security Dashboard...');
            
            // Initialize components
            await this.initializeComponents();
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Load initial data
            await this.loadInitialData();
            
            // Start auto-refresh
            this.startAutoRefresh();
            
            this.isInitialized = true;
            console.log('✅ Security Dashboard initialized successfully');
            
            // Show success notification
            this.showNotification('Dashboard loaded successfully', 'success');
            
        } catch (error) {
            console.error('❌ Dashboard initialization failed:', error);
            this.handleError('Failed to initialize dashboard', error);
        }
    }

    /**
     * Initialize dashboard components
     */
    async initializeComponents() {
        // Initialize data manager
        this.dataManager = new SecurityDashboardData(this);
        
        // Initialize chart manager
        this.chartManager = new SecurityDashboardCharts(this);
        
        // Initialize chart containers
        this.initializeChartContainers();
        
        // Setup loading states
        this.showLoadingStates();
        
        // Initialize mobile carrier protection banner
        this.initializeCarrierBanner();
    }

    /**
     * Initialize chart containers
     */
    initializeChartContainers() {
        const chartContainers = [
            'threatTimelineChart',
            'responseTimeChart',
            'geoDistributionChart',
            'eventDistributionChart',
            'performanceChart',
            'attackPatternChart'
        ];
        
        chartContainers.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                // Add loading state
                container.innerHTML = `
                    <div class="chart-loading">
                        <div class="loading-spinner"></div>
                        Loading chart data...
                    </div>
                `;
            }
        });
    }

    /**
     * Show loading states for all cards
     */
    showLoadingStates() {
        const loadingElements = document.querySelectorAll('[data-loading]');
        loadingElements.forEach(element => {
            element.style.opacity = '0.6';
            element.style.pointerEvents = 'none';
        });
    }

    /**
     * Initialize mobile carrier protection banner
     */
    initializeCarrierBanner() {
        const banner = document.querySelector('.carrier-protection-banner');
        if (banner) {
            // Add animation class
            banner.style.opacity = '0';
            banner.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                banner.style.transition = 'all 0.5s ease';
                banner.style.opacity = '1';
                banner.style.transform = 'translateY(0)';
            }, 100);
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Refresh button
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', this.handleRefreshClick);
            this.eventListeners.push({ element: refreshBtn, event: 'click', handler: this.handleRefreshClick });
        }

        // Timeline controls
        const timelineControls = document.querySelectorAll('.timeline-btn');
        timelineControls.forEach(btn => {
            const handler = () => this.chartManager.handleTimelineChange(btn.dataset.period);
            btn.addEventListener('click', handler);
            this.eventListeners.push({ element: btn, event: 'click', handler });
        });

        // Chart tabs
        const chartTabs = document.querySelectorAll('.chart-tab');
        chartTabs.forEach(tab => {
            const handler = () => this.chartManager.handleChartTabChange(tab.dataset.chart, tab.dataset.metric);
            tab.addEventListener('click', handler);
            this.eventListeners.push({ element: tab, event: 'click', handler });
        });

        // Pattern filters
        const patternFilters = document.querySelectorAll('.pattern-filter');
        patternFilters.forEach(filter => {
            const handler = () => this.chartManager.handlePatternFilterChange(filter.dataset.pattern);
            filter.addEventListener('click', handler);
            this.eventListeners.push({ element: filter, event: 'click', handler });
        });

        // Window events
        window.addEventListener('beforeunload', () => this.cleanup());
        window.addEventListener('visibilitychange', () => this.handleVisibilityChange());
    }

    /**
     * Load initial dashboard data
     */
    async loadInitialData() {
        console.log('📊 Loading initial dashboard data...');
        
        try {
            // Load data concurrently
            const [
                metrics,
                protectionStatus,
                recentEvents,
                geographicData,
                recommendations
            ] = await Promise.all([
                this.fetchSecurityMetrics(),
                this.fetchProtectionStatus(),
                this.fetchRecentEvents(),
                this.fetchGeographicData(),
                this.fetchAIRecommendations()
            ]);

            // Update UI components using data manager
            this.dataManager.updateSecurityMetrics(metrics);
            this.dataManager.updateProtectionStatus(protectionStatus);
            this.dataManager.updateRecentEvents(recentEvents);
            this.dataManager.updateGeographicData(geographicData);
            this.dataManager.updateAIRecommendations(recommendations);

            // Initialize charts
            await this.chartManager.initializeCharts();
            
            // Remove loading states
            this.hideLoadingStates();
            
        } catch (error) {
            console.error('❌ Failed to load initial data:', error);
            this.handleError('Failed to load dashboard data', error);
        }
    }

    /**
     * Fetch security metrics using data manager
     */
    async fetchSecurityMetrics() {
        return await this.dataManager.fetchSecurityMetrics();
    }

    /**
     * Fetch protection status using data manager
     */
    async fetchProtectionStatus() {
        return await this.dataManager.fetchProtectionStatus();
    }

    /**
     * Fetch recent events using data manager
     */
    async fetchRecentEvents() {
        return await this.dataManager.fetchRecentEvents();
    }

    /**
     * Fetch geographic data using data manager
     */
    async fetchGeographicData() {
        return await this.dataManager.fetchGeographicData();
    }

    /**
     * Fetch AI recommendations using data manager
     */
    async fetchAIRecommendations() {
        return await this.dataManager.fetchAIRecommendations();
    }

    /**
     * Fetch and update chart data
     */
    async fetchAndUpdateChart(chartName, options = {}) {
        try {
            const chartData = await this.dataManager.fetchChartData(chartName, options);
            this.chartManager.updateChartData(chartName, chartData);
        } catch (error) {
            console.error(`Failed to update chart ${chartName}:`, error);
        }
    }

    /**
     * Handle refresh button click
     */
    async handleRefreshClick(event) {
        event.preventDefault();
        
        const btn = event.target;
        const originalHTML = btn.innerHTML;
        
        try {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            btn.disabled = true;
            
            await this.refresh();
            
            this.showNotification('Dashboard refreshed successfully', 'success');
            
        } catch (error) {
            console.error('❌ Refresh failed:', error);
            this.showNotification('Failed to refresh dashboard', 'error');
        } finally {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }

    /**
     * Refresh dashboard data
     */
    async refresh() {
        console.log('🔄 Refreshing dashboard...');
        
        try {
            // Show loading states
            this.showLoadingStates();
            
            // Reload data using data manager
            await this.dataManager.refreshAllData();
            
            // Update timestamp
            this.updateLastRefreshTime();
            
        } catch (error) {
            throw error;
        }
    }

    /**
     * Start auto-refresh timer
     */
    startAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        this.refreshInterval = setInterval(async () => {
            if (document.visibilityState === 'visible') {
                try {
                    await this.refresh();
                } catch (error) {
                    console.error('❌ Auto-refresh failed:', error);
                }
            }
        }, this.refreshRate);
        
        console.log(`⏰ Auto-refresh started (${this.refreshRate / 1000}s interval)`);
    }

    /**
     * Handle visibility change
     */
    handleVisibilityChange() {
        if (document.visibilityState === 'visible') {
            console.log('👁️ Dashboard is visible - resuming refresh');
            this.startAutoRefresh();
        } else {
            console.log('👁️ Dashboard is hidden - pausing refresh');
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        }
    }

    /**
     * Hide loading states
     */
    hideLoadingStates() {
        const loadingElements = document.querySelectorAll('[data-loading]');
        loadingElements.forEach(element => {
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
        });
    }

    /**
     * Update last refresh time
     */
    updateLastRefreshTime() {
        const timeElement = document.getElementById('lastRefreshTime');
        if (timeElement) {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString();
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Show with animation
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    /**
     * Handle errors
     */
    handleError(message, error) {
        console.error('❌ Dashboard Error:', message, error);
        
        // Show error notification
        this.showNotification(message, 'error');
        
        // Hide loading states
        this.hideLoadingStates();
        
        // Show error in chart containers
        const chartContainers = document.querySelectorAll('.chart-container .chart-loading');
        chartContainers.forEach(container => {
            container.innerHTML = `
                <div class="chart-error">
                    <div class="chart-error-icon">⚠️</div>
                    <p class="chart-error-text">Failed to load chart data</p>
                </div>
            `;
        });
    }

    /**
     * Cleanup dashboard
     */
    cleanup() {
        console.log('🧹 Cleaning up dashboard...');
        
        // Clear auto-refresh
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
        
        // Remove event listeners
        this.eventListeners.forEach(({ element, event, handler }) => {
            if (element && typeof element.removeEventListener === 'function') {
                element.removeEventListener(event, handler);
            }
        });
        this.eventListeners = [];
        
        // Destroy charts using chart manager
        if (this.chartManager) {
            this.chartManager.destroyCharts();
        }
        
        // Clear data manager cache
        if (this.dataManager) {
            this.dataManager.clearCache();
        }
        
        this.isInitialized = false;
    }
}

// Global dashboard instance
let securityDashboard = null;

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
    try {
        securityDashboard = new SecurityDashboard();
        await securityDashboard.init();
    } catch (error) {
        console.error('❌ Failed to initialize dashboard:', error);
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (securityDashboard) {
        securityDashboard.cleanup();
    }
});

// Export for use in other modules
window.SecurityDashboard = SecurityDashboard;