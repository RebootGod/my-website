/**
 * Episode Edit JavaScript Functionality
 * File: resources/js/admin/episode-edit.js
 * 
 * Handles episode edit form interactions, validations, and AJAX submissions
 */

class EpisodeEditManager {
    constructor() {
        this.form = document.getElementById('episode-edit-form');
        this.submitBtn = document.getElementById('submit-btn');
        this.cancelBtn = document.getElementById('cancel-btn');
        this.deleteBtn = document.getElementById('delete-btn');
        this.isSubmitting = false;
        
        this.init();
    }

    init() {
        if (!this.form) {
            console.error('Episode edit form not found');
            return;
        }

        this.bindEvents();
        this.setupValidation();
        this.initializeFormData();
    }

    bindEvents() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Cancel button
        if (this.cancelBtn) {
            this.cancelBtn.addEventListener('click', (e) => this.handleCancel(e));
        }
        
        // Delete button
        if (this.deleteBtn) {
            this.deleteBtn.addEventListener('click', (e) => this.handleDelete(e));
        }
        
        // Real-time validation
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
        
        // Season change handler
        const seasonSelect = document.getElementById('season_id');
        if (seasonSelect) {
            seasonSelect.addEventListener('change', () => this.handleSeasonChange());
        }
        
        // URL validation
        const urlFields = ['embed_url', 'still_path'];
        urlFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.addEventListener('blur', () => this.validateUrl(field));
            }
        });
    }

    setupValidation() {
        // Set up validation rules
        this.validationRules = {
            season_id: {
                required: true,
                message: 'Please select a season'
            },
            episode_number: {
                required: true,
                min: 1,
                type: 'number',
                message: 'Episode number must be a positive number'
            },
            name: {
                required: true,
                maxLength: 255,
                message: 'Episode name is required and must be less than 255 characters'
            },
            overview: {
                required: true,
                message: 'Episode overview is required'
            },
            runtime: {
                required: true,
                min: 1,
                type: 'number',
                message: 'Runtime must be a positive number in minutes'
            },
            embed_url: {
                required: true,
                type: 'url',
                message: 'Please enter a valid embed URL'
            },
            still_path: {
                type: 'url',
                message: 'Please enter a valid still image URL'
            }
        };
    }

    initializeFormData() {
        // Store original form data for change detection
        this.originalData = new FormData(this.form);
        this.hasChanges = false;
        
        // Enable change detection
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                this.hasChanges = true;
                this.updateSubmitButton();
            });
        });
    }

    validateField(field) {
        const fieldName = field.name;
        const value = field.value.trim();
        const rules = this.validationRules[fieldName];
        
        if (!rules) return true;
        
        // Clear previous errors
        this.clearFieldError(field);
        
        // Required validation
        if (rules.required && !value) {
            this.showFieldError(field, rules.message);
            return false;
        }
        
        // Skip other validations if field is empty and not required
        if (!value && !rules.required) return true;
        
        // Type validations
        if (rules.type === 'number') {
            const numValue = parseFloat(value);
            if (isNaN(numValue)) {
                this.showFieldError(field, 'Must be a valid number');
                return false;
            }
            
            if (rules.min !== undefined && numValue < rules.min) {
                this.showFieldError(field, `Must be at least ${rules.min}`);
                return false;
            }
        }
        
        if (rules.type === 'url' && value) {
            if (!this.isValidUrl(value)) {
                this.showFieldError(field, 'Please enter a valid URL');
                return false;
            }
        }
        
        // Length validation
        if (rules.maxLength && value.length > rules.maxLength) {
            this.showFieldError(field, `Must be less than ${rules.maxLength} characters`);
            return false;
        }
        
        return true;
    }

    validateUrl(field) {
        const value = field.value.trim();
        if (value && !this.isValidUrl(value)) {
            this.showFieldError(field, 'Please enter a valid URL');
            return false;
        }
        this.clearFieldError(field);
        return true;
    }

    isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }

    showFieldError(field, message) {
        field.classList.add('error');
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const errorDiv = field.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    validateForm() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    handleSeasonChange() {
        // Clear episode number validation when season changes
        const episodeNumberField = document.getElementById('episode_number');
        if (episodeNumberField) {
            this.clearFieldError(episodeNumberField);
        }
    }

    updateSubmitButton() {
        if (this.submitBtn) {
            this.submitBtn.disabled = this.isSubmitting || !this.hasChanges;
            
            if (this.hasChanges && !this.isSubmitting) {
                this.submitBtn.classList.add('btn-primary');
                this.submitBtn.classList.remove('btn-secondary');
            } else {
                this.submitBtn.classList.add('btn-secondary');
                this.submitBtn.classList.remove('btn-primary');
            }
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        if (this.isSubmitting) return;
        
        // Validate form
        if (!this.validateForm()) {
            this.showMessage('Please fix the errors below', 'error');
            return;
        }
        
        this.isSubmitting = true;
        this.updateSubmitButton();
        
        // Show loading state
        const originalText = this.submitBtn.innerHTML;
        this.submitBtn.innerHTML = '<span class="loading-spinner"></span> Updating Episode...';
        
        try {
            const formData = new FormData(this.form);
            
            // Add method override for PUT request
            formData.append('_method', 'PUT');
            
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage(result.message, 'success');
                
                // Reset change detection
                this.hasChanges = false;
                this.originalData = new FormData(this.form);
                
                // Redirect after short delay
                setTimeout(() => {
                    const seriesId = document.querySelector('input[name="series_id"]').value;
                    window.location.href = `/admin/series/${seriesId}`;
                }, 1500);
                
            } else {
                this.showMessage(result.error || 'Failed to update episode', 'error');
            }
            
        } catch (error) {
            console.error('Submit error:', error);
            this.showMessage('Network error. Please try again.', 'error');
        } finally {
            this.isSubmitting = false;
            this.submitBtn.innerHTML = originalText;
            this.updateSubmitButton();
        }
    }

    handleCancel(e) {
        e.preventDefault();
        
        if (this.hasChanges) {
            if (!confirm('You have unsaved changes. Are you sure you want to leave?')) {
                return;
            }
        }
        
        // Go back to series detail page
        const seriesId = document.querySelector('input[name="series_id"]').value;
        window.location.href = `/admin/series/${seriesId}`;
    }

    async handleDelete(e) {
        e.preventDefault();
        
        const episodeName = document.getElementById('name').value;
        const episodeNumber = document.getElementById('episode_number').value;
        
        if (!confirm(`Are you sure you want to delete Episode ${episodeNumber}: "${episodeName}"?\n\nThis action cannot be undone.`)) {
            return;
        }
        
        const deleteBtn = e.target.closest('button');
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<span class="loading-spinner"></span> Deleting...';
        deleteBtn.disabled = true;
        
        try {
            const response = await fetch(this.form.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage(result.message, 'success');
                
                // Redirect after short delay
                setTimeout(() => {
                    const seriesId = document.querySelector('input[name="series_id"]').value;
                    window.location.href = `/admin/series/${seriesId}`;
                }, 1500);
                
            } else {
                this.showMessage(result.error || 'Failed to delete episode', 'error');
            }
            
        } catch (error) {
            console.error('Delete error:', error);
            this.showMessage('Network error. Please try again.', 'error');
        } finally {
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        }
    }

    showMessage(message, type = 'info') {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.error-message, .success-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = type === 'error' ? 'error-message' : 'success-message';
        messageDiv.textContent = message;
        
        // Insert at top of form
        const formContent = document.querySelector('.episode-form-content');
        if (formContent) {
            formContent.insertBefore(messageDiv, formContent.firstChild);
            
            // Auto-remove success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.remove();
                    }
                }, 5000);
            }
            
            // Scroll to message
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}

// Utility functions
function formatRuntime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
}

function previewUrl(url, type = 'embed') {
    if (!url) return;
    
    if (type === 'embed') {
        // Open embed URL in new tab
        window.open(url, '_blank', 'width=800,height=600');
    } else {
        // Open image URL in new tab
        window.open(url, '_blank');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new EpisodeEditManager();
});

// Prevent accidental page refresh when there are unsaved changes
window.addEventListener('beforeunload', (e) => {
    const manager = document.querySelector('.episode-edit-form');
    if (manager && manager.hasChanges) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
    }
});