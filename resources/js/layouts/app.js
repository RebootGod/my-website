// Layout App JavaScript - Extracted from app.blade.php

// Global variables
let csrfToken = '';

// Initialize app layout with data from view
function initializeAppLayout(data) {
    csrfToken = data.csrfToken;
    initNavbar();
}

// Navbar scroll effect for glassmorphism
function initNavbar() {
    const navbar = document.getElementById('mainNavbar');
    if (!navbar) return;
    
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        // Add/remove scrolled class for glassmorphism
        if (currentScroll > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
}

// Global watchlist function
function addToWatchlist(movieId) {
    if (!csrfToken) {
        console.error('CSRF token not available');
        showNotification('Security token missing. Please refresh the page.', 'error');
        return;
    }
    
    fetch(`/watchlist/add/${movieId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button
            if (event && event.target) {
                const button = event.target;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.remove('btn-outline-light');
                button.classList.add('btn-success');
                button.onclick = null;
                button.title = 'In Watchlist';
            }
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Notification function using Bootstrap Toast
function showNotification(message, type = 'info') {
    // Check if Bootstrap is available
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded');
        // Fallback to simple alert
        alert(message);
        return;
    }
    
    const toastHtml = `
        <div class="toast position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
            <div class="toast-header">
                <strong class="me-auto">Noobz Cinema</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} text-white">
                ${message}
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.querySelector('.toast:last-child');
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Auto remove toast element after it hides
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}