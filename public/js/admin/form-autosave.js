/* ======================================== */
/* FORM AUTO-SAVE SYSTEM */
/* ======================================== */
/* File: resources/js/admin/form-autosave.js */

/**
 * FormAutoSave - Automatic form backup and recovery system
 * 
 * Features:
 * - Auto-save form data to localStorage every 30 seconds
 * - Warn user before leaving page with unsaved changes
 * - Auto-restore form data on page return
 * - Clear saved data after successful form submission
 * 
 * Security: Data stored locally only, no sensitive data exposure
 */

class FormAutoSave {
    constructor(formSelector, options = {}) {
        this.form = document.querySelector(formSelector);
        if (!this.form) {
            console.warn(`FormAutoSave: Form not found with selector "${formSelector}"`);
            return;
        }

        this.options = {
            saveInterval: options.saveInterval || 30000, // 30 seconds default
            storageKey: options.storageKey || `autosave_${window.location.pathname}`,
            excludeFields: options.excludeFields || ['_token', '_method', 'password', 'password_confirmation'],
            onRestore: options.onRestore || null,
            onSave: options.onSave || null,
            showNotifications: options.showNotifications !== false
        };

        this.isDirty = false;
        this.saveTimer = null;
        this.originalData = null;

        this.init();
    }

    /**
     * Initialize auto-save system
     */
    init() {
        // Store original form data
        this.originalData = this.getFormData();

        // Check for saved data and restore if found
        this.checkAndRestore();

        // Attach event listeners
        this.attachEventListeners();

        // Start auto-save timer
        this.startAutoSave();

        console.log('FormAutoSave initialized for:', this.options.storageKey);
    }

    /**
     * Attach event listeners to form
     */
    attachEventListeners() {
        // Detect form changes
        this.form.addEventListener('input', () => this.markAsDirty());
        this.form.addEventListener('change', () => this.markAsDirty());

        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', (e) => this.handleBeforeUnload(e));

        // Clear saved data on successful form submission
        this.form.addEventListener('submit', () => this.handleFormSubmit());

        // Listen for custom clear events
        window.addEventListener('formAutoSaveClear', () => this.clearSavedData());
    }

    /**
     * Mark form as dirty (has unsaved changes)
     */
    markAsDirty() {
        const currentData = this.getFormData();
        this.isDirty = JSON.stringify(currentData) !== JSON.stringify(this.originalData);
    }

    /**
     * Get form data as object
     */
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};

        for (const [key, value] of formData.entries()) {
            // Skip excluded fields
            if (this.options.excludeFields.includes(key)) {
                continue;
            }

            // Handle multiple values (checkboxes, multi-selects)
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }

        return data;
    }

    /**
     * Save form data to localStorage
     */
    saveFormData() {
        if (!this.isDirty) return;

        try {
            const data = this.getFormData();
            const saveData = {
                data: data,
                timestamp: Date.now(),
                url: window.location.href
            };

            localStorage.setItem(this.options.storageKey, JSON.stringify(saveData));

            // Show notification if enabled
            if (this.options.showNotifications && window.showToast) {
                window.showToast('Draft saved', 'info', 2000);
            }

            // Call custom callback
            if (this.options.onSave) {
                this.options.onSave(data);
            }

            console.log('Form data auto-saved:', this.options.storageKey);
        } catch (error) {
            console.error('FormAutoSave: Error saving data', error);
        }
    }

    /**
     * Check for saved data and restore if found
     */
    checkAndRestore() {
        try {
            const savedDataStr = localStorage.getItem(this.options.storageKey);
            if (!savedDataStr) return;

            const savedData = JSON.parse(savedDataStr);
            const savedTimestamp = savedData.timestamp;
            const currentTime = Date.now();
            const hoursSinceAutoSave = (currentTime - savedTimestamp) / (1000 * 60 * 60);

            // Only restore if saved within last 24 hours
            if (hoursSinceAutoSave > 24) {
                this.clearSavedData();
                return;
            }

            // Ask user if they want to restore
            this.showRestorePrompt(savedData);
        } catch (error) {
            console.error('FormAutoSave: Error checking saved data', error);
        }
    }

    /**
     * Show restore prompt to user
     */
    showRestorePrompt(savedData) {
        const timeSaved = new Date(savedData.timestamp).toLocaleString();
        const message = `Found auto-saved draft from ${timeSaved}. Do you want to restore it?`;

        if (confirm(message)) {
            this.restoreFormData(savedData.data);
        } else {
            this.clearSavedData();
        }
    }

    /**
     * Restore form data from saved object
     */
    restoreFormData(data) {
        try {
            for (const [key, value] of Object.entries(data)) {
                const field = this.form.elements[key];
                
                if (!field) continue;

                // Handle different input types
                if (field.type === 'checkbox') {
                    field.checked = value === 'on' || value === '1' || value === true;
                } else if (field.type === 'radio') {
                    const radioBtn = this.form.querySelector(`input[name="${key}"][value="${value}"]`);
                    if (radioBtn) radioBtn.checked = true;
                } else if (field.tagName === 'SELECT' && field.multiple) {
                    Array.from(field.options).forEach(option => {
                        option.selected = Array.isArray(value) ? value.includes(option.value) : false;
                    });
                } else {
                    field.value = value;
                }

                // Trigger change event for any dependent logic
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Show notification
            if (this.options.showNotifications && window.showToast) {
                window.showToast('Draft restored successfully', 'success', 3000);
            }

            // Call custom callback
            if (this.options.onRestore) {
                this.options.onRestore(data);
            }

            // Mark as dirty since we just restored data
            this.isDirty = true;

            console.log('Form data restored successfully');
        } catch (error) {
            console.error('FormAutoSave: Error restoring data', error);
            if (window.showToast) {
                window.showToast('Error restoring draft', 'error', 3000);
            }
        }
    }

    /**
     * Start auto-save timer
     */
    startAutoSave() {
        this.saveTimer = setInterval(() => {
            this.saveFormData();
        }, this.options.saveInterval);
    }

    /**
     * Stop auto-save timer
     */
    stopAutoSave() {
        if (this.saveTimer) {
            clearInterval(this.saveTimer);
            this.saveTimer = null;
        }
    }

    /**
     * Handle beforeunload event (warn user)
     */
    handleBeforeUnload(e) {
        if (this.isDirty) {
            const message = 'You have unsaved changes. Are you sure you want to leave?';
            e.preventDefault();
            e.returnValue = message;
            return message;
        }
    }

    /**
     * Handle form submission
     */
    handleFormSubmit() {
        // Stop auto-save
        this.stopAutoSave();

        // Clear saved data after short delay (allow form to submit first)
        setTimeout(() => {
            this.clearSavedData();
            this.isDirty = false;
        }, 1000);
    }

    /**
     * Clear saved data from localStorage
     */
    clearSavedData() {
        try {
            localStorage.removeItem(this.options.storageKey);
            console.log('Auto-saved data cleared:', this.options.storageKey);
        } catch (error) {
            console.error('FormAutoSave: Error clearing data', error);
        }
    }

    /**
     * Manually save form data
     */
    save() {
        this.saveFormData();
    }

    /**
     * Destroy auto-save instance
     */
    destroy() {
        this.stopAutoSave();
        window.removeEventListener('beforeunload', this.handleBeforeUnload);
    }
}

// Export for global use
window.FormAutoSave = FormAutoSave;
