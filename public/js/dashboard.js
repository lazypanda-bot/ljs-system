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

            let tooltipLines = [`<div style="font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.2); margin-bottom: 3px; padding-bottom: 2px;">${dateString}</div>`];

            dayAppointments.forEach(app => {
                const badge = document.createElement('span');
                badge.className = 'appointment-badge appointment-event';
                badge.style.width = '7px';
                badge.style.height = '7px';
                badge.style.borderRadius = '50%';
                badge.style.backgroundColor = '#3b82f6';
                badge.style.display = 'inline-block';
                badgeContainer.appendChild(badge);

                const appTitle = `${app.contact_name || 'Appointment'}`;
                tooltipLines.push(`<div>• ${app.preferred_time ? app.preferred_time + ' - ' : ''}${appTitle}</div>`);
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

                const reason = blocked.reason || 'Blocked date';
                tooltipLines.push(`<div>• [Blocked] ${reason}</div>`);
            });

            if (!customTooltip) {
                customTooltip = document.createElement('div');
                customTooltip.className = 'custom-day-tooltip';
                cell.appendChild(customTooltip);
            }
            customTooltip.innerHTML = tooltipLines.join('');
        }
    });
});