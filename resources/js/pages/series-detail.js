// Series Detail Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('📺 Series detail page loaded');

    // Initialize components
    initializeStickyNavigation();
    initializeEpisodeGrid();
    initializeSeasonSwitching();
    initializePageAnimations();
    initializeThumbnailLoading();
    addKeyboardShortcuts();
});

// Sticky Season Navigation
function initializeStickyNavigation() {
    const seasonsNav = document.querySelector('.seasons-nav');
    if (!seasonsNav) {
        // Check if we have season cards first
        const seasonCards = document.querySelectorAll('.season-card');
        if (seasonCards.length > 0) {
            createStickyNavigation();
        }
        return;
    }

    let lastScrollY = window.pageYOffset;

    window.addEventListener('scroll', () => {
        const currentScrollY = window.pageYOffset;

        // Add scrolled class for styling
        if (currentScrollY > 100) {
            seasonsNav.classList.add('scrolled');
        } else {
            seasonsNav.classList.remove('scrolled');
        }

        // Auto-hide/show on scroll
        if (currentScrollY > lastScrollY && currentScrollY > 200) {
            // Scrolling down
            seasonsNav.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up
            seasonsNav.style.transform = 'translateY(0)';
        }

        lastScrollY = currentScrollY;
    }, { passive: true });

    // Smooth scroll to seasons
    const navItems = seasonsNav.querySelectorAll('.season-nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                const offsetTop = targetElement.offsetTop - seasonsNav.offsetHeight - 80;

                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });

                // Update active state
                updateActiveNavItem(this);
            }
        });
    });

    // Update active nav item based on scroll position
    window.addEventListener('scroll', updateActiveNavOnScroll, { passive: true });
}

// Create sticky navigation if it doesn't exist
function createStickyNavigation() {
    const seasonCards = document.querySelectorAll('.season-card');
    if (seasonCards.length === 0) return;

    // Check if navigation already exists
    if (document.querySelector('.seasons-nav')) return;

    const navContainer = document.createElement('div');
    navContainer.className = 'seasons-nav';
    navContainer.innerHTML = `
        <div class="container">
            <div class="seasons-nav-list">
                ${Array.from(seasonCards).map((card, index) => {
                    const seasonTitle = card.querySelector('.season-title');
                    const seasonNumber = index + 1;
                    const title = seasonTitle ? seasonTitle.textContent : `Season ${seasonNumber}`;

                    return `
                        <a href="#season-${seasonNumber}" class="season-nav-item" data-season="${seasonNumber}">
                            ${title}
                        </a>
                    `;
                }).join('')}
            </div>
        </div>
    `;

    // Add IDs to season cards
    seasonCards.forEach((card, index) => {
        card.id = `season-${index + 1}`;
    });

    // Insert navigation at the beginning of seasons section
    const seasonsSection = document.querySelector('.seasons-section');
    if (seasonsSection) {
        try {
            // Get the first element (should be the section title)
            const sectionTitle = seasonsSection.querySelector('.section-title');
            if (sectionTitle && sectionTitle.nextSibling) {
                seasonsSection.insertBefore(navContainer, sectionTitle.nextSibling);
            } else {
                seasonsSection.appendChild(navContainer);
            }
        } catch (error) {
            console.warn('Failed to insert navigation, appending instead:', error);
            seasonsSection.appendChild(navContainer);
        }
    }

    // Re-initialize with the created navigation
    setTimeout(() => {
        const newNav = document.querySelector('.seasons-nav');
        if (newNav) {
            setupNavigationEventListeners(newNav);
        }
    }, 100);
}

