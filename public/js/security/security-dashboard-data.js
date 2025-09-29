/**
 * ========================================
 * SECURITY DASHBOARD DATA JS
 * Data management and API integration for Enhanced Security Dashboard
 * Following workinginstruction.md: Modular data layer architecture
 * ========================================
 */

class SecurityDashboardData {
    constructor(dashboard) {
        this.dashboard = dashboard;
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
        this.apiEndpoints = {
            metrics: '/admin/security/api/metrics',
            protectionStatus: '/admin/security/api/protection-status',
            recentEvents: '/admin/security/api/recent-events',
            geographicData: '/admin/security/api/geographic-data',
            aiRecommendations: '/admin/security/api/ai-recommendations',
            chartData: '/admin/security/api/chart-data',
            performanceData: '/admin/security/api/performance-data',
            cloudflareStats: '/admin/security/api/cloudflare-stats'
        };
        this.requestQueue = [];
        this.isProcessingQueue = false;
    }

    /**
     * Get CSRF token
     */
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Get default headers for API requests
     */
    getDefaultHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.getCSRFToken(),
            'Accept': 'application/json'
        };
    }

    /**
     * Make API request with error handling and caching
     */
    async makeRequest(endpoint, options = {}) {
        const cacheKey = `${endpoint}_${JSON.stringify(options)}`;
        
        // Check cache first
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTimeout) {
                console.log(`📋 Cache hit for ${endpoint}`);
                return cached.data;
            }
        }

        try {
            const url = new URL(endpoint, window.location.origin);
            
            // Add query parameters
            if (options.params) {
                Object.keys(options.params).forEach(key => {
                    url.searchParams.append(key, options.params[key]);
                });
            }

            const response = await fetch(url.toString(), {
                method: options.method || 'GET',
                headers: {
                    ...this.getDefaultHeaders(),
                    ...options.headers
                },
                body: options.body ? JSON.stringify(options.body) : null
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            // Cache successful response
            this.cache.set(cacheKey, {
                data,
                timestamp: Date.now()
            });

            console.log(`✅ API request successful: ${endpoint}`);
            return data;

        } catch (error) {
            console.error(`❌ API request failed: ${endpoint}`, error);
            
            // Return cached data if available, even if expired
            if (this.cache.has(cacheKey)) {
                console.log(`📋 Using expired cache for ${endpoint}`);
                return this.cache.get(cacheKey).data;
            }
            
            throw error;
        }
    }

    /**
     * Fetch security metrics
     */
    async fetchSecurityMetrics() {
        return await this.makeRequest(this.apiEndpoints.metrics);
    }

    /**
     * Fetch protection status
     */
    async fetchProtectionStatus() {
        return await this.makeRequest(this.apiEndpoints.protectionStatus);
    }

    /**
     * Fetch recent events
     */
    async fetchRecentEvents(limit = 10) {
        return await this.makeRequest(this.apiEndpoints.recentEvents, {
            params: { limit }
        });
    }

    /**
     * Fetch geographic data
     */
    async fetchGeographicData() {
        return await this.makeRequest(this.apiEndpoints.geographicData);
    }

    /**
     * Fetch AI recommendations
     */
    async fetchAIRecommendations() {
        return await this.makeRequest(this.apiEndpoints.aiRecommendations);
    }

    /**
     * Fetch chart data
     */
    async fetchChartData(chartType, options = {}) {
        return await this.makeRequest(this.apiEndpoints.chartData, {
            params: { 
                chart: chartType,
                ...options
            }
        });
    }

    /**
     * Fetch performance data
     */
    async fetchPerformanceData(timeframe = '24h') {
        return await this.makeRequest(this.apiEndpoints.performanceData, {
            params: { timeframe }
        });
    }

    /**
     * Fetch Cloudflare statistics
     */
    async fetchCloudflareStats() {
        return await this.makeRequest(this.apiEndpoints.cloudflareStats);
    }

    /**
     * Update security metrics in UI
     */
    updateSecurityMetrics(data) {
        try {
            // Update metric cards
            const metrics = [
                { key: 'totalThreats', element: 'totalThreatsValue', format: 'number' },
                { key: 'blockedAttacks', element: 'blockedAttacksValue', format: 'number' },
                { key: 'activeProtection', element: 'activeProtectionValue', format: 'percentage' },
                { key: 'responseTime', element: 'responseTimeValue', format: 'time' },
                { key: 'uptime', element: 'uptimeValue', format: 'percentage' },
                { key: 'securityScore', element: 'securityScoreValue', format: 'score' }
            ];

            metrics.forEach(({ key, element, format }) => {
                const elem = document.getElementById(element);
                if (elem && data[key] !== undefined) {
                    elem.textContent = this.formatMetricValue(data[key], format);
                    
                    // Add trend indicator if available
                    const trendElem = document.getElementById(`${element}Trend`);
                    if (trendElem && data[`${key}Trend`]) {
                        this.updateTrendIndicator(trendElem, data[`${key}Trend`]);
                    }
                }
            });

            // Update last updated timestamp
            const timestampElem = document.getElementById('metricsTimestamp');
            if (timestampElem) {
                timestampElem.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
            }

        } catch (error) {
            console.error('❌ Failed to update security metrics:', error);
        }
    }

    /**
     * Update protection status in UI
     */
    updateProtectionStatus(data) {
        try {
            const features = [
                'firewallStatus',
                'ddosProtectionStatus', 
                'botProtectionStatus',
                'rateLimitingStatus',
                'geoBlockingStatus'
            ];

            features.forEach(feature => {
                const elem = document.getElementById(feature);
                if (elem && data[feature] !== undefined) {
                    const status = data[feature] ? 'Active' : 'Inactive';
                    const statusClass = data[feature] ? 'status-active' : 'status-inactive';
                    
                    elem.textContent = status;
                    elem.className = `feature-status ${statusClass}`;
                }
            });

            // Update mobile carrier protection status
            const carrierProtectionElem = document.getElementById('carrierProtectionStatus');
            if (carrierProtectionElem && data.mobileCarrierProtection !== undefined) {
                const isActive = data.mobileCarrierProtection;
                carrierProtectionElem.textContent = isActive ? 'Active' : 'Inactive';
                carrierProtectionElem.className = `status ${isActive ? 'status-active' : 'status-inactive'}`;
            }

        } catch (error) {
            console.error('❌ Failed to update protection status:', error);
        }
    }

    /**
     * Update recent events in UI
     */
    updateRecentEvents(data) {
        try {
            const eventsList = document.getElementById('recentEventsList');
            if (!eventsList || !Array.isArray(data.events)) return;

            eventsList.innerHTML = '';

            data.events.forEach(event => {
                const eventElement = this.createEventElement(event);
                eventsList.appendChild(eventElement);
            });

            // Update events count
            const eventsCountElem = document.getElementById('eventsCount');
            if (eventsCountElem) {
                eventsCountElem.textContent = data.totalCount || data.events.length;
            }

        } catch (error) {
            console.error('❌ Failed to update recent events:', error);
        }
    }

    /**
     * Update geographic data in UI
     */
    updateGeographicData(data) {
        try {
            const countriesList = document.getElementById('countriesList');
            if (!countriesList || !Array.isArray(data.countries)) return;

            countriesList.innerHTML = '';

            data.countries.forEach(country => {
                const countryElement = this.createCountryElement(country);
                countriesList.appendChild(countryElement);
            });

            // Update chart data if chart manager is available
            if (this.dashboard.chartManager) {
                this.dashboard.chartManager.updateChartData('geoDistribution', {
                    countries: data.countries.map(c => c.name),
                    percentages: data.countries.map(c => c.percentage)
                });
            }

        } catch (error) {
            console.error('❌ Failed to update geographic data:', error);
        }
    }

    /**
     * Update AI recommendations in UI
     */
    updateAIRecommendations(data) {
        try {
            const recommendationsList = document.getElementById('recommendationsList');
            if (!recommendationsList || !Array.isArray(data.recommendations)) return;

            recommendationsList.innerHTML = '';

            data.recommendations.forEach(recommendation => {
                const recommendationElement = this.createRecommendationElement(recommendation);
                recommendationsList.appendChild(recommendationElement);
            });

        } catch (error) {
            console.error('❌ Failed to update AI recommendations:', error);
        }
    }

    /**
     * Create event element for recent events
     */
    createEventElement(event) {
        const div = document.createElement('div');
        div.className = 'event-item';
        
        const iconClass = this.getEventIconClass(event.severity);
        
        div.innerHTML = `
            <div class="event-icon ${event.severity}">
                <i class="fas fa-${iconClass}"></i>
            </div>
            <div class="event-content">
                <h4 class="event-title">${this.escapeHtml(event.title)}</h4>
                <p class="event-description">${this.escapeHtml(event.description)}</p>
                <div class="event-meta">
                    <span>IP: ${this.escapeHtml(event.ip)}</span>
                    <span>Country: ${this.escapeHtml(event.country)}</span>
                    <span>${this.formatTimestamp(event.timestamp)}</span>
                </div>
            </div>
        `;
        
        return div;
    }

    /**
     * Create country element for geographic data
     */
    createCountryElement(country) {
        const div = document.createElement('div');
        div.className = 'country-item';
        
        div.innerHTML = `
            <div class="country-info">
                <div class="country-flag">${country.flag || '🌍'}</div>
                <span class="country-name">${this.escapeHtml(country.name)}</span>
            </div>
            <div class="country-stats">
                <span class="threat-level ${country.threatLevel}">${country.threatLevel}</span>
                <span class="request-count">${this.formatNumber(country.requests)} requests</span>
            </div>
        `;
        
        return div;
    }

    /**
     * Create recommendation element for AI recommendations
     */
    createRecommendationElement(recommendation) {
        const div = document.createElement('div');
        div.className = 'recommendation-item';
        
        div.innerHTML = `
            <div class="recommendation-priority priority-${recommendation.priority}">
                ${recommendation.priority.toUpperCase()}
            </div>
            <h4 class="recommendation-title">${this.escapeHtml(recommendation.title)}</h4>
            <p class="recommendation-description">${this.escapeHtml(recommendation.description)}</p>
            <div class="recommendation-actions">
                ${recommendation.actions.map(action => 
                    `<button class="action-btn ${action.type || ''}" data-action="${action.id}">
                        ${this.escapeHtml(action.label)}
                    </button>`
                ).join('')}
            </div>
        `;
        
        return div;
    }

    /**
     * Format metric value based on type
     */
    formatMetricValue(value, format) {
        switch (format) {
            case 'number':
                return this.formatNumber(value);
            case 'percentage':
                return `${value}%`;
            case 'time':
                return `${value}ms`;
            case 'score':
                return `${value}/100`;
            default:
                return value.toString();
        }
    }

    /**
     * Format number with commas
     */
    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    /**
     * Format timestamp
     */
    formatTimestamp(timestamp) {
        return new Date(timestamp).toLocaleString();
    }

    /**
     * Get icon class for event severity
     */
    getEventIconClass(severity) {
        const icons = {
            'high': 'exclamation-triangle',
            'medium': 'exclamation-circle',
            'low': 'info-circle',
            'info': 'info'
        };
        return icons[severity] || 'circle';
    }

    /**
     * Update trend indicator
     */
    updateTrendIndicator(element, trend) {
        const isPositive = trend > 0;
        const icon = isPositive ? 'arrow-up' : 'arrow-down';
        const className = isPositive ? 'positive' : 'negative';
        
        element.innerHTML = `
            <i class="fas fa-${icon}"></i>
            ${Math.abs(trend)}%
        `;
        element.className = `metric-change ${className}`;
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
        console.log('🗑️ Data cache cleared');
    }

    /**
     * Get cache statistics
     */
    getCacheStats() {
        return {
            size: this.cache.size,
            entries: Array.from(this.cache.keys())
        };
    }

    /**
     * Preload critical data
     */
    async preloadCriticalData() {
        console.log('🚀 Preloading critical dashboard data...');
        
        try {
            const promises = [
                this.fetchSecurityMetrics(),
                this.fetchProtectionStatus(),
                this.fetchRecentEvents(5)
            ];

            await Promise.allSettled(promises);
            console.log('✅ Critical data preloaded');
            
        } catch (error) {
            console.error('❌ Failed to preload critical data:', error);
        }
    }

    /**
     * Refresh all data
     */
    async refreshAllData() {
        console.log('🔄 Refreshing all dashboard data...');
        
        try {
            this.clearCache();
            
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

            // Update UI
            this.updateSecurityMetrics(metrics);
            this.updateProtectionStatus(protectionStatus);
            this.updateRecentEvents(recentEvents);
            this.updateGeographicData(geographicData);
            this.updateAIRecommendations(recommendations);

            console.log('✅ All data refreshed successfully');
            return true;
            
        } catch (error) {
            console.error('❌ Failed to refresh data:', error);
            throw error;
        }
    }
}

// Export for use in main dashboard
window.SecurityDashboardData = SecurityDashboardData;