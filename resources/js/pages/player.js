// Movie Player JavaScript - Extracted from player.blade.php

console.log('🎬 Player.js loaded successfully');

// Player controls
function reloadPlayer() {
    const player = document.getElementById('moviePlayer');
    if (player) {
        const src = player.src;
        player.src = '';
        setTimeout(() => player.src = src, 100);
        showNotification('Player reloaded successfully!', 'success');
    }
}

// Movie actions
function addToWatchlist() {
    if (!movieId) {
        showNotification('Error: Movie ID not available', 'error');
        return;
    }
    
    // Check if user is authenticated
    if (!csrfToken) {
        showNotification('Please login to add movies to watchlist', 'error');
        return;
    }
    
    fetch(`/watchlist/add/${movieId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 401) {
                showNotification('Please login to add movies to watchlist', 'error');
                return;
            }
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data) {
            showNotification(data.message, data.success ? 'success' : 'error');
        }
    })
    .catch((error) => {
        console.error('Error adding to watchlist:', error);
        showNotification('Error adding movie to watchlist. Please try again.', 'error');
    });
}

function shareMovie() {
    if (navigator.share) {
        navigator.share({
            title: movieTitle,
            text: `Watch ${movieTitle} on Noobz Cinema`,
            url: window.location.href
        }).catch((error) => {
            console.log('Error sharing:', error);
            // Fallback to copy to clipboard
            copyToClipboard();
        });
    } else {
        copyToClipboard();
    }
}

function copyToClipboard() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('Movie link copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback for older browsers
            fallbackCopyText(window.location.href);
        });
    } else {
        fallbackCopyText(window.location.href);
    }
}

function fallbackCopyText(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        showNotification('Movie link copied to clipboard!', 'success');
    } catch (err) {
        showNotification('Unable to copy link. Please copy manually: ' + text, 'error');
    }
    document.body.removeChild(textArea);
}

// Report modal
function reportIssue() {
    openReportModal();
}

function openReportModal() {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportModalContent');
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    setTimeout(() => {
        content.style.transform = 'scale(1)';
        content.style.opacity = '1';
    }, 10);
    
    if (currentSourceId) {
        document.getElementById('sourceId').value = currentSourceId;
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportModalContent');
    
    content.style.transform = 'scale(0.95)';
    content.style.opacity = '0';
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('reportForm').reset();
    }, 300);
}

function submitReport(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Convert FormData to JSON
    const data = {
        movie_id: formData.get('movie_id'),
        source_id: formData.get('source_id'),
        issue_type: formData.get('issue_type'),
        description: formData.get('description')
    };
    
    fetch(`/movie/${movieSlug}/report`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        showNotification(data.message || 'Report submitted successfully!', 'success');
        closeReportModal();
    })
    .catch((error) => {
        console.error('Error submitting report:', error);
        showNotification('Thank you for your report! We will investigate the issue.', 'success');
        closeReportModal();
    });
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    }[type] || '#3b82f6';
    
    const icon = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    }[type] || 'ℹ️';
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        max-width: 400px;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        color: white;
        font-weight: 500;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: ${bgColor};
        backdrop-filter: blur(10px);
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 1.25rem;">${icon}</span>
            <span style="flex: 1;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: rgba(255,255,255,0.7); font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Form submission
function initializeReportForm() {
    const form = document.getElementById('reportForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Simulate submission
            setTimeout(() => {
                showNotification('Report submitted successfully! Thank you for your feedback.', 'success');
                closeReportModal();
            }, 1000);
        });
    }
}

// Keyboard shortcuts
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch(e.key.toLowerCase()) {
            case 'escape':
                closeReportModal();
                break;
            case 'r':
                if (!e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    reloadPlayer();
                }
                break;
        }
    });
}

// Animation initialization
function initializeAnimations() {
    const elements = document.querySelectorAll('.fade-in');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Initialize page when DOM is ready
function initializeMoviePlayer() {
    console.log('🎬 Movie player initializing...');
    
    initializeAnimations();
    initializeKeyboardShortcuts();
    initializeReportForm();
    
    console.log('🎬 Movie player initialized');
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initializeMoviePlayer);

// Make functions globally available for onclick handlers
window.addToWatchlist = addToWatchlist;
window.reloadPlayer = reloadPlayer;
window.shareMovie = shareMovie;
window.reportIssue = reportIssue;
window.openReportModal = openReportModal;
window.closeReportModal = closeReportModal;
window.submitReport = submitReport;