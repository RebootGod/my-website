/**
 * Bulk Operations Manager
 * 
 * Handles bulk operations for content management
 * Max 350 lines per workinginstruction.md
 * 
 * Features:
 * - Checkbox selection (select all, select page)
 * - Bulk actions (update, delete, status change, etc.)
 * - Progress tracking
 * - Error handling
 * 
 * Security: CSRF token, input validation, XSS prevention
 */

class BulkOperationsManager {
    constructor(contentType) {
        this.contentType = contentType; // 'movie' or 'series'
        this.selectedIds = new Set();
        this.allCheckboxes = [];
        this.selectAllCheckbox = null;
        this.bulkActionBar = null;
        this.isProcessing = false;
        
        this.init();
    }

    /**
     * Initialize bulk operations
     */
    init() {
        this.setupCheckboxes();
        this.setupBulkActionBar();
        this.setupEventListeners();
    }

    /**
     * Setup checkboxes
     */
    setupCheckboxes() {
        // Get all item checkboxes
        this.allCheckboxes = document.querySelectorAll('.bulk-checkbox');
        
        // Get select all checkbox
        this.selectAllCheckbox = document.getElementById('bulk-select-all');
        
        if (!this.selectAllCheckbox) {
            console.warn('Select all checkbox not found');
            return;
        }

        // Initialize from localStorage if exists
        this.loadSelectionState();
    }

