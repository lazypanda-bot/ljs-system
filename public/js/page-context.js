document.addEventListener('DOMContentLoaded', () => {
    const paths = {
        users: ['/app/users', '/admin/users', '/admin/teams'],
        properties: ['/app/property', '/app/listings'],
        appointments: ['/app/appointments', '/app/appointment'],
    };
    const root = document.querySelector('[data-page-context]');
    const groupFor = (path) => {
        if (/^\/admin\/(users|teams)(\/|$)/.test(path) || path === '/app/users') return 'users';
        if (/^\/admin\/properties(\/|$)/.test(path) || paths.properties.includes(path)) return 'properties';
        if (/^\/(appointments|appointment-proposals)(\/|$)/.test(path) || paths.appointments.includes(path)) return 'appointments';
        return null;
    };
    const group = root?.dataset.pageContext || groupFor(location.pathname);
    const key = (name) => `ljs:page-context:${name}`;
    const read = (name) => {
        try {
            const state = JSON.parse(sessionStorage.getItem(key(name)) || '{}');
            return state && typeof state === 'object' ? state : {};
        } catch { return {}; }
    };
    const write = (name, state) => {
        try { sessionStorage.setItem(key(name), JSON.stringify(state)); } catch { /* URL state still works without storage. */ }
    };
    const safe = (value, name) => {
        if (!value?.startsWith('/') || value.startsWith('//') || !paths[name]) return null;
        const url = new URL(value, location.origin);
        return url.origin === location.origin && paths[name].includes(url.pathname) ? `${url.pathname}${url.search}${url.hash}` : null;
    };
    const cleanUrl = () => {
        const url = new URL(location.href);
        ['_return_to', 'table_only', 'list_only', '_token'].forEach((name) => url.searchParams.delete(name));
        return `${url.pathname}${url.search}${url.hash}`;
    };
    const tabOf = (button) => button?.dataset.tab || button?.dataset.adminPropertyTab || button?.dataset.listingGroupFilter;
    const currentTab = () => new URL(location.href).searchParams.get('tab')
        || tabOf(root?.querySelector('[data-tab].active, [data-admin-property-tab].active, [data-listing-group-filter].active'))
        || 'default';
    const fieldIds = ['searchUsers', 'propertySearch', 'propertyStatusFilter', 'requestSearch', 'requestStatusFilter', 'historySearch', 'historyStatusFilter', 'listingSearch', 'listingStatusFilter'];
    let restoring = false;
    const remember = () => {
        if (!group || !paths[group]?.includes(location.pathname) || restoring) return;
        const state = read(group);
        const tab = currentTab();
        const fields = {};
        fieldIds.forEach((id) => {
            const field = document.getElementById(id);
            if (field) fields[id] = field.value;
        });
        state.tabs ||= {};
        state.tabs[tab] = { url: cleanUrl(), fields, scroll: window.scrollY };
        state.lastTab = tab;
        state.url = cleanUrl();
        write(group, state);
    };
    const returnTo = (name) => {
        if (paths[name]?.includes(location.pathname)) return safe(cleanUrl(), name);
        return safe(new URL(location.href).searchParams.get('_return_to'), name) || safe(read(name).url, name);
    };
    const restoreFields = (snapshot) => {
        Object.entries(snapshot?.fields || {}).forEach(([id, value]) => {
            if (!fieldIds.includes(id)) return;
            const field = document.getElementById(id);
            if (!field) return;
            field.value = value;
            field.dispatchEvent(new Event(field.tagName === 'SELECT' ? 'change' : 'input', { bubbles: true }));
        });
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form.method.toUpperCase() === 'GET') return;
        const action = new URL(form.action, location.href);
        if (action.origin !== location.origin) return;
        const name = groupFor(action.pathname);
        if (!name) return;
        remember();
        const context = returnTo(name);
        if (!context) return;
        let input = form.querySelector('input[name="_return_to"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_return_to';
            form.append(input);
        }
        input.value = context;
    }, true);

    document.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-tab], [data-admin-property-tab], [data-listing-group-filter]');
        if (tab && root?.contains(tab) && group && !restoring) {
            remember();
            const name = tabOf(tab);
            const snapshot = read(group).tabs?.[name];
            const url = new URL(location.href);
            url.searchParams.set('tab', name);
            history.replaceState(history.state, '', `${url.pathname}${url.search}${url.hash}`);
            window.setTimeout(() => {
                restoring = true;
                restoreFields(snapshot || { fields: Object.fromEntries(fieldIds.map((id) => [id, ''])) });
                restoring = false;
                remember();
            }, 0);
        }
        const link = event.target.closest('a[href]');
        if (!link) return;
        const target = new URL(link.href, location.href);
        if (target.origin !== location.origin) return;
        if (group === 'users' && link.closest('#usersTableContent nav')) {
            target.searchParams.set('tab', currentTab());
            link.href = `${target.pathname}${target.search}${target.hash}`;
        }
        if (root?.contains(link) && link.closest('[role="tablist"]')) {
            remember();
            const targetTab = target.searchParams.get('tab');
            const previous = read(group).tabs?.[targetTab];
            const destination = safe(previous?.url, group);
            if (destination) link.href = destination;
        }
        if (group && !paths[group].includes(location.pathname) && paths[group].includes(target.pathname)) {
            const context = returnTo(group);
            if (context) link.href = context;
        }
        if (/^\/admin\/(properties|teams|users)\/(create|\d+\/edit)$/.test(target.pathname)) {
            remember();
            const name = groupFor(target.pathname);
            const context = returnTo(name);
            if (context) {
                target.searchParams.set('_return_to', context);
                link.href = `${target.pathname}${target.search}${target.hash}`;
            }
        }
    }, true);
    document.addEventListener('input', (event) => { if (fieldIds.includes(event.target.id)) remember(); });
    document.addEventListener('change', (event) => { if (fieldIds.includes(event.target.id)) remember(); });
    window.addEventListener('pagehide', remember);
    window.addEventListener('beforeunload', remember);

    // Wait until the existing page scripts have installed their tab/filter handlers.
    window.setTimeout(() => {
        if (!group) return;
        const state = read(group);
        const requestedTab = new URL(location.href).searchParams.get('tab');
        const wanted = requestedTab || (paths[group].includes(location.pathname) ? state.lastTab : null);
        const button = [...(root?.querySelectorAll('[data-tab], [data-admin-property-tab], [data-listing-group-filter]') || [])].find((item) => tabOf(item) === wanted);
        restoring = true;
        if (button) {
            button.click();
            const url = new URL(location.href);
            url.searchParams.set('tab', wanted);
            history.replaceState(history.state, '', `${url.pathname}${url.search}${url.hash}`);
        }
        const snapshot = state.tabs?.[currentTab()];
        restoreFields(snapshot);
        const context = returnTo(group);
        if (context) document.querySelectorAll('.property-edit-back, .property-create-back, .property-create-actions a').forEach((link) => { link.href = context; });
        restoring = false;
        if (snapshot?.scroll !== undefined) requestAnimationFrame(() => window.scrollTo({ top: snapshot.scroll, behavior: 'instant' }));
        remember();
    }, 0);
});
