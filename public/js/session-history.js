(() => {
    const page = document.documentElement;
    const originalVisibility = page.style.visibility;

    // Keep private content hidden in snapshots restored by browser Back/Forward.
    window.addEventListener('pagehide', () => {
        page.style.visibility = 'hidden';
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
            return;
        }
        page.style.visibility = originalVisibility;
    });
})();
