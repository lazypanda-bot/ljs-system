/* Shared date + time picker for appointment booking and time proposals; unavailable dates and times are greyed out and disabled. */
window.LjsAvailability = {
    init({ dateInput, timeSelect, utcField, status, timezone, urlFor, ready = () => true, idleText = 'Choose a date first', readyText = 'Times are shown in your local time.' }) {
        const pad = (n) => String(n).padStart(2, '0');
        const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        const todayIso = () => new Date().toLocaleDateString('en-CA', { timeZone: timezone() });
        const [ty, tm, td] = todayIso().split('-').map(Number);
        const today = new Date(ty, tm - 1, td);
        const maxDate = new Date(ty, tm - 1, td + 44);
        let unavailable = new Set();
        let datesLoaded = false;
        let view = new Date(ty, tm - 1, 1);

        dateInput.type = 'text';
        dateInput.readOnly = true;
        dateInput.placeholder = 'Select a date';
        dateInput.autocomplete = 'off';
        const host = dateInput.closest('label') || dateInput.parentElement;
        host.style.position = 'relative';

        const pop = document.createElement('div');
        pop.className = 'availability-calendar';
        pop.hidden = true;
        host.appendChild(pop);
        pop.addEventListener('click', (event) => event.preventDefault());

        const isClosed = (d) => d < today || d > maxDate || unavailable.has(iso(d));

        const render = () => {
            const monthLabel = view.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const first = new Date(view.getFullYear(), view.getMonth(), 1);
            const days = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
            let html = `<div class="availability-calendar-head"><button type="button" data-nav="-1" aria-label="Previous month">&lsaquo;</button><strong>${monthLabel}</strong><button type="button" data-nav="1" aria-label="Next month">&rsaquo;</button></div><div class="availability-calendar-grid">`;
            ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].forEach((d) => { html += `<span class="availability-calendar-dow">${d}</span>`; });
            for (let i = 0; i < first.getDay(); i++) html += '<span></span>';
            for (let day = 1; day <= days; day++) {
                const d = new Date(view.getFullYear(), view.getMonth(), day);
                const closed = isClosed(d);
                const selected = dateInput.value === iso(d);
                html += `<button type="button" class="availability-day${closed ? ' is-unavailable' : ''}${selected ? ' is-selected' : ''}" data-date="${iso(d)}" ${closed ? 'disabled aria-disabled="true"' : ''}>${day}</button>`;
            }
            pop.innerHTML = html + '</div>';
        };

        const loadSlots = async () => {
            utcField.value = '';
            if (!dateInput.value || !ready()) {
                timeSelect.replaceChildren(new Option(idleText, ''));
                timeSelect.disabled = true;
                return;
            }
            timeSelect.replaceChildren(new Option('Loading times…', ''));
            timeSelect.disabled = true;
            try {
                const url = urlFor({ date: dateInput.value, withDates: false });
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Could not load available times.');
                const slots = data.all_slots || data.slots.map((s) => ({ ...s, available: true }));
                const open = slots.filter((s) => s.available).length;
                timeSelect.replaceChildren(new Option(open ? 'Select an available time' : 'No times available', ''));
                slots.forEach((slot) => {
                    const option = new Option(slot.available ? slot.label : `${slot.label} (unavailable)`, slot.local_time);
                    option.dataset.slotUtc = slot.value;
                    option.disabled = !slot.available;
                    timeSelect.add(option);
                });
                timeSelect.disabled = open === 0;
                status.textContent = open ? readyText : 'No available times for this date. Choose another date.';
            } catch (error) {
                timeSelect.replaceChildren(new Option('No times available', ''));
                status.textContent = error.message;
            }
        };

        const loadDates = async () => {
            if (!ready()) return;
            try {
                const url = urlFor({ date: todayIso(), withDates: true });
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (response.ok && Array.isArray(data.unavailable_dates)) {
                    unavailable = new Set(data.unavailable_dates);
                    datesLoaded = true;
                    if (dateInput.value && unavailable.has(dateInput.value)) {
                        dateInput.value = '';
                        loadSlots();
                    }
                    if (!pop.hidden) render();
                }
            } catch { /* calendar still blocks past dates; the server rejects bad slots */ }
        };

        const open = () => {
            if (!datesLoaded) loadDates();
            render();
            pop.hidden = false;
        };

        dateInput.addEventListener('click', open);
        dateInput.addEventListener('focus', open);
        dateInput.addEventListener('keydown', (event) => { if (event.key === 'Escape') pop.hidden = true; });
        pop.addEventListener('click', (event) => {
            const nav = event.target.closest('[data-nav]');
            if (nav) {
                view = new Date(view.getFullYear(), view.getMonth() + Number(nav.dataset.nav), 1);
                render();
                return;
            }
            const day = event.target.closest('.availability-day');
            if (day && !day.disabled) {
                dateInput.value = day.dataset.date;
                pop.hidden = true;
                loadSlots();
            }
        });
        document.addEventListener('click', (event) => {
            if (!host.contains(event.target)) pop.hidden = true;
        });

        timeSelect.addEventListener('change', () => {
            utcField.value = timeSelect.selectedOptions[0]?.dataset.slotUtc || '';
        });

        return { reload: () => { datesLoaded = false; unavailable = new Set(); loadSlots(); }, loadSlots };
    },
};
