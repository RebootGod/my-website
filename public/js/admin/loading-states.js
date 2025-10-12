/**
 * Loading States Manager
 * 
 * Provides modern loading states for admin panel:
 * - Skeleton screens for content loading
 * - Button loading states with spinners
 * - Progress bars for long operations
 * - Full-page loading overlays
 * 
 * Security: XSS-safe HTML generation, no eval()
 * 
 * @package NoobzSpace Admin
 */

class LoadingStates {
    constructor() {
        this.activeLoaders = new Set();
        this.progressBars = new Map();
        this.init();
    }

    /**
     * Initialize loading states system
     */
    init() {
        // Add CSS if not already loaded
        if (!document.getElementById('loading-states-css')) {
            const link = document.createElement('link');
            link.id = 'loading-states-css';
            link.rel = 'stylesheet';
            link.href = '/css/admin/loading-states.css?v=' + Date.now();
            document.head.appendChild(link);
        }

        // Create overlay container if not exists
        if (!document.getElementById('loading-overlay-container')) {
            const container = document.createElement('div');
            container.id = 'loading-overlay-container';
            document.body.appendChild(container);
        }

        console.log('Loading States: System initialized');
    }

    /**
     * Show button loading state
     * 
     * @param {HTMLElement|string} button Button element or selector
     * @param {string} loadingText Text to show while loading
     */
    buttonLoading(button, loadingText = 'Loading...') {
        const btn = typeof button === 'string' ? document.querySelector(button) : button;
        if (!btn) return;

        // Store original state
        btn.dataset.originalText = btn.innerHTML;
        btn.dataset.originalDisabled = btn.disabled;
        
        // Set loading state
        btn.disabled = true;
        btn.classList.add('btn-loading');
        btn.innerHTML = `
            <span class="btn-spinner"></span>
            <span class="btn-loading-text">${this.escapeHtml(loadingText)}</span>
        `;

        this.activeLoaders.add(btn);
    }

    /**
     * Reset button to normal state
     * 
     * @param {HTMLElement|string} button Button element or selector
     */
    buttonReset(button) {
        const btn = typeof button === 'string' ? document.querySelector(button) : button;
        if (!btn || !btn.dataset.originalText) return;

        // Restore original state
        btn.innerHTML = btn.dataset.originalText;
        btn.disabled = btn.dataset.originalDisabled === 'true';
        btn.classList.remove('btn-loading');

        delete btn.dataset.originalText;
        delete btn.dataset.originalDisabled;

        this.activeLoaders.delete(btn);
    }

    /**
     * Show skeleton screen in container
     * 
     * @param {HTMLElement|string} container Container element or selector
     * @param {string} type Skeleton type: 'table', 'card', 'list'
     * @param {number} count Number of skeleton items
     */
    showSkeleton(container, type = 'table', count = 5) {
        const elem = typeof container === 'string' ? document.querySelector(container) : container;
        if (!elem) return;

        // Store original content
        elem.dataset.originalContent = elem.innerHTML;
        
        let skeletonHTML = '';

        switch (type) {
            case 'table':
                skeletonHTML = this.generateTableSkeleton(count);
                break;
            case 'card':
                skeletonHTML = this.generateCardSkeleton(count);
                break;
            case 'list':
                skeletonHTML = this.generateListSkeleton(count);
                break;
            default:
                skeletonHTML = this.generateGenericSkeleton(count);
        }

        elem.innerHTML = `<div class="skeleton-container">${skeletonHTML}</div>`;
        this.activeLoaders.add(elem);
    }

    /**
     * Hide skeleton and restore content
     * 
     * @param {HTMLElement|string} container Container element or selector
     */
    hideSkeleton(container) {
        const elem = typeof container === 'string' ? document.querySelector(container) : container;
        if (!elem || !elem.dataset.originalContent) return;

        elem.innerHTML = elem.dataset.originalContent;
        delete elem.dataset.originalContent;
        this.activeLoaders.delete(elem);
    }

    /**
     * Generate table skeleton
     */
    generateTableSkeleton(rows = 5) {
        let html = '<div class="skeleton-table">';
        for (let i = 0; i < rows; i++) {
            html += `
                <div class="skeleton-row">
                    <div class="skeleton-cell skeleton-avatar"></div>
                    <div class="skeleton-cell skeleton-text skeleton-text-long"></div>
                    <div class="skeleton-cell skeleton-text skeleton-text-short"></div>
                    <div class="skeleton-cell skeleton-text skeleton-text-medium"></div>
                    <div class="skeleton-cell skeleton-badge"></div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }

    /**
     * Generate card skeleton
     */
    generateCardSkeleton(count = 3) {
        let html = '<div class="skeleton-cards">';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-card">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-card-body">
                        <div class="skeleton-text skeleton-text-long"></div>
                        <div class="skeleton-text skeleton-text-medium"></div>
                        <div class="skeleton-text skeleton-text-short"></div>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }

    /**
     * Generate list skeleton
     */
    generateListSkeleton(count = 5) {
        let html = '<div class="skeleton-list">';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-list-item">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-list-content">
                        <div class="skeleton-text skeleton-text-medium"></div>
                        <div class="skeleton-text skeleton-text-short"></div>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }

    /**
     * Generate generic skeleton
     */
    generateGenericSkeleton(count = 3) {
        let html = '<div class="skeleton-generic">';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-text skeleton-text-long"></div>
                <div class="skeleton-text skeleton-text-medium"></div>
            `;
        }
        html += '</div>';
        return html;
    }

