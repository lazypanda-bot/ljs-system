document.addEventListener('DOMContentLoaded', function() {
    const composeModal = document.getElementById('composeMessageModal');
    const closeCompose = () => composeModal?.classList.remove('open');
    document.getElementById('openComposeMessage')?.addEventListener('click', () => composeModal?.classList.add('open'));
    document.querySelector('.compose-close')?.addEventListener('click', closeCompose);
    document.querySelector('.compose-cancel')?.addEventListener('click', closeCompose);
    composeModal?.addEventListener('click', (event) => { if (event.target === composeModal) closeCompose(); });
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
