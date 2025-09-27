/* ======================================== */
/* RESET PASSWORD PAGE JAVASCRIPT */
/* ======================================== */
/* Extracted from reset-password.blade.php for better code organization */

function resetPasswordHandler() {
    return {
        password: '',
        passwordConfirmation: '',
        showPassword: false,
        showPasswordConfirmation: false,
        isSubmitting: false,
        passwordStrength: 0,
        strengthClass: '',
        strengthText: '',

        get canSubmit() {
            return this.password.length >= 8 &&
                   this.passwordsMatch &&
                   this.passwordStrength >= 3 &&
                   !this.isSubmitting;
        },

        get passwordsMatch() {
            return this.password === this.passwordConfirmation && this.passwordConfirmation.length > 0;
        },

        init() {
            console.log('resetPasswordHandler initialized');

            // Reset loading state on page unload
            window.addEventListener('beforeunload', () => {
                this.isSubmitting = false;
            });
        },

        togglePassword() {
            this.showPassword = !this.showPassword;
        },

        togglePasswordConfirmation() {
            this.showPasswordConfirmation = !this.showPasswordConfirmation;
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

        checkPasswordMatch() {
            // This method is called on input for password confirmation
            // The computed property passwordsMatch handles the logic
        },

        handleSubmit(event) {
            if (!this.canSubmit) {
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
function initializeResetPasswordForm(config) {
    // Store config globally for access in Alpine component
    window.authConfig = config;

    console.log('Reset password form initialized with config:', config);
}