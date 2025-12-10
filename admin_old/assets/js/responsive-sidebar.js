// Sidebar Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar
    sidebarToggle.addEventListener('click', function() {
        body.classList.toggle('sidebar-active');
    });

    // Close sidebar when clicking the close button
    sidebarClose.addEventListener('click', function() {
        body.classList.remove('sidebar-active');
    });

    // Close sidebar when clicking the overlay
    sidebarOverlay.addEventListener('click', function() {
        body.classList.remove('sidebar-active');
    });

    // Double tap toggle button for icon-only mode
    let lastTap = 0;
    sidebarToggle.addEventListener('click', function(event) {
        const currentTime = new Date().getTime();
        const tapLength = currentTime - lastTap;
        if (tapLength < 300 && tapLength > 0) {
            body.classList.toggle('sidebar-icon-only');
            event.preventDefault();
        }
        lastTap = currentTime;
    });

    // Close sidebar on window resize if it's open (mobile)
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            body.classList.remove('sidebar-active');
        }
    });
});