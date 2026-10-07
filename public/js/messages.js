document.addEventListener('DOMContentLoaded', function() {
    const composeModal = document.getElementById('composeMessageModal');
    const closeCompose = () => composeModal?.classList.remove('open');
    document.getElementById('openComposeMessage')?.addEventListener('click', () => composeModal?.classList.add('open'));
    document.querySelector('.compose-close')?.addEventListener('click', closeCompose);
    document.querySelector('.compose-cancel')?.addEventListener('click', closeCompose);
    composeModal?.addEventListener('click', (event) => { if (event.target === composeModal) closeCompose(); });

    const closeMessageMenus = (except = null) => {
        document.querySelectorAll('[data-message-menu-toggle]').forEach((button) => {
            const menu = button.parentElement?.querySelector('.message-action-menu');
            if (button === except) return;
            if (menu) menu.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        });
    };

    // Fixed positioning keeps the menu visible when the message list scrolls or clips it.
    const placeMessageMenu = (button, menu) => {
        menu.style.position = 'fixed';
        menu.style.right = 'auto';
        const anchor = button.getBoundingClientRect();
        const size = menu.getBoundingClientRect();
        const left = Math.min(Math.max(8, anchor.right - size.width), window.innerWidth - size.width - 8);
        let top = anchor.bottom + 5;
        if (top + size.height > window.innerHeight - 8) top = Math.max(8, anchor.top - size.height - 5);
        menu.style.left = `${left}px`;
        menu.style.top = `${top}px`;
    };

    document.querySelectorAll('[data-message-menu-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const menu = button.parentElement?.querySelector('.message-action-menu');
            const shouldOpen = menu?.hidden ?? false;
            closeMessageMenus(button);
            if (menu) {
                menu.hidden = !shouldOpen;
                if (shouldOpen) placeMessageMenu(button, menu);
            }
            button.setAttribute('aria-expanded', String(shouldOpen));
        });
    });
    document.querySelector('.chat-messages')?.addEventListener('scroll', () => closeMessageMenus());
    window.addEventListener('resize', () => closeMessageMenus());

    document.querySelectorAll('[data-edit-message]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('.message-row');
            const menu = button.closest('.message-action-menu');
            const toggle = button.closest('.message-actions')?.querySelector('[data-message-menu-toggle]');
            const display = row?.querySelector('[data-message-display]');
            const form = row?.querySelector('[data-message-edit-form]');
            if (!form || !display) return;
            if (menu) menu.hidden = true;
            toggle?.setAttribute('aria-expanded', 'false');
            display.hidden = true;
            form.hidden = false;
            form.querySelector('textarea')?.focus();
        });
    });

    document.querySelectorAll('[data-cancel-message-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('.message-row');
            const display = row?.querySelector('[data-message-display]');
            const form = row?.querySelector('[data-message-edit-form]');
            if (form) form.hidden = true;
            if (display) display.hidden = false;
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.message-actions')) closeMessageMenus();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMessageMenus();
    });

    const closeThreadMenus = (except = null) => {
        document.querySelectorAll('[data-thread-menu-toggle]').forEach((button) => {
            const menu = button.parentElement?.querySelector('.thread-action-menu');
            if (button === except) return;
            if (menu) menu.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        });
    };

    document.querySelectorAll('[data-thread-menu-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const menu = button.parentElement?.querySelector('.thread-action-menu');
            const shouldOpen = menu?.hidden ?? false;
            closeThreadMenus(button);
            if (menu) menu.hidden = !shouldOpen;
            button.setAttribute('aria-expanded', String(shouldOpen));
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.thread-menu-wrap')) closeThreadMenus();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeThreadMenus();
    });

    let activeMembersModal = null;
    document.querySelectorAll('[data-members-modal]').forEach((modal) => document.body.appendChild(modal));

    const closeMembersModal = (modal) => {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        if (activeMembersModal === modal) activeMembersModal = null;
        modal._opener?.focus();
    };

    document.querySelectorAll('[data-open-members-modal]').forEach((button) => {
        const modal = document.getElementById(button.dataset.openMembersModal);
        if (!modal) return;

        button.addEventListener('click', () => {
            closeThreadMenus();
            activeMembersModal = modal;
            modal._opener = button.closest('.thread-menu-wrap')?.querySelector('[data-thread-menu-toggle]');
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            modal.querySelector('[data-close-members-modal]')?.focus();
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeMembersModal(modal);
        });
        modal.querySelector('[data-close-members-modal]')?.addEventListener('click', () => closeMembersModal(modal));
        modal.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab') return;
            const focusable = [...modal.querySelectorAll('button:not([disabled]), [href], [tabindex]:not([tabindex="-1"])')];
            if (!focusable.length) {
                event.preventDefault();
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeMembersModal) closeMembersModal(activeMembersModal);
    });

    const chatMessages = document.querySelector('.chat-messages');
    const replyInput = document.querySelector('.reply-input');

    if (replyInput) {
        replyInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }

    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;

        const updatesUrl = document.querySelector('.chat-view')?.dataset.updatesUrl;
        const currentUserId = Number(chatMessages.dataset.currentUserId);

        if (updatesUrl) {
            window.setInterval(async function () {
                const lastMessageId = chatMessages.dataset.lastMessageId || '0';
                const response = await fetch(`${updatesUrl}?after=${lastMessageId}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) return;

                const { messages } = await response.json();
                messages.forEach(function (message) {
                    const sent = message.sender_id === currentUserId;
                    const row = document.createElement('div');
                    row.className = `message-row ${sent ? 'message-row-sent' : 'message-row-received'}`;
                    row.innerHTML = `<span class="message-avatar role-${message.sender_role}"></span><div class="message-bubble role-${message.sender_role} ${sent ? 'message-sent' : 'message-received'}"><div class="message-author"></div><div class="message-text"></div><div class="message-meta"></div></div>`;
                    const bubble = row.querySelector('.message-bubble');
                    row.querySelector('.message-avatar').textContent = message.sender_name.charAt(0).toUpperCase();
                    bubble.querySelector('.message-author').textContent = message.sender_name;
                    bubble.querySelector('.message-text').textContent = message.body;
                    bubble.querySelector('.message-meta').textContent = message.sent_at;
                    chatMessages.appendChild(row);
                    chatMessages.dataset.lastMessageId = message.id;
                });

                if (messages.length) chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 5000);
        }
    }
});
