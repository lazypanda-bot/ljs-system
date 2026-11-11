document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.report-trend-bars [data-bar-size]').forEach((bar) => {
        const size = Number(bar.dataset.barSize);
        if (Number.isFinite(size)) bar.style.setProperty('--bar-size', `${Math.min(100, Math.max(3, size))}%`);
    });

    const performanceContent = document.querySelector('[data-report-performance-content]');
    if (!performanceContent) return;

    let activeRequest;

    const loadPerformancePage = async (url, { addHistory = false } = {}) => {
        activeRequest?.abort();
        const request = new AbortController();
        activeRequest = request;
        performanceContent.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Report-Fragment': 'performance',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: request.signal,
            });
            if (!response.ok) throw new Error(`Performance pagination failed (${response.status}).`);

            const result = await response.json();
            if (typeof result.html !== 'string') throw new Error('Performance pagination returned an invalid response.');

            performanceContent.innerHTML = result.html;
            if (addHistory) history.pushState({}, '', url);

            const pagination = performanceContent.querySelector('.ljs-pagination');
            if (pagination) {
                pagination.tabIndex = -1;
                pagination.focus({ preventScroll: true });
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            window.location.assign(url);
        } finally {
            if (activeRequest === request) {
                activeRequest = null;
                performanceContent.removeAttribute('aria-busy');
            }
        }
    };

    performanceContent.addEventListener('click', (event) => {
        const link = event.target.closest('.ljs-pagination a[href]');
        if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;

        event.preventDefault();
        loadPerformancePage(url.href, { addHistory: true });
    });

    window.addEventListener('popstate', () => {
        loadPerformancePage(window.location.href);
    });
});
