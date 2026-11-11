document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('appointmentBookingForm');
    if (!form) return;

    const dateField = form.querySelector('#appointmentDate');
    const timeField = form.querySelector('#appointmentTime');
    const periodField = form.querySelector('#appointmentTimePeriod');
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

    const picker = window.LjsAvailability.init({
        dateInput: dateField,
        timeSelect: timeField,
        utcField: slotField,
        periodSelect: periodField,
        status,
        timezone: () => timezoneField.value,
        ready: () => Boolean(assignmentField?.value),
        urlFor: ({ date, withDates }) => {
            const url = new URL(form.dataset.slotsUrl, window.location.href);
            url.searchParams.set('assign_id', assignmentField.value);
            url.searchParams.set('date', date);
            url.searchParams.set('timezone', timezoneField.value);
            if (withDates) url.searchParams.set('with_dates', '1');
            const referral = form.elements.namedItem('ref');
            if (referral?.value) url.searchParams.set('ref', referral.value);
            return url;
        },
    });

    assignmentField?.addEventListener('change', () => picker.reload());
    form.addEventListener('submit', (event) => {
        if (!dateField.value) {
            event.preventDefault();
            status.textContent = 'Choose an appointment date before requesting the appointment.';
            dateField.focus();
            return;
        }

        if (!timeField.value) {
            event.preventDefault();
            status.textContent = 'Choose an available time before requesting the appointment.';
            timeField.focus();
            return;
        }

        if (!slotField.value) {
            event.preventDefault();
            status.textContent = 'Choose an available date and time before requesting the appointment.';
            timeField.focus();
        }
    });
});
