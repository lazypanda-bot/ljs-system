document.addEventListener('DOMContentLoaded', function() {
    const threadItems = document.querySelectorAll('.thread-item');
    const chatMessages = document.querySelector('.chat-messages');
    const replyInput = document.querySelector('.reply-input');
    const sendBtn = document.querySelector('.btn-send');
    const attachBtn = document.querySelector('.btn-attach');

    // Auto-expand textarea
    if (replyInput) {
        replyInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }

    // Handle thread selection
    threadItems.forEach(item => {
        item.addEventListener('click', function() {
            threadItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // In a real app, you would load messages here
            console.log('Loading thread for: ' + this.querySelector('.thread-name').innerText);
        });
    });

    // Handle send message
    function sendMessage() {
        if (!replyInput || !chatMessages) return;
        
        const text = replyInput.value.trim();
        if (text) {
            const now = new Date();
            const timeStr = now.getHours() + ':' + now.getMinutes().toString().padStart(2, '0');
            
            const messageHtml = `
                <div class="message-bubble message-sent">
                    <div class="message-text">${escapeHtml(text)}</div>
                    <div class="message-meta">${timeStr}</div>
                </div>
            `;
            
            chatMessages.insertAdjacentHTML('beforeend', messageHtml);
            replyInput.value = '';
            replyInput.style.height = 'auto';
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    if (replyInput) {
        replyInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // Handle attach button
    if (attachBtn) {
        attachBtn.addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.onchange = e => {
                const file = e.target.files[0];
                if (file) {
                    console.log('File selected:', file.name);
                    // Handle file upload
                }
            };
            input.click();
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