// Setup navigation event listeners separately
function setupNavigationEventListeners(seasonsNav) {
    let lastScrollY = window.pageYOffset;

    window.addEventListener('scroll', () => {
        const currentScrollY = window.pageYOffset;

        // Add scrolled class for styling
        if (currentScrollY > 100) {
            seasonsNav.classList.add('scrolled');
        } else {
            seasonsNav.classList.remove('scrolled');
        }

        // Auto-hide/show on scroll
        if (currentScrollY > lastScrollY && currentScrollY > 200) {
            // Scrolling down
            seasonsNav.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up
            seasonsNav.style.transform = 'translateY(0)';
        }

        lastScrollY = currentScrollY;
    }, { passive: true });

    // Smooth scroll to seasons
    const navItems = seasonsNav.querySelectorAll('.season-nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                const offsetTop = targetElement.offsetTop - seasonsNav.offsetHeight - 80;

                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });

                // Update active state
                updateActiveNavItem(this);
            }
        });
    });

    // Update active nav item based on scroll position
    window.addEventListener('scroll', updateActiveNavOnScroll, { passive: true });
}

// Update active navigation item
function updateActiveNavItem(activeItem) {
    const navItems = document.querySelectorAll('.season-nav-item');
    navItems.forEach(item => item.classList.remove('active'));
    activeItem.classList.add('active');
}

