// ADMN PROPERTY
document.addEventListener('DOMContentLoaded', function () {
    const propertySearch = document.getElementById('propertySearch');
    const propertyStatusFilter = document.getElementById('propertyStatusFilter');
    const propertyCards = document.querySelectorAll('[data-property-card]');

    function filterProperties() {
        const search = (propertySearch?.value || '').trim().toLowerCase();
        const status = propertyStatusFilter?.value || '';

        propertyCards.forEach((card) => {
            const matchesSearch = !search || card.dataset.propertyName.includes(search);
            const matchesStatus = !status || card.dataset.propertyStatus === status;
            card.hidden = !(matchesSearch && matchesStatus);
        });
    }

    propertySearch?.addEventListener('input', filterProperties);
    propertyStatusFilter?.addEventListener('change', filterProperties);

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