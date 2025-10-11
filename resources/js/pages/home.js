// Home Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏠 Home page loaded');

    // Initialize components
    initializeFilters();
    initializeMobileFilters();
    initializeSearch();
    initializeLoadingStates();
    initializeImageLoading();
});

// Mobile Filter Bottom Sheet
function initializeMobileFilters() {
    const toggleBtn = document.getElementById('filterToggleBtn');
    const bottomSheet = document.getElementById('filterBottomSheet');
    const overlay = document.getElementById('filterOverlay');
    const closeBtn = document.getElementById('filterCloseBtn');

    if (!toggleBtn || !bottomSheet || !overlay) {
        console.warn('⚠️ Filter elements not found');
        return;
    }

    // Add loaded class for entrance animation
    setTimeout(() => {
        toggleBtn.classList.add('loaded');
    }, 100);

    console.log('✅ Mobile filters initialized');

    // Open bottom sheet
    toggleBtn.addEventListener('click', openFilterSheet);
    
    // Close bottom sheet
    closeBtn?.addEventListener('click', closeFilterSheet);
    overlay.addEventListener('click', closeFilterSheet);
    
    // Swipe down to close (touch gesture)
    let startY = 0;
    let currentY = 0;
    
    bottomSheet.addEventListener('touchstart', (e) => {
        startY = e.touches[0].clientY;
    });
    
    bottomSheet.addEventListener('touchmove', (e) => {
        currentY = e.touches[0].clientY;
        const diff = currentY - startY;
        
        // Only allow dragging down
        if (diff > 0) {
            bottomSheet.style.transform = `translateY(${diff}px)`;
        }
    });
    
    bottomSheet.addEventListener('touchend', () => {
        const diff = currentY - startY;
        
        // Close if dragged down more than 100px
        if (diff > 100) {
            closeFilterSheet();
        } else {
            // Snap back
            bottomSheet.style.transform = 'translateY(0)';
        }
        
        startY = 0;
        currentY = 0;
    });
    
    // Prevent body scroll when sheet is open
    function disableBodyScroll() {
        document.body.style.overflow = 'hidden';
    }
    
    function enableBodyScroll() {
        document.body.style.overflow = '';
    }
    
    function openFilterSheet() {
        console.log('🔍 Opening filter sheet');
        bottomSheet.classList.add('active');
        overlay.classList.add('active');
        disableBodyScroll();
        
        // Add haptic feedback on mobile
        if (navigator.vibrate) {
            navigator.vibrate(10);
        }
    }
    
    function closeFilterSheet() {
        console.log('🔍 Closing filter sheet');
        bottomSheet.classList.remove('active');
        overlay.classList.remove('active');
        bottomSheet.style.transform = '';
        enableBodyScroll();
    }
    
    // Export for global access
    window.openFilterSheet = openFilterSheet;
    window.closeFilterSheet = closeFilterSheet;
}

// Filter functionality
function initializeFilters() {
    const filterSelects = document.querySelectorAll('.modern-select');

    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Add loading state
            this.classList.add('filter-loading');

            // Submit form with smooth transition
            setTimeout(() => {
                this.closest('form').submit();
            }, 200);
        });
    });
}

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('.modern-select[name="search"]');
    if (!searchInput) return;

    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);

        // Add visual feedback
        this.classList.add('filter-loading');

        // Debounce search
        searchTimeout = setTimeout(() => {
            this.classList.remove('filter-loading');
            // Future: implement real-time search suggestions
        }, 500);
    });
}

// Loading states management
function initializeLoadingStates() {
    // Show skeleton loading while page loads
    showSkeletonCards();

    // Hide skeleton when content is loaded
    window.addEventListener('load', function() {
        setTimeout(() => {
            hideSkeletonCards();
            showMovieCards();
        }, 500);
    });
}

function showSkeletonCards() {
    const movieGrid = document.querySelector('.movie-grid');
    if (!movieGrid) return;

    // Create skeleton cards
    const skeletonCards = Array.from({length: 10}, () => createSkeletonCard()).join('');

    // Insert skeleton cards before actual content
    movieGrid.insertAdjacentHTML('afterbegin', `
        <div class="skeleton-container">
            ${skeletonCards}
        </div>
    `);
}

function hideSkeletonCards() {
    const skeletonContainer = document.querySelector('.skeleton-container');
    if (skeletonContainer) {
        skeletonContainer.style.opacity = '0';
        setTimeout(() => {
            skeletonContainer.remove();
        }, 300);
    }
}

function showMovieCards() {
    const movieCards = document.querySelectorAll('.movie-card-modern');

    movieCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.classList.add('fade-in-up');
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function createSkeletonCard() {
    return `
        <div class="skeleton-card">
            <div class="skeleton-poster skeleton-loader"></div>
            <div class="skeleton-info">
                <div class="skeleton-title skeleton-loader"></div>
                <div class="skeleton-meta skeleton-loader"></div>
                <div class="skeleton-description skeleton-loader"></div>
                <div class="skeleton-description skeleton-loader"></div>
                <div class="skeleton-actions">
                    <div class="skeleton-btn skeleton-loader"></div>
                    <div class="skeleton-btn-icon skeleton-loader"></div>
                </div>
            </div>
        </div>
    `;
}

// Image loading with lazy loading and placeholders
function initializeImageLoading() {
    const images = document.querySelectorAll('img[loading="lazy"]');

    images.forEach(img => {
        // Add loading placeholder
        img.style.background = 'var(--card-bg)';
        img.style.minHeight = '280px';

        // Handle load event
        img.addEventListener('load', function() {
            this.classList.add('fade-in');
        });

        // Handle error event
        img.addEventListener('error', function() {
            this.src = 'https://via.placeholder.com/300x450/2c3e50/ecf0f1?text=No+Image';
            this.classList.add('fade-in');
        });
    });
}

// Watchlist functionality
function toggleWatchlist(movieId) {
    if (!movieId) return;

    const button = document.querySelector(`[onclick*="${movieId}"]`);
    if (!button) return;

    // Add loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(`/watchlist/toggle/${movieId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state with animation
            button.classList.add('btn-success');
            button.innerHTML = '<i class="fas fa-check"></i>';

            // Show notification
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');

            // Reset button
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-bookmark"></i>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');

        // Reset button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-bookmark"></i>';
    });
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 15px 20px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 300px;
    `;

    // Set background color based on type
    const colors = {
        success: '#4ade80',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };

    notification.style.background = colors[type] || colors.info;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Animate out
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Export functions for global access
window.toggleWatchlist = toggleWatchlist;
window.showNotification = showNotification;