// Update active nav based on scroll position
function updateActiveNavOnScroll() {
    const seasonCards = document.querySelectorAll('.season-card');
    const navItems = document.querySelectorAll('.season-nav-item');
    const scrollPosition = window.pageYOffset + 200;

    let activeIndex = 0;

    seasonCards.forEach((card, index) => {
        if (card.offsetTop <= scrollPosition) {
            activeIndex = index;
        }
    });

    navItems.forEach((item, index) => {
        if (index === activeIndex) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

// Enhanced Episode Grid with Visual Improvements
function initializeEpisodeGrid() {
    const episodeCards = document.querySelectorAll('.episode-card');

    episodeCards.forEach((card, index) => {
        // Add episode thumbnails if missing
        addEpisodeThumbnail(card);

        // Add play overlay
        addPlayOverlay(card);

        // Add click handlers
        card.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Episode card clicked:', this);
            handleEpisodeClick(this, index);
        });

        // Add enhanced hover effects
        addEpisodeHoverEffects(card);

        // Add loading states
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Add episode thumbnail
function addEpisodeThumbnail(card) {
    const episodeInfo = card.querySelector('.episode-info');
    const episodeTitle = card.querySelector('.episode-title');

    if (!card.querySelector('.episode-thumbnail') && !card.querySelector('.episode-thumbnail-placeholder')) {
        const thumbnailContainer = document.createElement('div');
        thumbnailContainer.className = 'episode-thumbnail-container';
        thumbnailContainer.style.position = 'relative';

        // Get poster URL from data attribute
        const posterUrl = card.dataset.episodePoster;

        if (posterUrl && posterUrl.trim()) {
            // Use episode poster URL from database
            const thumbnail = document.createElement('img');
            thumbnail.className = 'episode-thumbnail';
            thumbnail.src = posterUrl;
            thumbnail.alt = episodeTitle?.textContent || 'Episode thumbnail';
            thumbnail.loading = 'lazy';

            thumbnail.addEventListener('error', function() {
                this.replaceWith(createThumbnailPlaceholder());
            });

            thumbnailContainer.appendChild(thumbnail);
        } else {
            // Fallback to placeholder if no poster URL
            thumbnailContainer.appendChild(createThumbnailPlaceholder());
        }

        // Insert thumbnail before episode info
        try {
            if (episodeInfo && episodeInfo.parentNode === card) {
                card.insertBefore(thumbnailContainer, episodeInfo);
            } else {
                card.appendChild(thumbnailContainer);
            }
        } catch (error) {
            console.warn('Failed to insert thumbnail, appending instead:', error);
            card.appendChild(thumbnailContainer);
        }
    }
}

// Create thumbnail placeholder
function createThumbnailPlaceholder() {
    const placeholder = document.createElement('div');
    placeholder.className = 'episode-thumbnail-placeholder';
    placeholder.innerHTML = '<i class="fas fa-play-circle"></i>';
    return placeholder;
}


// Add play overlay to episodes
function addPlayOverlay(card) {
    const thumbnailContainer = card.querySelector('.episode-thumbnail-container');
    if (!thumbnailContainer) return;

    const playOverlay = document.createElement('div');
    playOverlay.className = 'episode-play-overlay';
    playOverlay.innerHTML = '<i class="fas fa-play"></i>';

    thumbnailContainer.style.position = 'relative';
    thumbnailContainer.appendChild(playOverlay);
}

// Handle episode click
function handleEpisodeClick(card, index) {
    const episodeTitle = card.querySelector('.episode-title')?.textContent;
    const episodeNumber = card.querySelector('.episode-number')?.textContent;

    // Add click animation
    card.style.transform = 'scale(0.98)';
    setTimeout(() => {
        card.style.transform = '';
    }, 150);

    // Get series ID from URL or data attribute
    const seriesId = getSeriesIdFromUrl();
    const episodeId = card.dataset.episodeId || episodeNumber;

    console.log(`Playing Episode ${episodeNumber}: ${episodeTitle}`);

    // Show notification
    if (window.showNotification) {
        window.showNotification(`Loading Episode ${episodeNumber}...`, 'info');
    }

    // Navigate to episode player after animation
    setTimeout(() => {
        if (seriesId && episodeId) {
            window.location.href = `/series/${seriesId}/episode/${episodeId}/watch`;
        } else {
            console.error('Series ID or Episode ID not found');
            if (window.showNotification) {
                window.showNotification('Error: Unable to play episode', 'error');
            }
        }
    }, 300);
}

// Helper function to get series ID from current URL
function getSeriesIdFromUrl() {
    const urlPath = window.location.pathname;
    const match = urlPath.match(/\/series\/(\d+)/);
    return match ? match[1] : null;
}

// Add enhanced hover effects to episodes
function addEpisodeHoverEffects(card) {
    card.addEventListener('mouseenter', function() {
        // Add glow effect
        this.style.boxShadow = '0 10px 30px rgba(0, 255, 136, 0.2), 0 0 20px rgba(0, 255, 136, 0.1)';

        // Animate episode number
        const episodeNumber = this.querySelector('.episode-number');
        if (episodeNumber) {
            episodeNumber.style.transform = 'scale(1.1) rotate(5deg)';
        }
    });

    card.addEventListener('mouseleave', function() {
        // Reset effects
        this.style.boxShadow = '';

        const episodeNumber = this.querySelector('.episode-number');
        if (episodeNumber) {
            episodeNumber.style.transform = '';
        }
    });
}

// Season switching functionality
function initializeSeasonSwitching() {
    const seasonCards = document.querySelectorAll('.season-card');

    // Add toggle functionality to season headers
    seasonCards.forEach(card => {
        const header = card.querySelector('.season-header');
        const episodesGrid = card.querySelector('.episodes-grid');

        if (header && episodesGrid) {
            // Add toggle icon
            const toggleIcon = document.createElement('i');
            toggleIcon.className = 'fas fa-chevron-down season-toggle';
            toggleIcon.style.cssText = `
                margin-left: 1rem;
                transition: transform 0.3s ease;
                cursor: pointer;
                color: var(--accent-color);
            `;

            header.appendChild(toggleIcon);

            // Add click handler
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                const isExpanded = episodesGrid.style.display !== 'none';

                if (isExpanded) {
                    // Collapse
                    episodesGrid.style.maxHeight = episodesGrid.scrollHeight + 'px';
                    episodesGrid.offsetHeight; // Force reflow
                    episodesGrid.style.maxHeight = '0';
                    episodesGrid.style.opacity = '0';
                    toggleIcon.style.transform = 'rotate(-90deg)';

                    setTimeout(() => {
                        episodesGrid.style.display = 'none';
                    }, 300);
                } else {
                    // Expand
                    episodesGrid.style.display = 'grid';
                    episodesGrid.style.maxHeight = '0';
                    episodesGrid.style.opacity = '0';

                    setTimeout(() => {
                        episodesGrid.style.maxHeight = episodesGrid.scrollHeight + 'px';
                        episodesGrid.style.opacity = '1';
                        toggleIcon.style.transform = 'rotate(0deg)';

                        setTimeout(() => {
                            episodesGrid.style.maxHeight = '';
                        }, 300);
                    }, 10);
                }
            });

            // Add transition styles
            episodesGrid.style.transition = 'max-height 0.3s ease, opacity 0.3s ease';
            episodesGrid.style.overflow = 'hidden';
        }
    });
}

// Page entrance animations
function initializePageAnimations() {
    const animationElements = [
        { selector: '.series-poster', delay: 200 },
        { selector: '.series-info', delay: 400 },
        { selector: '.series-badges', delay: 600 },
        { selector: '.series-title', delay: 800 },
        { selector: '.series-meta', delay: 1000 },
        { selector: '.series-genres', delay: 1200 },
        { selector: '.series-description', delay: 1400 }
    ];

    animationElements.forEach(({ selector, delay }) => {
        const element = document.querySelector(selector);
        if (element) {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';

            setTimeout(() => {
                element.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, delay);
        }
    });

    // Animate season cards
    const seasonCards = document.querySelectorAll('.season-card');
    seasonCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(40px)';

        setTimeout(() => {
            card.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 1600 + (index * 200));
    });
}

// Thumbnail loading with intersection observer
function initializeThumbnailLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;

                    // Add loading animation
                    img.style.opacity = '0';
                    img.style.transform = 'scale(1.1)';

                    // Simulate loading
                    setTimeout(() => {
                        img.style.transition = 'all 0.5s ease';
                        img.style.opacity = '1';
                        img.style.transform = 'scale(1)';
                    }, 100);

                    imageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px'
        });

        // Observe all thumbnails
        document.querySelectorAll('.episode-thumbnail, .series-poster').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

