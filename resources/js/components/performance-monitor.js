/* ======================================== */
/* PERFORMANCE MONITOR COMPONENT */
/* ======================================== */
/* Phase 6.3: Performance Optimization */
/* Monitor Core Web Vitals and performance metrics */

class PerformanceMonitor {
    constructor() {
        this.metrics = {
            lcp: null,  // Largest Contentful Paint
            fid: null,  // First Input Delay
            cls: null,  // Cumulative Layout Shift
            fcp: null,  // First Contentful Paint
            ttfb: null  // Time to First Byte
        };

        this.init();
    }

    init() {
        console.log('📊 Performance Monitor: Initializing...');

        if (!this.isSupported()) {
            console.warn('⚠️ Performance API not fully supported');
            return;
        }

        this.measureCoreWebVitals();
        this.measureNavigationTiming();
        this.measureResourceTiming();
        
        console.log('✅ Performance Monitor: Ready');
    }

    /**
     * Check if Performance API is supported
     */
    isSupported() {
        return 'performance' in window && 'PerformanceObserver' in window;
    }

    /**
     * Measure Core Web Vitals
     */
    measureCoreWebVitals() {
        // Largest Contentful Paint (LCP)
        if ('PerformanceObserver' in window) {
            try {
                const lcpObserver = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    this.metrics.lcp = lastEntry.renderTime || lastEntry.loadTime;
                    this.logMetric('LCP', this.metrics.lcp, 2500, 4000);
                });
                lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (e) {
                console.warn('LCP observer error:', e);
            }

            // First Input Delay (FID)
            try {
                const fidObserver = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach(entry => {
                        this.metrics.fid = entry.processingStart - entry.startTime;
                        this.logMetric('FID', this.metrics.fid, 100, 300);
                    });
                });
                fidObserver.observe({ entryTypes: ['first-input'] });
            } catch (e) {
                console.warn('FID observer error:', e);
            }

            // Cumulative Layout Shift (CLS)
            try {
                let clsValue = 0;
                const clsObserver = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                            this.metrics.cls = clsValue;
                            this.logMetric('CLS', this.metrics.cls, 0.1, 0.25);
                        }
                    }
                });
                clsObserver.observe({ entryTypes: ['layout-shift'] });
            } catch (e) {
                console.warn('CLS observer error:', e);
            }

            // First Contentful Paint (FCP)
            try {
                const fcpObserver = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach(entry => {
                        if (entry.name === 'first-contentful-paint') {
                            this.metrics.fcp = entry.startTime;
                            this.logMetric('FCP', this.metrics.fcp, 1800, 3000);
                        }
                    });
                });
                fcpObserver.observe({ entryTypes: ['paint'] });
            } catch (e) {
                console.warn('FCP observer error:', e);
            }
        }
    }

    /**
     * Measure Navigation Timing
     */
    measureNavigationTiming() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const perfData = performance.getEntriesByType('navigation')[0];
                
                if (perfData) {
                    this.metrics.ttfb = perfData.responseStart - perfData.requestStart;
                    this.logMetric('TTFB', this.metrics.ttfb, 600, 1500);

                    const domContentLoaded = perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart;
                    const loadComplete = perfData.loadEventEnd - perfData.loadEventStart;

                    console.log('📊 Navigation Timing:', {
                        'DNS Lookup': (perfData.domainLookupEnd - perfData.domainLookupStart).toFixed(2) + 'ms',
                        'TCP Connection': (perfData.connectEnd - perfData.connectStart).toFixed(2) + 'ms',
                        'DOM Content Loaded': domContentLoaded.toFixed(2) + 'ms',
                        'Load Complete': loadComplete.toFixed(2) + 'ms'
                    });
                }
            }, 1000);
        });
    }

    /**
     * Measure Resource Timing
     */
    measureResourceTiming() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const resources = performance.getEntriesByType('resource');
                
                const stats = {
                    scripts: [],
                    styles: [],
                    images: [],
                    fonts: []
                };

                resources.forEach(resource => {
                    const duration = resource.duration;
                    const size = resource.transferSize || 0;
                    
                    if (resource.initiatorType === 'script') {
                        stats.scripts.push({ name: resource.name, duration, size });
                    } else if (resource.initiatorType === 'css') {
                        stats.styles.push({ name: resource.name, duration, size });
                    } else if (resource.initiatorType === 'img') {
                        stats.images.push({ name: resource.name, duration, size });
                    } else if (resource.name.includes('.woff') || resource.name.includes('.ttf')) {
                        stats.fonts.push({ name: resource.name, duration, size });
                    }
                });

                // Find slowest resources
                const slowResources = resources
                    .filter(r => r.duration > 500)
                    .sort((a, b) => b.duration - a.duration)
                    .slice(0, 5);

                if (slowResources.length > 0) {
                    console.warn('⚠️ Slow Resources:', slowResources.map(r => ({
                        name: r.name.split('/').pop(),
                        duration: r.duration.toFixed(2) + 'ms',
                        size: this.formatBytes(r.transferSize || 0)
                    })));
                }
            }, 2000);
        });
    }

    /**
     * Log metric with color coding
     */
    logMetric(name, value, goodThreshold, poorThreshold) {
        if (value === null) return;

        const formatted = name === 'CLS' ? value.toFixed(3) : value.toFixed(2) + 'ms';
        const status = value <= goodThreshold ? '✅ Good' : 
                      value <= poorThreshold ? '⚠️ Needs Improvement' : 
                      '❌ Poor';

        console.log(`${status} ${name}: ${formatted}`);
    }

    /**
     * Format bytes to human readable
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Get all metrics
     */
    getMetrics() {
        return { ...this.metrics };
    }

    /**
     * Get memory usage (if available)
     */
    getMemoryUsage() {
        if (performance.memory) {
            return {
                used: this.formatBytes(performance.memory.usedJSHeapSize),
                total: this.formatBytes(performance.memory.totalJSHeapSize),
                limit: this.formatBytes(performance.memory.jsHeapSizeLimit)
            };
        }
        return null;
    }

    /**
     * Report to analytics (placeholder)
     */
    reportToAnalytics() {
        // This can be extended to send metrics to your analytics service
        const report = {
            ...this.metrics,
            userAgent: navigator.userAgent,
            timestamp: Date.now()
        };

        console.log('📊 Performance Report:', report);
        
        // Example: Send to server
        // fetch('/api/analytics/performance', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify(report)
        // });
    }
}

// Initialize on DOM ready
let performanceMonitor;
document.addEventListener('DOMContentLoaded', () => {
    performanceMonitor = new PerformanceMonitor();
    window.performanceMonitor = performanceMonitor;

    // Report metrics after page fully loaded
    window.addEventListener('load', () => {
        setTimeout(() => {
            performanceMonitor.reportToAnalytics();
        }, 3000);
    });
});

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PerformanceMonitor;
}
