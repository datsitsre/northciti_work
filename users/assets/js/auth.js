// users/assets/js/auth.js - Secure Authentication

'use strict';

class Auth {
    constructor() {
        this.initializeAuthForms();
        this.initializePasswordToggles();
        this.initializePasswordStrength();
    }
    
    initializeAuthForms() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', this.handleLogin.bind(this));
        }
        
        // Register form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', this.handleRegister.bind(this));
        }
        
        // Forgot password form
        const forgotForm = document.getElementById('forgotPasswordForm');
        if (forgotForm) {
            forgotForm.addEventListener('submit', this.handleForgotPassword.bind(this));
        }
    }
    
    initializePasswordToggles() {
        const toggleButtons = document.querySelectorAll('[id^="toggle"][id$="Password"]');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.id.replace('toggle', '').replace('Password', 'Password');
                const targetInput = document.getElementById(targetId.charAt(0).toLowerCase() + targetId.slice(1));
                
                if (targetInput) {
                    const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    targetInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
            });
        });
    }
    
    initializePasswordStrength() {
        const passwordInput = document.getElementById('registerPassword');
        if (passwordInput) {
            passwordInput.addEventListener('input', this.checkPasswordStrength.bind(this));
        }
        
        const confirmInput = document.getElementById('confirmPassword');
        if (confirmInput) {
            confirmInput.addEventListener('input', this.checkPasswordMatch.bind(this));
        }
    }
    
    async handleLogin(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitBtn = form.querySelector('#loginSubmitBtn');
        const spinner = submitBtn.querySelector('.spinner-border');
        const buttonText = submitBtn.querySelector('span');
        
        // Show loading state
        this.setLoadingState(submitBtn, spinner, buttonText, true);
        
        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            // Security: Validate input
            if (!this.validateEmail(data.email)) {
                throw new Error('Please enter a valid email address');
            }
            
            if (!data.password || data.password.length < 6) {
                throw new Error('Password must be at least 6 characters long');
            }
            
            const response = await window.api.post('/auth/login', data);
            
            if (response.success) {
                // Store user data securely
                window.SecureApp.SecureStorage.setItem('userSession', {
                    user: response.user,
                    timestamp: Date.now()
                });
                
                // Redirect to dashboard or intended page
                const redirect = new URLSearchParams(window.location.search).get('redirect');
                window.location.href = redirect || '/user/dashboard';
            } else {
                throw new Error(response.message || 'Login failed');
            }
            
        } catch (error) {
            this.showError('loginError', 'loginErrorMessage', error.message);
            window.SecureApp.logSecurityEvent('login_failed', { error: error.message });
        } finally {
            this.setLoadingState(submitBtn, spinner, buttonText, false);
        }
    }
    
    async handleRegister(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitBtn = form.querySelector('#registerSubmitBtn');
        const spinner = submitBtn.querySelector('.spinner-border');
        const buttonText = submitBtn.querySelector('span');
        
        // Show loading state
        this.setLoadingState(submitBtn, spinner, buttonText, true);
        
        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            // Security: Validate all fields
            this.validateRegistrationData(data);
            
            const response = await window.api.post('/auth/register', data);
            
            if (response.success) {
                this.showSuccess('registerSuccess', 'registerSuccessMessage', 
                    'Account created successfully! Please check your email to verify your account.');
                
                // Clear form
                form.reset();
                
                // Switch to login tab after delay
                setTimeout(() => {
                    document.getElementById('login-tab').click();
                }, 3000);
            } else {
                throw new Error(response.message || 'Registration failed');
            }
            
        } catch (error) {
            this.showError('registerError', 'registerErrorMessage', error.message);
            window.SecureApp.logSecurityEvent('registration_failed', { error: error.message });
        } finally {
            this.setLoadingState(submitBtn, spinner, buttonText, false);
        }
    }
    
    async handleForgotPassword(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitBtn = form.querySelector('#forgotPasswordSubmitBtn');
        const spinner = submitBtn.querySelector('.spinner-border');
        const buttonText = submitBtn.querySelector('span');
        
        this.setLoadingState(submitBtn, spinner, buttonText, true);
        
        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            if (!this.validateEmail(data.email)) {
                throw new Error('Please enter a valid email address');
            }
            
            const response = await window.api.post('/auth/forgot-password', data);
            
            if (response.success) {
                this.showSuccess('forgotSuccess', 'forgotSuccessMessage',
                    'Password reset link has been sent to your email address.');
                form.reset();
            } else {
                throw new Error(response.message || 'Failed to send reset link');
            }
            
        } catch (error) {
            this.showError('forgotError', 'forgotErrorMessage', error.message);
        } finally {
            this.setLoadingState(submitBtn, spinner, buttonText, false);
        }
    }
    
    validateRegistrationData(data) {
        const errors = [];
        
        // Name validation
        if (!data.first_name || data.first_name.length < 2) {
            errors.push('First name must be at least 2 characters long');
        }
        
        if (!data.last_name || data.last_name.length < 2) {
            errors.push('Last name must be at least 2 characters long');
        }
        
        // Username validation
        if (!data.username || !/^[a-zA-Z0-9_]{3,30}$/.test(data.username)) {
            errors.push('Username must be 3-30 characters with letters, numbers, and underscores only');
        }
        
        // Email validation
        if (!this.validateEmail(data.email)) {
            errors.push('Please enter a valid email address');
        }
        
        // Password validation
        if (!this.validatePassword(data.password)) {
            errors.push('Password must be at least 8 characters with uppercase, lowercase, number, and special character');
        }
        
        // Password confirmation
        if (data.password !== data.confirm_password) {
            errors.push('Passwords do not match');
        }
        
        // Terms agreement
        if (!data.agree_terms) {
            errors.push('You must agree to the Terms of Service and Privacy Policy');
        }
        
        if (errors.length > 0) {
            throw new Error(errors.join('; '));
        }
    }
    
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    validatePassword(password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        return passwordRegex.test(password);
    }
    
    checkPasswordStrength(event) {
        const password = event.target.value;
        const strengthBar = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('passwordStrengthText');
        
        if (!strengthBar || !strengthText) return;
        
        let strength = 0;
        let feedback = [];
        
        // Length check
        if (password.length >= 8) {
            strength += 20;
        } else {
            feedback.push('At least 8 characters');
        }
        
        // Uppercase check
        if (/[A-Z]/.test(password)) {
            strength += 20;
        } else {
            feedback.push('One uppercase letter');
        }
        
        // Lowercase check
        if (/[a-z]/.test(password)) {
            strength += 20;
        } else {
            feedback.push('One lowercase letter');
        }
        
        // Number check
        if (/\d/.test(password)) {
            strength += 20;
        } else {
            feedback.push('One number');
        }
        
        // Special character check
        if (/[@$!%*?&]/.test(password)) {
            strength += 20;
        } else {
            feedback.push('One special character');
        }
        
        // Update progress bar
        strengthBar.style.width = strength + '%';
        strengthBar.setAttribute('aria-valuenow', strength);
        
        // Update colors and text
        if (strength < 40) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Weak - Need: ' + feedback.join(', ');
            strengthText.className = 'text-danger';
        } else if (strength < 80) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Medium - Need: ' + feedback.join(', ');
            strengthText.className = 'text-warning';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Strong password!';
            strengthText.className = 'text-success';
        }
    }
    
    checkPasswordMatch() {
        const password = document.getElementById('registerPassword');
        const confirm = document.getElementById('confirmPassword');
        const error = document.getElementById('confirmPasswordError');
        
        if (!password || !confirm || !error) return;
        
        if (confirm.value && password.value !== confirm.value) {
            confirm.setCustomValidity('Passwords do not match');
            error.textContent = 'Passwords do not match';
            confirm.classList.add('is-invalid');
        } else {
            confirm.setCustomValidity('');
            error.textContent = '';
            confirm.classList.remove('is-invalid');
        }
    }
    
    setLoadingState(button, spinner, text, loading) {
        if (loading) {
            button.disabled = true;
            spinner.classList.remove('d-none');
            text.textContent = 'Processing...';
        } else {
            button.disabled = false;
            spinner.classList.add('d-none');
            text.textContent = button.id.includes('login') ? 'Login Securely' : 
                               button.id.includes('register') ? 'Create Secure Account' : 
                               'Send Reset Link';
        }
    }
    
    showError(containerId, messageId, message) {
        const container = document.getElementById(containerId);
        const messageEl = document.getElementById(messageId);
        
        if (container && messageEl) {
            messageEl.textContent = message;
            container.classList.remove('d-none');
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                container.classList.add('d-none');
            }, 10000);
        }
    }
    
    showSuccess(containerId, messageId, message) {
        const container = document.getElementById(containerId);
        const messageEl = document.getElementById(messageId);
        
        if (container && messageEl) {
            messageEl.textContent = message;
            container.classList.remove('d-none');
        }
    }
}

// Initialize authentication when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new Auth();
});

// Handle auth modal tab switching
document.addEventListener('DOMContentLoaded', function() {
    const authModal = document.getElementById('authModal');
    if (authModal) {
        authModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tab = button.getAttribute('data-tab');
            
            if (tab === 'register') {
                document.getElementById('register-tab').click();
            } else {
                document.getElementById('login-tab').click();
            }
        });
    }
});