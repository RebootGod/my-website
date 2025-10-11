/* ======================================== */
/* CACHE STRATEGY COMPONENT */
/* ======================================== */
/* Phase 6.3: Performance Optimization */
/* Client-side caching for API responses and assets */

class CacheStrategy {
    constructor(options = {}) {
        this.options = {
            prefix: options.prefix || 'noobz_cache_',
            maxAge: options.maxAge || 3600000, // 1 hour default
            maxItems: options.maxItems || 50,
            version: options.version || '1.0',
            ...options
        };

        this.init();
    }

    init() {
        console.log('💾 Cache Strategy: Initializing...');
        this.cleanExpired();
        console.log('✅ Cache Strategy: Ready');
    }

    /**
     * Generate cache key
     */
    getCacheKey(key) {
        return `${this.options.prefix}${this.options.version}_${key}`;
    }

    /**
     * Set item in cache
     */
    set(key, data, maxAge = null) {
        try {
            const cacheKey = this.getCacheKey(key);
            const item = {
                data: data,
                timestamp: Date.now(),
                maxAge: maxAge || this.options.maxAge
            };

            localStorage.setItem(cacheKey, JSON.stringify(item));
            this.enforceMaxItems();
            return true;
        } catch (error) {
            console.warn('Cache set error:', error);
            return false;
        }
    }

    /**
     * Get item from cache
     */
    get(key) {
        try {
            const cacheKey = this.getCacheKey(key);
            const item = localStorage.getItem(cacheKey);

            if (!item) return null;

            const parsed = JSON.parse(item);
            const age = Date.now() - parsed.timestamp;

            // Check if expired
            if (age > parsed.maxAge) {
                this.remove(key);
                return null;
            }

            return parsed.data;
        } catch (error) {
            console.warn('Cache get error:', error);
            return null;
        }
    }

    /**
     * Remove item from cache
     */
    remove(key) {
        try {
            const cacheKey = this.getCacheKey(key);
            localStorage.removeItem(cacheKey);
            return true;
        } catch (error) {
            console.warn('Cache remove error:', error);
            return false;
        }
    }

    /**
     * Clear all cache
     */
    clear() {
        try {
            const keys = Object.keys(localStorage);
            const cacheKeys = keys.filter(k => k.startsWith(this.options.prefix));
            
            cacheKeys.forEach(key => localStorage.removeItem(key));
            console.log(`🗑️ Cleared ${cacheKeys.length} cache items`);
            return true;
        } catch (error) {
            console.warn('Cache clear error:', error);
            return false;
        }
    }

    /**
     * Clean expired items
     */
    cleanExpired() {
        try {
            const keys = Object.keys(localStorage);
            const cacheKeys = keys.filter(k => k.startsWith(this.options.prefix));
            let cleaned = 0;

            cacheKeys.forEach(key => {
                try {
                    const item = JSON.parse(localStorage.getItem(key));
                    const age = Date.now() - item.timestamp;

                    if (age > item.maxAge) {
                        localStorage.removeItem(key);
                        cleaned++;
                    }
                } catch (e) {
                    // Invalid item, remove it
                    localStorage.removeItem(key);
                    cleaned++;
                }
            });

            if (cleaned > 0) {
                console.log(`🗑️ Cleaned ${cleaned} expired cache items`);
            }
        } catch (error) {
            console.warn('Cache cleanup error:', error);
        }
    }

    /**
     * Enforce maximum number of items
     */
    enforceMaxItems() {
        try {
            const keys = Object.keys(localStorage);
            const cacheKeys = keys.filter(k => k.startsWith(this.options.prefix));

            if (cacheKeys.length > this.options.maxItems) {
                // Sort by timestamp and remove oldest
                const items = cacheKeys.map(key => {
                    const item = JSON.parse(localStorage.getItem(key));
                    return { key, timestamp: item.timestamp };
                }).sort((a, b) => a.timestamp - b.timestamp);

                const toRemove = items.slice(0, items.length - this.options.maxItems);
                toRemove.forEach(item => localStorage.removeItem(item.key));
            }
        } catch (error) {
            console.warn('Enforce max items error:', error);
        }
    }

    /**
     * Fetch with cache
     */
    async fetchWithCache(url, options = {}) {
        const cacheKey = `fetch_${url}`;
        const cached = this.get(cacheKey);

        // Return cached data if available and not forced refresh
        if (cached && !options.forceRefresh) {
            console.log('💾 Cache hit:', url);
            return cached;
        }

        try {
            console.log('🌐 Fetching:', url);
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            // Cache the response
            this.set(cacheKey, data, options.maxAge);
            
            return data;
        } catch (error) {
            console.error('Fetch error:', error);
            
            // Return cached data as fallback if available
            if (cached) {
                console.log('💾 Using stale cache as fallback:', url);
                return cached;
            }
            
            throw error;
        }
    }

    /**
     * Preload and cache multiple URLs
     */
    async preload(urls, maxAge = null) {
        const promises = urls.map(url => 
            this.fetchWithCache(url, { maxAge, forceRefresh: false })
        );

        try {
            await Promise.all(promises);
            console.log(`✅ Preloaded ${urls.length} resources`);
        } catch (error) {
            console.warn('Preload error:', error);
        }
    }

    /**
     * Get cache statistics
     */
    getStats() {
        try {
            const keys = Object.keys(localStorage);
            const cacheKeys = keys.filter(k => k.startsWith(this.options.prefix));
            
            let totalSize = 0;
            cacheKeys.forEach(key => {
                totalSize += localStorage.getItem(key).length;
            });

            return {
                items: cacheKeys.length,
                maxItems: this.options.maxItems,
                sizeKB: (totalSize / 1024).toFixed(2),
                storageUsed: ((totalSize / (5 * 1024 * 1024)) * 100).toFixed(2) + '%' // Assume 5MB limit
            };
        } catch (error) {
            console.warn('Get stats error:', error);
            return null;
        }
    }

    /**
     * Check if cache is available
     */
    isAvailable() {
        try {
            const test = '__cache_test__';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Invalidate cache by pattern
     */
    invalidate(pattern) {
        try {
            const keys = Object.keys(localStorage);
            const regex = new RegExp(pattern);
            let invalidated = 0;

            keys.forEach(key => {
                if (key.startsWith(this.options.prefix) && regex.test(key)) {
                    localStorage.removeItem(key);
                    invalidated++;
                }
            });

            console.log(`🗑️ Invalidated ${invalidated} cache items matching: ${pattern}`);
            return invalidated;
        } catch (error) {
            console.warn('Invalidate error:', error);
            return 0;
        }
    }
}

// Initialize cache strategy
const cacheStrategy = new CacheStrategy({
    prefix: 'noobz_',
    maxAge: 3600000, // 1 hour
    maxItems: 100
});

// Make available globally
window.cacheStrategy = cacheStrategy;

// Clean expired cache periodically (every 5 minutes)
setInterval(() => {
    cacheStrategy.cleanExpired();
}, 300000);

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CacheStrategy;
}
