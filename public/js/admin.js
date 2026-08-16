// ADMN PROPERTY
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('addPropertyModal');
    const openBtn = document.getElementById('openAddModalBtn');
    const closeBtn = document.getElementById('closeAddModalBtn');
    const cancelBtn = document.getElementById('cancelModalBtn');

    if (openBtn && modal) {
        openBtn.addEventListener('click', () => { modal.style.display = 'flex'; });
    }
    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => { modal.style.display = 'none'; });
    }
    if (cancelBtn && modal) {
        cancelBtn.addEventListener('click', () => { modal.style.display = 'none'; });
    }
    window.addEventListener('click', (e) => { 
        if (e.target === modal) { modal.style.display = 'none'; }
    });
});