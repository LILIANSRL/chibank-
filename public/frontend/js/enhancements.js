/**
 * Dark Mode Toggle Functionality
 * Provides enterprise-grade dark mode support with smooth transitions
 */

(function() {
    'use strict';

    // Dark Mode Manager
    const DarkModeManager = {
        // Configuration
        config: {
            storageKey: 'qrpay-theme-preference',
            darkModeClass: 'dark',
            themeAttribute: 'data-theme',
        },

        // Initialize dark mode
        init: function() {
            this.createToggleButton();
            this.loadThemePreference();
            this.attachEventListeners();
        },

        // Create dark mode toggle button
        createToggleButton: function() {
            const button = document.createElement('button');
            button.className = 'dark-mode-toggle';
            button.setAttribute('aria-label', 'Toggle dark mode');
            button.setAttribute('title', 'Toggle dark mode');
            button.innerHTML = '<i class="fas fa-moon"></i>';
            document.body.appendChild(button);
        },

        // Load theme preference from localStorage
        loadThemePreference: function() {
            const savedTheme = localStorage.getItem(this.config.storageKey);
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                this.enableDarkMode();
            } else {
                this.disableDarkMode();
            }
        },

        // Enable dark mode
        enableDarkMode: function() {
            document.documentElement.setAttribute(this.config.themeAttribute, 'dark');
            document.body.classList.add(this.config.darkModeClass);
            this.updateToggleIcon('sun');
            localStorage.setItem(this.config.storageKey, 'dark');
        },

        // Disable dark mode
        disableDarkMode: function() {
            document.documentElement.setAttribute(this.config.themeAttribute, 'light');
            document.body.classList.remove(this.config.darkModeClass);
            this.updateToggleIcon('moon');
            localStorage.setItem(this.config.storageKey, 'light');
        },

        // Toggle dark mode
        toggleDarkMode: function() {
            const isDark = document.documentElement.getAttribute(this.config.themeAttribute) === 'dark';
            if (isDark) {
                this.disableDarkMode();
            } else {
                this.enableDarkMode();
            }
        },

        // Update toggle button icon
        updateToggleIcon: function(icon) {
            const button = document.querySelector('.dark-mode-toggle');
            if (button) {
                button.innerHTML = icon === 'sun' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            }
        },

        // Attach event listeners
        attachEventListeners: function() {
            const self = this;
            
            // Toggle button click
            document.addEventListener('click', function(e) {
                if (e.target.closest('.dark-mode-toggle')) {
                    self.toggleDarkMode();
                }
            });

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                const savedTheme = localStorage.getItem(self.config.storageKey);
                if (!savedTheme) {
                    if (e.matches) {
                        self.enableDarkMode();
                    } else {
                        self.disableDarkMode();
                    }
                }
            });
        }
    };

    // Toast Notification System
    const ToastManager = {
        // Show toast notification
        show: function(message, type = 'info', duration = 3000) {
            const toast = this.createToast(message, type);
            document.body.appendChild(toast);

            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);

            // Auto-dismiss
            setTimeout(() => {
                this.dismiss(toast);
            }, duration);

            return toast;
        },

        // Create toast element
        createToast: function(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            
            const iconMap = {
                success: 'check-circle',
                error: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };

            toast.innerHTML = `
                <div class="toast-content" style="display: flex; align-items: center; gap: 12px; padding: 16px;">
                    <i class="fas fa-${iconMap[type] || 'info-circle'}" style="font-size: 20px;"></i>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 4px; text-transform: capitalize;">${type}</strong>
                        <span>${message}</span>
                    </div>
                    <button class="toast-close" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; margin-left: 8px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            // Close button
            toast.querySelector('.toast-close').addEventListener('click', () => {
                this.dismiss(toast);
            });

            return toast;
        },

        // Dismiss toast
        dismiss: function(toast) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    };

    // Enhanced Form Validation
    const FormValidator = {
        // Initialize form validation
        init: function() {
            const forms = document.querySelectorAll('form[data-validate]');
            forms.forEach(form => {
                this.attachValidation(form);
            });
        },

        // Attach validation to form
        attachValidation: function(form) {
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', () => this.clearError(input));
            });

            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        },

        // Validate single field
        validateField: function(field) {
            const value = field.value.trim();
            const type = field.type;
            let isValid = true;
            let message = '';

            // Required validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                message = 'This field is required';
            }

            // Email validation
            if (type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    message = 'Please enter a valid email address';
                }
            }

            // Min length validation
            if (field.hasAttribute('minlength') && value.length < parseInt(field.getAttribute('minlength'))) {
                isValid = false;
                message = `Minimum length is ${field.getAttribute('minlength')} characters`;
            }

            if (!isValid) {
                this.showError(field, message);
            } else {
                this.clearError(field);
            }

            return isValid;
        },

        // Validate entire form
        validateForm: function(form) {
            const inputs = form.querySelectorAll('input, textarea, select');
            let isValid = true;

            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });

            return isValid;
        },

        // Show field error
        showError: function(field, message) {
            field.classList.add('is-invalid');
            
            let errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                field.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        },

        // Clear field error
        clearError: function(field) {
            field.classList.remove('is-invalid');
            const errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
        }
    };

    // Smooth Scroll Enhancement
    const SmoothScroll = {
        init: function() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href === '#') return;
                    
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
    };

    // Loading State Manager
    const LoadingManager = {
        // Show loading overlay
        show: function(message = 'Loading...') {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(5px);
            `;
            overlay.innerHTML = `
                <div style="text-align: center; color: white;">
                    <div class="spinner-border" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p style="margin-top: 1rem; font-size: 1.125rem;">${message}</p>
                </div>
            `;
            document.body.appendChild(overlay);
        },

        // Hide loading overlay
        hide: function() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    if (overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                }, 300);
            }
        }
    };

    // Initialize all modules when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            DarkModeManager.init();
            FormValidator.init();
            SmoothScroll.init();
        });
    } else {
        DarkModeManager.init();
        FormValidator.init();
        SmoothScroll.init();
    }

    // Export to window for global access
    window.QRPayEnhancements = {
        DarkMode: DarkModeManager,
        Toast: ToastManager,
        FormValidator: FormValidator,
        Loading: LoadingManager
    };

})();
