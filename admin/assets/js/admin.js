
// admin/assets/js/login.js - Enhanced Login JavaScript

// Decode htmlspecialchars
function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}

function getParamFromUrl(param) {
    let dirUrl = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (let i = 0; i < dirUrl.length; i++) {
        let urlParam = dirUrl[i].split('=');
        if (urlParam[0] == param) {
            return urlParam[1];
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    const loginBtnSpinner = document.getElementById('loginBtnSpinner');

    // Password visibility toggle
    if (togglePassword && passwordInput && eyeIcon) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            if (type === 'text') {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    }

    // Form validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function showError(input, message) {
        const errorElement = document.getElementById(input.name + '-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
        input.classList.add('border-red-500');
        input.classList.remove('border-gray-300');
    }

    function clearError(input) {
        const errorElement = document.getElementById(input.name + '-error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
        input.classList.remove('border-red-500');
        input.classList.add('border-gray-300');
    }

    function validateForm() {
        let isValid = true;

        // Clear previous errors
        clearError(emailInput);
        clearError(passwordInput);

        // Validate email
        const email = emailInput.value.trim();
        if (!email) {
            showError(emailInput, 'Email is required');
            isValid = false;
        } else if (!validateEmail(email)) {
            showError(emailInput, 'Please enter a valid email address');
            isValid = false;
        }

        // Validate password
        const password = passwordInput.value.trim();
        if (!password) {
            showError(passwordInput, 'Password is required');
            isValid = false;
        } else if (password.length < 6) {
            showError(passwordInput, 'Password must be at least 6 characters');
            isValid = false;
        }

        return isValid;
    }

    // Real-time validation
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email && !validateEmail(email)) {
            showError(this, 'Please enter a valid email address');
        } else {
            clearError(this);
        }
    });

    emailInput.addEventListener('input', function() {
        clearError(this);
    });

    passwordInput.addEventListener('input', function() {
        clearError(this);
    });

    // Form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        // Show loading state
        loginBtn.disabled = true;
        loginBtnText.classList.add('hidden');
        loginBtnSpinner.classList.remove('hidden');

        // Simulate API call (in real implementation, this would be an actual API call)
        setTimeout(() => {
            // Submit the form
            this.submit();
        }, 1000);
    });

    // Auto-focus on email field
    emailInput.focus();

    // Handle Enter key in password field
    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
});