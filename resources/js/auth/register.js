/* ======================================== */
/* REGISTER PAGE JAVASCRIPT */
/* ======================================== */
/* Extracted from register.blade.php for better code organization */

function registerHandler() {
    return {
        name: '',
        email: '',
        password: '',
        passwordConfirmation: '',
        inviteCode: '',
        showPassword: false,
        showPasswordConfirmation: false,
        isSubmitting: false,
        inviteCodeValid: null,
        passwordStrength: 0,
        strengthClass: '',
        strengthText: '',

        get canSubmit() {
            return this.name.length > 0 &&
                   this.email.length > 0 &&
                   this.password.length >= 8 &&
                   this.passwordsMatch &&
                   this.passwordStrength >= 3 &&
                   this.isValidEmail(this.email) &&
                   (this.inviteCodeValid === true || this.inviteCodeValid === null) &&
                   !this.isSubmitting;
        },

        get passwordsMatch() {
            return this.password === this.passwordConfirmation && this.passwordConfirmation.length > 0;
        },

        init() {
            console.log('registerHandler initialized');

            // Reset loading state on page unload
            window.addEventListener('beforeunload', () => {
                this.isSubmitting = false;
            });

            // Auto-focus on first field
            this.$nextTick(() => {
                this.$refs.nameField?.focus();
            });
        },

        togglePassword() {
            this.showPassword = !this.showPassword;
        },

        togglePasswordConfirmation() {
            this.showPasswordConfirmation = !this.showPasswordConfirmation;
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

        checkPasswordStrength() {
            let strength = 0;
            const password = this.password;

            // Length check
            if (password.length >= 8) strength++;

            // Uppercase check
            if (/[A-Z]/.test(password)) strength++;

            // Lowercase check
            if (/[a-z]/.test(password)) strength++;

            // Number check
            if (/[0-9]/.test(password)) strength++;

            // Special character check
            if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;

            this.passwordStrength = strength;

            // Update strength display
            if (strength < 3) {
                this.strengthClass = 'strength-weak';
                this.strengthText = 'Lemah';
            } else if (strength < 5) {
                this.strengthClass = 'strength-medium';
                this.strengthText = 'Sedang';
            } else {
                this.strengthClass = 'strength-strong';
                this.strengthText = 'Kuat';
            }
        },

        async validateInviteCode() {
            if (!this.inviteCode || this.inviteCode.length < 3) {
                this.inviteCodeValid = null;
                return;
            }

            try {
                const response = await fetch(window.authConfig.inviteCodeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.authConfig.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ code: this.inviteCode })
                });

                const data = await response.json();
                this.inviteCodeValid = data.valid;

                // Update UI feedback
                const feedback = document.getElementById('inviteCodeFeedback');
                if (feedback) {
                    feedback.textContent = data.message;
                    feedback.className = `invite-feedback ${data.valid ? 'valid' : 'invalid'}`;
                }
            } catch (error) {
                console.log('Invite code validation failed:', error);
                this.inviteCodeValid = null;
            }
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

            // Validate passwords match
            if (!this.passwordsMatch) {
                alert('Password dan konfirmasi password tidak cocok');
                return;
            }

            // Validate password strength
            if (this.passwordStrength < 3) {
                alert('Password terlalu lemah. Harap gunakan kombinasi huruf besar, huruf kecil, angka, dan karakter khusus.');
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
function initializeRegisterForm(config) {
    // Store config globally for access in Alpine component
    window.authConfig = config || {};

    console.log('Register form initialized with config:', config);

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
}