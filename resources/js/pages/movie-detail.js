// Movie Detail Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎬 Movie detail page loaded');

    // Initialize components
    initializePageAnimations();
    initializeImageHandling();
    initializeScrollEffects();
    enhanceWatchlistButton();
});

// Page entrance animations
function initializePageAnimations() {
    const elements = [
        { selector: '.hero-content', delay: 200 },
        { selector: '.movie-title', delay: 400 },
        { selector: '.meta-badge', delay: 600, stagger: 100 },
        { selector: '.genre-tag', delay: 800, stagger: 50 },
        { selector: '.info-card', delay: 1000, stagger: 200 },
        { selector: '.stat-card', delay: 1200, stagger: 150 },
        { selector: '.movie-card', delay: 1400, stagger: 100 }
    ];

    elements.forEach(({ selector, delay, stagger = 0 }) => {
        const items = document.querySelectorAll(selector);

        items.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(30px)';

            setTimeout(() => {
                item.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, delay + (index * stagger));
        });
    });

    // Animate section titles
    setTimeout(() => {
        const sectionTitles = document.querySelectorAll('.section-title');
        sectionTitles.forEach(title => {
            title.classList.add('animate');
        });
    }, 1600);
}

// Enhanced image handling with fallbacks
function initializeImageHandling() {
    const backdropImg = document.querySelector('.hero-backdrop-img');
    const posterImg = document.querySelector('.poster-img');
    const cardImages = document.querySelectorAll('.movie-card-img');

    // Handle backdrop image
    if (backdropImg) {
        handleImageWithFallback(backdropImg, () => {
            // Success callback
            backdropImg.style.opacity = '0.4';
            backdropImg.style.transition = 'opacity 1s ease';
        }, () => {
            // Error callback - use gradient fallback
            const heroBackdrop = backdropImg.closest('.hero-backdrop');
            if (heroBackdrop) {
                heroBackdrop.classList.add('hero-backdrop-fallback');
                backdropImg.style.display = 'none';
            }
        });
    }

    // Handle poster image
    if (posterImg) {
        handleImageWithFallback(posterImg, null, () => {
            posterImg.src = generateFallbackPoster(
                posterImg.alt || 'Movie Poster'
            );
        });
    }

    // Handle related movie images
    cardImages.forEach(img => {
        handleImageWithFallback(img, null, () => {
            img.src = generateFallbackPoster(
                img.alt || 'Movie'
            );
        });
    });

    // Lazy loading for related movies
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

// Image handling utility
function handleImageWithFallback(img, successCallback, errorCallback) {
    // Set initial styles
    img.style.transition = 'opacity 0.5s ease';

    // Check if image is already loaded (cached)
    if (img.complete && img.naturalHeight !== 0) {
        img.style.opacity = '1';
        if (successCallback) successCallback();
        return;
    }

    // Set initial opacity for smooth loading
    img.style.opacity = '0';

    img.addEventListener('load', function() {
        this.style.opacity = '1';
        if (successCallback) successCallback();
    });

    img.addEventListener('error', function() {
        console.warn('Failed to load image:', this.src);
        if (errorCallback) errorCallback();
    });
}

// Generate fallback poster with movie title
function generateFallbackPoster(title) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = 300;
    canvas.height = 450;

    // Create gradient background
    const gradient = ctx.createLinearGradient(0, 0, 300, 450);
    gradient.addColorStop(0, '#667eea');
    gradient.addColorStop(1, '#764ba2');

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 300, 450);

    // Add title text
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 24px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    // Wrap text
    const words = title.split(' ');
    const lines = [];
    let currentLine = words[0];

    for (let i = 1; i < words.length; i++) {
        const word = words[i];
        const width = ctx.measureText(currentLine + ' ' + word).width;
        if (width < 260) {
            currentLine += ' ' + word;
        } else {
            lines.push(currentLine);
            currentLine = word;
        }
    }
    lines.push(currentLine);

    // Draw lines
    const lineHeight = 30;
    const startY = 225 - (lines.length * lineHeight) / 2;

    lines.forEach((line, index) => {
        ctx.fillText(line, 150, startY + (index * lineHeight));
    });

    return canvas.toDataURL();
}

// Scroll-based effects
function initializeScrollEffects() {
    const heroBackdrop = document.querySelector('.hero-backdrop');
    const statCards = document.querySelectorAll('.stat-card');

    if (!heroBackdrop) return;

    let ticking = false;

    function updateScrollEffects() {
        const scrollY = window.pageYOffset;
        const heroHeight = heroBackdrop.offsetHeight;

        // Parallax effect on backdrop
        if (scrollY < heroHeight) {
            const backdropImg = heroBackdrop.querySelector('.hero-backdrop-img');
            if (backdropImg) {
                const speed = scrollY * 0.5;
                backdropImg.style.transform = `translateY(${speed}px)`;
            }
        }

        // Animate stats when they come into view
        statCards.forEach(card => {
            const rect = card.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                card.classList.add('animate');
            }
        });

        ticking = false;
    }

    function requestScrollUpdate() {
        if (!ticking) {
            requestAnimationFrame(updateScrollEffects);
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestScrollUpdate, { passive: true });
}


// Enhanced watchlist button functionality
function enhanceWatchlistButton() {
    const watchlistButtons = document.querySelectorAll('[onclick*="addToWatchlist"]');

    watchlistButtons.forEach(button => {
        // Add enhanced click effect
        button.addEventListener('click', function(e) {
            // Add ripple effect
            createRippleEffect(this, e);

            // Add loading state
            this.classList.add('watchlist-loading');

            // Remove loading state after a delay (will be overridden by actual response)
            setTimeout(() => {
                this.classList.remove('watchlist-loading');
            }, 3000);
        });

        // Add enhanced hover effects
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.05)';
        });

        button.addEventListener('mouseleave', function() {
            if (!this.classList.contains('watchlist-loading')) {
                this.style.transform = '';
            }
        });
    });
}

// Create ripple effect on button click
function createRippleEffect(button, event) {
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    `;

    // Add ripple animation CSS if not already added
    if (!document.querySelector('#ripple-styles')) {
        const style = document.createElement('style');
        style.id = 'ripple-styles';
        style.textContent = `
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    button.style.position = 'relative';
    button.style.overflow = 'hidden';
    button.appendChild(ripple);

    setTimeout(() => {
        if (ripple.parentNode) {
            ripple.parentNode.removeChild(ripple);
        }
    }, 600);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Press 'P' to play movie
    if (e.key.toLowerCase() === 'p' && !e.ctrlKey && !e.altKey) {
        const playButton = document.querySelector('.play-btn');
        if (playButton) {
            playButton.click();
        }
    }

    // Press 'W' to add to watchlist
    if (e.key.toLowerCase() === 'w' && !e.ctrlKey && !e.altKey) {
        const watchlistButton = document.querySelector('[onclick*="addToWatchlist"]');
        if (watchlistButton && !watchlistButton.disabled) {
            watchlistButton.click();
        }
    }

    // Press 'B' to go back
    if (e.key.toLowerCase() === 'b' && !e.ctrlKey && !e.altKey) {
        window.history.back();
    }
});

// Export functions for global access
window.movieDetailPage = {
    createRippleEffect,
    generateFallbackPoster,
    handleImageWithFallback
};