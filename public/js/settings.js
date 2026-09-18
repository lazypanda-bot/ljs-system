document.addEventListener('DOMContentLoaded', () => {
    // Horizontal Tab switching functionality
    const tabButtons = document.querySelectorAll('.settings-tab-btn');
    const panels = document.querySelectorAll('.settings-panel');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            panels.forEach(panel => panel.classList.remove('active'));

            button.classList.add('active');
            const targetId = button.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Password visibility toggle
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🔒';
            } else {
                input.type = 'password';
                btn.textContent = '👁';
            }
        });
    });

    // Dynamic password requirements checklist
    const newPasswordInput = document.getElementById('password');
    if (newPasswordInput) {
        const reqLength = document.getElementById('req-length');
        const reqUpper = document.getElementById('req-upper');
        const reqNumber = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');

        newPasswordInput.addEventListener('input', (e) => {
            const val = e.target.value;

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