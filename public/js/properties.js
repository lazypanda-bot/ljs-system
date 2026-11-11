document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        const card = event.target.closest('.property-card');
        if (!card || event.target.closest('button, a, select, input')) return;
        card.querySelector('.property-btn')?.click();
	});
});


document.addEventListener('DOMContentLoaded', () => {
    const propertyThumbnails = [...document.querySelectorAll('.property-thumbnail')];
    const propertyMainImage = document.getElementById('mainImage');
    let activePropertyImage = Math.max(0, propertyThumbnails.findIndex((thumbnail) => thumbnail.classList.contains('active')));

    const showPropertyImage = (index) => {
        if (!propertyMainImage || propertyThumbnails.length === 0) return;
        activePropertyImage = (index + propertyThumbnails.length) % propertyThumbnails.length;
        const thumbnail = propertyThumbnails[activePropertyImage];
        propertyMainImage.src = thumbnail.src;
        propertyMainImage.alt = thumbnail.alt;
        propertyThumbnails.forEach((item, itemIndex) => item.classList.toggle('active', itemIndex === activePropertyImage));
        thumbnail.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
    };

    propertyThumbnails.forEach((thumbnail, index) => thumbnail.addEventListener('click', () => showPropertyImage(index)));
    document.getElementById('propertyImagePrevious')?.addEventListener('click', () => showPropertyImage(activePropertyImage - 1));
    document.getElementById('propertyImageNext')?.addEventListener('click', () => showPropertyImage(activePropertyImage + 1));

    document.getElementById('referralProfessionalSelect')?.addEventListener('change', function () {
        const option = this.selectedOptions[0];
        const recipient = document.getElementById('referralMessageRecipient');
        const contactName = document.getElementById('referralContactName');
        const contactRole = document.getElementById('referralContactRole');
        const contactEmail = document.getElementById('referralContactEmail');
        const contactPhone = document.getElementById('referralContactPhone');
        const modalAssignSelect = document.getElementById('modalAssignSelect');

        if (recipient) recipient.value = option.value;
        if (contactName) {
            contactName.textContent = option.dataset.name;
            if (option.dataset.profileUrl) contactName.href = option.dataset.profileUrl;
            else contactName.removeAttribute('href');
        }
        if (contactRole) contactRole.textContent = option.dataset.role;
        if (contactEmail) contactEmail.textContent = option.dataset.email;
        if (contactPhone) contactPhone.textContent = option.dataset.phone || 'Contact number not provided';

        if (modalAssignSelect) {
            for (let opt of modalAssignSelect.options) {
                if (opt.value === option.dataset.assignmentId) {
                    modalAssignSelect.value = opt.value;
                    break;
                }
            }
        }
    });
});

// Modal open/close functions defined globally so inline onclick attributes can access them
window.openAppointmentModal = function() {
    const modal = document.getElementById('appointmentModal');
    if (modal) {
        modal.style.display = 'flex';
    }
};

window.closeAppointmentModal = function() {
    const modal = document.getElementById('appointmentModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.addEventListener('click', function(event) {
    const modal = document.getElementById('appointmentModal');
    if (modal && event.target === modal) {
        modal.style.display = 'none';
    }
});