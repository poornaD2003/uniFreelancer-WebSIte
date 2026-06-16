// student.js - Client-side UI enhancements for the Student Dashboard

document.addEventListener('DOMContentLoaded', () => {
    // 1. Automatically highlight active sidebar link based on current path
    const currentPath = window.location.pathname.split('/').pop();
    const sidebarLinks = document.querySelectorAll('.sidebar nav a');

    sidebarLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath === linkPath) {
            sidebarLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        }
    });

    // 2. Auto-dismiss alert notification banners after 4 seconds
    const alerts = document.querySelectorAll('.main div[style*="background"]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => { alert.style.display = 'none'; }, 500);
        }, 4000);
    });
});
