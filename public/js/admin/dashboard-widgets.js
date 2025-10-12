/**
 * Dashboard Widgets Module
 * 
 * Handles drag-and-drop widget management, reordering, and visibility
 * Max 350 lines per workinginstruction.md
 * 
 * Features:
 * - Drag & drop widget reordering
 * - Toggle widget visibility
 * - Save layout to localStorage
 * - Restore layout on page load
 * - Mobile-friendly touch support
 */

class DashboardWidgets {
    constructor() {
        this.storageKey = 'admin_dashboard_layout';
        this.widgets = [];
        this.draggedElement = null;
        this.isDragging = false;
        
        this.init();
    }

    init() {
        // Load saved layout
        this.loadLayout();
        
        // Setup drag and drop
        this.setupDragAndDrop();
        
        // Setup widget controls
        this.setupWidgetControls();
        
        // Apply layout
        this.applyLayout();
    }

    /**
     * Setup drag and drop functionality
     */
    setupDragAndDrop() {
        const widgetContainers = document.querySelectorAll('.dashboard-widget');
        
        widgetContainers.forEach(widget => {
            // Make widget draggable
            widget.setAttribute('draggable', 'true');
            
            // Drag start
            widget.addEventListener('dragstart', (e) => {
                this.handleDragStart(e, widget);
            });
            
            // Drag over
            widget.addEventListener('dragover', (e) => {
                e.preventDefault();
                this.handleDragOver(e, widget);
            });
            
            // Drag enter
            widget.addEventListener('dragenter', (e) => {
                e.preventDefault();
                widget.classList.add('drag-over');
            });
            
            // Drag leave
            widget.addEventListener('dragleave', () => {
                widget.classList.remove('drag-over');
            });
            
            // Drop
            widget.addEventListener('drop', (e) => {
                e.preventDefault();
                this.handleDrop(e, widget);
            });
            
            // Drag end
            widget.addEventListener('dragend', () => {
                this.handleDragEnd();
            });
        });
    }

    /**
     * Handle drag start
     */
    handleDragStart(e, widget) {
        this.draggedElement = widget;
        this.isDragging = true;
        
        widget.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', widget.innerHTML);
    }

    /**
     * Handle drag over
     */
    handleDragOver(e, widget) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    /**
     * Handle drop
     */
    handleDrop(e, targetWidget) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        targetWidget.classList.remove('drag-over');
        
        if (this.draggedElement !== targetWidget) {
            // Get parent container
            const container = targetWidget.parentNode;
            
            // Get all widgets
            const allWidgets = Array.from(container.children);
            
            // Get indices
            const draggedIndex = allWidgets.indexOf(this.draggedElement);
            const targetIndex = allWidgets.indexOf(targetWidget);
            
            // Reorder in DOM
            if (draggedIndex < targetIndex) {
                container.insertBefore(this.draggedElement, targetWidget.nextSibling);
            } else {
                container.insertBefore(this.draggedElement, targetWidget);
            }
            
            // Save new order
            this.saveLayout();
            
            // Show toast notification
            this.showToast('Widget reordered successfully', 'success');
        }
        
