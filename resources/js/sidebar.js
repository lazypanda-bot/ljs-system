document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('[data-sidebar]');

    if (!sidebarToggle || !sidebar) {
        return;
    }

    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
    });
});