    /**
     * Show progress bar
     * 
     * @param {string} id Unique identifier for progress bar
     * @param {Object} options Options: title, subtitle, percentage
     */
    showProgress(id, options = {}) {
        const defaults = {
            title: 'Processing...',
            subtitle: '',
            percentage: 0,
            indeterminate: false
        };

        const config = { ...defaults, ...options };

        // Create progress bar if not exists
        if (!this.progressBars.has(id)) {
            const progressEl = document.createElement('div');
            progressEl.className = 'loading-progress';
            progressEl.dataset.progressId = id;
            progressEl.innerHTML = `
                <div class="progress-content">
                    <div class="progress-title">${this.escapeHtml(config.title)}</div>
                    ${config.subtitle ? `<div class="progress-subtitle">${this.escapeHtml(config.subtitle)}</div>` : ''}
                    <div class="progress-bar-container">
                        <div class="progress-bar ${config.indeterminate ? 'indeterminate' : ''}" style="width: ${config.percentage}%"></div>
                    </div>
                    <div class="progress-percentage">${config.percentage}%</div>
                </div>
            `;

            document.getElementById('loading-overlay-container').appendChild(progressEl);
            this.progressBars.set(id, progressEl);
        } else {
            this.updateProgress(id, config.percentage, config.title, config.subtitle);
        }
    }

    /**
     * Update progress bar
     * 
     * @param {string} id Progress bar identifier
     * @param {number} percentage Progress percentage (0-100)
     * @param {string} title Optional title update
     * @param {string} subtitle Optional subtitle update
     */
    updateProgress(id, percentage, title = null, subtitle = null) {
        const progressEl = this.progressBars.get(id);
        if (!progressEl) return;

        const bar = progressEl.querySelector('.progress-bar');
        const percentageText = progressEl.querySelector('.progress-percentage');

        if (bar && !bar.classList.contains('indeterminate')) {
            bar.style.width = `${Math.min(100, Math.max(0, percentage))}%`;
        }

        if (percentageText) {
            percentageText.textContent = `${Math.round(percentage)}%`;
        }

        if (title) {
            const titleEl = progressEl.querySelector('.progress-title');
            if (titleEl) titleEl.textContent = this.escapeHtml(title);
        }

        if (subtitle !== null) {
            let subtitleEl = progressEl.querySelector('.progress-subtitle');
            if (subtitle && !subtitleEl) {
                subtitleEl = document.createElement('div');
                subtitleEl.className = 'progress-subtitle';
                progressEl.querySelector('.progress-content').insertBefore(
                    subtitleEl,
                    progressEl.querySelector('.progress-bar-container')
                );
            }
            if (subtitleEl) {
                subtitleEl.textContent = this.escapeHtml(subtitle);
            }
        }
    }

    /**
     * Hide progress bar
     * 
     * @param {string} id Progress bar identifier
     */
    hideProgress(id) {
        const progressEl = this.progressBars.get(id);
        if (progressEl) {
            progressEl.remove();
            this.progressBars.delete(id);
        }
    }

    /**
     * Show full-page loading overlay
     * 
     * @param {string} message Loading message
     */
    showOverlay(message = 'Loading...') {
        let overlay = document.getElementById('loading-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.className = 'loading-overlay active';
            overlay.innerHTML = `
                <div class="loading-overlay-content">
                    <div class="loading-spinner-large"></div>
                    <div class="loading-overlay-text">${this.escapeHtml(message)}</div>
                </div>
            `;
            document.getElementById('loading-overlay-container').appendChild(overlay);
        } else {
            overlay.classList.add('active');
            const textEl = overlay.querySelector('.loading-overlay-text');
            if (textEl) textEl.textContent = this.escapeHtml(message);
        }

        this.activeLoaders.add('overlay');
    }

    /**
     * Hide full-page loading overlay
     */
    hideOverlay() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.classList.remove('active');
            this.activeLoaders.delete('overlay');
        }
    }

    /**
     * XSS-safe HTML escaping
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Clear all active loaders
     */
    clearAll() {
        // Reset all buttons
        this.activeLoaders.forEach(loader => {
            if (loader instanceof HTMLElement && loader.tagName === 'BUTTON') {
                this.buttonReset(loader);
            }
        });

        // Hide all progress bars
        this.progressBars.forEach((_, id) => this.hideProgress(id));

        // Hide overlay
        this.hideOverlay();

        this.activeLoaders.clear();
    }
}

// Global instance
window.Loading = new LoadingStates();

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.Loading.init());
} else {
    window.Loading.init();
}
