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

    const propertyTabs = document.querySelectorAll('[data-admin-property-tab]');
    const propertyPanels = document.querySelectorAll('[data-admin-property-panel]');
    propertyTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            propertyTabs.forEach((item) => {
                const active = item === tab;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            propertyPanels.forEach((panel) => {
                panel.hidden = panel.dataset.adminPropertyPanel !== tab.dataset.adminPropertyTab;
                panel.classList.toggle('active', !panel.hidden);
            });
        });
    });

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