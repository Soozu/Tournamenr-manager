document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const topBar = document.querySelector('.top-bar');

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        topBar.classList.toggle('expanded');
    });

    // Responsive sidebar
    const mediaQuery = window.matchMedia('(max-width: 992px)');
    
    function handleResponsiveSidebar(e) {
        if (e.matches) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            topBar.classList.remove('expanded');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            });
        }
    }
    
    mediaQuery.addListener(handleResponsiveSidebar);
    handleResponsiveSidebar(mediaQuery);

    // Set active nav link based on current page
    const currentPage = window.location.pathname.split('/').pop().split('.')[0];
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href').split('/').pop().split('.')[0];
        if (href === currentPage) {
            link.classList.add('active');
        }
    });

    // Update page title icon
    const pageTitle = document.querySelector('.page-title');
    if (pageTitle) {
        const activeLink = document.querySelector('.nav-link.active');
        if (activeLink) {
            const icon = activeLink.querySelector('i').cloneNode(true);
            pageTitle.insertBefore(icon, pageTitle.firstChild);
        }
    }
}); 