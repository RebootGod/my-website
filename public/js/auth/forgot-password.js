/* ======================================== */
/* FORGOT PASSWORD PAGE JAVASCRIPT */
/* ======================================== */
/* Extracted from forgot-password.blade.php for better code organization */

function forgotPasswordHandler() {
    return {
        email: '',
        isSubmitting: false,
        canSubmit: true,
        rateLimitData: {},

        init() {
            
            if (this.email) {
                this.checkRateLimit();
            }

            // Reset loading state on page unload
            window.addEventListener('beforeunload', () => {
                this.isSubmitting = false;
            });
        },

        async checkRateLimit() {
            if (!this.email || !this.isValidEmail(this.email)) {
                return;
            }

            try {
                const response = await fetch(window.authConfig.rateLimitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.authConfig.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ email: this.email })
                });

                if (response.ok) {
                    this.rateLimitData = await response.json();
                    this.updateRateLimitDisplay();
                }
            } catch (error) {
                            }
        },

        updateRateLimitDisplay() {
            const warning = document.getElementById('rateLimitWarning');
            const message = document.getElementById('rateLimitMessage');

            if (!this.rateLimitData.can_attempt) {
                warning.style.display = 'block';
                message.textContent = `Batas percobaan tercapai. Coba lagi dalam ${this.rateLimitData.reset_in_minutes} menit.`;
                this.canSubmit = false;
            } else if (this.rateLimitData.email_attempts_remaining <= 2) {
                warning.style.display = 'block';
                message.textContent = `Sisa percobaan: ${this.rateLimitData.email_attempts_remaining}`;
                this.canSubmit = true;
            } else {
                warning.style.display = 'none';
                this.canSubmit = true;
            }
        },

        validateEmail() {
            const emailField = document.querySelector('input[name="email"]');
            if (this.email && !this.isValidEmail(this.email)) {
                emailField.setCustomValidity('Format email tidak valid');
            } else {
                emailField.setCustomValidity('');
            }
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        handleSubmit(event) {
            if (!this.canSubmit || this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;

            // Submit the form after setting loading state
            setTimeout(() => {
                event.target.submit();
            }, 100);
        }
    }
}

// Initialize function to be called from blade template
function initializeForgotPasswordForm(config) {
    // Store config globally for access in Alpine component
    window.authConfig = config;

    // Set initial email value in Alpine component
    if (config.email) {
        // Wait for Alpine to initialize
        document.addEventListener('alpine:init', () => {
            const component = Alpine.evaluate(document.getElementById('forgotPasswordForm'), 'this');
            if (component) {
                component.email = config.email;
            }
        });
    }

    // Auto-hide success message after 10 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 300);
        }, 10000);
    }
}