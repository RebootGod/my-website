/* ======================================== */
/* ARIA LABELS COMPONENT */
/* ======================================== */
/* Phase 6.4: Accessibility Audit */
/* Dynamic ARIA attributes for screen readers */

class AriaLabels {
    constructor() {
        this.liveRegion = null;
        this.init();
    }

    init() {
        console.log('🔊 ARIA Labels: Initializing...');
        
        this.createLiveRegion();
        this.enhanceButtons();
        this.enhanceLinks();
        this.enhanceForms();
        this.enhanceCards();
        this.enhanceModals();
        
        console.log('✅ ARIA Labels: Ready');
    }

    /**
     * Create ARIA live region for announcements
     */
    createLiveRegion() {
        if (document.getElementById('aria-live-region')) return;

        this.liveRegion = document.createElement('div');
        this.liveRegion.id = 'aria-live-region';
        this.liveRegion.className = 'aria-live-region';
        this.liveRegion.setAttribute('aria-live', 'polite');
        this.liveRegion.setAttribute('aria-atomic', 'true');
        this.liveRegion.setAttribute('role', 'status');
        
        document.body.appendChild(this.liveRegion);
    }

    /**
     * Announce to screen readers
     */
    announce(message, priority = 'polite') {
        if (!this.liveRegion) return;

        this.liveRegion.setAttribute('aria-live', priority);
        this.liveRegion.textContent = message;

        console.log(`🔊 Announced: "${message}"`);

        // Clear after 3 seconds
        setTimeout(() => {
            this.liveRegion.textContent = '';
        }, 3000);
    }

    /**
     * Enhance buttons with ARIA labels
     */
    enhanceButtons() {
        // Icon-only buttons
        document.querySelectorAll('button:not([aria-label])').forEach(btn => {
            const icon = btn.querySelector('i, svg');
            const text = btn.textContent.trim();

            if (icon && !text) {
                // Icon only, needs label
                const iconClass = icon.className;
                let label = '';

                if (iconClass.includes('play')) label = 'Play';
                else if (iconClass.includes('pause')) label = 'Pause';
                else if (iconClass.includes('heart')) label = 'Add to favorites';
                else if (iconClass.includes('share')) label = 'Share';
                else if (iconClass.includes('download')) label = 'Download';
                else if (iconClass.includes('close')) label = 'Close';
                else if (iconClass.includes('menu')) label = 'Menu';
                else if (iconClass.includes('search')) label = 'Search';
                else label = 'Button';

                btn.setAttribute('aria-label', label);
            }
        });

        // Toggle buttons
        document.querySelectorAll('[data-toggle]').forEach(btn => {
            const target = btn.dataset.toggle;
            btn.setAttribute('aria-controls', target);
            
            if (!btn.hasAttribute('aria-expanded')) {
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', () => {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', !expanded);
            });
        });
    }

    /**
     * Enhance links with descriptive labels
     */
    enhanceLinks() {
        document.querySelectorAll('a:not([aria-label])').forEach(link => {
            const text = link.textContent.trim();

            // External links
            if (link.hostname !== window.location.hostname) {
                link.setAttribute('aria-label', `${text} (opens in new tab)`);
                link.setAttribute('rel', 'noopener noreferrer');
                if (link.target === '_blank') {
                    link.setAttribute('aria-describedby', 'external-link-desc');
                }
            }

            // Links with only icons
            const icon = link.querySelector('i, svg');
            if (icon && !text) {
                const href = link.getAttribute('href');
                let label = 'Link';

                if (href.includes('twitter')) label = 'Twitter';
                else if (href.includes('facebook')) label = 'Facebook';
                else if (href.includes('instagram')) label = 'Instagram';
                else if (href.includes('youtube')) label = 'YouTube';

                link.setAttribute('aria-label', label);
            }
        });
    }

    /**
     * Enhance forms with ARIA
     */
    enhanceForms() {
        // Required fields
        document.querySelectorAll('input[required], textarea[required], select[required]').forEach(input => {
            input.setAttribute('aria-required', 'true');
            
            const label = document.querySelector(`label[for="${input.id}"]`);
            if (label && !label.querySelector('.required-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'required-indicator';
                indicator.textContent = ' *';
                indicator.setAttribute('aria-label', 'required');
                label.appendChild(indicator);
            }
        });

        // Invalid fields
        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('invalid', () => {
                input.setAttribute('aria-invalid', 'true');
                
                const errorId = `${input.id}-error`;
                if (!document.getElementById(errorId)) {
                    const error = document.createElement('div');
                    error.id = errorId;
                    error.className = 'form-error';
                    error.textContent = input.validationMessage;
                    input.parentNode.appendChild(error);
                    input.setAttribute('aria-describedby', errorId);
                }
            });

            input.addEventListener('input', () => {
                if (input.validity.valid) {
                    input.setAttribute('aria-invalid', 'false');
                    const errorId = `${input.id}-error`;
                    const error = document.getElementById(errorId);
                    if (error) error.remove();
                }
            });
        });
    }

    /**
     * Enhance cards with ARIA
     */
    enhanceCards() {
        document.querySelectorAll('.movie-card, .series-card, [class*="card"]').forEach(card => {
            // Make cards focusable
            if (!card.hasAttribute('tabindex')) {
                card.setAttribute('tabindex', '0');
            }

            // Add role if not present
            if (!card.hasAttribute('role')) {
                card.setAttribute('role', 'article');
            }

            // Add label from title
            const title = card.querySelector('h2, h3, .title, .card-title');
            if (title && !card.hasAttribute('aria-label')) {
                card.setAttribute('aria-label', title.textContent.trim());
            }

            // Link wrapper
            const link = card.querySelector('a');
            if (link && !link.hasAttribute('aria-label')) {
                const titleText = title ? title.textContent.trim() : 'View details';
                link.setAttribute('aria-label', `${titleText}`);
            }
        });
    }

    /**
     * Enhance modals with ARIA
     */
    enhanceModals() {
        document.querySelectorAll('.modal, [data-modal]').forEach(modal => {
            if (!modal.hasAttribute('role')) {
                modal.setAttribute('role', 'dialog');
            }

            if (!modal.hasAttribute('aria-modal')) {
                modal.setAttribute('aria-modal', 'true');
            }

            // Find modal title
            const title = modal.querySelector('h1, h2, h3, .modal-title');
            if (title) {
                if (!title.id) {
                    title.id = `modal-title-${Date.now()}`;
                }
                modal.setAttribute('aria-labelledby', title.id);
            }

            // Close button
            const closeBtn = modal.querySelector('[data-close], .close, .modal-close');
            if (closeBtn && !closeBtn.hasAttribute('aria-label')) {
                closeBtn.setAttribute('aria-label', 'Close dialog');
            }
        });
    }

    /**
     * Update ARIA expanded state
     */
    setExpanded(element, expanded) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.setAttribute('aria-expanded', expanded.toString());
        }
    }

    /**
     * Update ARIA hidden state
     */
    setHidden(element, hidden) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.setAttribute('aria-hidden', hidden.toString());
        }
    }

    /**
     * Update ARIA busy state
     */
    setBusy(element, busy) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.setAttribute('aria-busy', busy.toString());
        }
    }

    /**
     * Update ARIA live region
     */
    updateLive(message, priority = 'polite') {
        this.announce(message, priority);
    }
}

// Initialize
const ariaLabels = new AriaLabels();

// Make globally available
window.ariaLabels = ariaLabels;

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AriaLabels;
}
