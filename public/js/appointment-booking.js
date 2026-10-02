document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('appointmentBookingForm');
    if (!form) return;

    const dateField = form.querySelector('#appointmentDate');
    const timeField = form.querySelector('#appointmentTime');
    const timezoneField = form.querySelector('#appointmentTimezone');
    const slotField = form.querySelector('#appointmentSlotUtc');
    const status = form.querySelector('#appointmentSlotsStatus');
    const assignmentField = form.elements.namedItem('assign_id');

    if (!timezoneField.value) {
        try {
            timezoneField.value = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Manila';
        } catch {
            timezoneField.value = 'Asia/Manila';
        }
    }

    const loadSlots = async () => {
        slotField.value = '';
        timeField.replaceChildren(new Option('Loading available times…', ''));
        timeField.disabled = true;

        if (!dateField.value || !assignmentField?.value) {
            timeField.replaceChildren(new Option('Choose a date and professional first', ''));
            status.textContent = 'Times are shown in your local time.';
            return;
        }

        const url = new URL(form.dataset.slotsUrl, window.location.href);
        url.searchParams.set('assign_id', assignmentField.value);
        url.searchParams.set('date', dateField.value);
        url.searchParams.set('timezone', timezoneField.value);
        const referral = form.elements.namedItem('ref');
        if (referral?.value) url.searchParams.set('ref', referral.value);

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not load appointment times.');

            timeField.replaceChildren(new Option(data.slots.length ? 'Select an available time' : 'No times available', ''));
            data.slots.forEach((slot) => {
                const option = new Option(slot.label, slot.local_time);
                option.dataset.slotUtc = slot.value;
                timeField.add(option);
            });
            timeField.disabled = data.slots.length === 0;
            status.textContent = data.slots.length
                ? 'Times are shown in your local time.'
                : 'No available times for this date. Choose another date.';
        } catch (error) {
            timeField.replaceChildren(new Option('No times available', ''));
            status.textContent = error.message;
        }
    };

    dateField.addEventListener('change', loadSlots);
    assignmentField?.addEventListener('change', loadSlots);
    timeField.addEventListener('change', () => {
        slotField.value = timeField.selectedOptions[0]?.dataset.slotUtc || '';
    });
    form.addEventListener('submit', (event) => {
        if (!slotField.value) {
            event.preventDefault();
            status.textContent = 'Choose an available time before requesting the appointment.';
            timeField.focus();
        }
    });
});