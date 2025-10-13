/**
 * Bulk Progress Tracker
 * 
 * Tracks and displays progress for bulk operations
 * Max 350 lines per workinginstruction.md
 * 
 * Features:
 * - Real-time progress updates
 * - Visual progress bar
 * - Error display
 * - Auto-reload on completion
 */

class BulkProgressTracker {
    constructor(progressKey) {
        this.progressKey = progressKey;
        this.pollInterval = null;
        this.modalElement = null;
        this.isTracking = false;
    }

    /**
     * Start tracking
     */
    async start() {
        this.createModal();
        this.showModal();
        this.startPolling();
    }

    /**
     * Create progress modal
     */
    createModal() {
        // Remove existing modal if any
        const existing = document.getElementById('bulk-progress-modal');
        if (existing) {
            existing.remove();
        }

        // Create modal HTML
        const modal = document.createElement('div');
        modal.id = 'bulk-progress-modal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4">
                <h3 class="text-xl font-bold text-white mb-4">Processing Bulk Operation</h3>
                
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-400 mb-2">
                        <span>Progress</span>
                        <span id="progress-percentage">0%</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div id="progress-bar" class="bg-green-500 h-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Batch Info -->
                <div class="mb-4 text-sm text-gray-400">
                    <span id="batch-info">Batch 0 / 0</span>
                    <span id="current-processing" class="ml-4"></span>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-4 gap-4 mb-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-blue-400" id="progress-processed">0</div>
                        <div class="text-xs text-gray-400">Processed</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-500" id="progress-success">0</div>
                        <div class="text-xs text-gray-400">Success</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-red-500" id="progress-failed">0</div>
                        <div class="text-xs text-gray-400">Failed</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-400" id="progress-waiting">0</div>
                        <div class="text-xs text-gray-400">Waiting</div>
                    </div>
                </div>

                <!-- Total -->
                <div class="text-center mb-4">
                    <span class="text-sm text-gray-400">Total: </span>
                    <span class="text-lg font-bold text-white" id="progress-total">0</span>
                </div>

                <!-- Status Message -->
                <div class="text-sm text-gray-400 mb-4">
                    <span id="progress-status">Initializing...</span>
                </div>

                <!-- Errors Section -->
                <div id="errors-container" class="hidden">
                    <div class="bg-red-900 bg-opacity-30 border border-red-700 rounded p-3 mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-semibold text-red-400">Recent Errors</span>
                            <span class="text-xs text-red-400" id="error-count">0 errors</span>
                        </div>
                        <div id="error-list" class="text-xs text-red-300 max-h-32 overflow-y-auto space-y-1"></div>
                    </div>
                </div>
                <!-- Errors (hidden by default) -->
                <div id="progress-errors" class="hidden">
                    <div class="text-sm font-semibold text-red-500 mb-2">Errors:</div>
                    <div id="progress-errors-list" class="max-h-32 overflow-y-auto bg-gray-900 rounded p-2 text-xs text-gray-300"></div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 mt-4">
                    <button id="progress-cancel" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded transition">
                        Cancel
                    </button>
                    <button id="progress-close" class="hidden px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded transition">
                        Close & Reload
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        this.modalElement = modal;

        // Setup event listeners
        document.getElementById('progress-cancel').addEventListener('click', () => {
            this.stopPolling();
            this.hideModal();
        });

        document.getElementById('progress-close').addEventListener('click', () => {
            this.hideModal();
            window.location.reload();
        });
    }

    /**
     * Show modal
     */
    showModal() {
        if (this.modalElement) {
            this.modalElement.classList.remove('hidden');
        }
    }

    /**
     * Hide modal
     */
    hideModal() {
        if (this.modalElement) {
            this.modalElement.remove();
            this.modalElement = null;
        }
    }

    /**
     * Start polling for progress
     */
    startPolling() {
        this.isTracking = true;
        this.pollProgress();
        
        // Poll every 2 seconds
        this.pollInterval = setInterval(() => {
            this.pollProgress();
        }, 2000);
    }

