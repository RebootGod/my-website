/**
 * Episode Draft Management
 * File: resources/js/admin/episode-draft-manager.js
 * 
 * Handles draft saving and restoration for episode edit forms
 * Following workinginstruction.md - separate JS file for easy debugging
 */

class EpisodeDraftManager {
    constructor(episodeId) {
        this.episodeId = episodeId;
        this.draftKey = `episode_edit_draft_${episodeId}`;
        this.form = document.getElementById('episode-edit-form');
        this.draftTimer = null;
        this.originalData = {};
        
        this.init();
    }
    
    init() {
        if (!this.form) {
            console.error('Episode edit form not found for draft manager');
            return;
        }
        
        // Store original form data
        this.storeOriginalData();
        
        // Check for existing drafts
        this.checkAndRestoreDraft();
        
        // Setup auto-save
        this.setupAutoSave();
        
        // Setup form submission handler to clear drafts
        this.setupSubmissionHandler();
    }
    
    storeOriginalData() {
        const formData = new FormData(this.form);
        this.originalData = {};
        
        for (let [key, value] of formData.entries()) {
            this.originalData[key] = value;
        }
    }
    
    checkAndRestoreDraft() {
        const savedDraft = localStorage.getItem(this.draftKey);
        
        if (!savedDraft) {
            return;
        }
        
        try {
            const draft = JSON.parse(savedDraft);
            
            // Check if draft is different from current form data
            if (this.isDraftDifferentFromCurrent(draft)) {
                this.showDraftRestoreDialog(draft);
            } else {
                // Draft is same as current data, remove it
                this.clearDraft();
            }
        } catch (e) {
            console.warn('Failed to parse saved draft:', e);
            this.clearDraft();
        }
    }
    
    isDraftDifferentFromCurrent(draft) {
        for (let key in draft) {
            const input = this.form.querySelector(`[name="${key}"]`);
            if (input && input.value !== draft[key]) {
                return true;
            }
        }
        return false;
    }
    
    showDraftRestoreDialog(draft) {
        // Create modern dialog instead of browser confirm
        const dialog = this.createDraftDialog(draft);
        document.body.appendChild(dialog);
        
        // Show dialog with animation
        setTimeout(() => {
            dialog.classList.add('show');
        }, 10);
    }
    
    createDraftDialog(draft) {
        const dialog = document.createElement('div');
        dialog.className = 'draft-restore-dialog';
        dialog.innerHTML = `
            <div class="draft-dialog-overlay"></div>
            <div class="draft-dialog-content">
                <div class="draft-dialog-header">
                    <i class="fas fa-save text-warning"></i>
                    <h5>Draft Found</h5>
                </div>
                <div class="draft-dialog-body">
                    <p>A draft of your changes was found. Would you like to restore it?</p>
                    <div class="draft-changes">
                        ${this.getDraftChangesHtml(draft)}
                    </div>
                </div>
                <div class="draft-dialog-actions">
                    <button type="button" class="btn btn-secondary" id="draft-cancel">
                        <i class="fas fa-times"></i> Discard Draft
                    </button>
                    <button type="button" class="btn btn-primary" id="draft-restore">
                        <i class="fas fa-undo"></i> Restore Draft
                    </button>
                </div>
            </div>
        `;
        
        // Add event listeners
        const cancelBtn = dialog.querySelector('#draft-cancel');
        const restoreBtn = dialog.querySelector('#draft-restore');
        
        cancelBtn.addEventListener('click', () => {
            this.clearDraft();
            this.closeDraftDialog(dialog);
        });
        
        restoreBtn.addEventListener('click', () => {
            this.restoreDraft(draft);
            this.closeDraftDialog(dialog);
        });
        
        return dialog;
    }
    
