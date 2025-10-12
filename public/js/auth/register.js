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

            // Uppercase check - update checkmark
            const hasUppercase = /[A-Z]/.test(password);
            if (hasUppercase) strength++;
            this.updateCheckmark('check-uppercase', hasUppercase);

            // Lowercase check - update checkmark
            const hasLowercase = /[a-z]/.test(password);
            if (hasLowercase) strength++;
            this.updateCheckmark('check-lowercase', hasLowercase);

            // Number check - update checkmark
            const hasNumber = /[0-9]/.test(password);
            if (hasNumber) strength++;
            this.updateCheckmark('check-number', hasNumber);

            // Special character check - update checkmark
            const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
            if (hasSpecialChar) strength++;
            this.updateCheckmark('check-special', hasSpecialChar);

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

        updateCheckmark(elementId, isValid) {
            const element = document.getElementById(elementId);
            if (element) {
                element.style.color = isValid ? '#10b981' : '#6c757d';
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
                    body: JSON.stringify({ invite_code: this.inviteCode })
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

    // Add password strength checker
    const passwordInput = document.getElementById('register_password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordRequirements(this.value);
        });
    }

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

// Check password requirements and update checkmarks
function checkPasswordRequirements(password) {
    // Uppercase check
    updateCheckmark('check-uppercase', /[A-Z]/.test(password));
    
    // Lowercase check
    updateCheckmark('check-lowercase', /[a-z]/.test(password));
    
    // Number check
    updateCheckmark('check-number', /[0-9]/.test(password));
    
    // Special character check
    updateCheckmark('check-special', /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password));
}

// Update checkmark color
function updateCheckmark(elementId, isValid) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.color = isValid ? '#10b981' : '#6c757d';
    }
}