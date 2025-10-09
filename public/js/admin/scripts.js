document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Toggle sidebar
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.querySelector('.admin-sidebar');
    const content = document.querySelector('.admin-content');
    
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
            
            // Save sidebar state to localStorage
            const isSidebarCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('admin_sidebar_collapsed', isSidebarCollapsed);
        });
        
        // Check localStorage for saved sidebar state
        const savedState = localStorage.getItem('admin_sidebar_collapsed');
        if (savedState === 'null' || savedState === null) {
            // Set default state to collapsed
            localStorage.setItem('admin_sidebar_collapsed', 'true');
        } else if (savedState === 'false') {
            // If user has explicitly expanded the sidebar
            sidebar.classList.remove('collapsed');
            content.classList.remove('expanded');
        }
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add tooltips to sidebar items when collapsed
    function updateTooltips() {
        const sidebarLinks = document.querySelectorAll('.sidebar-menu li a, .bottom-menu li a');
        if (sidebar.classList.contains('collapsed')) {
            sidebarLinks.forEach(link => {
                const text = link.querySelector('span').textContent;
                link.setAttribute('data-bs-toggle', 'tooltip');
                link.setAttribute('data-bs-placement', 'right');
                link.setAttribute('title', text);
                new bootstrap.Tooltip(link);
            });
        } else {
            sidebarLinks.forEach(link => {
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
    
    // Update tooltips on sidebar toggle
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', updateTooltips);
        
        // Initialize tooltips based on saved state
        if (sidebar.classList.contains('collapsed')) {
            updateTooltips();
        }
    }
}); 