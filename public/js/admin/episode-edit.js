/**
 * Clean Episode Edit JavaScript
 * File: resources/js/admin/episode-edit.js
 */

class EpisodeEditManager {
    constructor() {
        this.form = document.getElementById('episode-edit-form');
        this.submitBtn = document.getElementById('submit-btn');
        this.cancelBtn = document.getElementById('cancel-btn');
        this.deleteBtn = document.getElementById('delete-btn');
        this.isSubmitting = false;
        this.episodeId = this.form ? this.form.dataset.episodeId : null;
        
        this.init();
    }

    init() {
        if (!this.form) {
            console.error('Episode edit form not found');
            return;
        }

        this.bindEvents();
        this.setupValidation();
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
    }

    setupValidation() {
        // Episode number uniqueness check
        const episodeNumberInput = document.getElementById('episode_number');
        const seasonSelect = document.getElementById('season_id');
        
        if (episodeNumberInput && seasonSelect) {
            const checkUniqueness = () => {
                const seasonId = seasonSelect.value;
                const episodeNumber = episodeNumberInput.value;
                
                if (seasonId && episodeNumber) {
                    this.checkEpisodeNumberUnique(seasonId, episodeNumber, episodeNumberInput);
                }
            };
            
            episodeNumberInput.addEventListener('blur', checkUniqueness);
            seasonSelect.addEventListener('change', checkUniqueness);
        }
    }

    async checkEpisodeNumberUnique(seasonId, episodeNumber, input) {
        try {
            const currentEpisodeId = this.form.dataset.episodeId;
            const response = await fetch('/admin/series/episodes/check-unique', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    season_id: seasonId,
                    episode_number: episodeNumber,
                    exclude_id: currentEpisodeId
                })
            });
            
            const data = await response.json();
            
            if (!data.unique) {
                this.showFieldError(input, 'Episode number already exists in this season');
            } else {
                this.clearFieldError(input);
            }
        } catch (error) {
            console.error('Error checking episode uniqueness:', error);
        }
    }

    validateField(input) {
        this.clearFieldError(input);
        
        const value = input.value.trim();
        const isRequired = input.hasAttribute('required');
        
        // Required field validation
        if (isRequired && !value) {
            this.showFieldError(input, 'This field is required');
            return false;
        }
        
        // Specific field validations
        switch (input.type) {
            case 'email':
                if (value && !this.isValidEmail(value)) {
                    this.showFieldError(input, 'Please enter a valid email address');
                    return false;
                }
                break;
                
            case 'url':
                if (value && !this.isValidUrl(value)) {
                    this.showFieldError(input, 'Please enter a valid URL');
                    return false;
                }
                break;
                
            case 'number':
                const min = input.getAttribute('min');
                const max = input.getAttribute('max');
                const numValue = parseFloat(value);
                
                if (value && isNaN(numValue)) {
                    this.showFieldError(input, 'Please enter a valid number');
                    return false;
                }
                
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
        
        return true;
    }

    showFieldError(input, message) {
        input.classList.add('error');
        
        // Remove existing error message
        const existingError = input.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        input.parentElement.appendChild(errorDiv);
    }

    clearFieldError(input) {
        input.classList.remove('error');
        
        const errorDiv = input.parentElement.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        if (this.isSubmitting) return;
        
        // Validate all fields
        const inputs = this.form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            this.showMessage('Please fix the validation errors before submitting', 'error');
            return;
        }
        
        this.isSubmitting = true;
        this.submitBtn.disabled = true;
        this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Episode...';
        
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
                // Clear any saved drafts
                const episodeId = this.form.dataset.episodeId;
                if (episodeId) {
                    localStorage.removeItem(`episode_edit_draft_${episodeId}`);
                }
                
                this.showMessage('Episode updated successfully!', 'success');
                
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = this.form.dataset.redirectUrl || '/admin/series';
                }, 1500);
            } else {
                const errorData = await response.json();
                this.showMessage(errorData.message || 'An error occurred while updating the episode', 'error');
            }
        } catch (error) {
            console.error('Submission error:', error);
            this.showMessage('Network error occurred. Please try again.', 'error');
        } finally {
            this.isSubmitting = false;
            this.submitBtn.disabled = false;
            this.submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Episode';
        }
    }

    handleCancel(e) {
        e.preventDefault();
        
        if (this.hasFormChanged()) {
            if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
                window.history.back();
            }
        } else {
            window.history.back();
        }
    }

    handleDelete(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this episode? This action cannot be undone.')) {
            return;
        }
        
        // Show confirmation modal or redirect to delete action
        const deleteForm = document.createElement('form');
        deleteForm.method = 'POST';
        deleteForm.action = this.deleteBtn.dataset.deleteUrl;
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
    }

    hasFormChanged() {
        const formData = new FormData(this.form);
        const currentData = Object.fromEntries(formData);
        
        // Compare with original data (you'd need to store this on page load)
        return JSON.stringify(currentData) !== JSON.stringify(this.originalData || {});
    }

    showMessage(message, type = 'info') {
        // Remove existing messages
        const existing = document.querySelectorAll('.alert-message');
        existing.forEach(el => el.remove());
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert-message ${type === 'success' ? 'success-message' : 'error-message'}`;
        messageDiv.textContent = message;
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '400px';
        
        document.body.appendChild(messageDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 5000);
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new EpisodeEditManager();
});

// Global functions for preview buttons
function previewUrl(url, type) {
    if (!url) {
        alert('Please enter a URL first');
        return;
    }
    
    if (type === 'embed') {
        window.open(url, '_blank', 'width=800,height=600');
    } else if (type === 'image') {
        const img = new Image();
        img.onload = function() {
            const popup = window.open('', '_blank', 'width=600,height=400');
            popup.document.write(`
                <html>
                    <head><title>Image Preview</title></head>
                    <body style="margin:0;padding:20px;background:#f0f0f0;text-align:center;">
                        <img src="${url}" style="max-width:100%;max-height:100%;"/>
                    </body>
                </html>
            `);
        };
        img.onerror = function() {
            alert('Failed to load image. Please check the URL.');
        };
        img.src = url;
    }
}