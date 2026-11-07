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
        const formatTime = (value) => {
            if (!value) return '';
            const [hours, minutes] = String(value).split(':').map(Number);
            const date = new Date();
            date.setHours(hours || 0, minutes || 0, 0, 0);
            return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        };
        
        // CLEAR EXISTING BADGES & TOOLTIPS TO PREVENT DUPLICATES
        let badgeContainer = cell.querySelector('.day-badges');
        if (badgeContainer) {
            badgeContainer.innerHTML = ''; // Removes old duplicate dots
        }

        let customTooltip = cell.querySelector('.custom-day-tooltip');
        if (customTooltip) {
            customTooltip.innerHTML = ''; // Clears old tooltip lines
        }

        if (dayAppointments.length > 0 || dayBlockedDates.length > 0) {
            if (!badgeContainer) {
                badgeContainer = document.createElement('div');
                badgeContainer.className = 'day-badges';
                badgeContainer.style.display = 'flex';
                badgeContainer.style.gap = '4px';
                badgeContainer.style.marginTop = '6px';
                cell.appendChild(badgeContainer);
            }

            const tooltipDate = document.createElement('div');
            tooltipDate.className = 'custom-day-tooltip-date';
            tooltipDate.textContent = dateString;
            customTooltip ??= document.createElement('div');
            customTooltip.className = 'custom-day-tooltip';
            customTooltip.replaceChildren(tooltipDate);

            dayAppointments.forEach(app => {
                const badge = document.createElement('span');
                badge.className = 'appointment-badge appointment-event';
                badge.style.width = '7px';
                badge.style.height = '7px';
                badge.style.borderRadius = '50%';
                badge.style.backgroundColor = '#3b82f6';
                badge.style.display = 'inline-block';
                badgeContainer.appendChild(badge);

                const appointmentLine = document.createElement('div');
                const appointmentTitle = app.property_name
                    ? `${app.contact_name || 'Appointment'} · ${app.property_name}`
                    : (app.contact_name || 'Appointment');
                appointmentLine.textContent = `${app.preferred_time ? app.preferred_time + ' - ' : ''}${appointmentTitle}`;
                customTooltip.appendChild(appointmentLine);
            });

            dayBlockedDates.forEach((blocked) => {
                const badge = document.createElement('span');
                badge.className = 'appointment-badge blocked-date-event';
                badge.style.width = '7px';
                badge.style.height = '7px';
                badge.style.borderRadius = '50%';
                badge.style.backgroundColor = '#ef4444';
                badge.style.display = 'inline-block';
                badgeContainer.appendChild(badge);
                cell.classList.add('calendar-cell-blocked');

                const blocker = blocked.blocked_by ? `Blocked by ${blocked.blocked_by}` : 'Blocked date';
                const reason = blocked.reason ? `: ${blocked.reason}` : '';
                const startTime = blocked.start_time ? formatTime(blocked.start_time) : 'All day';
                const endTime = blocked.end_time ? ` - ${formatTime(blocked.end_time)}` : '';
                const blockedLine = document.createElement('div');
                blockedLine.textContent = `[${blocker}${reason}] ${startTime}${endTime}`;
                customTooltip.appendChild(blockedLine);
            });

            if (!cell.contains(customTooltip)) cell.appendChild(customTooltip);
        }
    });
});
// Block only complete viewing intervals; blank start/end means the whole day.
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.block-date-dialog');
    const start = form?.querySelector('[name="start_time"]');
    const end = form?.querySelector('[name="end_time"]');
    if (!start || !end) return;
    const updateTimes = () => {
        [...end.options].forEach((option) => {
            option.disabled = Boolean(option.value && start.value && option.value <= start.value);
        });
        if (end.selectedOptions[0]?.disabled) end.value = '';
        start.required = Boolean(end.value);
        end.required = Boolean(start.value);
    };
    start.addEventListener('change', updateTimes);
    end.addEventListener('change', updateTimes);
    form.addEventListener('reset', () => queueMicrotask(updateTimes));
    updateTimes();
});