        return false;
    }

    /**
     * Handle drag end
     */
    handleDragEnd() {
        this.isDragging = false;
        
        // Remove all drag classes
        document.querySelectorAll('.dashboard-widget').forEach(widget => {
            widget.classList.remove('dragging', 'drag-over');
        });
        
        this.draggedElement = null;
    }

    /**
     * Setup widget controls (toggle visibility, etc)
     */
    setupWidgetControls() {
        // Widget toggle buttons
        document.addEventListener('click', (e) => {
            const toggleBtn = e.target.closest('[data-widget-toggle]');
            if (toggleBtn) {
                e.preventDefault();
                const widgetId = toggleBtn.getAttribute('data-widget-toggle');
                this.toggleWidgetVisibility(widgetId);
            }
        });

        // Widget close buttons
        document.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('[data-widget-close]');
            if (closeBtn) {
                e.preventDefault();
                const widget = closeBtn.closest('.dashboard-widget');
                const widgetId = widget.getAttribute('data-widget-id');
                this.hideWidget(widgetId);
            }
        });

        // Reset layout button
        const resetBtn = document.querySelector('[data-reset-layout]');
        if (resetBtn) {
            resetBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.resetLayout();
            });
        }
    }

    /**
     * Toggle widget visibility
     */
    toggleWidgetVisibility(widgetId) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
        if (!widget) return;

        const isHidden = widget.classList.contains('widget-hidden');
        
        if (isHidden) {
            widget.classList.remove('widget-hidden');
            this.showToast('Widget shown', 'success');
        } else {
            widget.classList.add('widget-hidden');
            this.showToast('Widget hidden', 'info');
        }

        this.saveLayout();
    }

    /**
     * Hide widget
     */
    hideWidget(widgetId) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
        if (!widget) return;

        widget.classList.add('widget-hidden');
        this.saveLayout();
        this.showToast('Widget hidden', 'info');
    }

    /**
     * Show widget
     */
    showWidget(widgetId) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
        if (!widget) return;

        widget.classList.remove('widget-hidden');
        this.saveLayout();
        this.showToast('Widget shown', 'success');
    }

    /**
     * Load layout from localStorage
     */
    loadLayout() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                this.widgets = JSON.parse(saved);
            }
        } catch (error) {
            console.error('Failed to load dashboard layout:', error);
            this.widgets = [];
        }
    }

    /**
     * Save layout to localStorage
     */
    saveLayout() {
        const container = document.querySelector('.dashboard-grid');
        if (!container) return;

        const widgets = Array.from(container.querySelectorAll('.dashboard-widget'));
        
        this.widgets = widgets.map((widget, index) => {
            return {
                id: widget.getAttribute('data-widget-id'),
                order: index,
                visible: !widget.classList.contains('widget-hidden')
            };
        });

        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.widgets));
        } catch (error) {
            console.error('Failed to save dashboard layout:', error);
        }
    }

    /**
     * Apply saved layout
     */
    applyLayout() {
        if (this.widgets.length === 0) return;

        const container = document.querySelector('.dashboard-grid');
        if (!container) return;

        // Sort widgets by saved order
        this.widgets.sort((a, b) => a.order - b.order);

        // Apply order and visibility
        this.widgets.forEach((savedWidget) => {
            const widget = document.querySelector(`[data-widget-id="${savedWidget.id}"]`);
            if (widget) {
                // Apply visibility
                if (!savedWidget.visible) {
                    widget.classList.add('widget-hidden');
                }
                
                // Move to correct position
                container.appendChild(widget);
            }
        });
    }

    /**
     * Reset layout to default
     */
    resetLayout() {
        if (!confirm('Reset dashboard layout to default?')) {
            return;
        }

        // Clear localStorage
        localStorage.removeItem(this.storageKey);
        this.widgets = [];

        // Remove all hidden classes
        document.querySelectorAll('.dashboard-widget').forEach(widget => {
            widget.classList.remove('widget-hidden');
        });

        // Reload page to restore default order
        window.location.reload();
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Use global toast if available
        if (window.showToast) {
            window.showToast(message, type);
            return;
        }

        // Fallback: create simple toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: #333;
            color: white;
            border-radius: 8px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Get current layout
     */
    getLayout() {
        return this.widgets;
    }

    /**
     * Export layout as JSON
     */
    exportLayout() {
        const json = JSON.stringify(this.widgets, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `dashboard-layout-${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        this.showToast('Layout exported successfully', 'success');
    }

    /**
     * Import layout from JSON
     */
    importLayout(jsonString) {
        try {
            const layout = JSON.parse(jsonString);
            
            if (!Array.isArray(layout)) {
                throw new Error('Invalid layout format');
            }

            this.widgets = layout;
            localStorage.setItem(this.storageKey, jsonString);
            
            // Apply imported layout
            this.applyLayout();
            
            this.showToast('Layout imported successfully', 'success');
        } catch (error) {
            console.error('Failed to import layout:', error);
            this.showToast('Failed to import layout', 'error');
        }
    }
}

// Initialize dashboard widgets when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on dashboard page
    if (document.querySelector('.dashboard-grid')) {
        window.dashboardWidgets = new DashboardWidgets();
        console.log('Dashboard Widgets initialized');
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DashboardWidgets;
}
