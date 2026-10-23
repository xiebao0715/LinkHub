(function () {
    'use strict';

    const ADMIN_THEME_KEY = 'link_manager_admin_theme';

    const html = document.documentElement;
    const themeToggle = document.getElementById('adminThemeToggle');
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const serverTheme = html.dataset.theme || 'light';
    const serverRev = html.dataset.themeRev || '';

    function saveTheme(theme) {
        localStorage.setItem(ADMIN_THEME_KEY, JSON.stringify({ theme, rev: serverRev }));
    }

    function loadTheme() {
        try {
            const stored = JSON.parse(localStorage.getItem(ADMIN_THEME_KEY) || 'null');
            if (stored && stored.rev === serverRev && stored.theme) {
                html.dataset.theme = stored.theme;
                return stored.theme;
            }
        } catch (e) { /* ignore */ }
        html.dataset.theme = serverTheme;
        saveTheme(serverTheme);
        return serverTheme;
    }

    function getTheme() {
        return html.dataset.theme || serverTheme;
    }

    function setTheme(theme) {
        html.dataset.theme = theme;
        saveTheme(theme);
    }

    loadTheme();

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            setTheme(getTheme() === 'light' ? 'dark' : 'light');
        });
    }

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                e.target !== menuToggle) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Modal
    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.modal.is-open')) {
            document.body.classList.remove('modal-open');
        }
    }

    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-modal-open');
            if (id) openModal(id);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(el => {
        el.addEventListener('click', () => {
            closeModal(el.closest('.modal'));
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.is-open').forEach(closeModal);
        }
    });

    document.querySelectorAll('[data-fill-form]').forEach(btn => {
        btn.addEventListener('click', () => {
            const formId = btn.getAttribute('data-fill-form');
            const form = document.getElementById(formId);
            if (!form) return;
            form.querySelectorAll('[data-field]').forEach(input => {
                const field = input.getAttribute('data-field');
                const val = btn.getAttribute('data-' + field);
                if (val !== null) {
                    if (input.tagName === 'SELECT' || input.tagName === 'INPUT' || input.tagName === 'TEXTAREA') {
                        input.value = val;
                    }
                }
            });
            const actionInput = form.querySelector('[name="action"]');
            if (actionInput && btn.hasAttribute('data-action')) {
                actionInput.value = btn.getAttribute('data-action');
            }
            const modalId = btn.getAttribute('data-modal-open');
            if (modalId) openModal(modalId);
        });
    });

    window.openAdminModal = openModal;
    window.closeAdminModal = closeModal;

    const autoModal = document.body.getAttribute('data-auto-modal');
    if (autoModal) {
        openModal(autoModal);
    }
})();
