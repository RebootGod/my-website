/* ======================================== */
/* SCROLL ANIMATIONS COMPONENT */
/* ======================================== */
/* Phase 6.2: Scroll-triggered animations */
/* Reveal elements, parallax effects, scroll progress */

class ScrollAnimations {
    constructor() {
        this.observers = new Map();
        this.animatedElements = new Set();
        this.scrollProgress = null;
        this.init();
    }

    init() {
        console.log('📜 Scroll Animations: Initializing...');
        
        this.setupIntersectionObserver();
        this.setupScrollProgress();
        this.setupParallax();
        this.initializeElements();
        
        console.log('✅ Scroll Animations: Ready');
    }

    /**
     * Setup Intersection Observer for scroll animations
     */
    setupIntersectionObserver() {
        const options = {
            root: null,
            rootMargin: '0px 0px -100px 0px',
            threshold: 0.1
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.animatedElements.has(entry.target)) {
                    this.animateElement(entry.target);
                    this.animatedElements.add(entry.target);
                }
            });
        }, options);
    }

    /**
     * Initialize elements with scroll animations
     */
    initializeElements() {
        // Elements with data-scroll attribute
        const scrollElements = document.querySelectorAll('[data-scroll]');
        scrollElements.forEach(el => {
            this.observer.observe(el);
        });

        // Elements with scroll-reveal class
        const revealElements = document.querySelectorAll('.scroll-reveal');
        revealElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = this.getInitialTransform(el);
            this.observer.observe(el);
        });
    }

    /**
     * Get initial transform based on animation type
     */
    getInitialTransform(element) {
        const animation = element.dataset.scrollAnimation || 'fade-up';
        
        const transforms = {
            'fade-up': 'translateY(30px)',
            'fade-down': 'translateY(-30px)',
            'fade-left': 'translateX(30px)',
            'fade-right': 'translateX(-30px)',
            'zoom-in': 'scale(0.9)',
            'zoom-out': 'scale(1.1)',
            'flip-x': 'rotateX(90deg)',
            'flip-y': 'rotateY(90deg)'
        };

        return transforms[animation] || transforms['fade-up'];
    }

    /**
     * Animate element when it comes into view
     */
    animateElement(element) {
        const delay = parseInt(element.dataset.scrollDelay || 0);
        const duration = parseInt(element.dataset.scrollDuration || 600);
        
        setTimeout(() => {
            element.style.transition = `opacity ${duration}ms ease, transform ${duration}ms ease`;
            element.style.opacity = '1';
            element.style.transform = 'none';
            
            // Add animated class for CSS hooks
            element.classList.add('scroll-animated');
            
            // Trigger custom event
            element.dispatchEvent(new CustomEvent('scrollAnimated'));
        }, delay);
    }

    /**
     * Setup scroll progress indicator
     */
    setupScrollProgress() {
        // Create progress bar
        this.scrollProgress = document.createElement('div');
        this.scrollProgress.className = 'scroll-progress-bar';
        document.body.appendChild(this.scrollProgress);

        // Update on scroll
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    this.updateScrollProgress();
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    /**
     * Update scroll progress bar
     */
    updateScrollProgress() {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        
        if (this.scrollProgress) {
            this.scrollProgress.style.width = scrolled + '%';
        }
    }

    /**
     * Setup parallax effects
     */
    setupParallax() {
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        
        if (parallaxElements.length === 0) return;

        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    this.updateParallax(parallaxElements);
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    /**
     * Update parallax elements
     */
    updateParallax(elements) {
        const scrollY = window.pageYOffset;

        elements.forEach(element => {
            const rect = element.getBoundingClientRect();
            const elementTop = rect.top + scrollY;
            const speed = parseFloat(element.dataset.parallax || 0.5);

            // Only update if element is in viewport
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                const yPos = (scrollY - elementTop) * speed;
                element.style.transform = `translateY(${yPos}px)`;
            }
        });
    }

    /**
     * Stagger animation for multiple elements
     */
    staggerAnimate(selector, delay = 100) {
        const elements = document.querySelectorAll(selector);
        
        elements.forEach((element, index) => {
            element.dataset.scrollDelay = index * delay;
            this.observer.observe(element);
        });
    }

    /**
     * Add element to be observed
     */
    observe(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            this.observer.observe(element);
        }
    }

    /**
     * Remove element from observation
     */
    unobserve(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            this.observer.unobserve(element);
        }
    }

    /**
     * Scroll to element with smooth animation
     */
    scrollTo(target, offset = 0) {
        let targetElement;
        
        if (typeof target === 'string') {
            targetElement = document.querySelector(target);
        } else {
            targetElement = target;
        }

        if (!targetElement) return;

        const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - offset;
        
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }

    /**
     * Check if element is in viewport
     */
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Reset animations (useful for dynamic content)
     */
    reset() {
        this.animatedElements.clear();
        const elements = document.querySelectorAll('.scroll-animated');
        elements.forEach(el => {
            el.classList.remove('scroll-animated');
            el.style.opacity = '0';
            el.style.transform = this.getInitialTransform(el);
        });
    }
}

// Inject scroll animation styles
const scrollStyles = document.createElement('style');
scrollStyles.textContent = `
.scroll-progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    z-index: 9999;
    transition: width 0.1s ease;
    width: 0%;
}

.scroll-reveal {
    will-change: opacity, transform;
}

.scroll-animated {
    animation-fill-mode: forwards;
}

[data-parallax] {
    will-change: transform;
}

/* Smooth scroll for all */
html {
    scroll-behavior: smooth;
}

/* Optional: Disable animations for users who prefer reduced motion */
@media (prefers-reduced-motion: reduce) {
    .scroll-reveal,
    .scroll-animated,
    [data-parallax] {
        animation: none !important;
        transition: none !important;
        transform: none !important;
        opacity: 1 !important;
    }
    
    .scroll-progress-bar {
        display: none;
    }
}
`;

document.head.appendChild(scrollStyles);

// Initialize on DOM ready
let scrollAnimations;
document.addEventListener('DOMContentLoaded', () => {
    scrollAnimations = new ScrollAnimations();
    window.scrollAnimations = scrollAnimations;
});

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ScrollAnimations;
}
