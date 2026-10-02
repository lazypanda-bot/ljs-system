document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-proposal-availability-url]').forEach((form) => {
        const date = form.querySelector('[name="preferred_date"]');
        const time = form.querySelector('[name="preferred_time"]');
        const utcSlot = form.querySelector('[name="slot_start_utc"]');
        const timezone = form.querySelector('[name="timezone"]');
        const status = form.querySelector('.appointment-slots-status');

        const loadSlots = async () => {
            utcSlot.value = '';
            time.replaceChildren(new Option('Loading available times…', ''));
            time.disabled = true;
            if (!date.value) {
                time.replaceChildren(new Option('Choose a date first', ''));
                return;
            }

            const url = new URL(form.dataset.proposalAvailabilityUrl, window.location.href);
            url.searchParams.set('date', date.value);
            url.searchParams.set('timezone', timezone.value);

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Could not load available times.');

                time.replaceChildren(new Option(data.slots.length ? 'Select an available time' : 'No times available', ''));
                data.slots.forEach((slot) => {
                    const option = new Option(slot.label, slot.local_time);
                    option.dataset.slotUtc = slot.value;
                    time.add(option);
                });
                time.disabled = data.slots.length === 0;
                status.textContent = data.slots.length
                    ? 'Times are shown in your local time.'
                    : 'No available times for this date.';
            } catch (error) {
                time.replaceChildren(new Option('No times available', ''));
                status.textContent = error.message;
            }
        };

        date.addEventListener('change', loadSlots);
        time.addEventListener('change', () => {
            utcSlot.value = time.selectedOptions[0]?.dataset.slotUtc || '';
        });
        form.addEventListener('submit', (event) => {
            if (!utcSlot.value) {
                event.preventDefault();
                status.textContent = 'Choose an available time before sending the proposal.';
                time.focus();
            }
        });
    });
});