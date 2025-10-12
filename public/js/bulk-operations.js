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
        console.log('🔧 BulkOperationsManager constructor called with:', contentType);
        
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
        console.log('🔧 BulkOperationsManager.init() started');
        
        try {
            this.setupCheckboxes();
            this.setupBulkActionBar();
            this.setupEventListeners();
            console.log('✅ BulkOperationsManager.init() completed successfully');
        } catch (error) {
            console.error('❌ BulkOperationsManager.init() failed:', error);
        }
    }

    /**
     * Setup checkboxes
     */
    setupCheckboxes() {
        console.log('🔧 Setting up checkboxes...');
        
        // Get all item checkboxes
        this.allCheckboxes = document.querySelectorAll('.bulk-checkbox');
        console.log('📦 Found', this.allCheckboxes.length, 'item checkboxes');
        
        // Get select all checkbox
        this.selectAllCheckbox = document.getElementById('bulk-select-all');
        
        if (!this.selectAllCheckbox) {
            console.error('❌ Select all checkbox not found! ID: bulk-select-all');
            return;
        }
        
        console.log('✅ Select all checkbox found');

        // Initialize from localStorage if exists
        this.loadSelectionState();
    }

    /**
     * Setup bulk action bar
     */
    setupBulkActionBar() {
        console.log('🔧 Setting up bulk action bar...');
        
        this.bulkActionBar = document.getElementById('bulk-action-bar');
        
        if (!this.bulkActionBar) {
            console.error('❌ Bulk action bar not found! ID: bulk-action-bar');
            return;
        }
        
        console.log('✅ Bulk action bar found');
        this.updateBulkActionBar();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        console.log('🔧 Setting up event listeners...');
        
        // Select all checkbox
        if (this.selectAllCheckbox) {
            this.selectAllCheckbox.addEventListener('change', (e) => {
                console.log('📦 Select all clicked, checked:', e.target.checked);
                this.toggleSelectAll(e.target.checked);
            });
            console.log('✅ Select all event listener attached');
        }

        // Individual checkboxes
        console.log('📦 Attaching event listeners to', this.allCheckboxes.length, 'checkboxes');
        this.allCheckboxes.forEach((checkbox, index) => {
            checkbox.addEventListener('change', (e) => {
                console.log(`📦 Checkbox ${index} clicked, value: ${e.target.value}, checked: ${e.target.checked}`);
                this.toggleSelection(e.target.value, e.target.checked);
            });
        });
        console.log('✅ Individual checkbox event listeners attached');

        // Bulk action buttons
        this.setupBulkActionButtons();
    }

    /**
     * Setup bulk action buttons
     */
    setupBulkActionButtons() {
        console.log('🔧 Setting up bulk action buttons...');
        
        let buttonCount = 0;
        
        // Change status
        const publishBtn = document.getElementById('bulk-publish');
        if (publishBtn) {
            publishBtn.addEventListener('click', () => this.changeStatus('published'));
            buttonCount++;
        }
        
        const draftBtn = document.getElementById('bulk-draft');
        if (draftBtn) {
            draftBtn.addEventListener('click', () => this.changeStatus('draft'));
            buttonCount++;
        }
        
        const archiveBtn = document.getElementById('bulk-archive');
        if (archiveBtn) {
            archiveBtn.addEventListener('click', () => this.changeStatus('archived'));
            buttonCount++;
        }

        // Toggle featured
        const featureBtn = document.getElementById('bulk-feature');
        if (featureBtn) {
            featureBtn.addEventListener('click', () => this.toggleFeatured(true));
            buttonCount++;
        }
        
        const unfeatureBtn = document.getElementById('bulk-unfeature');
        if (unfeatureBtn) {
            unfeatureBtn.addEventListener('click', () => this.toggleFeatured(false));
            buttonCount++;
        }

        // Refresh TMDB
        const refreshBtn = document.getElementById('bulk-refresh-tmdb');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshTMDB());
            buttonCount++;
        }

        // Delete
        const deleteBtn = document.getElementById('bulk-delete');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => this.bulkDelete());
            buttonCount++;
        }

        // Clear selection
        const clearBtn = document.getElementById('bulk-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearSelection());
            buttonCount++;
        }
        
        console.log(`✅ Attached ${buttonCount} bulk action button listeners`);
    }

    /**
     * Toggle select all
     */
    toggleSelectAll(checked) {
        console.log('📦 toggleSelectAll called with:', checked);
        console.log('📦 Total checkboxes:', this.allCheckboxes.length);
        
        this.allCheckboxes.forEach((checkbox, index) => {
            checkbox.checked = checked;
            if (checked) {
                this.selectedIds.add(checkbox.value);
                console.log(`📦 Selected checkbox ${index}, value: ${checkbox.value}`);
            } else {
                this.selectedIds.delete(checkbox.value);
                console.log(`📦 Deselected checkbox ${index}, value: ${checkbox.value}`);
            }
        });

        console.log('📦 Total selected IDs:', this.selectedIds.size);
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
        if (!this.bulkActionBar) {
            console.warn('⚠️ Bulk action bar not available');
            return;
        }

        const count = this.selectedIds.size;
        const countElement = document.getElementById('bulk-selected-count');
        
        console.log('📊 Updating bulk action bar, selected count:', count);
        
        if (count > 0) {
            this.bulkActionBar.classList.remove('hidden');
            if (countElement) {
                countElement.textContent = count;
            }
            console.log('✅ Bulk action bar shown');
        } else {
            this.bulkActionBar.classList.add('hidden');
            console.log('✅ Bulk action bar hidden');
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
    console.log('🔧 Bulk Operations: Initializing...');
    
    // Try to get content type from container or body
    let contentType = null;
    
    // Check container div first
    const container = document.querySelector('[data-content-type]');
    if (container) {
        contentType = container.dataset.contentType;
        console.log('✅ Found content type from container:', contentType);
    }
    
    // Fallback to body
    if (!contentType && document.body.dataset.contentType) {
        contentType = document.body.dataset.contentType;
        console.log('✅ Found content type from body:', contentType);
    }
    
    // Initialize if valid content type
    if (contentType && (contentType === 'movie' || contentType === 'series')) {
        console.log('✅ Initializing BulkOperationsManager for:', contentType);
        window.bulkOpsManager = new BulkOperationsManager(contentType);
        console.log('✅ BulkOperationsManager initialized successfully');
    } else {
        console.warn('⚠️ Content type not found or invalid:', contentType);
    }
});
