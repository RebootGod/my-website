/**
 * ========================================
 * ADMIN BULK OPERATIONS
 * Bulk selection and operations functionality
 * ========================================
 */

// Extend Admin namespace
window.Admin = window.Admin || {};

/**
 * Bulk Operations Manager
 */
Admin.Bulk = {
    isInitialized: false,
    selectedItems: new Set(),
    csrfToken: null,

    init: function() {
        if (this.isInitialized) return;

        console.log('🔄 Admin Bulk: Initializing...');

        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        this.initBulkSelection();
        this.initBulkActions();
        this.initSelectAll();

        this.isInitialized = true;
        console.log('✅ Admin Bulk: Initialized successfully');
    },

    /**
     * Initialize bulk selection checkboxes
     */
    initBulkSelection: function() {
        const checkboxes = document.querySelectorAll('.bulk-checkbox:not(.select-all)');

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.handleItemSelection(e.target);
            });
        });
    },

    /**
     * Initialize select all functionality
     */
    initSelectAll: function() {
        const selectAllCheckbox = document.querySelector('.bulk-checkbox.select-all');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.handleSelectAll(e.target.checked);
            });
        }
    },

    /**
     * Initialize bulk action buttons
     */
    initBulkActions: function() {
        const actionButtons = document.querySelectorAll('[data-bulk-action]');

        actionButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const action = button.dataset.bulkAction;
                const confirmation = button.dataset.confirmation;

                this.executeBulkAction(action, confirmation);
            });
        });
    },

    /**
     * Handle individual item selection
     */
    handleItemSelection: function(checkbox) {
        const itemId = checkbox.value;
        const row = checkbox.closest('tr');

        if (checkbox.checked) {
            this.selectedItems.add(itemId);
            row?.classList.add('selected');
        } else {
            this.selectedItems.delete(itemId);
            row?.classList.remove('selected');
        }

        this.updateSelectAllState();
        this.updateBulkActionsBar();
    },

    /**
     * Handle select all checkbox
     */
    handleSelectAll: function(checked) {
        const checkboxes = document.querySelectorAll('.bulk-checkbox:not(.select-all)');

        checkboxes.forEach(checkbox => {
            const itemId = checkbox.value;
            const row = checkbox.closest('tr');

            checkbox.checked = checked;

            if (checked) {
                this.selectedItems.add(itemId);
                row?.classList.add('selected');
            } else {
                this.selectedItems.delete(itemId);
                row?.classList.remove('selected');
            }
        });

        this.updateBulkActionsBar();
    },

    /**
     * Update select all checkbox state
     */
    updateSelectAllState: function() {
        const selectAllCheckbox = document.querySelector('.bulk-checkbox.select-all');
        const individualCheckboxes = document.querySelectorAll('.bulk-checkbox:not(.select-all)');

        if (!selectAllCheckbox || individualCheckboxes.length === 0) return;

        const checkedCount = Array.from(individualCheckboxes).filter(cb => cb.checked).length;
        const totalCount = individualCheckboxes.length;

        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === totalCount) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    },

    /**
     * Update bulk actions bar visibility and count
     */
    updateBulkActionsBar: function() {
        const bulkActionsBar = document.querySelector('.bulk-actions-bar');
        const countElement = document.querySelector('.selected-count');

        if (!bulkActionsBar) return;

        const selectedCount = this.selectedItems.size;

        if (selectedCount > 0) {
            bulkActionsBar.classList.add('show');
            if (countElement) {
                countElement.textContent = `${selectedCount} item${selectedCount > 1 ? 's' : ''} selected`;
            }
        } else {
            bulkActionsBar.classList.remove('show');
        }
    },

    /**
     * Execute bulk action
     */
    executeBulkAction: function(action, confirmation = null) {
        if (this.selectedItems.size === 0) {
            Admin.showToast('Please select items first', 'warning');
            return;
        }

        // Show confirmation if required
        if (confirmation) {
            const message = confirmation.replace('{count}', this.selectedItems.size);
            if (!confirm(message)) {
                return;
            }
        }

        // Show loading state
        this.setLoadingState(true);

        // Prepare data
        const data = {
            action: action,
            items: Array.from(this.selectedItems),
            _token: this.csrfToken
        };

        // Execute AJAX request
        this.performBulkRequest(data);
    },

    /**
     * Perform bulk operation request
     */
    performBulkRequest: function(data) {
        fetch(window.location.pathname + '/bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            this.handleBulkResponse(result);
        })
        .catch(error => {
            console.error('Bulk operation failed:', error);
            Admin.showToast('Bulk operation failed. Please try again.', 'error');
        })
        .finally(() => {
            this.setLoadingState(false);
        });
    },

    /**
     * Handle bulk operation response
     */
    handleBulkResponse: function(result) {
        if (result.success) {
            Admin.showToast(result.message || 'Bulk operation completed successfully', 'success');

            // Clear selection
            this.clearSelection();

            // Reload page or update UI
            if (result.reload) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else if (result.updated_items) {
                this.updateRowsInPlace(result.updated_items);
            }
        } else {
            Admin.showToast(result.message || 'Bulk operation failed', 'error');
        }
    },

    /**
     * Update rows in place without page reload
     */
    updateRowsInPlace: function(updatedItems) {
        updatedItems.forEach(item => {
            const row = document.querySelector(`tr[data-id="${item.id}"]`);
            if (row) {
                // Update status badge
                const statusBadge = row.querySelector('.status-badge');
                if (statusBadge && item.status) {
                    statusBadge.className = `status-badge ${item.status}`;
                    statusBadge.textContent = item.status;
                }

                // Update other fields if provided
                if (item.updated_at) {
                    const updatedAtCell = row.querySelector('.updated-at');
                    if (updatedAtCell) {
                        updatedAtCell.textContent = item.updated_at;
                    }
                }
            }
        });
    },

    /**
     * Clear all selections
     */
    clearSelection: function() {
        this.selectedItems.clear();

        // Uncheck all checkboxes
        const checkboxes = document.querySelectorAll('.bulk-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.indeterminate = false;
        });

        // Remove selected class from rows
        const rows = document.querySelectorAll('tr.selected');
        rows.forEach(row => {
            row.classList.remove('selected');
        });

        // Hide bulk actions bar
        this.updateBulkActionsBar();
    },

    /**
     * Set loading state for bulk operations
     */
    setLoadingState: function(loading) {
        const buttons = document.querySelectorAll('[data-bulk-action]');
        const bulkActionsBar = document.querySelector('.bulk-actions-bar');

        buttons.forEach(button => {
            button.disabled = loading;
            if (loading) {
                button.dataset.originalText = button.textContent;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            } else {
                button.textContent = button.dataset.originalText || button.textContent;
            }
        });

        if (bulkActionsBar) {
            bulkActionsBar.style.opacity = loading ? '0.7' : '1';
        }
    },

    /**
     * Add keyboard shortcuts
     */
    initKeyboardShortcuts: function() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + A to select all
            if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !e.target.matches('input, textarea')) {
                e.preventDefault();
                const selectAllCheckbox = document.querySelector('.bulk-checkbox.select-all');
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = !selectAllCheckbox.checked;
                    this.handleSelectAll(selectAllCheckbox.checked);
                }
            }

            // Escape to clear selection
            if (e.key === 'Escape') {
                this.clearSelection();
            }
        });
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Admin.Bulk.init();
    Admin.Bulk.initKeyboardShortcuts();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Admin.Bulk;
}