    getDraftChangesHtml(draft) {
        let changesHtml = '<div class="draft-changes-list">';
        
        for (let key in draft) {
            const input = this.form.querySelector(`[name="${key}"]`);
            if (input && input.value !== draft[key]) {
                const label = this.getFieldLabel(input) || key;
                changesHtml += `
                    <div class="draft-change-item">
                        <strong>${label}:</strong>
                        <div class="draft-change-values">
                            <span class="current-value">Current: "${input.value || '(empty)'}"</span>
                            <span class="draft-value">Draft: "${draft[key]}"</span>
                        </div>
                    </div>
                `;
            }
        }
        
        changesHtml += '</div>';
        return changesHtml;
    }
    
    getFieldLabel(input) {
        const label = this.form.querySelector(`label[for="${input.id}"]`);
        if (label) {
            return label.textContent.replace('*', '').trim();
        }
        
        // Try to find label by proximity
        const parentGroup = input.closest('.form-group');
        if (parentGroup) {
            const groupLabel = parentGroup.querySelector('label');
            if (groupLabel) {
                return groupLabel.textContent.replace('*', '').trim();
            }
        }
        
        return input.getAttribute('placeholder') || input.name;
    }
    
    restoreDraft(draft) {
        for (let key in draft) {
            const input = this.form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = draft[key] === '1' || draft[key] === 'on';
                } else {
                    input.value = draft[key];
                }
                
                // Trigger change event for any listeners
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        
        this.clearDraft();
        this.showNotification('Draft restored successfully!', 'success');
    }
    
    closeDraftDialog(dialog) {
        dialog.classList.remove('show');
        setTimeout(() => {
            if (dialog.parentNode) {
                dialog.parentNode.removeChild(dialog);
            }
        }, 300);
    }
    
    setupAutoSave() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(this.draftTimer);
                this.draftTimer = setTimeout(() => {
                    this.saveDraft();
                }, 3000); // Save draft after 3 seconds of inactivity
            });
        });
    }
    
    setupSubmissionHandler() {
        // Listen for custom episode-saved event
        window.addEventListener('episode-saved', (e) => {
            if (e.detail.episodeId === this.episodeId) {
                this.clearDraft();
                // Update original data to prevent false positives
                this.storeOriginalData();
            }
        });

        // Also clear on beforeunload after successful save
        let savingSuccess = false;
        window.addEventListener('episode-saved', () => {
            savingSuccess = true;
        });

        window.addEventListener('beforeunload', () => {
            if (savingSuccess) {
                this.clearDraft();
            }
        });
    }
    
    saveDraft() {
        if (!this.hasFormChanged()) {
            return;
        }
        
        const formData = new FormData(this.form);
        const draft = {};
        
        for (let [key, value] of formData.entries()) {
            draft[key] = value;
        }
        
        try {
            localStorage.setItem(this.draftKey, JSON.stringify(draft));
            this.showDraftIndicator();
        } catch (e) {
            console.warn('Failed to save draft:', e);
        }
    }
    
    hasFormChanged() {
        const formData = new FormData(this.form);
        const currentData = {};
        
        for (let [key, value] of formData.entries()) {
            currentData[key] = value;
        }
        
        return JSON.stringify(currentData) !== JSON.stringify(this.originalData);
    }
    
    clearDraft() {
        localStorage.removeItem(this.draftKey);
    }
    
    showDraftIndicator() {
        // Remove existing indicator
        const existing = document.querySelector('.draft-indicator');
        if (existing) {
            existing.remove();
        }
        
        // Create new indicator
        const indicator = document.createElement('div');
        indicator.className = 'draft-indicator';
        indicator.innerHTML = '<i class="fas fa-save"></i> Draft saved';
        
        document.body.appendChild(indicator);
        
        // Show and hide with animation
        setTimeout(() => {
            indicator.classList.add('show');
            setTimeout(() => {
                indicator.classList.remove('show');
                setTimeout(() => {
                    if (indicator.parentNode) {
                        indicator.parentNode.removeChild(indicator);
                    }
                }, 300);
            }, 2000);
        }, 10);
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }, 10);
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const episodeForm = document.getElementById('episode-edit-form');
    if (episodeForm && episodeForm.dataset.episodeId) {
        new EpisodeDraftManager(episodeForm.dataset.episodeId);
    }
});