/* ======================================== */
/* PAGE TRANSITIONS COMPONENT */
/* ======================================== */
/* Phase 6.1: Loading States & Animations */
/* Smooth page transitions and loading orchestration */

class PageTransitions {
    constructor() {
        this.isTransitioning = false;
        this.transitionDuration = 300; // ms
        this.init();
    }

    init() {
        console.log('🎬 Page Transitions: Initializing...');
        
        // Handle page load
        this.handlePageLoad();
        
        // Handle form submissions
        this.handleFormSubmissions();
        
        // Handle link clicks (optional SPA-style transitions)
        this.handleLinkClicks();
        
        console.log('✅ Page Transitions: Initialized');
    }

    /**
     * Handle initial page load animations
     */
    handlePageLoad() {
        // Remove loading overlay if exists
        const loadingOverlay = document.querySelector('.loading-fullscreen');
        if (loadingOverlay) {
            setTimeout(() => {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => loadingOverlay.remove(), 300);
            }, 500);
        }

        // Fade in page content
        document.body.style.opacity = '0';
        setTimeout(() => {
            document.body.style.transition = 'opacity 0.3s ease-out';
            document.body.style.opacity = '1';
        }, 100);

        // Animate elements with data-animate attribute
        this.animateElements();
    }

    /**
     * Animate elements on page load
     */
    animateElements() {
        const animatedElements = document.querySelectorAll('[data-animate]');
        
        animatedElements.forEach((element, index) => {
            const animationType = element.dataset.animate || 'fade-up';
            const delay = parseInt(element.dataset.delay || 0) + (index * 50);
            
            element.style.opacity = '0';
            element.style.transform = this.getInitialTransform(animationType);
            
            setTimeout(() => {
                element.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                element.style.opacity = '1';
                element.style.transform = 'none';
            }, delay);
        });
    }

    /**
     * Get initial transform for animation type
     */
    getInitialTransform(type) {
        const transforms = {
            'fade-up': 'translateY(30px)',
            'fade-down': 'translateY(-30px)',
            'fade-left': 'translateX(30px)',
            'fade-right': 'translateX(-30px)',
            'scale': 'scale(0.9)',
            'none': 'none'
        };
        return transforms[type] || transforms['fade-up'];
    }

    /**
     * Handle form submissions with loading states
     */
    handleFormSubmissions() {
        const forms = document.querySelectorAll('form[data-transition]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (this.isTransitioning) return;
                this.showFormLoading(form);
            });
        });
    }

    /**
     * Show form loading state
     */
    showFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.dataset.originalText = originalText;
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        }

        // Add overlay to form
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-overlay-content">
                <div class="loading-spinner"></div>
                <div class="loading-overlay-text">Processing...</div>
            </div>
        `;
        form.style.position = 'relative';
        form.appendChild(overlay);
    }

    /**
     * Hide form loading state
     */
    hideFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        
        if (submitBtn) {
            submitBtn.classList.remove('btn-loading');
            submitBtn.disabled = false;
            if (submitBtn.dataset.originalText) {
                submitBtn.innerHTML = submitBtn.dataset.originalText;
            }
        }

        const overlay = form.querySelector('.loading-overlay');
        if (overlay) overlay.remove();
    }

    /**
     * Handle smooth link transitions
     */
    handleLinkClicks() {
        const links = document.querySelectorAll('a[data-transition]:not([target="_blank"])');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                
                // Skip if external link or anchor
                if (!href || href.startsWith('#') || href.startsWith('http') || href.includes('://')) {
                    return;
                }

                e.preventDefault();
                this.transitionToPage(href);
            });
        });
    }

    /**
     * Transition to new page with animation
     */
    transitionToPage(url) {
        if (this.isTransitioning) return;
        this.isTransitioning = true;

        // Show top loading bar
        this.showTopLoadingBar();

        // Fade out current page
        document.body.style.transition = 'opacity 0.3s ease-out';
        document.body.style.opacity = '0';

        setTimeout(() => {
            window.location.href = url;
        }, this.transitionDuration);
    }

    /**
     * Show top loading bar
     */
    showTopLoadingBar() {
        const existingBar = document.querySelector('.page-loading');
        if (existingBar) return;

        const loadingBar = document.createElement('div');
        loadingBar.className = 'page-loading';
        loadingBar.innerHTML = '<div class="page-loading-bar"></div>';
        document.body.appendChild(loadingBar);

        setTimeout(() => loadingBar.remove(), 2000);
    }

    /**
     * Show loading overlay on specific element
     */
    showLoading(target = 'body', text = 'Loading...') {
        const targetEl = typeof target === 'string' ? document.querySelector(target) : target;
        if (!targetEl) return;

        // Remove existing overlay
        this.hideLoading(target);

        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.dataset.loadingId = Date.now();
        overlay.innerHTML = `
            <div class="loading-overlay-content">
                <div class="loading-spinner"></div>
                ${text ? `<div class="loading-overlay-text">${text}</div>` : ''}
            </div>
        `;

        targetEl.style.position = 'relative';
        targetEl.appendChild(overlay);

        return overlay.dataset.loadingId;
    }

    /**
     * Hide loading overlay
     */
    hideLoading(target = 'body') {
        const targetEl = typeof target === 'string' ? document.querySelector(target) : target;
        if (!targetEl) return;

        const overlay = targetEl.querySelector('.loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 200);
        }
    }

    /**
     * Show skeleton loader
     */
    showSkeleton(target, type = 'card') {
        const targetEl = typeof target === 'string' ? document.querySelector(target) : target;
        if (!targetEl) return;

        targetEl.classList.add('skeleton-container');
        targetEl.innerHTML = this.getSkeletonHTML(type);
    }

    /**
     * Hide skeleton loader
     */
    hideSkeleton(target) {
        const targetEl = typeof target === 'string' ? document.querySelector(target) : target;
        if (!targetEl) return;

        targetEl.classList.add('skeleton-fade-out');
        setTimeout(() => {
            targetEl.classList.remove('skeleton-container', 'skeleton-fade-out');
        }, 300);
    }

    /**
     * Get skeleton HTML by type
     */
    getSkeletonHTML(type) {
        const skeletons = {
            'card': `
                <div class="skeleton-movie-card">
                    <div class="skeleton-movie-poster skeleton"></div>
                    <div class="skeleton-movie-content">
                        <div class="skeleton-movie-title skeleton"></div>
                        <div class="skeleton-movie-meta">
                            <div class="skeleton-movie-badge skeleton"></div>
                            <div class="skeleton-movie-badge skeleton"></div>
                        </div>
                    </div>
                </div>
            `,
            'list': `
                <div class="skeleton-list-item">
                    <div class="skeleton-list-thumbnail skeleton"></div>
                    <div class="skeleton-list-content">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text skeleton-text-short"></div>
                    </div>
                </div>
            `,
            'hero': `
                <div class="skeleton-hero skeleton">
                    <div class="skeleton-hero-content">
                        <div class="skeleton-hero-title skeleton"></div>
                        <div class="skeleton-hero-description skeleton"></div>
                        <div class="skeleton-hero-description skeleton skeleton-text-short"></div>
                    </div>
                </div>
            `
        };

        return skeletons[type] || skeletons['card'];
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.pageTransitions = new PageTransitions();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PageTransitions;
}
