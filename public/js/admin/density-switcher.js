/**
 * Density Switcher Module
 * 
 * Manages view density modes (compact, normal, comfortable)
 * for admin dashboard and other pages
 * 
 * Features:
 * - Three density levels: compact, normal, comfortable
 * - localStorage persistence
 * - Auto-apply on page load
 * - Custom events
 * - Keyboard shortcut (Alt+D)
 */

class DensitySwitcher {
    constructor() {
        this.storageKey = 'admin_view_density';
        this.defaultDensity = 'normal';
        this.densities = ['compact', 'normal', 'comfortable'];
        this.currentDensity = this.loadDensity();
        
        this.init();
    }
    
    /**
     * Initialize density switcher
     */
    init() {
        // Apply saved density
        this.applyDensity(this.currentDensity, false);
        
        // Setup toggle button if exists
        this.setupToggleButton();
        
        // Setup keyboard shortcut (Alt+D)
        this.setupKeyboardShortcut();
        
        // Setup dropdown if exists
        this.setupDropdown();
        
        console.log('✅ Density Switcher initialized:', this.currentDensity);
    }
    
    /**
     * Load density from localStorage
     */
    loadDensity() {
        const saved = localStorage.getItem(this.storageKey);
        if (saved && this.densities.includes(saved)) {
            return saved;
        }
        return this.defaultDensity;
    }
    
    /**
     * Save density to localStorage
     */
    saveDensity(density) {
        localStorage.setItem(this.storageKey, density);
    }
    
    /**
     * Apply density to DOM
     */
    applyDensity(density, animate = true) {
        if (!this.densities.includes(density)) {
            console.warn('Invalid density:', density);
            return;
        }
        
        // Add transition class for animation
        if (animate) {
            document.body.classList.add('density-transitioning');
        }
        
        // Remove all density classes
        this.densities.forEach(d => {
            document.body.classList.remove(`density-${d}`);
        });
        
        // Add new density class
        document.body.classList.add(`density-${density}`);
        
        // Update current density
        this.currentDensity = density;
        
        // Save to localStorage
        this.saveDensity(density);
        
        // Update toggle button
        this.updateToggleButton();
        
        // Update dropdown
        this.updateDropdown();
        
        // Dispatch custom event
        this.dispatchDensityChangeEvent(density);
        
        // Remove transition class after animation
        if (animate) {
            setTimeout(() => {
                document.body.classList.remove('density-transitioning');
            }, 300);
        }
        
        console.log('🎨 Density applied:', density);
    }
    
    /**
     * Toggle between densities (cycle through)
     */
    toggle() {
        const currentIndex = this.densities.indexOf(this.currentDensity);
        const nextIndex = (currentIndex + 1) % this.densities.length;
        const nextDensity = this.densities[nextIndex];
        
        this.applyDensity(nextDensity, true);
    }
    
    /**
     * Set specific density
     */
    setDensity(density) {
        if (this.densities.includes(density)) {
            this.applyDensity(density, true);
        }
    }
    
    /**
     * Get current density
     */
    getCurrentDensity() {
        return this.currentDensity;
    }
    
    /**
     * Setup toggle button
     */
    setupToggleButton() {
        const btn = document.getElementById('density-toggle');
        if (!btn) return;
        
        btn.addEventListener('click', () => this.toggle());
        
        // Update button text/icon
        this.updateToggleButton();
    }
    
    /**
     * Update toggle button display
     */
    updateToggleButton() {
        const btn = document.getElementById('density-toggle');
        if (!btn) return;
        
        // Icon mapping
        const icons = {
            compact: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>',
            normal: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>',
            comfortable: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>'
        };
        
        // Label mapping
        const labels = {
            compact: 'Compact',
            normal: 'Normal',
            comfortable: 'Comfortable'
        };
        
        const iconHtml = icons[this.currentDensity] || icons.normal;
        const label = labels[this.currentDensity] || 'Normal';
        
        btn.innerHTML = `${iconHtml} <span>${label}</span>`;
        btn.setAttribute('title', `Current: ${label} (Alt+D to cycle)`);
    }
    
    /**
     * Setup dropdown selector
     */
    setupDropdown() {
        const dropdown = document.getElementById('density-dropdown');
        if (!dropdown) return;
        
        dropdown.addEventListener('change', (e) => {
            this.setDensity(e.target.value);
        });
        
        // Update dropdown
        this.updateDropdown();
    }
    
    /**
     * Update dropdown selection
     */
    updateDropdown() {
        const dropdown = document.getElementById('density-dropdown');
        if (!dropdown) return;
        
        dropdown.value = this.currentDensity;
    }
    
    /**
     * Setup keyboard shortcut (Alt+D)
     */
    setupKeyboardShortcut() {
        document.addEventListener('keydown', (e) => {
            // Alt+D
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                this.toggle();
                
                // Show toast notification if available
                if (window.showToast) {
                    const labels = {
                        compact: 'Compact',
                        normal: 'Normal',
                        comfortable: 'Comfortable'
                    };
                    window.showToast(`Density: ${labels[this.currentDensity]}`, 'info');
                }
            }
        });
    }
    
    /**
     * Dispatch custom event
     */
    dispatchDensityChangeEvent(density) {
        const event = new CustomEvent('densityChanged', {
            detail: {
                density: density,
                previous: this.densities[(this.densities.indexOf(density) - 1 + this.densities.length) % this.densities.length]
            }
        });
        document.dispatchEvent(event);
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.densitySwitcher = new DensitySwitcher();
    });
} else {
    window.densitySwitcher = new DensitySwitcher();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DensitySwitcher;
}
