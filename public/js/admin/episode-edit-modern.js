/**
 * Modern Professional Episode Edit JavaScript
 * File: resources/js/admin/episode-edit-modern.js
 * Following workinginstruction.md - separate JS file for easy debugging
 */

class ModernEpisodeEditor {
    constructor() {
        this.form = document.getElementById('episode-edit-form');
        this.isSubmitting = false;
        this.validators = {};
        this.originalData = {};
        
        this.init();
    }

    init() {
        if (!this.form) {
                        return;
        }

        this.storeOriginalData();
        this.setupEventListeners();
        this.setupValidators();
        this.setupEnhancements();
        this.initializeFormState();
    }

    storeOriginalData() {
        const formData = new FormData(this.form);
        this.originalData = {};
        
        for (let [key, value] of formData.entries()) {
            this.originalData[key] = value;
        }
    }

    setupEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Button handlers
        this.setupButtonHandlers();
        
        // Input handlers
        this.setupInputHandlers();
        
        // Real-time validation
        this.setupRealTimeValidation();
    }

    setupButtonHandlers() {
        // Cancel button
        const cancelBtn = document.getElementById('cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', (e) => this.handleCancel(e));
        }

        // Delete button
        const deleteBtn = document.getElementById('delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => this.handleDelete(e));
        }

        // Preview buttons
        document.querySelectorAll('.preview-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handlePreview(e));
        });
    }

    setupInputHandlers() {
        // Runtime formatting
        const runtimeInput = document.getElementById('runtime');
        if (runtimeInput) {
            runtimeInput.addEventListener('blur', () => this.formatRuntime(runtimeInput));
            runtimeInput.addEventListener('input', () => this.validateRuntime(runtimeInput));
        }

        // Episode number validation
        const episodeNumberInput = document.getElementById('episode_number');
        const seasonSelect = document.getElementById('season_id');
        
        if (episodeNumberInput && seasonSelect) {
            episodeNumberInput.addEventListener('blur', () => 
                this.checkEpisodeUniqueness(seasonSelect.value, episodeNumberInput.value, episodeNumberInput)
            );
            seasonSelect.addEventListener('change', () => 
                this.checkEpisodeUniqueness(seasonSelect.value, episodeNumberInput.value, episodeNumberInput)
            );
        }

        // URL validation
        document.querySelectorAll('input[type="url"]').forEach(input => {
            input.addEventListener('blur', () => this.validateUrl(input));
        });

        // Character counting
        document.querySelectorAll('textarea[maxlength]').forEach(textarea => {
            this.setupCharacterCounter(textarea);
        });
    }

    setupRealTimeValidation() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => {
                this.clearFieldError(input);
                this.updateFormProgress();
            });
        });
    }

    setupValidators() {
        this.validators = {
            required: (value) => value && value.trim().length > 0,
            email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            url: (value) => {
                try {
                    new URL(value);
                    return true;
                } catch {
                    return false;
                }
            },
            number: (value) => !isNaN(parseFloat(value)) && isFinite(value),
            minLength: (value, min) => value && value.length >= min,
            maxLength: (value, max) => !value || value.length <= max
        };
    }

    setupEnhancements() {
        // Enhanced checkbox styling
        this.setupCheckboxEnhancements();
        
        // Form progress indicator
        this.createProgressIndicator();
        
        // Auto-save indicator
        this.createAutoSaveIndicator();
        
        // Smooth focus transitions
        this.setupFocusEnhancements();
    }

    setupCheckboxEnhancements() {
        document.querySelectorAll('.checkbox-group').forEach(group => {
            const checkbox = group.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.addEventListener('change', () => {
                    group.classList.toggle('checked', checkbox.checked);
                });
                
                // Initialize state
                group.classList.toggle('checked', checkbox.checked);
            }
        });
    }

    createProgressIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'form-progress-indicator';
        indicator.innerHTML = `
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <span class="progress-text">Form completion: <span class="progress-percentage">0%</span></span>
        `;
        
        // Add to header
        const header = document.querySelector('.episode-header-content');
        if (header) {
            header.appendChild(indicator);
        }
        
        this.updateFormProgress();
    }

    createAutoSaveIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'auto-save-indicator';
        indicator.innerHTML = '<i class="fas fa-save"></i> Changes saved';
        document.body.appendChild(indicator);
    }

    setupFocusEnhancements() {
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', () => {
                input.closest('.form-group')?.classList.add('focused');
            });
            
            input.addEventListener('blur', () => {
                input.closest('.form-group')?.classList.remove('focused');
            });
        });
    }

    initializeFormState() {
        // Format existing values
        const runtimeInput = document.getElementById('runtime');
        if (runtimeInput && runtimeInput.value) {
            this.formatRuntime(runtimeInput);
        }
        
        // Initial progress calculation
        this.updateFormProgress();
        
        // Initialize checkbox states
        document.querySelectorAll('.checkbox-group').forEach(group => {
            const checkbox = group.querySelector('input[type="checkbox"]');
            if (checkbox) {
                group.classList.toggle('checked', checkbox.checked);
            }
        });
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        if (this.isSubmitting) return;
        
        // Validate all fields
        const isValid = this.validateAllFields();
        
        if (!isValid) {
            this.showNotification('Please fix all validation errors before submitting', 'error');
            return;
        }
        
        this.isSubmitting = true;
        const submitBtn = document.getElementById('submit-btn');
        
        // Show loading state
        this.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(this.form);
            
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            if (response.ok) {
                this.showNotification('Episode updated successfully!', 'success');
                
                // Clear any drafts
                const episodeId = this.form.dataset.episodeId;
                if (episodeId) {
                    localStorage.removeItem(`episode_edit_draft_${episodeId}`);
                }
                
                // Redirect after delay
                setTimeout(() => {
                    window.location.href = this.form.dataset.redirectUrl || '/admin/series';
                }, 1500);
            } else {
                const errorData = await response.json();
                this.handleSubmissionErrors(errorData);
            }
        } catch (error) {
                        this.showNotification('Network error occurred. Please try again.', 'error');
        } finally {
            this.isSubmitting = false;
            this.setButtonLoading(submitBtn, false);
        }
    }

    handleCancel(e) {
        e.preventDefault();
        
        if (this.hasUnsavedChanges()) {
            this.showConfirmDialog(
                'Unsaved Changes',
                'You have unsaved changes. Are you sure you want to leave?',
                () => window.history.back()
            );
        } else {
            window.history.back();
        }
    }

    handleDelete(e) {
        e.preventDefault();
        
        const episodeName = document.querySelector('[name="name"]').value || 'this episode';
        
        this.showConfirmDialog(
            'Delete Episode',
            `Are you sure you want to delete "${episodeName}"? This action cannot be undone.`,
            () => {
                // Create and submit delete form
                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.action = e.target.dataset.deleteUrl;
                deleteForm.style.display = 'none';
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                
                deleteForm.appendChild(csrfToken);
                deleteForm.appendChild(methodField);
                document.body.appendChild(deleteForm);
                
                deleteForm.submit();
            },
            'danger'
        );
    }

    handlePreview(e) {
        const input = e.target.closest('.input-group').querySelector('input');
        const url = input.value.trim();
        
        if (!url) {
            this.showNotification('Please enter a URL first', 'warning');
            return;
        }
        
        if (!this.validators.url(url)) {
            this.showNotification('Please enter a valid URL', 'error');
            return;
        }
        
        const type = e.target.dataset.previewType;
        
        if (type === 'video') {
            window.open(url, '_blank', 'width=800,height=600');
        } else if (type === 'image') {
            this.showImagePreview(url);
        }
    }

    validateField(input) {
        const value = input.value.trim();
        const isRequired = input.hasAttribute('required');
        
        this.clearFieldError(input);
        
        // Required validation
        if (isRequired && !this.validators.required(value)) {
            this.showFieldError(input, 'This field is required');
            return false;
        }
        
        // Skip other validations if empty and not required
        if (!value && !isRequired) {
            return true;
        }
        
        // Type-specific validations
        switch (input.type) {
            case 'email':
                if (!this.validators.email(value)) {
                    this.showFieldError(input, 'Please enter a valid email address');
                    return false;
                }
                break;
                
            case 'url':
                if (!this.validators.url(value)) {
                    this.showFieldError(input, 'Please enter a valid URL');
                    return false;
                }
                break;
                
            case 'number':
                if (!this.validators.number(value)) {
                    this.showFieldError(input, 'Please enter a valid number');
                    return false;
                }
                
                const min = input.getAttribute('min');
                const max = input.getAttribute('max');
                const numValue = parseFloat(value);
                
                if (min && numValue < parseFloat(min)) {
                    this.showFieldError(input, `Value must be at least ${min}`);
                    return false;
                }
                
                if (max && numValue > parseFloat(max)) {
                    this.showFieldError(input, `Value must be no more than ${max}`);
                    return false;
                }
                break;
        }
        
        // Length validations
        const minLength = input.getAttribute('minlength');
        const maxLength = input.getAttribute('maxlength');
        
        if (minLength && !this.validators.minLength(value, parseInt(minLength))) {
            this.showFieldError(input, `Must be at least ${minLength} characters`);
            return false;
        }
        
        if (maxLength && !this.validators.maxLength(value, parseInt(maxLength))) {
            this.showFieldError(input, `Must be no more than ${maxLength} characters`);
            return false;
        }
        
        this.showFieldSuccess(input);
        return true;
    }

    validateAllFields() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    async checkEpisodeUniqueness(seasonId, episodeNumber, input) {
        if (!seasonId || !episodeNumber) return;

        try {
            const response = await fetch('/admin/series/episodes/check-unique', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    season_id: seasonId,
                    episode_number: episodeNumber,
                    exclude_id: this.form.dataset.episodeId
                })
            });
            
            const data = await response.json();
            
            if (!data.unique) {
                this.showFieldError(input, 'Episode number already exists in this season');
            }
        } catch (error) {
                    }
    }

    formatRuntime(input) {
        const minutes = parseInt(input.value);
        
        if (minutes && minutes > 0) {
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            
            let formatted = '';
            if (hours > 0) {
                formatted += `${hours}h `;
            }
            formatted += `${remainingMinutes}min`;

            const formGroup = input.closest('.form-group');
            if (formGroup) {
                const helpText = formGroup.querySelector('.field-help');
                if (helpText) {
                    helpText.innerHTML = `<i class="fas fa-clock icon"></i>Duration: ${formatted}`;
                }
            }
        }
    }

    validateRuntime(input) {
        const value = parseInt(input.value);
        
        if (value && (value < 1 || value > 600)) {
            this.showFieldError(input, 'Runtime must be between 1 and 600 minutes');
            return false;
        }
        
        return true;
    }

    validateUrl(input) {
        const url = input.value.trim();
        
        if (url) {
            const previewBtn = input.closest('.input-group')?.querySelector('.preview-btn');
            if (previewBtn) {
                previewBtn.disabled = !this.validators.url(url);
            }
        }
    }

    setupCharacterCounter(textarea) {
        const maxLength = parseInt(textarea.getAttribute('maxlength'));
        
        if (maxLength) {
            const counter = document.createElement('div');
            counter.className = 'character-counter';
            
            const updateCounter = () => {
                const remaining = maxLength - textarea.value.length;
                counter.textContent = `${remaining} characters remaining`;
                counter.className = `character-counter ${remaining < 20 ? 'warning' : ''}`;
            };
            
            textarea.addEventListener('input', updateCounter);
            textarea.parentNode.appendChild(counter);
            updateCounter();
        }
    }

    updateFormProgress() {
        const requiredInputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        const filledInputs = Array.from(requiredInputs).filter(input => {
            if (input.type === 'checkbox') {
                return input.checked;
            }
            return input.value.trim() !== '';
        });
        
        const percentage = Math.round((filledInputs.length / requiredInputs.length) * 100);
        
        const progressFill = document.querySelector('.progress-fill');
        const progressText = document.querySelector('.progress-percentage');
        
        if (progressFill) {
            progressFill.style.width = `${percentage}%`;
            progressFill.style.background = this.getProgressColor(percentage);
        }
        
        if (progressText) {
            progressText.textContent = `${percentage}%`;
        }
    }

    getProgressColor(percentage) {
        if (percentage < 30) return '#dc2626';
        if (percentage < 70) return '#d97706';
        return '#059669';
    }

    hasUnsavedChanges() {
        const currentData = new FormData(this.form);
        const current = {};
        
        for (let [key, value] of currentData.entries()) {
            current[key] = value;
        }
        
        return JSON.stringify(current) !== JSON.stringify(this.originalData);
    }

    showFieldError(input, message) {
        input.classList.add('error');
        input.classList.remove('success');

        this.clearFieldMessages(input);

        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i>${message}`;

        const container = input.closest('.form-group') || input.closest('div') || input.parentElement;
        if (container) {
            container.appendChild(errorDiv);
        }
    }

    showFieldSuccess(input) {
        input.classList.add('success');
        input.classList.remove('error');

        this.clearFieldMessages(input);

        const successDiv = document.createElement('div');
        successDiv.className = 'field-success';
        successDiv.innerHTML = `<i class="fas fa-check-circle"></i>Looks good!`;

        const container = input.closest('.form-group') || input.closest('div') || input.parentElement;
        if (container) {
            container.appendChild(successDiv);
        }
    }

    clearFieldError(input) {
        input.classList.remove('error', 'success');
        this.clearFieldMessages(input);
    }

    clearFieldMessages(input) {
        // Try to find form-group, or use parent element, or the input itself
        const group = input.closest('.form-group') || input.closest('div') || input.parentElement;
        if (group) {
            group.querySelectorAll('.field-error, .field-success').forEach(el => el.remove());
        }
    }

    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.classList.add('btn-loading');
            button.disabled = true;
        } else {
            button.classList.remove('btn-loading');
            button.disabled = false;
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Auto remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    showConfirmDialog(title, message, onConfirm, type = 'primary') {
        const dialog = document.createElement('div');
        dialog.className = 'confirm-dialog';
        dialog.innerHTML = `
            <div class="dialog-overlay"></div>
            <div class="dialog-content">
                <div class="dialog-header">
                    <h3>${title}</h3>
                </div>
                <div class="dialog-body">
                    <p>${message}</p>
                </div>
                <div class="dialog-actions">
                    <button type="button" class="btn btn-secondary dialog-cancel">Cancel</button>
                    <button type="button" class="btn btn-${type} dialog-confirm">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(dialog);
        
        // Event listeners
        dialog.querySelector('.dialog-cancel').addEventListener('click', () => {
            dialog.remove();
        });
        
        dialog.querySelector('.dialog-confirm').addEventListener('click', () => {
            onConfirm();
            dialog.remove();
        });
        
        dialog.querySelector('.dialog-overlay').addEventListener('click', () => {
            dialog.remove();
        });
        
        // Show with animation
        setTimeout(() => dialog.classList.add('show'), 10);
    }

    showImagePreview(url) {
        const preview = document.createElement('div');
        preview.className = 'image-preview-modal';
        preview.innerHTML = `
            <div class="preview-overlay"></div>
            <div class="preview-content">
                <button class="preview-close">&times;</button>
                <img src="${url}" alt="Preview" />
            </div>
        `;
        
        document.body.appendChild(preview);
        
        // Event listeners
        preview.querySelector('.preview-close').addEventListener('click', () => preview.remove());
        preview.querySelector('.preview-overlay').addEventListener('click', () => preview.remove());
        
        setTimeout(() => preview.classList.add('show'), 10);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    handleSubmissionErrors(errorData) {
        if (errorData.errors) {
            Object.keys(errorData.errors).forEach(fieldName => {
                const input = this.form.querySelector(`[name="${fieldName}"]`);
                if (input) {
                    this.showFieldError(input, errorData.errors[fieldName][0]);
                }
            });
        }
        
        this.showNotification(errorData.message || 'Please fix the errors and try again', 'error');
    }
}

// Global preview function for compatibility
window.previewUrl = function(url, type) {
    const editor = window.episodeEditor;
    if (editor) {
        editor.handlePreview({ 
            target: { 
                dataset: { previewType: type },
                closest: () => ({ querySelector: () => ({ value: url }) })
            }
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.episodeEditor = new ModernEpisodeEditor();
});