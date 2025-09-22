// Watchlist Component with Enhanced Animations
class WatchlistComponent {
    constructor() {
        this.isProcessing = false;
        this.init();
    }

    init() {
        this.bindGlobalFunctions();
        this.initializeWatchlistButtons();
    }

    bindGlobalFunctions() {
        // Make functions globally available
        window.toggleWatchlist = (movieId) => this.toggleWatchlist(movieId);
        window.addToWatchlist = (movieId) => this.addToWatchlist(movieId);
        window.removeFromWatchlist = (movieId) => this.removeFromWatchlist(movieId);
    }

    initializeWatchlistButtons() {
        const watchlistButtons = document.querySelectorAll('[onclick*="toggleWatchlist"], [onclick*="addToWatchlist"]');

        watchlistButtons.forEach(button => {
            // Add enhanced hover effects
            this.addButtonHoverEffects(button);

            // Add click animation
            button.addEventListener('click', (e) => {
                this.addClickAnimation(button);
            });
        });
    }

    async toggleWatchlist(movieId) {
        if (this.isProcessing) return;

        const button = this.findWatchlistButton(movieId);
        if (!button) return;

        this.isProcessing = true;

        try {
            // Add loading state with animation
            this.setLoadingState(button, true);

            const response = await fetch(`/watchlist/toggle/${movieId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });

            const data = await response.json();

            if (data.success) {
                // Animate success state
                await this.animateSuccess(button, data.action);
                this.showNotification(data.message, 'success');
            } else {
                throw new Error(data.message || 'Failed to update watchlist');
            }

        } catch (error) {
            console.error('Watchlist error:', error);
            this.animateError(button);
            this.showNotification(error.message || 'An error occurred', 'error');
        } finally {
            this.setLoadingState(button, false);
            this.isProcessing = false;
        }
    }

    async addToWatchlist(movieId) {
        if (this.isProcessing) return;

        const button = this.findWatchlistButton(movieId);
        if (!button) return;

        this.isProcessing = true;

        try {
            this.setLoadingState(button, true);

            const response = await fetch(`/watchlist/add/${movieId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });

            if (!response.ok) {
                if (response.status === 401) {
                    this.showLoginPrompt();
                    return;
                }
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                await this.animateSuccess(button, 'added');
                this.showNotification(data.message || 'Added to watchlist!', 'success');
            } else {
                throw new Error(data.message || 'Failed to add to watchlist');
            }

        } catch (error) {
            console.error('Add to watchlist error:', error);
            this.animateError(button);

            const errorMessage = error.message.includes('401') ? 'Please login first' :
                               error.message.includes('419') ? 'Security token expired. Please refresh the page' :
                               'An error occurred. Please try again.';

            this.showNotification(errorMessage, 'error');
        } finally {
            this.setLoadingState(button, false);
            this.isProcessing = false;
        }
    }

    async removeFromWatchlist(movieId) {
        // Similar implementation to addToWatchlist but for removal
        // Implementation would be similar with different endpoint
    }

    findWatchlistButton(movieId) {
        return document.querySelector(`[onclick*="${movieId}"]`) ||
               document.getElementById(`watchlist-btn-${movieId}`);
    }

    setLoadingState(button, isLoading) {
        if (!button) return;

        if (isLoading) {
            button.disabled = true;
            button.dataset.originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.style.transform = 'scale(0.95)';
            button.style.opacity = '0.7';
        } else {
            button.disabled = false;
            if (button.dataset.originalContent) {
                button.innerHTML = button.dataset.originalContent;
            }
            button.style.transform = '';
            button.style.opacity = '';
        }
    }

    async animateSuccess(button, action) {
        if (!button) return;

        // Success animation sequence
        button.style.transform = 'scale(1.2)';
        button.style.background = '#4ade80';
        button.style.borderColor = '#4ade80';

        if (action === 'added') {
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.add('btn-success');
        } else if (action === 'removed') {
            button.innerHTML = '<i class="fas fa-minus"></i>';
        }

        // Bounce animation
        await this.sleep(200);
        button.style.transform = 'scale(1)';

        await this.sleep(100);
        button.style.transform = 'scale(1.1)';

        await this.sleep(100);
        button.style.transform = 'scale(1)';

        // Update button state permanently
        if (action === 'added') {
            button.onclick = null;
            button.classList.add('watchlist-added');
        }
    }

    animateError(button) {
        if (!button) return;

        // Error shake animation
        button.style.background = '#ef4444';
        button.style.borderColor = '#ef4444';
        button.style.animation = 'shake 0.5s ease-in-out';

        setTimeout(() => {
            button.style.background = '';
            button.style.borderColor = '';
            button.style.animation = '';
        }, 500);
    }

    addButtonHoverEffects(button) {
        button.addEventListener('mouseenter', () => {
            if (!button.disabled) {
                button.style.transform = 'translateY(-2px) scale(1.05)';
                button.style.boxShadow = '0 8px 20px rgba(102, 126, 234, 0.3)';
            }
        });

        button.addEventListener('mouseleave', () => {
            if (!button.disabled) {
                button.style.transform = '';
                button.style.boxShadow = '';
            }
        });
    }

    addClickAnimation(button) {
        // Click ripple effect
        const ripple = document.createElement('span');
        ripple.className = 'ripple-effect';
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;

        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (rect.width / 2 - size / 2) + 'px';
        ripple.style.top = (rect.height / 2 - size / 2) + 'px';

        button.style.position = 'relative';
        button.style.overflow = 'hidden';
        button.appendChild(ripple);

        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 600);
    }

    showLoginPrompt() {
        this.showNotification('Please login to add movies to watchlist', 'warning');

        // Smooth redirect after delay
        setTimeout(() => {
            document.body.style.opacity = '0.8';
            setTimeout(() => {
                window.location.href = '/login';
            }, 300);
        }, 1500);
    }

    showNotification(message, type = 'info') {
        // Enhanced notification system
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;

        // Notification styles
        const colors = {
            success: '#4ade80',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };

        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        notification.innerHTML = `
            <i class="${icons[type]}"></i>
            <span>${message}</span>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 16px 20px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            background: ${colors[type]};
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transform: translateX(400px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 350px;
            display: flex;
            align-items: center;
            gap: 10px;
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto dismiss
        setTimeout(() => {
            notification.classList.add('notification-exit');
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 400);
        }, 3000);
    }

    getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .watchlist-added {
        background: #4ade80 !important;
        border-color: #4ade80 !important;
        pointer-events: none;
    }
`;
document.head.appendChild(style);

// Auto-initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('❤️ Watchlist component loading...');
    window.watchlistComponent = new WatchlistComponent();
    console.log('❤️ Watchlist component loaded');
});

// Export for use in other components
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WatchlistComponent;
}