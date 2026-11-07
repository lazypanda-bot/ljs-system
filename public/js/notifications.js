document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-split-view="notifications"]');
    if (!page) return;
    const toggle = page.querySelector('.notification-filter-menu-toggle');
    toggle?.addEventListener('click', () => {
        const open = page.classList.toggle('filters-open');
        toggle.setAttribute('aria-expanded', String(open));
    });
    const mobile = matchMedia('(max-width: 768px)');
    const updateMobile = () => page.classList.toggle('mobile-detail-open', mobile.matches && page.dataset.mobileSelected === 'true');
    mobile.addEventListener('change', updateMobile);
    page.addEventListener('ljs:split-updated', updateMobile);
    updateMobile();
});