    /**
     * Stop polling
     */
    stopPolling() {
        this.isTracking = false;
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    /**
     * Poll progress from server
     */
    async pollProgress() {
        if (!this.isTracking) return;

        try {
            const response = await fetch(`/admin/bulk/progress?key=${encodeURIComponent(this.progressKey)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch progress');
            }

            const result = await response.json();

            if (result.success && result.progress) {
                this.updateProgress(result.progress);
            }
        } catch (error) {
            console.error('Progress polling error:', error);
        }
    }

    /**
     * Update progress display
     */
    updateProgress(progress) {
        const {
            total,
            processed,
            success,
            failed,
            waiting,
            current_batch,
            total_batches,
            current_processing,
            current_processing_count,
            status,
            errors,
            total_errors,
            percentage
        } = progress;

        // Update progress bar
        const progressBar = document.getElementById('progress-bar');
        const progressPercentage = document.getElementById('progress-percentage');
        
        if (progressBar) {
            progressBar.style.width = `${percentage || 0}%`;
        }
        
        if (progressPercentage) {
            progressPercentage.textContent = `${percentage || 0}%`;
        }

        // Update batch info
        const batchInfo = document.getElementById('batch-info');
        const currentProcessingEl = document.getElementById('current-processing');
        
        if (batchInfo) {
            batchInfo.textContent = `Batch ${current_batch || 0} / ${total_batches || 0}`;
        }
        
        if (currentProcessingEl && current_processing && current_processing.length > 0) {
            currentProcessingEl.textContent = `(Processing ${current_processing_count || 0} items...)`;
            currentProcessingEl.className = 'ml-4 text-yellow-400 animate-pulse';
        } else if (currentProcessingEl) {
            currentProcessingEl.textContent = '';
        }

        // Update stats
        const totalElement = document.getElementById('progress-total');
        const processedElement = document.getElementById('progress-processed');
        const successElement = document.getElementById('progress-success');
        const failedElement = document.getElementById('progress-failed');
        const waitingElement = document.getElementById('progress-waiting');

        if (totalElement) totalElement.textContent = total || 0;
        if (processedElement) processedElement.textContent = processed || 0;
        if (successElement) successElement.textContent = success || 0;
        if (failedElement) failedElement.textContent = failed || 0;
        if (waitingElement) waitingElement.textContent = waiting || 0;

        // Update status
        const statusElement = document.getElementById('progress-status');
        if (statusElement) {
            if (status === 'completed') {
                statusElement.textContent = 'Operation completed!';
                statusElement.className = 'text-sm text-green-400 font-semibold';
            } else {
                statusElement.textContent = `Processing batch ${current_batch || 0} of ${total_batches || 0}...`;
                statusElement.className = 'text-sm text-gray-400';
            }
        }

        // Update errors
        const errorsContainer = document.getElementById('errors-container');
        const errorList = document.getElementById('error-list');
        const errorCount = document.getElementById('error-count');

        if (errors && errors.length > 0) {
            if (errorsContainer) errorsContainer.classList.remove('hidden');
            
            if (errorCount) {
                errorCount.textContent = `${total_errors || errors.length} error(s)`;
            }
            
            if (errorList) {
                errorList.innerHTML = errors.map(err => {
                    const title = err.title || `ID ${err.id || 'unknown'}`;
                    const error = err.error || 'Unknown error';
                    return `<div class="py-1 border-b border-red-800 last:border-0">
                        <span class="font-semibold">${title}:</span> ${error}
                    </div>`;
                }).join('');
            }
        } else {
            if (errorsContainer) errorsContainer.classList.add('hidden');
        }

        // Update status message
        const statusElement = document.getElementById('progress-status');
        if (statusElement) {
            if (status === 'completed') {
                statusElement.textContent = 'Operation completed!';
                statusElement.className = 'text-sm text-green-500';
            } else if (status === 'processing') {
                statusElement.textContent = `Processing ${processed} of ${total} items...`;
                statusElement.className = 'text-sm text-blue-400';
            } else {
                statusElement.textContent = status;
                statusElement.className = 'text-sm text-gray-400';
            }
        }

        // Show errors if any
        if (errors && errors.length > 0) {
            const errorsContainer = document.getElementById('progress-errors');
            const errorsList = document.getElementById('progress-errors-list');
            
            if (errorsContainer) errorsContainer.classList.remove('hidden');
            if (errorsList) {
                errorsList.innerHTML = errors.map(err => 
                    `<div class="mb-1">ID ${this.escapeHtml(err.id)}: ${this.escapeHtml(err.error)}</div>`
                ).join('');
            }
        }

        // Handle completion
        if (status === 'completed') {
            this.stopPolling();
            
            // Show close button, hide cancel
            const cancelBtn = document.getElementById('progress-cancel');
            const closeBtn = document.getElementById('progress-close');
            
            if (cancelBtn) cancelBtn.classList.add('hidden');
            if (closeBtn) closeBtn.classList.remove('hidden');

            // Show success notification
            window.showToast(`Completed! Success: ${success}, Failed: ${failed}`, 'success');
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export to global scope
window.BulkProgressTracker = BulkProgressTracker;
