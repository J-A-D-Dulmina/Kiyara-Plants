document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    setupModals();
    
    // Setup alert dismissal
    setupAlertDismissal();
});

function setupModals() {
    // Get all elements that open modals
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    
    // Add click event to each trigger
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Get the target modal ID
            const modalId = trigger.getAttribute('data-target');
            const modal = document.querySelector(modalId);
            
            if (modal) {
                openModal(modal);
            }
        });
    });
    
    // Setup close buttons
    const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
    closeButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const modal = button.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modal when clicking outside content
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target);
        }
    });
    
    // Close modal on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
}

function openModal(modal) {
    // Add fade-in animation class
    modal.classList.add('fade');
    
    // Show the modal
    modal.style.display = 'block';
    
    // Add show class after a small delay (for animation)
    setTimeout(function() {
        modal.classList.add('show');
    }, 10);
    
    // Prevent body scrolling
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    // Remove show class first (for animation)
    modal.classList.remove('show');
    
    // Hide the modal after animation
    setTimeout(function() {
        modal.style.display = 'none';
        modal.classList.remove('fade');
    }, 300);
    
    // Re-enable body scrolling
    document.body.style.overflow = '';
}

function setupAlertDismissal() {
    // Get all close buttons in alerts
    const alertCloseButtons = document.querySelectorAll('.alert .btn-close');
    
    // Add click event to each button
    alertCloseButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const alert = button.closest('.alert');
            
            // Add fade out effect
            alert.style.opacity = '0';
            
            // Remove alert after animation
            setTimeout(function() {
                alert.style.display = 'none';
            }, 300);
        });
    });
} 