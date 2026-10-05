document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-proposal-availability-url]').forEach((form) => {
        const date = form.querySelector('[name="preferred_date"]');
        const time = form.querySelector('[name="preferred_time"]');
        const utcSlot = form.querySelector('[name="slot_start_utc"]');
        const timezone = form.querySelector('[name="timezone"]');
        const status = form.querySelector('.appointment-slots-status');

        window.LjsAvailability.init({
            dateInput: date,
            timeSelect: time,
            utcField: utcSlot,
            status,
            timezone: () => timezone.value,
            urlFor: ({ date: day, withDates }) => {
                const url = new URL(form.dataset.proposalAvailabilityUrl, window.location.href);
                url.searchParams.set('date', day);
                url.searchParams.set('timezone', timezone.value);
                if (withDates) url.searchParams.set('with_dates', '1');
                return url;
            },
        });

        form.addEventListener('submit', (event) => {
            if (!utcSlot.value) {
                event.preventDefault();
                status.textContent = 'Choose an available date and time before sending the proposal.';
                date.focus();
            }
        });
    });
});
