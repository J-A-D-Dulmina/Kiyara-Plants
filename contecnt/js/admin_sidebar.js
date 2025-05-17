document.addEventListener('DOMContentLoaded', function() {
    // Get the sidebar element
    const sidebar = document.querySelector('.sidebar');
    
    // Get or create the toggle button for mobile
    let toggleButton = document.querySelector('.mobile-sidebar-toggle');
    
    if (!toggleButton) {
        toggleButton = document.createElement('button');
        toggleButton.className = 'mobile-sidebar-toggle';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggleButton);
    }
    
    // Add click event to toggle button
    toggleButton.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        
        // Change the icon based on sidebar state
        if (sidebar.classList.contains('active')) {
            toggleButton.innerHTML = '<i class="fas fa-times"></i>';
        } else {
            toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggleButton = toggleButton.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggleButton && sidebar.classList.contains('active') && window.innerWidth <= 992) {
            sidebar.classList.remove('active');
            toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
    
    // Close sidebar when window is resized to desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
}); 