document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.password-toggle, #togglePassword').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordTarget || 'password');
            if (!input) return;
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Hide' : 'Show';
        });
    });

    const tabs = document.querySelectorAll('.auth-tab');
    const panels = document.querySelectorAll('.auth-panel');
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const panelId = tab.dataset.authPanel;
            tabs.forEach((item) => item.classList.toggle('active', item === tab));
            panels.forEach((panel) => { panel.hidden = panel.id !== panelId; panel.classList.toggle('active', panel.id === panelId); });
        });
    });

    if (document.querySelector('[name="name"]')?.value && document.querySelector('.auth-tab[data-auth-panel="register-panel"]')) {
        document.querySelector('.auth-tab[data-auth-panel="register-panel"]').click();
    }
});
