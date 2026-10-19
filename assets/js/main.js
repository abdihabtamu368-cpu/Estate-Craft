/**
 * EstateCraft - Main Application Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile navigation drawer toggle
    const menuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');

    if (menuBtn && navLinks) {
        menuBtn.addEventListener('click', function () {
            navLinks.classList.toggle('mobile-open');
        });
    }

    // Hero Search Tab Switcher (Buy / Rent / All)
    const searchTabs = document.querySelectorAll('.search-tabs .tab-btn');
    const purposeInput = document.getElementById('searchPurposeInput');

    if (searchTabs.length > 0 && purposeInput) {
        searchTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                searchTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const purpose = this.getAttribute('data-purpose');
                purposeInput.value = purpose;
            });
        });
    }

    // Flash alert close buttons
    const alertCloses = document.querySelectorAll('.alert-close');
    alertCloses.forEach(btn => {
        btn.addEventListener('click', function () {
            const alertBox = this.closest('.alert');
            if (alertBox) {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 200);
            }
        });
    });

    // Auto-dismiss success flash alerts after 6 seconds
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 6000);
    });

    // Modal Triggers
    const modalOpenTriggers = document.querySelectorAll('[data-modal-target]');
    const modalCloseTriggers = document.querySelectorAll('[data-modal-close]');

    modalOpenTriggers.forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-modal-target');
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.add('active');
            }
        });
    });

    modalCloseTriggers.forEach(trigger => {
        trigger.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });

    // Close modal on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // Property gallery thumbnail preview switcher
    const mainGalleryImg = document.getElementById('mainGalleryImage');
    const thumbImages = document.querySelectorAll('.gallery-thumb-item');

    if (mainGalleryImg && thumbImages.length > 0) {
        thumbImages.forEach(thumb => {
            thumb.addEventListener('click', function () {
                const newSrc = this.getAttribute('data-full-src') || this.src;
                mainGalleryImg.src = newSrc;
                thumbImages.forEach(t => t.style.opacity = '0.6');
                this.style.opacity = '1';
            });
        });
    }

    // ========================================================
    // Theme Mode Controller (Day, Night, Default)
    // ========================================================
    const initialTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('site_theme') || 'default';
    updateThemeUI(initialTheme);

    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeMenu = document.getElementById('themeMenu');

    if (themeToggleBtn && themeMenu) {
        themeToggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const langMenu = document.getElementById('langMenu');
            if (langMenu) langMenu.style.display = 'none';

            themeMenu.style.display = themeMenu.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function () {
            themeMenu.style.display = 'none';
        });
    }

    document.querySelectorAll('.theme-menu-item').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const chosen = this.getAttribute('data-theme-choice');
            setThemeMode(chosen, true);
        });
    });

    // Listen for OS system theme changes when user is in 'default' mode
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
            const activeMode = document.documentElement.getAttribute('data-theme') || 'default';
            if (activeMode === 'default') {
                if (e.matches) {
                    document.body.classList.add('theme-dark');
                } else {
                    document.body.classList.remove('theme-dark');
                }
            }
        });
    }
});

/**
 * Global Theme Mode Setter
 * @param {'light'|'dark'|'default'} mode
 * @param {boolean} saveToServer
 */
function setThemeMode(mode, saveToServer = true) {
    if (!['light', 'dark', 'default'].includes(mode)) {
        mode = 'default';
    }

    document.documentElement.setAttribute('data-theme', mode);

    if (mode === 'dark') {
        document.body.classList.add('theme-dark');
    } else if (mode === 'light') {
        document.body.classList.remove('theme-dark');
    } else {
        // System Default: sync body class with system prefers-color-scheme
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.classList.add('theme-dark');
        } else {
            document.body.classList.remove('theme-dark');
        }
    }

    // Local Storage persistence
    try {
        localStorage.setItem('site_theme', mode);
    } catch (e) {}

    // Cookie persistence (1 year)
    document.cookie = "site_theme=" + encodeURIComponent(mode) + ";path=/;max-age=" + (365 * 24 * 3600) + ";SameSite=Lax";

    // Update UI elements
    updateThemeUI(mode);

    // Hide dropdown menu
    const themeMenu = document.getElementById('themeMenu');
    if (themeMenu) {
        themeMenu.style.display = 'none';
    }

    // Persist to user record via API
    if (saveToServer) {
        const baseUrl = window.APP_BASE_URL || '';
        fetch(baseUrl + '/api/theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ theme: mode })
        }).catch(err => {
            console.warn('Theme preference sync:', err);
        });
    }
}

/**
 * Updates UI labels, active checks, and card states across the page
 * @param {'light'|'dark'|'default'} theme
 */
function updateThemeUI(theme) {
    const iconEl = document.getElementById('themeHeaderIcon');
    const labelEl = document.getElementById('themeHeaderLabel');

    let icon = '🖥️';
    let label = 'Default';

    if (theme === 'light') {
        icon = '☀️';
        label = 'Day';
    } else if (theme === 'dark') {
        icon = '🌙';
        label = 'Night';
    } else {
        icon = '🖥️';
        label = 'Default';
    }

    if (iconEl) iconEl.textContent = icon;
    if (labelEl) labelEl.textContent = label;

    // Update checkmarks in dropdown
    document.querySelectorAll('[data-theme-check]').forEach(el => {
        el.style.display = (el.getAttribute('data-theme-check') === theme) ? 'inline-block' : 'none';
    });

    // Update radio inputs and choice cards in Settings if present
    document.querySelectorAll('.theme-radio-input').forEach(input => {
        input.checked = (input.value === theme);
    });

    document.querySelectorAll('.theme-choice-card').forEach(card => {
        if (card.getAttribute('data-theme-card') === theme) {
            card.classList.add('active');
            const check = card.querySelector('.theme-card-selected');
            if (check) check.style.display = 'block';
        } else {
            card.classList.remove('active');
            const check = card.querySelector('.theme-card-selected');
            if (check) check.style.display = 'none';
        }
    });
}

