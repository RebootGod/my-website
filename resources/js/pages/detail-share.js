/* ======================================== */
/* DETAIL PAGE SHARE FUNCTIONALITY */
/* ======================================== */
/* File: resources/js/pages/detail-share.js */
/* Purpose: Share functionality for movie/series detail pages */

document.addEventListener('DOMContentLoaded', function() {
    initializeShareFunctionality();
});

function initializeShareFunctionality() {
    const shareButtons = document.querySelectorAll('[data-share-btn]');
    
    shareButtons.forEach(button => {
        button.addEventListener('click', handleShare);
    });
}

async function handleShare(event) {
    event.preventDefault();
    
    const button = event.currentTarget;
    const title = button.dataset.shareTitle || document.title;
    const url = button.dataset.shareUrl || window.location.href;
    const text = button.dataset.shareText || `Check out: ${title}`;
    
    // Check if Web Share API is available (mobile devices)
    if (navigator.share) {
        try {
            await navigator.share({
                title: title,
                text: text,
                url: url
            });
            
            showNotification('Shared successfully!', 'success');
            
            // Haptic feedback if available
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
        } catch (error) {
            // User cancelled or error occurred
            if (error.name !== 'AbortError') {
                console.error('Share failed:', error);
                fallbackShare(url, title);
            }
        }
    } else {
        // Fallback for desktop: Copy to clipboard
        fallbackShare(url, title);
    }
}

function fallbackShare(url, title) {
    // Try to copy to clipboard
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url)
            .then(() => {
                showNotification('Link copied to clipboard!', 'success');
            })
            .catch(err => {
                console.error('Failed to copy:', err);
                showShareModal(url, title);
            });
    } else {
        // Last fallback: Show modal with share options
        showShareModal(url, title);
    }
}

function showShareModal(url, title) {
    // Create modal if not exists
    let modal = document.getElementById('shareModal');
    
    if (!modal) {
        modal = createShareModal();
        document.body.appendChild(modal);
    }
    
    // Update modal content
    const urlInput = modal.querySelector('#shareUrlInput');
    if (urlInput) {
        urlInput.value = url;
    }
    
    // Show modal
    modal.classList.add('active');
    
    // Close on overlay click
    const overlay = modal.querySelector('.share-modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            modal.classList.remove('active');
        });
    }
    
    // Close button
    const closeBtn = modal.querySelector('.share-modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.remove('active');
        });
    }
    
    // Copy button
    const copyBtn = modal.querySelector('#copyUrlBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            copyToClipboard(url);
            modal.classList.remove('active');
        });
    }
    
    // Social media buttons
    setupSocialShare(modal, url, title);
}

function createShareModal() {
    const modal = document.createElement('div');
    modal.id = 'shareModal';
    modal.className = 'share-modal';
    modal.innerHTML = `
        <div class="share-modal-overlay"></div>
        <div class="share-modal-content">
            <div class="share-modal-header">
                <h3><i class="fas fa-share-alt me-2"></i>Share</h3>
                <button class="share-modal-close" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="share-modal-body">
                <div class="share-url-group">
                    <input type="text" 
                           id="shareUrlInput" 
                           readonly 
                           class="share-url-input"
                           placeholder="URL will appear here">
                    <button id="copyUrlBtn" class="share-copy-btn">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <div class="share-social">
                    <h4>Share on social media</h4>
                    <div class="share-social-buttons">
                        <a href="#" data-social="whatsapp" class="share-social-btn whatsapp">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                        <a href="#" data-social="facebook" class="share-social-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="#" data-social="twitter" class="share-social-btn twitter">
                            <i class="fab fa-twitter"></i>
                            <span>Twitter</span>
                        </a>
                        <a href="#" data-social="telegram" class="share-social-btn telegram">
                            <i class="fab fa-telegram-plane"></i>
                            <span>Telegram</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return modal;
}

function setupSocialShare(modal, url, title) {
    const socialButtons = modal.querySelectorAll('[data-social]');
    const encodedUrl = encodeURIComponent(url);
    const encodedTitle = encodeURIComponent(title);
    
    socialButtons.forEach(button => {
        const platform = button.dataset.social;
        let shareUrl = '';
        
        switch(platform) {
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`;
                break;
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
                break;
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`;
                break;
        }
        
        button.href = shareUrl;
        button.target = '_blank';
        button.rel = 'noopener noreferrer';
        
        button.addEventListener('click', () => {
            modal.classList.remove('active');
        });
    });
}

function copyToClipboard(text) {
    // Modern approach
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text)
            .then(() => {
                showNotification('Link copied to clipboard!', 'success');
            })
            .catch(err => {
                console.error('Copy failed:', err);
                fallbackCopyToClipboard(text);
            });
    } else {
        fallbackCopyToClipboard(text);
    }
}

function fallbackCopyToClipboard(text) {
    // Fallback for older browsers
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showNotification('Link copied!', 'success');
    } catch (err) {
        console.error('Fallback copy failed:', err);
        showNotification('Failed to copy link', 'error');
    }
    
    document.body.removeChild(textarea);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `detail-notification detail-notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.add('active');
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('active');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Export for global access
window.handleShare = handleShare;
window.showShareModal = showShareModal;
