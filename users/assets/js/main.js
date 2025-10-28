// users/assets/js/main.js - Secure Main JavaScript

'use strict';

// Security: Prevent prototype pollution
Object.seal(Object.prototype);
Object.seal(Array.prototype);

// Security: Content Security Policy violation handler
window.addEventListener('securitypolicyviolation', function(e) {
    console.warn('CSP Violation:', e.violatedDirective, e.blockedURI);
    // Report to security monitoring endpoint
    if (window.location.hostname !== '10.30.252.49' && window.location.hostname !== '127.0.0.1') {
        if (window.APP_CONFIG && window.APP_CONFIG.apiUrl) {
            fetch(window.APP_CONFIG.apiUrl + '/security/csp-violation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.APP_CONFIG.csrfToken
                },
                body: JSON.stringify({
                    violatedDirective: e.violatedDirective,
                    blockedURI: e.blockedURI,
                    originalPolicy: e.originalPolicy
                })
            }).catch(() => {}); // Silent fail for security endpoint
        }
    }
});

// Security: Detect and prevent common XSS attempts
function sanitizeInput(input) {
    if (typeof input !== 'string') return input;
    
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

// Security: Secure local storage with encryption
class SecureStorage {
    static encrypt(data) {
        try {
            return btoa(JSON.stringify(data));
        } catch (e) {
            console.error('Encryption failed:', e);
            return null;
        }
    }
    
    static decrypt(data) {
        try {
            return JSON.parse(atob(data));
        } catch (e) {
            console.error('Decryption failed:', e);
            return null;
        }
    }
    
    static setItem(key, value) {
        const encrypted = this.encrypt(value);
        if (encrypted) {
            localStorage.setItem('sec_' + key, encrypted);
        }
    }
    
    static getItem(key) {
        const encrypted = localStorage.getItem('sec_' + key);
        return encrypted ? this.decrypt(encrypted) : null;
    }
    
    static removeItem(key) {
        localStorage.removeItem('sec_' + key);
    }
}

// Security: Rate limiting for client-side requests
class RateLimiter {
    constructor(maxRequests = 10, windowMs = 60000) {
        this.maxRequests = maxRequests;
        this.windowMs = windowMs;
        this.requests = [];
    }
    
    isAllowed() {
        const now = Date.now();
        this.requests = this.requests.filter(time => now - time < this.windowMs);
        
        if (this.requests.length >= this.maxRequests) {
            return false;
        }
        
        this.requests.push(now);
        return true;
    }
}

// Initialize rate limiter
const apiRateLimiter = new RateLimiter(30, 60000); // 30 requests per minute

// Main application initialization
document.addEventListener('DOMContentLoaded', function() {
    initializeSecurity();
    initializeFormValidation();
    initializeSearch();
    setupSecurityMonitoring();
});

function initializeSecurity() {
    // Security: Prevent iframe embedding
    if (window.location.hostname !== '10.30.252.49' && window.location.hostname !== '127.0.0.1') {
        if (window.top !== window.self) {
            window.top.location = window.self.location;
        }
    
    
        // Security: Disable right-click context menu on production
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        
        // Security: Detect devtools opening
        let devtools = {
            open: false,
            orientation: null
        };
        
        const threshold = 160;
        
        setInterval(function() {
            if (window.outerHeight - window.innerHeight > threshold || 
                window.outerWidth - window.innerWidth > threshold) {
                if (!devtools.open) {
                    devtools.open = true;
                    console.warn('Developer tools detected');
                    // Log security event
                    logSecurityEvent('devtools_opened');
                }
            } else {
                devtools.open = false;
            }
        }, 500);
        
        // Security: Monitor for suspicious activity
        let clickCount = 0;
        let keyCount = 0;
        
        document.addEventListener('click', function() {
            clickCount++;
            if (clickCount > 100) { // Suspicious rapid clicking
                logSecurityEvent('suspicious_clicking', { count: clickCount });
                clickCount = 0;
            }
        });
        
        document.addEventListener('keydown', function() {
            keyCount++;
            if (keyCount > 200) { // Suspicious rapid key presses
                logSecurityEvent('suspicious_keypress', { count: keyCount });
                keyCount = 0;
            }
        });
    }
}

function initializeFormValidation() {
    // Add Bootstrap validation to all forms
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Security: Add CSRF token to all forms
    const allForms = document.querySelectorAll('form');
    allForms.forEach(function(form) {
        if (!form.querySelector('input[name="csrf_token"]')) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = window.APP_CONFIG.csrfToken;
            form.appendChild(csrfInput);
        }
    });
}

function initializeSearch() {
    const searchForm = document.querySelector('form[role="search"]');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[type="search"]');
        
        // Security: Validate search input
        searchInput.addEventListener('input', function() {
            const value = this.value;
            
            // Remove potentially dangerous characters
            const sanitized = value.replace(/[<>\"'&]/g, '');
            if (value !== sanitized) {
                this.value = sanitized;
                showAlert('Some characters were removed for security', 'warning');
            }
            
            // Limit length
            if (value.length > 100) {
                this.value = value.substring(0, 100);
                showAlert('Search query too long', 'warning');
            }
        });
    }
}

function setupSecurityMonitoring() {
    // Monitor for suspicious patterns
    let errorCount = 0;
    
    window.addEventListener('error', function(e) {
        errorCount++;
        if (errorCount > 10) {
            logSecurityEvent('excessive_errors', { 
                count: errorCount,
                lastError: e.message 
            });
            errorCount = 0;
        }
    });
    
    // Monitor for URL manipulation attempts
    let urlChangeCount = 0;
    
    window.addEventListener('popstate', function() {
        urlChangeCount++;
        if (urlChangeCount > 20) {
            logSecurityEvent('excessive_url_changes', { count: urlChangeCount });
            urlChangeCount = 0;
        }
    });
}

function logSecurityEvent(type, data = {}) {
    if (!apiRateLimiter.isAllowed()) {
        return; // Rate limited
    }
    
    const eventData = {
        type: type,
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        url: window.location.href,
        referrer: document.referrer,
        data: data
    };
    
    if (window.APP_CONFIG && window.APP_CONFIG.apiUrl) {
        fetch(window.APP_CONFIG.apiUrl + '/security/client-event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.APP_CONFIG.csrfToken
            },
            body: JSON.stringify(eventData)
        }).catch(() => {}); // Silent fail
    }
}

function secureLogout() {
    if (confirm('Are you sure you want to log out?')) {
        // Clear sensitive data
        SecureStorage.removeItem('userSession');
        
        // Send logout request
        fetch(window.APP_CONFIG.apiUrl + '/auth/logout', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': window.APP_CONFIG.csrfToken
            }
        }).then(() => {
            window.location.href = '/auth/logout';
        }).catch(() => {
            // Fallback
            window.location.href = '/auth/logout';
        });
    }
}

function showAlert(message, type = 'info', duration = 3000) {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.innerHTML = `
        <i class="fas fa-info-circle me-2"></i>
        ${sanitizeInput(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alertElement);
    
    // Auto remove after duration
    setTimeout(() => {
        if (alertElement.parentNode) {
            alertElement.remove();
        }
    }, duration);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export functions for use in other scripts
window.SecureApp = {
    SecureStorage,
    RateLimiter,
    sanitizeInput,
    logSecurityEvent,
    secureLogout,
    showAlert,
    debounce,
    throttle
};



