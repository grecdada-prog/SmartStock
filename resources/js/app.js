import './bootstrap';
import './notifications';

// Auto-dismiss notifications après 5 secondes
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('[data-auto-dismiss]');
    
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.transition = 'opacity 0.5s ease-out';
            notification.style.opacity = '0';
            
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 5000); // 5 secondes
    });
});