document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabBtns = document.querySelectorAll('.au-tab-btn');
    const tabContents = document.querySelectorAll('.au-tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(tabName).classList.add('active');

            closeModal(document.getElementById('addUserModalOverlay'));
            closeModal(document.getElementById('addTeamModalOverlay'));
        });
    });

    // Generic modal open/close helpers
    function openModal(overlay) {
        if (!overlay) return;
        overlay.classList.add('open');
    }

    function closeModal(overlay) {
        if (!overlay) return;
        overlay.classList.remove('open');
    }

    function setupModal({ openBtnId, overlayId, closeBtnId, cancelBtnId }) {
        const overlay = document.getElementById(overlayId);
        const openBtn = document.getElementById(openBtnId);
        const closeBtn = document.getElementById(closeBtnId);
        const cancelBtn = document.getElementById(cancelBtnId);

        if (openBtn && overlay) {
            openBtn.addEventListener('click', () => openModal(overlay));
        }

        const closeAndReset = () => {
            closeModal(overlay);
            const form = overlay?.querySelector('form');
            if (form) form.reset();
        };

        if (closeBtn) closeBtn.addEventListener('click', closeAndReset);
        if (cancelBtn) cancelBtn.addEventListener('click', closeAndReset);

        if (overlay) {
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) closeAndReset();
            });
        }

        return overlay;
    }

    const addUserModalOverlay = setupModal({
        openBtnId: 'openAddUserModal',
        overlayId: 'addUserModalOverlay',
        closeBtnId: 'closeAddUserModal',
        cancelBtnId: 'cancelAddUserForm',
    });

    const addTeamModalOverlay = setupModal({
        openBtnId: 'openAddTeamModal',
        overlayId: 'addTeamModalOverlay',
        closeBtnId: 'closeAddTeamModal',
        cancelBtnId: 'cancelAddTeamForm',
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeModal(addUserModalOverlay);
        closeModal(addTeamModalOverlay);
    });

    // Search Users
    const searchUsers = document.getElementById('searchUsers');
    if (searchUsers) {
        searchUsers.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#all-users tbody tr');

            tableRows.forEach(row => {
                const userName = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
                const userEmail = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                
                if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Delete User
    window.deleteUser = function(userId) {
        if (confirm('Are you sure you want to delete this user?')) {
            fetch(`/admin/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    };

    // Delete Team
    window.deleteTeam = function(teamId) {
        if (confirm('Are you sure you want to delete this team?')) {
            fetch(`/admin/teams/${teamId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    };
});