// Keyboard shortcuts
function addKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Press 'S' to scroll to seasons
        if (e.key.toLowerCase() === 's' && !e.ctrlKey && !e.altKey) {
            const seasonsSection = document.querySelector('.seasons-section');
            if (seasonsSection) {
                seasonsSection.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Press number keys to jump to season
        if (e.key >= '1' && e.key <= '9' && !e.ctrlKey && !e.altKey) {
            const seasonNumber = parseInt(e.key);
            const seasonCard = document.getElementById(`season-${seasonNumber}`);
            if (seasonCard) {
                seasonCard.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Press 'E' to focus on first episode
        if (e.key.toLowerCase() === 'e' && !e.ctrlKey && !e.altKey) {
            const firstEpisode = document.querySelector('.episode-card');
            if (firstEpisode) {
                firstEpisode.scrollIntoView({ behavior: 'smooth' });
                firstEpisode.focus();
            }
        }

        // Press 'B' to go back
        if (e.key.toLowerCase() === 'b' && !e.ctrlKey && !e.altKey) {
            window.history.back();
        }
    });
}

// Utility functions
function formatRating(rating) {
    return rating ? parseFloat(rating).toFixed(1) : 'N/A';
}

function formatRuntime(minutes) {
    if (!minutes) return 'Unknown';

    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
}

function formatAirDate(dateString) {
    if (!dateString) return 'TBA';

    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Export functions for global access
window.seriesDetailPage = {
    initializeStickyNavigation,
    initializeEpisodeGrid,
    initializeSeasonSwitching,
    handleEpisodeClick,
    formatRating,
    formatRuntime,
    formatAirDate
};

// Add CSS for dynamic styles
const style = document.createElement('style');
style.textContent = `
    .season-toggle {
        transition: transform 0.3s ease !important;
    }

    .episode-card:focus {
        outline: 2px solid var(--accent-color);
        outline-offset: 2px;
    }

    .episodes-grid {
        transition: max-height 0.3s ease, opacity 0.3s ease;
    }
`;
document.head.appendChild(style);