/**
 * MANTIK — Sidebar Toggle Logic
 * Shared across all layout roles.
 *
 * Features:
 *  - Toggle expanded / collapsed
 *  - Persist state in localStorage
 *  - Bootstrap tooltips in collapsed mode
 *  - Mobile hamburger overlay
 */
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('mantikSidebar');
    const body = document.body;
    const toggleBtn = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const STORAGE_KEY = 'mantik_sidebar_collapsed';

    if (!sidebar) return;

    // ── Restore persisted state (desktop only) ──
    if (window.innerWidth >= 992 && localStorage.getItem(STORAGE_KEY) === '1') {
        sidebar.classList.add('collapsed');
        body.classList.add('sidebar-collapsed');
        enableTooltips();
    }

    // ── Desktop toggle ──
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isCollapsing = !sidebar.classList.contains('collapsed');

            sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed');

            localStorage.setItem(STORAGE_KEY, isCollapsing ? '1' : '0');

            if (isCollapsing) {
                enableTooltips();
            } else {
                disableTooltips();
            }
        });
    }

    // ── Mobile menu ──
    if (mobileBtn) {
        mobileBtn.addEventListener('click', function () {
            sidebar.classList.add('mobile-open');
            if (backdrop) backdrop.classList.add('show');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('mobile-open');
            backdrop.classList.remove('show');
        });
    }

    // ── Tooltip helpers ──
    function enableTooltips() {
        sidebar.querySelectorAll('.nav-link[title]').forEach(function (el) {
            el.setAttribute('data-bs-toggle', 'tooltip');
            el.setAttribute('data-bs-placement', 'right');
            var tooltip = new bootstrap.Tooltip(el, { trigger: 'hover' });
        });
    }

    function disableTooltips() {
        sidebar.querySelectorAll('.nav-link[title]').forEach(function (el) {
            var tooltipInstance = bootstrap.Tooltip.getInstance(el);
            if (tooltipInstance) tooltipInstance.dispose();
            el.removeAttribute('data-bs-toggle');
        });
    }
});
