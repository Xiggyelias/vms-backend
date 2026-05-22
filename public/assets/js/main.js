// Main JavaScript file for Vehicle Registration System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and other interactive elements
    initializeTooltips();
    initializeFormValidation();
    initializeAjaxRequests();
    initializeNotifications();
});

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            showTooltip(this, this.getAttribute('data-tooltip'));
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

// Show tooltip
function showTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: #1f2937;
        color: white;
        padding: 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        z-index: 1000;
        pointer-events: none;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.top  = (rect.top  + window.scrollY - tooltip.offsetHeight - 8) + 'px';
    tooltip.style.left = (rect.left + window.scrollX + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
}

// Hide tooltip
function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Initialize form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    });
}

// Validate form
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Validate individual field
function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Check if required field is empty
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address';
    }
    
    // Password validation — must match backend minimum of 12 characters
    if (field.type === 'password' && value && value.length < 12) {
        isValid = false;
        errorMessage = 'Password must be at least 12 characters long';
    }
    
    // Show or remove error message
    showFieldError(field, isValid, errorMessage);
    
    return isValid;
}

// Show field error
function showFieldError(field, isValid, errorMessage) {
    // Ensure the field has a stable ID so we can link it to its error message
    if (!field.id) {
        field.id = 'field-' + Math.random().toString(36).slice(2, 9);
    }
    const errorId = field.id + '-error';
    let errorElement = document.getElementById(errorId);

    if (!isValid) {
        field.classList.add('border-red-500');
        field.classList.remove('border-gray-300');
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', errorId);

        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = errorId;
            errorElement.className = 'error-message text-red-500 text-sm mt-1';
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = errorMessage;
    } else {
        field.classList.remove('border-red-500');
        field.classList.add('border-gray-300');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');

        if (errorElement) {
            errorElement.remove();
        }
    }
}

// Check if email is valid
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Initialize AJAX requests
function initializeAjaxRequests() {
    // Add CSRF token to all AJAX requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            if (args[1] && args[1].method && ['POST', 'PUT', 'DELETE'].includes(args[1].method.toUpperCase())) {
                args[1].headers = {
                    ...args[1].headers,
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                };
            }
            return originalFetch.apply(this, args);
        };
    }
    
    // Handle AJAX form submissions
    const ajaxForms = document.querySelectorAll('form[data-ajax]');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAjaxForm(form);
        });
    });
}

// Submit form via AJAX
function submitAjaxForm(form) {
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    
    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner"></span> Loading...';
    
    fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Success!', 'success');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
    });
}

// Initialize notifications
function initializeNotifications() {
    // Create the persistent aria-live region once so screen readers announce every toast
    if (!document.getElementById('notification-region')) {
        const region = document.createElement('div');
        region.id = 'notification-region';
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-atomic', 'false');
        region.className = 'fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none';
        document.body.appendChild(region);
    }

    // Auto-hide server-rendered success messages after 5 seconds
    const successMessages = document.querySelectorAll('.alert-success');
    successMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 5000);
    });
}

// Show notification — injects toasts into the persistent aria-live region
function showNotification(message, type = 'info') {
    // Lazily create the region if showNotification is called before DOMContentLoaded
    let region = document.getElementById('notification-region');
    if (!region) {
        region = document.createElement('div');
        region.id = 'notification-region';
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-atomic', 'false');
        region.className = 'fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none';
        document.body.appendChild(region);
    }

    const notification = document.createElement('div');
    notification.className = `alert alert-${type} max-w-sm fade-in pointer-events-auto`;
    notification.textContent = message;

    region.appendChild(notification);

    // Auto-hide after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);

    // Allow manual close
    notification.addEventListener('click', function() {
        this.style.opacity = '0';
        setTimeout(() => {
            this.remove();
        }, 300);
    });
}

// Utility functions
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed?');
}

function formatCurrency(amount, currency, locale) {
    // Defaults read from a <meta> tag so the server can inject the campus locale.
    // <meta name="app-currency" content="USD"> <meta name="app-locale" content="en-ZW">
    const appCurrency = currency
        || document.querySelector('meta[name="app-currency"]')?.getAttribute('content')
        || 'USD';
    const appLocale = locale
        || document.querySelector('meta[name="app-locale"]')?.getAttribute('content')
        || navigator.language
        || 'en';

    return new Intl.NumberFormat(appLocale, {
        style: 'currency',
        currency: appCurrency,
    }).format(amount);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(new Date(date));
}

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

// Handle responsive navigation
function toggleMobileMenu() {
    const mobileMenu = document.querySelector('.mobile-menu');
    if (mobileMenu) {
        mobileMenu.classList.toggle('hidden');
    }
}

// Smooth scroll to element
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copied to clipboard!', 'success');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Copied to clipboard!', 'success');
    }
}

// Export functions for global use
window.showNotification = showNotification;
window.confirmAction = confirmAction;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.debounce = debounce;
window.toggleMobileMenu = toggleMobileMenu;
window.scrollToElement = scrollToElement;
window.copyToClipboard = copyToClipboard;
