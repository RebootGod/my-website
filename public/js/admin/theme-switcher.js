/**
 * Theme Switcher Module
 * 
 * Handles dark/light theme toggling with smooth transitions
 * Max 350 lines per workinginstruction.md
 * Reusable across all admin pages
 * 
 * Features:
 * - Dark/Light theme toggle
 * - System theme detection
 * - Smooth transitions
 * - localStorage persistence
 * - Keyboard shortcut (Alt+T)
 */

class ThemeSwitcher {
    constructor() {
        this.currentTheme = 'dark'; // default
        this.storageKey = 'admin_theme_preference';
        this.transitionDuration = 300; // ms
        
        this.init();
    }

    init() {
        // Load saved theme or detect system preference
        this.loadTheme();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Apply theme
        this.applyTheme(this.currentTheme, false);
        
        // Listen for system theme changes
        this.watchSystemTheme();
    }

    /**
     * Load theme from localStorage or system
     */
    loadTheme() {
        // Check localStorage first
        const saved = localStorage.getItem(this.storageKey);
        
        if (saved && (saved === 'dark' || saved === 'light')) {
            this.currentTheme = saved;
            return;
        }

        // Detect system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
            this.currentTheme = 'light';
        } else {
            this.currentTheme = 'dark';
        }
    }

    /**
     * Save theme to localStorage
     */
    saveTheme() {
        try {
            localStorage.setItem(this.storageKey, this.currentTheme);
        } catch (error) {
            console.error('Failed to save theme preference:', error);
        }
    }

    /**
     * Apply theme to document
     * 
     * @param {string} theme - 'dark' or 'light'
     * @param {boolean} animate - whether to animate transition
     */
    applyTheme(theme, animate = true) {
        const html = document.documentElement;
        const body = document.body;

        // Add transition class if animating
        if (animate) {
            html.classList.add('theme-transitioning');
            body.classList.add('theme-transitioning');
        }

        // Remove old theme class
        const oldTheme = theme === 'dark' ? 'light' : 'dark';
        html.classList.remove(`theme-${oldTheme}`);
        body.classList.remove(`theme-${oldTheme}`);

        // Add new theme class
        html.classList.add(`theme-${theme}`);
        body.classList.add(`theme-${theme}`);

        // Update data attribute
        html.setAttribute('data-theme', theme);

        // Update current theme
        this.currentTheme = theme;

        // Remove transition class after animation
        if (animate) {
            setTimeout(() => {
                html.classList.remove('theme-transitioning');
                body.classList.remove('theme-transitioning');
            }, this.transitionDuration);
        }

        // Update toggle button if exists
        this.updateToggleButton();

        // Save preference
        this.saveTheme();

        // Dispatch custom event
        this.dispatchThemeChangeEvent(theme);
    }

    /**
     * Toggle between dark and light theme
     */
    toggle() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme, true);
    }

    /**
     * Set specific theme
     * 
     * @param {string} theme - 'dark' or 'light'
     */
    setTheme(theme) {
        if (theme !== 'dark' && theme !== 'light') {
            console.error('Invalid theme:', theme);
            return;
        }

        if (theme !== this.currentTheme) {
            this.applyTheme(theme, true);
        }
    }

    /**
     * Get current theme
     * 
     * @returns {string} - 'dark' or 'light'
     */
    getTheme() {
        return this.currentTheme;
    }

    /**
     * Check if dark theme is active
     * 
     * @returns {boolean}
     */
    isDark() {
        return this.currentTheme === 'dark';
    }

    /**
     * Check if light theme is active
     * 
     * @returns {boolean}
     */
    isLight() {
        return this.currentTheme === 'light';
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Theme toggle buttons
        document.addEventListener('click', (e) => {
            const toggleBtn = e.target.closest('[data-theme-toggle]');
            if (toggleBtn) {
                e.preventDefault();
                this.toggle();
            }
        });

        // Keyboard shortcut: Alt+T
        document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key.toLowerCase() === 't') {
                e.preventDefault();
                this.toggle();
            }
        });

        // Theme selector dropdowns
        document.addEventListener('change', (e) => {
            if (e.target.matches('[data-theme-selector]')) {
                const theme = e.target.value;
                this.setTheme(theme);
            }
        });
    }

    /**
     * Watch for system theme changes
     */
    watchSystemTheme() {
        if (!window.matchMedia) return;

        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        darkModeQuery.addEventListener('change', (e) => {
            // Only auto-switch if user hasn't set a preference
            const savedPreference = localStorage.getItem(this.storageKey);
            if (!savedPreference) {
                const newTheme = e.matches ? 'dark' : 'light';
                this.applyTheme(newTheme, true);
            }
        });
    }

    /**
     * Update toggle button icon/text
     */
    updateToggleButton() {
        const toggleBtns = document.querySelectorAll('[data-theme-toggle]');
        
        toggleBtns.forEach(btn => {
            const icon = btn.querySelector('i');
            const text = btn.querySelector('.theme-toggle-text');

            if (this.currentTheme === 'dark') {
                if (icon) {
                    icon.className = 'fas fa-sun';
                }
                if (text) {
                    text.textContent = 'Light Mode';
                }
                btn.setAttribute('title', 'Switch to Light Mode (Alt+T)');
                btn.setAttribute('aria-label', 'Switch to Light Mode');
            } else {
                if (icon) {
                    icon.className = 'fas fa-moon';
                }
                if (text) {
                    text.textContent = 'Dark Mode';
                }
                btn.setAttribute('title', 'Switch to Dark Mode (Alt+T)');
                btn.setAttribute('aria-label', 'Switch to Dark Mode');
            }
        });

        // Update theme selectors
        const selectors = document.querySelectorAll('[data-theme-selector]');
        selectors.forEach(select => {
            select.value = this.currentTheme;
        });
    }

    /**
     * Dispatch theme change event
     * 
     * @param {string} theme
     */
    dispatchThemeChangeEvent(theme) {
        const event = new CustomEvent('themeChanged', {
            detail: {
                theme: theme,
                isDark: theme === 'dark',
                isLight: theme === 'light'
            }
        });
        
        window.dispatchEvent(event);
    }

    /**
     * Create theme toggle button
     * 
     * @param {string} position - 'fixed' or 'inline'
     * @returns {HTMLElement}
     */
    createToggleButton(position = 'fixed') {
        const btn = document.createElement('button');
        btn.className = `theme-toggle-btn ${position === 'fixed' ? 'theme-toggle-fixed' : ''}`;
        btn.setAttribute('data-theme-toggle', '');
        btn.setAttribute('title', 'Toggle Theme (Alt+T)');
        btn.setAttribute('aria-label', 'Toggle Theme');
        
        btn.innerHTML = `
            <i class="fas ${this.currentTheme === 'dark' ? 'fa-sun' : 'fa-moon'}"></i>
            <span class="theme-toggle-text">${this.currentTheme === 'dark' ? 'Light' : 'Dark'} Mode</span>
        `;

        return btn;
    }

    /**
     * Add fixed toggle button to page
     */
    addFixedToggleButton() {
        // Check if already exists
        if (document.querySelector('.theme-toggle-fixed')) {
            return;
        }

        const btn = this.createToggleButton('fixed');
        document.body.appendChild(btn);
    }
}

// Initialize theme switcher when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Create global instance
    window.themeSwitcher = new ThemeSwitcher();
    
    console.log('Theme Switcher initialized:', window.themeSwitcher.getTheme());
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeSwitcher;
}
