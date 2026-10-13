document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('appointmentCancelModal');
    if (!modal) return;

    const dialog = modal.querySelector('.appointment-cancel-dialog');
    const form = modal.querySelector('[data-cancel-form]');
    const context = modal.querySelector('[data-cancel-context]');
    const reason = modal.querySelector('textarea[name="cancellation_reason"]');
    let opener = null;

    const closeModal = () => {
        modal.hidden = true;
        form?.reset();
        opener?.focus();
        opener = null;
    };

    document.querySelectorAll('[data-cancel-appointment]').forEach((button) => {
        button.addEventListener('click', () => {
            opener = button;
            if (form) form.action = button.dataset.action || '';
            if (context) context.textContent = button.dataset.context || 'Selected appointment';
            modal.hidden = false;
            reason?.focus();
        });
    });

    modal.querySelectorAll('[data-cancel-dismiss]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    dialog?.addEventListener('click', (event) => event.stopPropagation());
});
