document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.report-trend-bars [data-bar-size]').forEach((bar) => {
        const size = Number(bar.dataset.barSize);
        if (Number.isFinite(size)) bar.style.setProperty('--bar-size', `${Math.min(100, Math.max(3, size))}%`);
    });
});
