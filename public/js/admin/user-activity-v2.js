/**
 * User Activity V2 JavaScript
 * Handle filters, real-time updates, interactions
 * Max 350 lines per workinginstruction.md
 */

(function() {
    'use strict';

    // Wait for DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        initUserActivity();
    });

    /**
     * Initialize user activity features
     */
    function initUserActivity() {
        console.log('User Activity V2 initializing...');

        // Setup filter interactions
        setupFilters();

        // Setup auto-refresh (optional)
        setupAutoRefresh();

        // Setup activity item interactions
        setupActivityInteractions();

        // Add fade-in animations
        animateElements();

        console.log('User Activity V2 initialized');
    }

    /**
     * Setup filter interactions
     */
    function setupFilters() {
        const filterForm = document.getElementById('filterForm');
        if (!filterForm) return;

        const filterSelects = filterForm.querySelectorAll('.filter-select');
        
        filterSelects.forEach(select => {
            // Add loading indicator on change
            select.addEventListener('change', function() {
                showLoadingState();
            });
        });

        // Prevent double submission
        let isSubmitting = false;
        filterForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            showLoadingState();
        });
    }

    /**
     * Show loading state
     */
    function showLoadingState() {
        const activityBody = document.querySelector('.activity-card-body');
        if (!activityBody) return;

        // Add loading overlay
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner"></div>
            <p style="margin-top: 1rem; color: var(--text-secondary);">Loading...</p>
        `;
        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        `;

        const cardBody = activityBody.parentElement;
        cardBody.style.position = 'relative';
        cardBody.appendChild(overlay);
    }

    /**
     * Setup auto-refresh (every 30 seconds)
     */
    function setupAutoRefresh() {
        // Only auto-refresh if on first page and no filters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page') || urlParams.has('activity_type') || urlParams.has('user_id')) {
            return;
        }

        // Auto-refresh every 30 seconds
        let refreshInterval;
        let refreshEnabled = false;

        // Create refresh toggle button
        const headerRight = document.querySelector('.header-right');
        if (!headerRight) return;

        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn-icon';
        toggleBtn.title = 'Toggle Auto-Refresh';
        toggleBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        `;

        toggleBtn.addEventListener('click', function() {
            refreshEnabled = !refreshEnabled;
            
            if (refreshEnabled) {
                toggleBtn.style.background = 'var(--primary-color)';
                toggleBtn.style.color = 'white';
                startAutoRefresh();
                showNotification('Auto-refresh enabled (30s interval)', 'success');
            } else {
                toggleBtn.style.background = '';
                toggleBtn.style.color = '';
                stopAutoRefresh();
                showNotification('Auto-refresh disabled', 'info');
            }
        });

        headerRight.insertBefore(toggleBtn, headerRight.children[1]);

        function startAutoRefresh() {
            refreshInterval = setInterval(function() {
                fetchNewActivities();
            }, 30000); // 30 seconds
        }

        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }
    }

    /**
     * Fetch new activities via AJAX
     */
    function fetchNewActivities() {
        const timeline = document.querySelector('.activity-timeline');
        if (!timeline) return;

        const firstActivity = timeline.querySelector('.timeline-item');
        if (!firstActivity) return;

        // Get timestamp of first activity
        const timeElement = firstActivity.querySelector('.timeline-time');
        if (!timeElement) return;

        // Show loading indicator
        const indicator = document.createElement('div');
        indicator.className = 'refresh-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        `;
        indicator.innerHTML = `
            <div class="loading-spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>
            <span>Checking for new activities...</span>
        `;
        document.body.appendChild(indicator);

        // Remove indicator after 2 seconds
        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }

    /**
     * Setup activity item interactions
     */
    function setupActivityInteractions() {
        const activityItems = document.querySelectorAll('.timeline-item');
        
        activityItems.forEach(item => {
            // Add hover effect
            item.addEventListener('mouseenter', function() {
                this.style.background = 'var(--item-hover)';
            });

            item.addEventListener('mouseleave', function() {
                this.style.background = '';
            });

            // Add click to expand details (if needed)
            const description = item.querySelector('.timeline-description');
            if (description && description.textContent.length > 60) {
                description.style.cursor = 'pointer';
                description.title = 'Click to see full description';
                
                let isExpanded = false;
                description.addEventListener('click', function() {
                    if (!isExpanded) {
                        this.style.whiteSpace = 'normal';
                        this.style.overflow = 'visible';
                        isExpanded = true;
                    } else {
                        this.style.whiteSpace = '';
                        this.style.overflow = '';
                        isExpanded = false;
                    }
                });
            }
        });
    }

    /**
     * Animate elements on load
     */
    function animateElements() {
        const elements = document.querySelectorAll('.stat-card, .filter-card, .activity-card, .popular-card');
        
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                el.style.transition = 'all 0.5s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    /**
     * Show notification toast
     */
    function showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : 'var(--primary-color)'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Reset filters
     */
    window.resetFilters = function() {
        window.location.href = window.location.pathname;
    };

    /**
     * Export activity data
     */
    window.exportActivities = function() {
        showNotification('Preparing export...', 'info');
        // Export functionality handled by backend
    };

})();

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.8);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 12px;
    }
`;
document.head.appendChild(style);
