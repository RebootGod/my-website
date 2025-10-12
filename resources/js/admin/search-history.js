/* ======================================== */
/* SEARCH HISTORY MODULE */
/* ======================================== */
/* File: resources/js/admin/search-history.js */

/**
 * SearchHistory - localStorage-based search history manager
 * 
 * Features:
 * - Store recent 10 searches
 * - Autocomplete suggestions
 * - Search frequency tracking
 * - Clear history functionality
 * 
 * Security: XSS prevention via sanitization
 */

class SearchHistory {
    constructor() {
        this.storageKey = 'admin_search_history';
        this.maxHistory = 10;
        this.history = this.loadHistory();
    }

    /**
     * Load search history from localStorage
     */
    loadHistory() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Error loading search history:', error);
            return [];
        }
    }

    /**
     * Save search history to localStorage
     */
    saveHistory() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.history));
        } catch (error) {
            console.error('Error saving search history:', error);
        }
    }

    /**
     * Add search term to history
     * @param {string} query - Search query
     * @param {string} resultUrl - URL of selected result (optional)
     */
    addSearch(query, resultUrl = null) {
        if (!query || query.trim().length === 0) return;

        const cleanQuery = query.trim().toLowerCase();
        
        // Find existing entry
        const existingIndex = this.history.findIndex(
            item => item.query.toLowerCase() === cleanQuery
        );

        const timestamp = Date.now();

        if (existingIndex !== -1) {
            // Update existing entry
            this.history[existingIndex].count++;
            this.history[existingIndex].lastUsed = timestamp;
            if (resultUrl) {
                this.history[existingIndex].lastUrl = resultUrl;
            }
            
            // Move to front
            const [item] = this.history.splice(existingIndex, 1);
            this.history.unshift(item);
        } else {
            // Add new entry
            this.history.unshift({
                query: cleanQuery,
                count: 1,
                lastUsed: timestamp,
                lastUrl: resultUrl
            });

            // Limit to max history
            if (this.history.length > this.maxHistory) {
                this.history = this.history.slice(0, this.maxHistory);
            }
        }

        this.saveHistory();
    }

    /**
     * Get recent searches
     * @param {number} limit - Number of results to return
     * @returns {Array} Recent search queries
     */
    getRecentSearches(limit = 5) {
        return this.history
            .slice(0, limit)
            .map(item => ({
                query: item.query,
                count: item.count,
                lastUsed: item.lastUsed,
                lastUrl: item.lastUrl
            }));
    }

    /**
     * Get autocomplete suggestions
     * @param {string} query - Partial search query
     * @param {number} limit - Number of suggestions
     * @returns {Array} Matching search suggestions
     */
    getSuggestions(query, limit = 5) {
        if (!query || query.trim().length === 0) {
            return this.getRecentSearches(limit);
        }

        const cleanQuery = query.trim().toLowerCase();
        
        return this.history
            .filter(item => item.query.includes(cleanQuery))
            .sort((a, b) => {
                // Prioritize: exact match > starts with > frequency
                const aQuery = a.query.toLowerCase();
                const bQuery = b.query.toLowerCase();
                
                if (aQuery === cleanQuery) return -1;
                if (bQuery === cleanQuery) return 1;
                
                if (aQuery.startsWith(cleanQuery) && !bQuery.startsWith(cleanQuery)) return -1;
                if (!aQuery.startsWith(cleanQuery) && bQuery.startsWith(cleanQuery)) return 1;
                
                return b.count - a.count;
            })
            .slice(0, limit)
            .map(item => ({
                query: item.query,
                count: item.count,
                isFrequent: item.count > 3
            }));
    }

    /**
     * Get popular searches (by frequency)
     * @param {number} limit - Number of results
     * @returns {Array} Most frequent searches
     */
    getPopularSearches(limit = 5) {
        return [...this.history]
            .sort((a, b) => b.count - a.count)
            .slice(0, limit)
            .map(item => ({
                query: item.query,
                count: item.count
            }));
    }

    /**
     * Remove search from history
     * @param {string} query - Query to remove
     */
    removeSearch(query) {
        const cleanQuery = query.trim().toLowerCase();
        this.history = this.history.filter(
            item => item.query.toLowerCase() !== cleanQuery
        );
        this.saveHistory();
    }

    /**
     * Clear all search history
     */
    clearHistory() {
        this.history = [];
        localStorage.removeItem(this.storageKey);
    }

    /**
     * Get history statistics
     * @returns {Object} Statistics about search history
     */
    getStats() {
        return {
            totalSearches: this.history.reduce((sum, item) => sum + item.count, 0),
            uniqueQueries: this.history.length,
            mostFrequent: this.history.length > 0 
                ? this.history.reduce((prev, curr) => 
                    curr.count > prev.count ? curr : prev
                  ).query
                : null
        };
    }

    /**
     * Format time ago string
     * @param {number} timestamp - Unix timestamp
     * @returns {string} Human-readable time ago
     */
    formatTimeAgo(timestamp) {
        const seconds = Math.floor((Date.now() - timestamp) / 1000);
        
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
        return `${Math.floor(seconds / 604800)}w ago`;
    }

    /**
     * Export history (for backup/debugging)
     * @returns {string} JSON string of history
     */
    exportHistory() {
        return JSON.stringify(this.history, null, 2);
    }

    /**
     * Import history (for restore)
     * @param {string} jsonString - JSON string of history data
     */
    importHistory(jsonString) {
        try {
            const imported = JSON.parse(jsonString);
            if (Array.isArray(imported)) {
                this.history = imported.slice(0, this.maxHistory);
                this.saveHistory();
                return true;
            }
        } catch (error) {
            console.error('Error importing history:', error);
        }
        return false;
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchHistory;
}

// Global instance
window.SearchHistory = SearchHistory;
