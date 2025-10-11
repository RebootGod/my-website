/* ======================================== */
/* LOGIN PAGE JAVASCRIPT */
/* ======================================== */
/* Extracted from login.blade.php for better code organization */

function loginHandler() {
    return {
        email: '',
        password: '',
        remember: false,
        showPassword: false,
        isSubmitting: false,

        get canSubmit() {
            return this.email.length > 0 &&
                   this.password.length > 0 &&
                   this.isValidEmail(this.email) &&
                   !this.isSubmitting;
        },

        init() {
            
            // Reset loading state on page unload
            window.addEventListener('beforeunload', () => {
                this.isSubmitting = false;
            });

            // Auto-focus on first empty field
            this.$nextTick(() => {
                if (!this.email) {
                    this.$refs.emailField?.focus();
                } else if (!this.password) {
                    this.$refs.passwordField?.focus();
                }
            });
        },

        togglePassword() {
            this.showPassword = !this.showPassword;
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
            if (!this.canSubmit) {
                return;
            }

            // Validate email
            if (!this.isValidEmail(this.email)) {
                alert('Format email tidak valid');
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

// Security Functions for Login Form
function sanitizeInput(input) {
    // Remove HTML tags and trim whitespace
    return input.replace(/<[^>]*>/g, '').trim();
}

function validateInputSecurity(input) {
    // Check for potentially dangerous patterns
    const dangerousPatterns = [
        /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
        /javascript:/gi,
        /on\w+\s*=/gi,
        /data:text\/html/gi,
        /<iframe\b/gi,
        /<object\b/gi
    ];

    return !dangerousPatterns.some(pattern => pattern.test(input));
}

// Initialize function to be called from blade template
function initializeLoginForm(config) {
    // Store config globally for access in Alpine component
    window.authConfig = config || {};

    
    // Auto-hide alert messages after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });

    // Security validation for login form
    const loginForm = document.querySelector('form[action*="login"]');
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');

    if (loginForm && usernameInput) {
        // Username input validation
        usernameInput.addEventListener('input', function() {
            let value = this.value;

            // Sanitize input
            const sanitized = sanitizeInput(value);

            if (sanitized !== value) {
                this.value = sanitized;
            }

            // Validate security
            if (!validateInputSecurity(this.value)) {
                this.setCustomValidity('Input mengandung karakter berbahaya');
            } else if (!/^[a-zA-Z0-9_]*$/.test(this.value)) {
                this.setCustomValidity('Username hanya boleh huruf, angka, dan underscore');
            } else if (this.value.length > 0 && this.value.length < 3) {
                this.setCustomValidity('Username minimal 3 karakter');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form submission security check
        loginForm.addEventListener('submit', function(e) {
            const username = usernameInput.value;
            const password = passwordInput.value;

            // Final security validation before submission
            if (!validateInputSecurity(username)) {
                e.preventDefault();
                alert('Username mengandung karakter berbahaya');
                return false;
            }

            if (username.length < 3 || username.length > 20) {
                e.preventDefault();
                alert('Username harus 3-20 karakter');
                return false;
            }

            if (password.length < 8 || password.length > 128) {
                e.preventDefault();
                alert('Password harus 8-128 karakter');
                return false;
            }

            // Add loading state to prevent multiple submissions
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';

            // Re-enable after 3 seconds to prevent indefinite lock
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>MASUK SEKARANG';
            }, 3000);
        });
    }
}