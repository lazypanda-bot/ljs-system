document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-delete-user]').forEach((button) => {
        button.addEventListener('click', () => window.deleteUser(button.dataset.deleteUser));
    });
    document.querySelectorAll('[data-delete-team]').forEach((button) => {
        button.addEventListener('click', () => window.deleteTeam(button.dataset.deleteTeam));
    });

    // Tab switching
    const tabBtns = document.querySelectorAll('.au-tab-btn');
    const tabContents = document.querySelectorAll('.au-tab-content');
    const usersSearchWrapper = document.getElementById('usersSearchWrapper');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(tabName).classList.add('active');
            if (usersSearchWrapper) {
                const search = document.getElementById('searchUsers');
                if (search) {
                    search.placeholder = tabName === 'teams' ? 'Search teams...' : 'Search by name...';
                    search.value = '';
                }
                document.querySelectorAll('.au-tab-content tbody tr').forEach((row) => { row.style.display = ''; });
            }
            const addUserButton = document.getElementById('openAddUserModal');
            const addTeamButton = document.getElementById('openAddTeamModal');
            if (addUserButton) addUserButton.hidden = tabName !== 'all-users';
            if (addTeamButton) addTeamButton.hidden = tabName !== 'teams';

            closeModal(document.getElementById('addUserModalOverlay'));
            closeModal(document.getElementById('addTeamModalOverlay'));
        });
    });

    const openAddUserBtn = document.getElementById('openAddUserModal');
    const allUsersTab = document.querySelector('[data-tab="all-users"]');
    const allUsersPanel = document.getElementById('all-users');
    if (openAddUserBtn && allUsersTab && allUsersPanel) {
        openAddUserBtn.addEventListener('click', function () {
            if (!allUsersPanel.classList.contains('active')) {
                allUsersTab.click();
            }
        });
    }

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
        closeCredentialViewer();
    });

    const membersOverlay = document.getElementById('teamMembersOverlay');
    const closeMembers = () => membersOverlay?.classList.remove('open');
    document.querySelectorAll('[data-team-members]').forEach((button) => {
        button.addEventListener('click', () => {
            const members = JSON.parse(button.dataset.teamMembers || '[]');
            document.getElementById('teamMembersTitle').textContent = `${button.dataset.teamName} members`;
            document.getElementById('teamMembersLeader').textContent = button.dataset.teamLeader ? `Leader: ${button.dataset.teamLeader}` : '';
            const list = document.getElementById('teamMembersList');
            list.replaceChildren();
            if (!members.length) {
                const empty = document.createElement('li');
                empty.textContent = 'No members in this team yet.';
                list.appendChild(empty);
            }
            members.forEach((member) => {
                const item = document.createElement('li');
                const name = document.createElement('strong');
                name.textContent = member.name;
                const role = document.createElement('small');
                role.textContent = member.role || '';
                item.append(name, role);
                list.appendChild(item);
            });
            membersOverlay?.classList.add('open');
        });
    });
    document.getElementById('closeTeamMembers')?.addEventListener('click', closeMembers);
    membersOverlay?.addEventListener('click', (event) => { if (event.target === membersOverlay) closeMembers(); });

    const credentialOverlay = document.getElementById('credentialViewerOverlay');
    const credentialImage = document.getElementById('credentialViewerImage');
    const credentialPdf = document.getElementById('credentialViewerPdf');

    function closeCredentialViewer() {
        if (!credentialOverlay) return;
        closeModal(credentialOverlay);
        credentialImage.removeAttribute('src');
        credentialPdf.removeAttribute('src');
    }

    document.querySelectorAll('[data-view-credential]').forEach((button) => {
        button.addEventListener('click', () => {
            const isPdf = button.dataset.credentialType === 'pdf';
            document.getElementById('credentialViewerTitle').textContent = `${button.dataset.credentialOwner}'s credential`;
            credentialImage.hidden = isPdf;
            credentialPdf.hidden = !isPdf;
            (isPdf ? credentialPdf : credentialImage).src = button.dataset.viewCredential;
            openModal(credentialOverlay);
        });
    });
    document.getElementById('closeCredentialViewer')?.addEventListener('click', closeCredentialViewer);
    credentialOverlay?.addEventListener('click', (event) => {
        if (event.target === credentialOverlay) closeCredentialViewer();
    });

    // Search Users
    const searchUsers = document.getElementById('searchUsers');
    if (searchUsers) {
        searchUsers.addEventListener('input', function() {
            const searchTerm = this.value.trim().toLowerCase();
            const tableRows = document.querySelectorAll('.au-tab-content.active tbody tr');

            tableRows.forEach(row => {
                row.style.display = (row.textContent || '').toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Delete User
    window.deleteUser = async function(userId) {
        if (await window.appConfirm('Are you sure you want to delete this user? This action cannot be undone.')) {
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
    window.deleteTeam = async function(teamId) {
        if (await window.appConfirm('Are you sure you want to delete this team? This action cannot be undone.')) {
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