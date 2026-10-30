/**
 * App.js - Shared Dashboard Interactions
 * Provides: sidebar toggle, toasts, form helpers, global error handling.
 */

(function () {
    'use strict';

    /* ─── Sidebar Toggle ─── */
    function initSidebar() {
        var menuBtn = document.querySelector('.menu-btn');
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.querySelector('.menu-overlay');

        if (!menuBtn || !sidebar || !overlay) return;

        function openSidebar() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        menuBtn.addEventListener('click', function () {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        overlay.addEventListener('click', closeSidebar);

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });

        // Close on resize to desktop
        var mql = window.matchMedia('(min-width: 993px)');
        mql.addEventListener('change', function (e) {
            if (e.matches) closeSidebar();
        });
    }

    /* ─── Toast Notifications ─── */
    function createToastContainer() {
        var container = document.getElementById('agri-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'agri-toast-container';
            container.className = 'toast-container';
            container.setAttribute('role', 'status');
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
        return container;
    }

    window.showToast = function (message, type, duration) {
        type = type || 'info';
        duration = duration || 4000;

        var container = createToastContainer();
        var toast = document.createElement('div');
        toast.className = 'agri-toast toast-' + type;

        var icons = {
            success: 'fa-circle-check',
            error: 'fa-circle-exclamation',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info'
        };

        toast.innerHTML = '<i class="fa-solid ' + (icons[type] || icons.info) + '"></i> ' + message;
        container.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('toast-exit');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, duration);
    };

    /* ─── Flash Messages → Toasts ─── */
    function flashToToasts() {
        var alerts = document.querySelectorAll('.alert-success, .alert-danger, .alert-warning, .alert-info');
        alerts.forEach(function (alert) {
            var type = 'info';
            if (alert.classList.contains('alert-success')) type = 'success';
            else if (alert.classList.contains('alert-danger')) type = 'error';
            else if (alert.classList.contains('alert-warning')) type = 'warning';

            var text = alert.textContent.trim();
            if (text) {
                window.showToast(text, type, 5000);
            }
            // Fade out the original alert
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(function () {
                alert.style.display = 'none';
            }, 300);
        });
    }

    /* ─── Confirm Dialogs ─── */
    function initConfirmDialogs() {
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                var msg = el.getAttribute('data-confirm') || 'Are you sure?';
                if (!confirm(msg)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            });
        });
    }

    /* ─── Password Toggles ─── */
    function initPasswordToggles() {
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                if (!targetId) return;
                var input = document.getElementById(targetId);
                if (!input) return;
                var isPass = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPass ? 'text' : 'password');
                btn.classList.toggle('is-on', isPass);
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye', !isPass);
                    icon.classList.toggle('fa-eye-slash', isPass);
                }
            });
        });
    }

    /* ─── Reveal on Load ─── */
    function initReveal() {
        document.querySelectorAll('.reveal').forEach(function (el) {
            el.classList.add('is-visible');
        });
    }

    /* ─── Client-Side Form Validation ─── */
    function initFormValidation() {
        document.querySelectorAll('form[data-validate]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var valid = true;
                form.querySelectorAll('[required]').forEach(function (input) {
                    input.classList.remove('is-invalid');
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        valid = false;
                    }
                    if (input.type === 'email' && input.value) {
                        var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRe.test(input.value)) {
                            input.classList.add('is-invalid');
                            valid = false;
                        }
                    }
                });
                if (!valid) {
                    e.preventDefault();
                    window.showToast('Please fill in all required fields.', 'error');
                }
            });
        });
    }

    /* ─── Global Error Handler ─── */
    window.addEventListener('error', function (e) {
        console.error('Global error:', e.message);
    });

    window.addEventListener('unhandledrejection', function (e) {
        console.error('Unhandled promise rejection:', e.reason);
    });

    /* ─── Initialize ─── */
    function init() {
        initSidebar();
        initPasswordToggles();
        initReveal();
        initConfirmDialogs();
        initFormValidation();
        // Delay flash messages to allow page render
        setTimeout(flashToToasts, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
