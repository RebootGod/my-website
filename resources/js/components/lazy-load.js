/* ======================================== */
/* LAZY LOAD COMPONENT */
/* ======================================== */
/* Phase 6.3: Performance Optimization */
/* Lazy load images, videos, and iframes for better performance */

class LazyLoad {
    constructor(options = {}) {
        this.options = {
            rootMargin: options.rootMargin || '50px',
            threshold: options.threshold || 0.01,
            placeholderClass: options.placeholderClass || 'lazy-placeholder',
            loadedClass: options.loadedClass || 'lazy-loaded',
            errorClass: options.errorClass || 'lazy-error',
            ...options
        };

        this.observer = null;
        this.items = new Set();
        this.init();
    }

    init() {
        console.log('🖼️ Lazy Load: Initializing...');

        if (!('IntersectionObserver' in window)) {
            console.warn('⚠️ IntersectionObserver not supported, loading all images');
            this.loadAll();
            return;
        }

        this.setupObserver();
        this.observe();

        console.log('✅ Lazy Load: Ready');
    }

    /**
     * Setup Intersection Observer
     */
    setupObserver() {
        const options = {
            root: null,
            rootMargin: this.options.rootMargin,
            threshold: this.options.threshold
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadElement(entry.target);
                    this.observer.unobserve(entry.target);
                    this.items.delete(entry.target);
                }
            });
        }, options);
    }

    /**
     * Observe all lazy elements
     */
    observe() {
        // Images with data-src
        const images = document.querySelectorAll('img[data-src]:not(.lazy-loaded)');
        images.forEach(img => {
            this.items.add(img);
            this.observer.observe(img);
        });

        // Background images
        const bgElements = document.querySelectorAll('[data-bg]:not(.lazy-loaded)');
        bgElements.forEach(el => {
            this.items.add(el);
            this.observer.observe(el);
        });

        // Iframes (videos)
        const iframes = document.querySelectorAll('iframe[data-src]:not(.lazy-loaded)');
        iframes.forEach(iframe => {
            this.items.add(iframe);
            this.observer.observe(iframe);
        });

        // Videos
        const videos = document.querySelectorAll('video[data-src]:not(.lazy-loaded)');
        videos.forEach(video => {
            this.items.add(video);
            this.observer.observe(video);
        });
    }

    /**
     * Load element when it enters viewport
     */
    loadElement(element) {
        const tagName = element.tagName.toLowerCase();

        try {
            switch (tagName) {
                case 'img':
                    this.loadImage(element);
                    break;
                case 'iframe':
                    this.loadIframe(element);
                    break;
                case 'video':
                    this.loadVideo(element);
                    break;
                default:
                    this.loadBackground(element);
            }
        } catch (error) {
            console.error('Lazy load error:', error);
            element.classList.add(this.options.errorClass);
        }
    }

    /**
     * Load image
     */
    loadImage(img) {
        const src = img.dataset.src;
        const srcset = img.dataset.srcset;

        if (!src) return;

        // Create temporary image to preload
        const tempImg = new Image();

        tempImg.onload = () => {
            img.src = src;
            if (srcset) img.srcset = srcset;
            
            img.classList.remove(this.options.placeholderClass);
            img.classList.add(this.options.loadedClass);
            
            // Trigger custom event
            img.dispatchEvent(new CustomEvent('lazyloaded'));
        };

        tempImg.onerror = () => {
            img.classList.add(this.options.errorClass);
            img.dispatchEvent(new CustomEvent('lazyerror'));
        };

        tempImg.src = src;
    }

    /**
     * Load background image
     */
    loadBackground(element) {
        const bg = element.dataset.bg;
        if (!bg) return;

        element.style.backgroundImage = `url(${bg})`;
        element.classList.remove(this.options.placeholderClass);
        element.classList.add(this.options.loadedClass);
        
        element.dispatchEvent(new CustomEvent('lazyloaded'));
    }

    /**
     * Load iframe
     */
    loadIframe(iframe) {
        const src = iframe.dataset.src;
        if (!src) return;

        iframe.src = src;
        iframe.classList.remove(this.options.placeholderClass);
        iframe.classList.add(this.options.loadedClass);
        
        iframe.dispatchEvent(new CustomEvent('lazyloaded'));
    }

    /**
     * Load video
     */
    loadVideo(video) {
        const src = video.dataset.src;
        const sources = video.querySelectorAll('source[data-src]');

        if (src) {
            video.src = src;
        }

        sources.forEach(source => {
            source.src = source.dataset.src;
        });

        video.load();
        video.classList.remove(this.options.placeholderClass);
        video.classList.add(this.options.loadedClass);
        
        video.dispatchEvent(new CustomEvent('lazyloaded'));
    }

    /**
     * Load all elements immediately (fallback)
     */
    loadAll() {
        const allLazy = document.querySelectorAll('[data-src]:not(.lazy-loaded), [data-bg]:not(.lazy-loaded)');
        allLazy.forEach(el => this.loadElement(el));
    }

    /**
     * Update lazy elements (for dynamic content)
     */
    update() {
        this.observe();
    }

    /**
     * Disconnect observer
     */
    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    /**
     * Preload specific image
     */
    preload(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }

    /**
     * Preload multiple images
     */
    preloadMultiple(sources) {
        return Promise.all(sources.map(src => this.preload(src)));
    }
}

// Inject lazy load styles
const lazyStyles = document.createElement('style');
lazyStyles.textContent = `
.lazy-placeholder {
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.05) 25%,
        rgba(255, 255, 255, 0.1) 50%,
        rgba(255, 255, 255, 0.05) 75%
    );
    background-size: 200% 100%;
    animation: lazy-shimmer 1.5s ease-in-out infinite;
}

@keyframes lazy-shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.lazy-loaded {
    animation: lazy-fade-in 0.3s ease-out;
}

@keyframes lazy-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

.lazy-error {
    opacity: 0.3;
    filter: grayscale(100%);
}
`;

document.head.appendChild(lazyStyles);

// Initialize on DOM ready
let lazyLoad;
document.addEventListener('DOMContentLoaded', () => {
    lazyLoad = new LazyLoad();
    window.lazyLoad = lazyLoad;
});

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LazyLoad;
}
