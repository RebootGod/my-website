/**
 * ========================================
 * MOBILE DROPDOWN FIX
 * ========================================
 * 
 * Fixes dropdown menu click issues on mobile
 * Ensures dropdown items are clickable and not blocked by content
 * 
 * File: resources/js/mobile-dropdown-fix.js
 * Created: October 14, 2025
 * ========================================
 */

document.addEventListener('DOMContentLoaded', function() {
    // Only apply fixes on mobile devices
    if (window.innerWidth <= 768) {
        initMobileDropdownFix();
    }
    
    // Re-apply on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            initMobileDropdownFix();
        }
    });
});

function initMobileDropdownFix() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const dropdownToggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        
        if (!dropdownToggle || !dropdownMenu) return;
        
        // Ensure dropdown menu is properly positioned and clickable
        dropdownToggle.addEventListener('shown.bs.dropdown', function() {
            // Force dropdown menu to be on top
            dropdownMenu.style.position = 'fixed';
            dropdownMenu.style.zIndex = '99999';
            dropdownMenu.style.pointerEvents = 'auto';
            
            // Make all dropdown items clickable
            const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item, a, button, form');
            dropdownItems.forEach(item => {
                item.style.pointerEvents = 'auto';
                item.style.cursor = 'pointer';
                item.style.zIndex = '99999';
            });
            
            // Lower z-index of content behind
            const mainContent = document.querySelector('main');
            const containers = document.querySelectorAll('.container, .content-section, .movie-grid, .series-grid');
            
            if (mainContent) {
                mainContent.style.zIndex = '0';
            }
            
            containers.forEach(container => {
                container.style.zIndex = '0';
            });
            
            // Prevent scroll on body when dropdown is open
            document.body.style.overflow = 'hidden';
        });
        
        // Reset when dropdown closes
        dropdownToggle.addEventListener('hidden.bs.dropdown', function() {
            // Re-enable scroll
            document.body.style.overflow = '';
            
            // Reset z-index
            const mainContent = document.querySelector('main');
            const containers = document.querySelectorAll('.container, .content-section, .movie-grid, .series-grid');
            
            if (mainContent) {
                mainContent.style.zIndex = '';
            }
            
            containers.forEach(container => {
                container.style.zIndex = '';
            });
        });
        
        // Ensure clicks on dropdown items work
        const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // If it's a link, ensure it navigates
                if (this.tagName === 'A') {
                    e.stopPropagation();
                    const href = this.getAttribute('href');
                    if (href && href !== '#') {
                        window.location.href = href;
                    }
                }
                
                // If it's a button in form (logout), ensure it submits
                if (this.tagName === 'BUTTON' && this.type === 'submit') {
                    e.stopPropagation();
                    const form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            });
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(menu => {
                const dropdown = menu.closest('.dropdown');
                if (dropdown) {
                    const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                    if (toggle) {
                        bootstrap.Dropdown.getInstance(toggle)?.hide();
                    }
                }
            });
        }
    });
}

// Debug helper - log dropdown state
if (window.location.search.includes('debug=1')) {
    document.addEventListener('shown.bs.dropdown', function(e) {
        console.log('Dropdown opened:', e.target);
        const menu = e.target.nextElementSibling;
        if (menu) {
            console.log('Dropdown menu z-index:', window.getComputedStyle(menu).zIndex);
            console.log('Dropdown menu pointer-events:', window.getComputedStyle(menu).pointerEvents);
        }
    });
}
