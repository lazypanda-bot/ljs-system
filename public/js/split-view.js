(() => {
    const initialize = () => {
        const root = document.querySelector('[data-split-view]');
        if (!root || root.dataset.splitInitialized) return;
        root.dataset.splitInitialized = 'true';

        const layouts = {
            appointments: { list: '.appointments-master-list', item: '.appointment-master-item', detail: '.appointment-detail-panel', key: 'selected' },
            notifications: { list: '.notifications-list', item: '.notification-list-item', detail: '.notification-detail-panel', key: 'selected' },
            messages: { list: '.thread-list', item: '.thread-item', detail: '.chat-view', key: 'conversation' },
        };
        const config = layouts[root.dataset.splitView];
        if (!config) return;
        const status = document.createElement('p');
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        status.hidden = true;
        root.prepend(status);
        let activeRequest;

        const itemId = (link) => new URL(link.href, location.href).searchParams.get(config.key);
        const updateHighlight = (incoming) => {
            const incomingItems = new Map([...incoming.querySelectorAll(config.item)].map((item) => [itemId(item), item]));
            root.querySelectorAll(config.item).forEach((item) => {
                const updated = incomingItems.get(itemId(item));
                const selected = incoming.dataset.splitSelected !== undefined
                    ? itemId(item) === incoming.dataset.splitSelected
                    : updated?.getAttribute('aria-current') === 'true';
                item.classList.toggle('active', selected);
                if (selected) item.setAttribute('aria-current', 'true');
                else item.removeAttribute('aria-current');
                item.closest('.thread-entry')?.classList.toggle('active', selected);
                if (root.dataset.splitView === 'notifications' && (updated || selected)) {
                    item.classList.toggle('read', updated ? updated.classList.contains('read') : selected);
                    item.classList.toggle('unread', updated ? updated.classList.contains('unread') : false);
                }
            });
        };

        const load = async (url, { history = true, replaceList = false } = {}) => {
            activeRequest?.abort();
            const request = new AbortController();
            activeRequest = request;
            const detail = root.querySelector(config.detail);
            detail?.setAttribute('aria-busy', 'true');
            status.textContent = '';
            status.hidden = true;
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin', cache: 'no-store', signal: request.signal,
                });
                if (!response.ok) throw new Error('Unable to load details. Please try again.');
                const html = await response.text();
                if (activeRequest !== request) return;
                const document = new DOMParser().parseFromString(html, 'text/html');
                const incoming = document.querySelector(`[data-split-view="${root.dataset.splitView}"]`);
                const nextDetail = incoming?.querySelector(config.detail);
                if (!incoming || !nextDetail) throw new Error('Unable to load details. Please refresh to check your session.');

                root.dispatchEvent(new CustomEvent('ljs:split-before-update', { bubbles: true }));
                updateHighlight(incoming);
                if (replaceList && root.dataset.splitView !== 'messages') {
                    const list = root.querySelector(config.list);
                    const scrollTop = list.scrollTop;
                    list.replaceChildren(...incoming.querySelector(config.list).childNodes);
                    list.scrollTop = scrollTop;
                }
                detail.replaceWith(nextDetail);
                root.dataset.splitSelected = incoming.dataset.splitSelected ?? '';
                root.classList.toggle('has-selected-thread', incoming.classList.contains('has-selected-thread'));
                if (incoming.dataset.mobileSelected !== undefined) {
                    root.dataset.mobileSelected = incoming.dataset.mobileSelected;
                    root.classList.toggle('mobile-detail-open', matchMedia('(max-width: 768px)').matches && root.dataset.mobileSelected === 'true');
                }
                if (history) window.history.pushState({ splitView: root.dataset.splitView }, '', url);
                status.hidden = true;
                window.lucide?.createIcons();
                root.dispatchEvent(new CustomEvent('ljs:split-updated', { bubbles: true, detail: { panel: nextDetail } }));
            } catch (error) {
                if (activeRequest !== request || error.name === 'AbortError') return;
                status.textContent = error.message || 'Unable to load details. Please try again.';
                status.hidden = false;
            } finally {
                if (activeRequest === request) {
                    activeRequest = null;
                    root.querySelector(config.detail)?.removeAttribute('aria-busy');
                }
            }
        };

        root.addEventListener('click', (event) => {
            if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            const link = event.target.closest('a[href]');
            if (!link || link.target || link.hasAttribute('download')) return;
            const item = link.matches(config.item);
            const pagination = link.closest(config.list) && link.closest('nav[role="navigation"]');
            const back = link.matches('.thread-back, [data-close-notification-detail]');
            if (!item && !pagination && !back) return;
            const url = new URL(link.href, location.href);
            if (url.origin !== location.origin) return;
            event.preventDefault();
            load(`${url.pathname}${url.search}${url.hash}`, { replaceList: Boolean(pagination) });
        });
        window.addEventListener('popstate', () => load(`${location.pathname}${location.search}${location.hash}`, { history: false, replaceList: true }));
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
