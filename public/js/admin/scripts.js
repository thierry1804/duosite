document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();

    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.querySelector('.admin-sidebar');
    const content = document.querySelector('.admin-content');
    const backdrop = document.getElementById('adminSidebarBackdrop');

    function isMobileNav() {
        return window.matchMedia('(max-width: 768px)').matches;
    }

    function setBackdropVisible(visible) {
        if (!backdrop) {
            return;
        }
        backdrop.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    function closeMobileSidebar() {
        if (!sidebar || !content) {
            return;
        }
        sidebar.classList.remove('active');
        document.body.classList.remove('admin-sidebar-open');
        setBackdropVisible(false);
        if (sidebarCollapse) {
            sidebarCollapse.setAttribute('aria-expanded', 'false');
        }
    }

    function openMobileSidebar() {
        if (!sidebar || !content) {
            return;
        }
        sidebar.classList.add('active');
        document.body.classList.add('admin-sidebar-open');
        setBackdropVisible(true);
        if (sidebarCollapse) {
            sidebarCollapse.setAttribute('aria-expanded', 'true');
        }
    }

    function toggleMobileSidebar() {
        if (sidebar.classList.contains('active')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    }

    function updateTooltips() {
        if (!sidebar) {
            return;
        }
        if (isMobileNav()) {
            document.querySelectorAll('.sidebar-menu li a, .bottom-menu li a').forEach(function(link) {
                link.removeAttribute('data-bs-toggle');
                link.removeAttribute('data-bs-placement');
                link.removeAttribute('title');
                const tooltip = bootstrap.Tooltip.getInstance(link);
                if (tooltip) {
                    tooltip.dispose();
                }
            });
            return;
        }
        const sidebarLinks = document.querySelectorAll('.sidebar-menu li a, .bottom-menu li a');
        if (sidebar.classList.contains('collapsed')) {
            sidebarLinks.forEach(function(link) {
                const span = link.querySelector('span');
                if (!span) {
                    return;
                }
                const text = span.textContent;
                link.setAttribute('data-bs-toggle', 'tooltip');
                link.setAttribute('data-bs-placement', 'right');
                link.setAttribute('title', text);
                new bootstrap.Tooltip(link);
            });
        } else {
            sidebarLinks.forEach(function(link) {
                link.removeAttribute('data-bs-toggle');
                link.removeAttribute('data-bs-placement');
                link.removeAttribute('title');
                const tooltip = bootstrap.Tooltip.getInstance(link);
                if (tooltip) {
                    tooltip.dispose();
                }
            });
        }
    }

    if (sidebarCollapse && sidebar && content) {
        sidebarCollapse.setAttribute('aria-controls', 'admin-sidebar');
        sidebarCollapse.setAttribute('aria-expanded', 'false');

        sidebarCollapse.addEventListener('click', function() {
            if (isMobileNav()) {
                toggleMobileSidebar();
                return;
            }
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
            const isSidebarCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('admin_sidebar_collapsed', isSidebarCollapsed);
            updateTooltips();
        });

        const savedState = localStorage.getItem('admin_sidebar_collapsed');
        if (!isMobileNav()) {
            if (savedState === 'null' || savedState === null) {
                localStorage.setItem('admin_sidebar_collapsed', 'true');
            } else if (savedState === 'false') {
                sidebar.classList.remove('collapsed');
                content.classList.remove('expanded');
            }
        }

        if (backdrop) {
            backdrop.addEventListener('click', function() {
                closeMobileSidebar();
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isMobileNav() && sidebar.classList.contains('active')) {
                closeMobileSidebar();
            }
        });

        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (!isMobileNav()) {
                    closeMobileSidebar();
                }
            }, 150);
        });
    }

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    if (sidebarCollapse && sidebar && sidebar.classList.contains('collapsed') && !isMobileNav()) {
        updateTooltips();
    }
});
