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
        this.originalData = {};
        
        this.init();
        this.addVisualEnhancements();
    }

    init() {
        if (!this.form) {
            console.error('Episode edit form not found');
            return;
        }

        this.bindEvents();
        this.setupValidation();
        this.initializeFormData();
        this.setupAutoSave();
    }
    
    addVisualEnhancements() {
        // Add loading overlay
        this.createLoadingOverlay();
        
        // Add form animations
        this.animateFormSections();
        
        // Add input focus effects
        this.enhanceInputEffects();
        
        // Add progress indicator
        this.createProgressIndicator();
    }
    
    createLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'episode-loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner-large"></div>
                <p class="loading-text">Processing episode...</p>
            </div>
        `;
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;
        document.body.appendChild(overlay);
    }
    
    animateFormSections() {
        const sections = document.querySelectorAll('.form-section');
        sections.forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                section.style.transition = 'all 0.5s ease';
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, index * 150);
        });
    }
    
    enhanceInputEffects() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Add floating label effect
            this.addFloatingLabel(input);
            
            // Add focus glow effect
            input.addEventListener('focus', () => {
                input.style.boxShadow = '0 0 20px rgba(99, 102, 241, 0.3)';
                input.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', () => {
                input.style.boxShadow = '';
                input.style.transform = 'scale(1)';
            });
        });
    }
    
    addFloatingLabel(input) {
        const parent = input.parentElement;
        const label = parent.querySelector('label');
        
        if (label && input.type !== 'checkbox') {
            label.style.transition = 'all 0.3s ease';
            
            if (input.value) {
                label.style.transform = 'translateY(-8px) scale(0.85)';
                label.style.color = '#6366f1';
            }
            
            input.addEventListener('focus', () => {
                label.style.transform = 'translateY(-8px) scale(0.85)';
                label.style.color = '#6366f1';
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    label.style.transform = 'translateY(0) scale(1)';
                    label.style.color = '#e2e8f0';
                }
            });
        }
    }
    
    createProgressIndicator() {
        const progressBar = document.createElement('div');
        progressBar.id = 'form-progress';
        progressBar.innerHTML = `
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="progress-text">Form completion: <span id="progress-percentage">0%</span></div>
        `;
        progressBar.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #1e293b, #374151);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #475569;
            color: #e2e8f0;
            font-size: 14px;
            z-index: 1000;
            min-width: 200px;
        `;
        document.body.appendChild(progressBar);
        
        this.updateProgress();
    }
    
    updateProgress() {
        const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        const filled = Array.from(inputs).filter(input => input.value.trim() !== '').length;
        const percentage = Math.round((filled / inputs.length) * 100);
        
        const progressFill = document.querySelector('#form-progress .progress-fill');
        const progressText = document.querySelector('#progress-percentage');
        
        if (progressFill && progressText) {
            progressFill.style.width = percentage + '%';
            progressFill.style.background = `linear-gradient(90deg, #ef4444 0%, #f59e0b 50%, #22c55e 100%)`;
            progressText.textContent = percentage + '%';
        }
    }
    
    setupAutoSave() {
        let autoSaveTimeout;
        
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    this.autoSaveDraft();
                }, 2000);
                
                this.updateProgress();
            });
        });
    }
    
    autoSaveDraft() {
        const formData = new FormData(this.form);
        const data = Object.fromEntries(formData);
        
        localStorage.setItem('episode_edit_draft_' + (this.form.dataset.episodeId || 'new'), JSON.stringify(data));
        
        this.showNotification('Draft saved automatically', 'success', 2000);
    }
    
    showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: ${type === 'success' ? '#16a34a' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            z-index: 10000;
            font-weight: 600;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideUp 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
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