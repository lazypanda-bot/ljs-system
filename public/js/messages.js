document.addEventListener('DOMContentLoaded', function() {
    const composeModal = document.getElementById('composeMessageModal');
    const attachmentInput = document.getElementById('messageAttachment');
    const attachmentStatus = document.getElementById('messageAttachmentStatus');
    const maxAttachmentBytes = 5 * 1024 * 1024;
    const showAttachmentCard = (input, status, file) => {
        const icon = document.createElement('i');
        icon.dataset.lucide = 'file';
        icon.setAttribute('aria-hidden', 'true');
        const details = document.createElement('span');
        details.className = 'attachment-card-details';
        const name = document.createElement('strong');
        name.textContent = file.name;
        name.title = file.name;
        const size = document.createElement('small');
        size.textContent = file.size >= 1024 * 1024
            ? `${(file.size / (1024 * 1024)).toFixed(1)} MB`
            : `${Math.max(1, Math.round(file.size / 1024))} KB`;
        details.append(name, size);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'attachment-card-remove';
        remove.setAttribute('aria-label', `Remove ${file.name}`);
        remove.title = 'Remove attachment';
        const closeIcon = document.createElement('i');
        closeIcon.dataset.lucide = 'x';
        closeIcon.setAttribute('aria-hidden', 'true');
        remove.append(closeIcon);
        remove.addEventListener('click', () => {
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        status.replaceChildren(icon, details, remove);
        status.classList.add('has-file');
        status.hidden = false;
        window.lucide?.createIcons();
    };
    const rejectOversizedAttachment = (input, status, file) => {
        if (!file || file.size <= maxAttachmentBytes) return false;
        input.value = '';
        status.textContent = 'File is too large. Maximum attachment size is 5 MB.';
        status.hidden = false;
        status.classList.remove('has-file');
        status.classList.add('is-error');
        return true;
    };
    const resetComposeAttachment = () => {
        if (attachmentInput) attachmentInput.value = '';
        if (attachmentStatus) {
            attachmentStatus.textContent = 'No file attached';
            attachmentStatus.classList.remove('has-file', 'is-error');
        }
    };
    const closeCompose = () => {
        composeModal?.classList.remove('open');
        resetComposeAttachment();
    };
    document.getElementById('openComposeMessage')?.addEventListener('click', () => composeModal?.classList.add('open'));
    document.querySelector('.compose-close')?.addEventListener('click', closeCompose);
    document.querySelector('.compose-cancel')?.addEventListener('click', closeCompose);
    composeModal?.addEventListener('click', (event) => { if (event.target === composeModal) closeCompose(); });
    attachmentInput?.addEventListener('change', () => {
        const file = attachmentInput.files?.[0];
        if (!attachmentStatus) return;
        attachmentStatus.classList.remove('is-error');
        if (rejectOversizedAttachment(attachmentInput, attachmentStatus, file)) return;

        if (!file) {
            attachmentStatus.textContent = 'No file attached';
            attachmentStatus.classList.remove('has-file');
            return;
        }

        showAttachmentCard(attachmentInput, attachmentStatus, file);
    });

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

    const initializeMessageActions = (scope = document) => {
        scope.querySelectorAll('[data-message-menu-toggle]').forEach((button) => {
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
        scope.querySelector('.chat-messages')?.addEventListener('scroll', () => closeMessageMenus());

        scope.querySelectorAll('[data-edit-message]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('.message-row');
                const menu = button.closest('.message-action-menu');
                const toggle = button.closest('.message-actions')?.querySelector('[data-message-menu-toggle]');
                const display = row?.querySelector('[data-message-display]');
                const form = row?.querySelector('[data-message-edit-form]');
                const bubble = row?.querySelector('.message-bubble');
                if (!form || !display) return;
                if (menu) menu.hidden = true;
                toggle?.setAttribute('aria-expanded', 'false');
                display.hidden = true;
                form.hidden = false;
                bubble?.classList.add('is-editing');
                form.querySelector('textarea')?.focus();
            });
        });

        scope.querySelectorAll('[data-cancel-message-edit]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('.message-row');
                const display = row?.querySelector('[data-message-display]');
                const form = row?.querySelector('[data-message-edit-form]');
                const bubble = row?.querySelector('.message-bubble');
                if (form) form.hidden = true;
                if (display) display.hidden = false;
                bubble?.classList.remove('is-editing');
            });
        });

    };
    window.addEventListener('resize', () => closeMessageMenus());

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

    let stopChat = () => {};
    const initializeChat = (scope = document) => {
        const requests = new AbortController();
        let timer;
        let polling = false;
        stopChat = () => {
            window.clearInterval(timer);
            requests.abort();
        };
        const chatMessages = scope.querySelector('.chat-messages');
        const replyInput = scope.querySelector('.reply-input');
        const replyAttachment = scope.querySelector('#replyAttachment');
        const replyAttachmentStatus = scope.querySelector('#replyAttachmentStatus');

        if (replyInput) {
            replyInput.addEventListener('keydown', (event) => {
                const desktopKeyboard = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                if (!desktopKeyboard || event.key !== 'Enter' || event.shiftKey
                    || event.ctrlKey || event.altKey || event.metaKey
                    || event.isComposing || event.keyCode === 229) return;

                event.preventDefault();
                if (event.repeat || !replyInput.value.trim()) return;

                const form = replyInput.form;
                const sendButton = form?.querySelector('button[type="submit"]');
                if (sendButton && !sendButton.disabled) form.requestSubmit(sendButton);
            });
            replyInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        replyAttachment?.addEventListener('change', () => {
            const file = replyAttachment.files?.[0];
            if (!replyAttachmentStatus) return;
            replyAttachmentStatus.classList.remove('is-error');
            if (rejectOversizedAttachment(replyAttachment, replyAttachmentStatus, file)) return;

            if (!file) {
                replyAttachmentStatus.hidden = true;
                replyAttachmentStatus.textContent = '';
                replyAttachmentStatus.classList.remove('has-file');
                return;
            }

            showAttachmentCard(replyAttachment, replyAttachmentStatus, file);
        });

        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;

            const chatView = scope.matches?.('.chat-view') ? scope : scope.querySelector('.chat-view');
            const updatesUrl = chatView?.dataset.updatesUrl;
            const currentUserId = Number(chatMessages.dataset.currentUserId);

            if (updatesUrl) {
                timer = window.setInterval(async function () {
                    if (polling || !chatMessages.isConnected) return;
                    polling = true;
                    try {
                        const lastMessageId = chatMessages.dataset.lastMessageId || '0';
                        const response = await fetch(`${updatesUrl}?after=${lastMessageId}`, {
                            headers: { Accept: 'application/json' }, signal: requests.signal,
                        });

                        if (!response.ok || !chatMessages.isConnected) return;

                        const { messages } = await response.json();
                        messages.forEach(function (message) {
                            const sent = message.sender_id === currentUserId;
                            const row = document.createElement('div');
                            row.className = `message-row ${sent ? 'message-row-sent' : 'message-row-received'}`;
                            row.innerHTML = `<span class="message-avatar role-${message.sender_role}"></span><div class="message-bubble role-${message.sender_role} ${sent ? 'message-sent' : 'message-received'}"><div class="message-author"></div><div class="message-text"></div><a class="message-attachment" target="_blank" rel="noopener" hidden><i data-lucide="paperclip" aria-hidden="true"></i> Attachment</a><div class="message-meta"></div></div>`;
                            const bubble = row.querySelector('.message-bubble');
                            row.querySelector('.message-avatar').textContent = message.sender_name.charAt(0).toUpperCase();
                            bubble.querySelector('.message-author').textContent = message.sender_name;
                            bubble.querySelector('.message-text').textContent = message.body;
                            const attachment = bubble.querySelector('.message-attachment');
                            if (message.attachment_url && attachment) {
                                attachment.href = message.attachment_url;
                                attachment.hidden = false;
                            }
                            bubble.querySelector('.message-meta').textContent = message.sent_at;
                            chatMessages.appendChild(row);
                            window.lucide?.createIcons();
                            chatMessages.dataset.lastMessageId = message.id;
                        });

                        if (messages.length) chatMessages.scrollTop = chatMessages.scrollHeight;
                    } catch { /* Retry transient failures on the next poll. */ }
                    finally { polling = false; }
                }, 5000);
            }
        }
    };
    initializeMessageActions();
    initializeChat();
    document.addEventListener('ljs:split-before-update', (event) => {
        if (event.target.dataset.splitView === 'messages') stopChat();
    });
    document.addEventListener('ljs:split-updated', (event) => {
        if (event.target.dataset.splitView !== 'messages') return;
        initializeMessageActions(event.detail.panel);
        initializeChat(event.detail.panel);
    });
    window.addEventListener('pagehide', () => stopChat());
});