    /**
     * Setup bulk action bar
     */
    setupBulkActionBar() {
        this.bulkActionBar = document.getElementById('bulk-action-bar');
        
        if (!this.bulkActionBar) {
            console.warn('Bulk action bar not found');
            return;
        }

        this.updateBulkActionBar();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Select all checkbox
        if (this.selectAllCheckbox) {
            this.selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleSelectAll(e.target.checked);
            });
        }

        // Individual checkboxes
        this.allCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.toggleSelection(e.target.value, e.target.checked);
            });
        });

        // Bulk action buttons
        this.setupBulkActionButtons();
    }

    /**
     * Setup bulk action buttons
     */
    setupBulkActionButtons() {
        // Change status
        document.getElementById('bulk-publish')?.addEventListener('click', () => {
            this.changeStatus('published');
        });
        
        document.getElementById('bulk-draft')?.addEventListener('click', () => {
            this.changeStatus('draft');
        });
        
        document.getElementById('bulk-archive')?.addEventListener('click', () => {
            this.changeStatus('archived');
        });

        // Toggle featured
        document.getElementById('bulk-feature')?.addEventListener('click', () => {
            this.toggleFeatured(true);
        });
        
        document.getElementById('bulk-unfeature')?.addEventListener('click', () => {
            this.toggleFeatured(false);
        });

        // Refresh TMDB
        document.getElementById('bulk-refresh-tmdb')?.addEventListener('click', () => {
            this.refreshTMDB();
        });

        // Delete
        document.getElementById('bulk-delete')?.addEventListener('click', () => {
            this.bulkDelete();
        });

        // Clear selection
        document.getElementById('bulk-clear')?.addEventListener('click', () => {
            this.clearSelection();
        });
    }

    /**
     * Toggle select all
     */
    toggleSelectAll(checked) {
        this.allCheckboxes.forEach(checkbox => {
            checkbox.checked = checked;
            if (checked) {
                this.selectedIds.add(checkbox.value);
            } else {
                this.selectedIds.delete(checkbox.value);
            }
        });

        this.saveSelectionState();
        this.updateBulkActionBar();
    }

    /**
     * Toggle individual selection
     */
    toggleSelection(id, checked) {
        if (checked) {
            this.selectedIds.add(id);
        } else {
            this.selectedIds.delete(id);
        }

        // Update select all checkbox
        if (this.selectAllCheckbox) {
            const allChecked = Array.from(this.allCheckboxes).every(cb => cb.checked);
            this.selectAllCheckbox.checked = allChecked;
        }

        this.saveSelectionState();
        this.updateBulkActionBar();
    }

    /**
     * Clear selection
     */
    clearSelection() {
        this.selectedIds.clear();
        this.allCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        if (this.selectAllCheckbox) {
            this.selectAllCheckbox.checked = false;
        }

        this.saveSelectionState();
        this.updateBulkActionBar();
    }

    /**
     * Update bulk action bar
     */
    updateBulkActionBar() {
        if (!this.bulkActionBar) return;

        const count = this.selectedIds.size;
        const countElement = document.getElementById('bulk-selected-count');
        
        if (count > 0) {
            this.bulkActionBar.classList.remove('hidden');
            if (countElement) {
                countElement.textContent = count;
            }
        } else {
            this.bulkActionBar.classList.add('hidden');
        }
    }

    /**
     * Change status
     */
    async changeStatus(status) {
        if (this.selectedIds.size === 0) {
            window.showToast('Please select items first', 'warning');
            return;
        }

        if (!confirm(`Are you sure you want to change status to "${status}" for ${this.selectedIds.size} items?`)) {
            return;
        }

        await this.executeBulkAction('change-status', {
            type: this.contentType,
            ids: Array.from(this.selectedIds),
            status: status
        });
    }

    /**
     * Toggle featured
     */
    async toggleFeatured(featured) {
        if (this.selectedIds.size === 0) {
            window.showToast('Please select items first', 'warning');
            return;
        }

        const action = featured ? 'feature' : 'unfeature';
        if (!confirm(`Are you sure you want to ${action} ${this.selectedIds.size} items?`)) {
            return;
        }

        await this.executeBulkAction('toggle-featured', {
            type: this.contentType,
            ids: Array.from(this.selectedIds),
            featured: featured
        });
    }

    /**
     * Refresh from TMDB
     */
    async refreshTMDB() {
        if (this.selectedIds.size === 0) {
            window.showToast('Please select items first', 'warning');
            return;
        }

        if (!confirm(`Refresh ${this.selectedIds.size} items from TMDB? This may take a while.`)) {
            return;
        }

        await this.executeBulkAction('refresh-tmdb', {
            type: this.contentType,
            ids: Array.from(this.selectedIds)
        }, true); // Enable progress tracking
    }

    /**
     * Bulk delete
     */
    async bulkDelete() {
        if (this.selectedIds.size === 0) {
            window.showToast('Please select items first', 'warning');
            return;
        }

        if (!confirm(`⚠️ WARNING: Delete ${this.selectedIds.size} items permanently? This cannot be undone!`)) {
            return;
        }

        // Double confirmation
        const confirmation = prompt('Type "DELETE" to confirm permanent deletion:');
        if (confirmation !== 'DELETE') {
            window.showToast('Deletion cancelled', 'info');
            return;
        }

        await this.executeBulkAction('delete', {
            type: this.contentType,
            ids: Array.from(this.selectedIds)
        });
    }

    /**
     * Execute bulk action
     */
    async executeBulkAction(action, data, trackProgress = false) {
        if (this.isProcessing) {
            window.showToast('Another operation is in progress', 'warning');
            return;
        }

        this.isProcessing = true;
        const loadingToast = window.showToast('Processing...', 'info', 0);

        try {
            const response = await fetch(`/admin/bulk/${action}`, {
                method: action === 'delete' ? 'DELETE' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                window.showToast(result.message, 'success');
                
                // Track progress if enabled
                if (trackProgress && result.progressKey) {
                    this.trackProgress(result.progressKey);
                } else {
                    // Reload page to reflect changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                window.showToast(result.message || 'Operation failed', 'error');
            }
        } catch (error) {
            console.error('Bulk operation error:', error);
            window.showToast('Operation failed: ' + error.message, 'error');
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Track progress
     */
    async trackProgress(progressKey) {
        // Implementation in bulk-progress-tracker.js
        if (window.BulkProgressTracker) {
            const tracker = new window.BulkProgressTracker(progressKey);
            await tracker.start();
        }
    }

    /**
     * Save selection state to localStorage
     */
    saveSelectionState() {
        const key = `bulk_selection_${this.contentType}`;
        localStorage.setItem(key, JSON.stringify(Array.from(this.selectedIds)));
    }

    /**
     * Load selection state from localStorage
     */
    loadSelectionState() {
        const key = `bulk_selection_${this.contentType}`;
        const saved = localStorage.getItem(key);
        
        if (saved) {
            try {
                const ids = JSON.parse(saved);
                ids.forEach(id => {
                    this.selectedIds.add(id);
                    const checkbox = document.querySelector(`.bulk-checkbox[value="${id}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            } catch (e) {
                console.error('Failed to load selection state:', e);
            }
        }
    }
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const contentType = document.body.dataset.contentType;
    if (contentType && (contentType === 'movie' || contentType === 'series')) {
        window.bulkOpsManager = new BulkOperationsManager(contentType);
    }
});
