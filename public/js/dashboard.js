document.addEventListener('DOMContentLoaded', function () {
    const blockDateModal = document.getElementById('blockDateModal');
    document.getElementById('openBlockDateModal')?.addEventListener('click', () => blockDateModal?.classList.add('open'));
    document.getElementById('closeBlockDateModal')?.addEventListener('click', () => blockDateModal?.classList.remove('open'));

    const calendarDates = document.getElementById('calendarDates');
    if (!calendarDates) return;

    let appointments = [];
    let blockedDates = [];
    if (calendarDates.dataset.appointments) {
        try {
            appointments = JSON.parse(calendarDates.dataset.appointments);
        } catch (e) {
            console.error('Failed to parse appointments data', e);
        }
    }
    if (calendarDates.dataset.blockedDates) {
        try {
            blockedDates = JSON.parse(calendarDates.dataset.blockedDates);
        } catch (e) {
            console.error('Failed to parse blocked dates data', e);
        }
    }

    const cells = calendarDates.querySelectorAll('.calendar-cell:not(.other-month)');
    
    cells.forEach(cell => {
        const dayNumberElem = cell.querySelector('.calendar-day-number');
        if (!dayNumberElem) return;

        const dayNum = String(dayNumberElem.textContent.trim()).padStart(2, '0');
        const monthDisplay = document.querySelector('.calendar-month-display')?.textContent || '';
        
        if (!monthDisplay) return;

        const [monthName, year] = monthDisplay.trim().split(/\s+/);
        if (!monthName || !year) return;

        const dateObj = new Date(`${monthName} 1, ${year}`);
        const monthNum = String(dateObj.getMonth() + 1).padStart(2, '0');
        const dateString = `${year}-${monthNum}-${dayNum}`;

        const dayAppointments = appointments.filter((app) => String(app.preferred_date).slice(0, 10) === dateString);
        const dayBlockedDates = blockedDates.filter((blocked) => String(blocked.date).slice(0, 10) === dateString);
        if (dayAppointments.length > 0 || dayBlockedDates.length > 0) {
            let badgeContainer = cell.querySelector('.day-badges');
            if (!badgeContainer) {
                badgeContainer = document.createElement('div');
                badgeContainer.className = 'day-badges';
                cell.appendChild(badgeContainer);
            }

            dayAppointments.forEach(app => {
                const badge = document.createElement('span');
                badge.className = 'appointment-badge appointment-event';
                badge.title = `${app.contact_name || 'Appointment'}${app.assignment?.property?.property_name ? `: ${app.assignment.property.property_name}` : ''}`;
                badgeContainer.appendChild(badge);
            });

            dayBlockedDates.forEach((blocked) => {
                const badge = document.createElement('span');
                badge.className = 'appointment-badge blocked-date-event';
                badge.title = blocked.reason || 'Blocked date';
                badge.setAttribute('aria-label', badge.title);
                badgeContainer.appendChild(badge);
                cell.classList.add('calendar-cell-blocked');
            });
        }
    });
});