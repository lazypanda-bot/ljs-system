document.addEventListener('DOMContentLoaded', () => {
    const photoInput = document.getElementById('profile_photo');
    const preview = document.querySelector('.settings-avatar-preview');
    const photoEdit = document.getElementById('profilePhotoEdit');
    photoEdit?.addEventListener('click', () => photoInput?.click());
    if (photoInput && preview) {
        photoInput.addEventListener('change', () => {
            const file = photoInput.files && photoInput.files[0];
            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                let image = preview.querySelector('img');
                if (!image) {
                    preview.replaceChildren();
                    image = document.createElement('img');
                    image.alt = 'Selected profile photo';
                    preview.appendChild(image);
                }
                image.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }

    const tabButtons = document.querySelectorAll('.settings-tab-btn');
    const panels = document.querySelectorAll('.settings-panel');

    const activateTab = (button) => {
        const targetId = button.dataset.target;
        const target = document.getElementById(targetId);
        if (!target) return;

        tabButtons.forEach((tab) => {
            const active = tab === button;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((panel) => {
            const active = panel === target;
            panel.classList.toggle('active', active);
            panel.hidden = !active;
        });
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activateTab(button);
        });
    });

    const deleteModal = document.getElementById('deleteAccountModal');
    const setDeleteModal = (open) => {
        deleteModal?.classList.toggle('open', open);
        deleteModal?.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (open) document.getElementById('delete_current_password')?.focus();
    };
    document.querySelectorAll('[data-open-delete-account]').forEach((button) => button.addEventListener('click', () => setDeleteModal(true)));
    document.querySelectorAll('[data-close-delete-account]').forEach((button) => button.addEventListener('click', () => setDeleteModal(false)));
    deleteModal?.addEventListener('click', (event) => { if (event.target === deleteModal) setDeleteModal(false); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setDeleteModal(false); });

    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input = document.getElementById(targetId);
            if (!input) return;

            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            btn.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${btn.getAttribute('aria-label')?.replace(/^(Show|Hide) /, '')}`);
            btn.querySelector('i')?.classList.toggle('fa-eye-slash', visible);
            btn.querySelector('i')?.classList.toggle('fa-eye', !visible);
        });
    });

    // Dynamic password requirements checklist
    const newPasswordInput = document.getElementById('password');
    if (newPasswordInput) {
        const reqLength = document.getElementById('req-length');
        const reqUpper = document.getElementById('req-upper');
        const reqNumber = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');

        newPasswordInput.addEventListener('input', (event) => {
            const val = event.target.value;

            // Length check
            if (val.length >= 8) {
                reqLength.style.color = '#059669';
            } else {
                reqLength.style.color = '#047857';
            }

            // Uppercase check
            if (/[A-Z]/.test(val)) {
                reqUpper.style.color = '#059669';
            } else {
                reqUpper.style.color = '#047857';
            }

            // Number check
            if (/[0-9]/.test(val)) {
                reqNumber.style.color = '#059669';
            } else {
                reqNumber.style.color = '#047857';
            }

            // Special character check
            if (/[^A-Za-z0-9]/.test(val)) {
                reqSpecial.style.color = '#059669';
            } else {
                reqSpecial.style.color = '#047857';
            }
        });
    }
});