/**
 * Filter Presets Module
 * 
 * Manages saving/loading filter presets
 * Max 350 lines per workinginstruction.md
 * 
 * Features:
 * - Save current filters as preset
 * - Load saved presets
 * - Delete presets
 * - Export/Import presets
 */

class FilterPresets {
    constructor(contentType) {
        this.contentType = contentType; // 'movie' or 'series'
        this.storageKey = `filter_presets_${contentType}`;
        this.presets = this.loadPresets();
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.renderPresetsList();
    }

    setupEventListeners() {
        // Save preset button
        const saveBtn = document.getElementById('save-filter-preset');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.showSaveDialog());
        }

        // Preset select dropdown
        const presetSelect = document.getElementById('filter-presets-select');
        if (presetSelect) {
            presetSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    this.loadPreset(e.target.value);
                }
            });
        }

        // Delete preset button
        const deleteBtn = document.getElementById('delete-filter-preset');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => this.deleteCurrentPreset());
        }

        // Export presets
        const exportBtn = document.getElementById('export-presets');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportPresets());
        }

        // Import presets
        const importInput = document.getElementById('import-presets-input');
        if (importInput) {
            importInput.addEventListener('change', (e) => this.importPresets(e));
        }
    }

    loadPresets() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Failed to load presets:', error);
            return [];
        }
    }

    savePresets() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.presets));
            return true;
        } catch (error) {
            console.error('Failed to save presets:', error);
            return false;
        }
    }

    showSaveDialog() {
        const name = prompt('Enter a name for this filter preset:');
        
        if (!name || name.trim() === '') {
            return;
        }

        // Get current filter values
        const filters = window.advancedFilters ? 
            window.advancedFilters.getFilterValues() : 
            this.getCurrentFilters();

        // Create preset
        const preset = {
            id: Date.now().toString(),
            name: name.trim(),
            filters: filters,
            created_at: new Date().toISOString()
        };

        // Add to presets
        this.presets.push(preset);
        
        // Save to storage
        if (this.savePresets()) {
            this.renderPresetsList();
            this.showToast(`Preset "${name}" saved successfully!`, 'success');
        } else {
            this.showToast('Failed to save preset', 'error');
        }
    }

    getCurrentFilters() {
        const form = document.getElementById('advanced-filter-form');
        if (!form) return {};

        const formData = new FormData(form);
        const filters = {};

        for (let [key, value] of formData.entries()) {
            if (value !== '' && value !== null) {
                if (key.endsWith('[]')) {
                    const cleanKey = key.replace('[]', '');
                    if (!filters[cleanKey]) filters[cleanKey] = [];
                    filters[cleanKey].push(value);
                } else {
                    filters[key] = value;
                }
            }
        }

        return filters;
    }

    loadPreset(presetId) {
        const preset = this.presets.find(p => p.id === presetId);
        
        if (!preset) {
            this.showToast('Preset not found', 'error');
            return;
        }

        // Apply filters to form
        const form = document.getElementById('advanced-filter-form');
        if (!form) return;

        // Reset form first
        form.reset();

        // Apply preset values
        Object.entries(preset.filters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                // Handle multi-select
                value.forEach(v => {
                    const input = form.querySelector(`input[name="${key}[]"][value="${v}"]`);
                    if (input) input.checked = true;
                });
            } else {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = value === '1' || value === 'true';
                    } else {
                        input.value = value;
                    }
                }
            }
        });

        // Re-initialize range sliders if available
        if (window.advancedFilters) {
            window.advancedFilters.initializeRangeSliders();
        }

        this.showToast(`Preset "${preset.name}" loaded`, 'success');
    }

    deleteCurrentPreset() {
        const presetSelect = document.getElementById('filter-presets-select');
        if (!presetSelect || !presetSelect.value) {
            this.showToast('No preset selected', 'error');
            return;
        }

        const presetId = presetSelect.value;
        const preset = this.presets.find(p => p.id === presetId);

        if (!preset) return;

        if (!confirm(`Delete preset "${preset.name}"?`)) {
            return;
        }

        // Remove preset
        this.presets = this.presets.filter(p => p.id !== presetId);
        
        // Save and update UI
        if (this.savePresets()) {
            this.renderPresetsList();
            this.showToast(`Preset "${preset.name}" deleted`, 'success');
        }
    }

    renderPresetsList() {
        const presetSelect = document.getElementById('filter-presets-select');
        if (!presetSelect) return;

        // Clear current options
        presetSelect.innerHTML = '<option value="">-- Load Preset --</option>';

        // Add presets
        this.presets.forEach(preset => {
            const option = document.createElement('option');
            option.value = preset.id;
            option.textContent = preset.name;
            presetSelect.appendChild(option);
        });

        // Update count display
        const countDisplay = document.getElementById('presets-count');
        if (countDisplay) {
            countDisplay.textContent = `${this.presets.length} saved`;
        }
    }

    exportPresets() {
        if (this.presets.length === 0) {
            this.showToast('No presets to export', 'warning');
            return;
        }

        const dataStr = JSON.stringify(this.presets, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `filter-presets-${this.contentType}-${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        this.showToast('Presets exported successfully', 'success');
    }

    importPresets(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        
        reader.onload = (e) => {
            try {
                const imported = JSON.parse(e.target.result);
                
                if (!Array.isArray(imported)) {
                    throw new Error('Invalid preset format');
                }

                // Merge with existing presets (avoid duplicates by name)
                imported.forEach(preset => {
                    const exists = this.presets.find(p => p.name === preset.name);
                    if (!exists) {
                        preset.id = Date.now().toString() + Math.random();
                        this.presets.push(preset);
                    }
                });

                // Save and update UI
                if (this.savePresets()) {
                    this.renderPresetsList();
                    this.showToast(`Imported ${imported.length} presets`, 'success');
                }
            } catch (error) {
                console.error('Import error:', error);
                this.showToast('Failed to import presets', 'error');
            }
        };

        reader.readAsText(file);
        
        // Reset input
        event.target.value = '';
    }

    showToast(message, type = 'info') {
        // Use existing toast system if available
        if (window.showToast) {
            window.showToast(message, type);
            return;
        }

        // Fallback to alert
        alert(message);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Detect content type from URL
    const path = window.location.pathname;
    let contentType = null;

    if (path.includes('/admin/movies')) {
        contentType = 'movie';
    } else if (path.includes('/admin/series')) {
        contentType = 'series';
    }

    if (contentType) {
        window.filterPresets = new FilterPresets(contentType);
    }
});
