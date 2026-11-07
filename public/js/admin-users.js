document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : event.target?.parentElement;
        const deleteUserButton = target?.closest('[data-delete-user]');
        const deleteTeamButton = target?.closest('[data-delete-team]');
        if (deleteUserButton) window.deleteUser(deleteUserButton.dataset.deleteUser);
        if (deleteTeamButton) window.deleteTeam(deleteTeamButton.dataset.deleteTeam);
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
                filterStaffRows();
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

    const openCredential = (button) => {
        const isPdf = button.dataset.credentialType === 'pdf';
        document.getElementById('credentialViewerTitle').textContent = `${button.dataset.credentialOwner}'s credential`;
        credentialImage.hidden = isPdf;
        credentialPdf.hidden = !isPdf;
        (isPdf ? credentialPdf : credentialImage).src = button.dataset.viewCredential;
        openModal(credentialOverlay);
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : event.target?.parentElement;
        const credentialButton = target?.closest('[data-view-credential]');
        if (credentialButton) openCredential(credentialButton);
    });
    document.getElementById('closeCredentialViewer')?.addEventListener('click', closeCredentialViewer);
    credentialOverlay?.addEventListener('click', (event) => {
        if (event.target === credentialOverlay) closeCredentialViewer();
    });

    // Search the table in the active Staff Management tab.
    const searchUsers = document.getElementById('searchUsers');
    const usersTable = document.getElementById('usersTableContent');
    const getSearchableRows = (table) => Array.from(table?.querySelectorAll('tbody tr') || [])
        .filter((row) => !row.matches('[data-search-empty]') && !(row.cells.length === 1 && row.cells[0].hasAttribute('colspan')));
    const getEmptyRows = (table) => Array.from(table?.querySelectorAll('tbody tr') || [])
        .filter((row) => !row.matches('[data-search-empty]') && row.cells.length === 1 && row.cells[0].hasAttribute('colspan'));
    const ensureSearchEmptyRow = (table) => {
        if (!table) return null;
        let row = table.querySelector('[data-search-empty]');
        if (row) return row;

        const columnCount = table.tHead?.rows[0]?.cells.length || 1;
        row = document.createElement('tr');
        row.setAttribute('data-search-empty', '');
        row.hidden = true;
        const cell = document.createElement('td');
        cell.colSpan = columnCount;
        cell.className = 'staff-table-empty text-center text-muted py-5';
        cell.textContent = 'No results match your search.';
        row.appendChild(cell);
        table.tBodies[0]?.appendChild(row);

        return row;
    };
    const filterStaffRows = () => {
        const searchTerm = (searchUsers?.value || '').trim().toLowerCase();
        const activePanel = document.querySelector('.au-tab-content.active');
        const activeTable = activePanel?.querySelector('.staff-property-table');
        const rows = getSearchableRows(activeTable);
        const emptyRows = getEmptyRows(activeTable);
        const searchEmptyRow = ensureSearchEmptyRow(activeTable);
        let visibleCount = 0;

        if (rows.length === 0) {
            emptyRows.forEach((row) => { row.style.display = searchTerm ? 'none' : ''; });
            if (searchEmptyRow) searchEmptyRow.hidden = true;
            return;
        }

        emptyRows.forEach((row) => { row.style.display = 'none'; });
        rows.forEach((row) => {
            const isVisible = !searchTerm || (row.textContent || '').toLowerCase().includes(searchTerm);
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount += 1;
        });
        if (searchEmptyRow) searchEmptyRow.hidden = !searchTerm || visibleCount > 0;
    };
    searchUsers?.addEventListener('input', filterStaffRows);

    let activeUsersRequest;
    const loadUsersPage = async (url, updateHistory = true) => {
        if (!usersTable) return;
        const targetUrl = new URL(url, window.location.href);
        if (targetUrl.hostname !== window.location.hostname) return;
        targetUrl.searchParams.delete('table_only');
        const relativeUrl = `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`;

        activeUsersRequest?.abort();
        const request = new AbortController();
        activeUsersRequest = request;
        usersTable.setAttribute('aria-busy', 'true');
        usersTable.classList.add('is-loading');

        const fetchUrl = new URL(relativeUrl, window.location.href);
        fetchUrl.searchParams.set('table_only', '1');

        try {
            const response = await fetch(fetchUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: request.signal,
            });
            if (!response.ok) throw new Error(`Request failed (${response.status}).`);
            const data = await response.json();
            const template = document.createElement('template');
            template.innerHTML = data.html;
            const updatedTable = template.content.querySelector('#usersTableContent');
            if (!updatedTable) throw new Error('User table was not returned.');

            usersTable.innerHTML = updatedTable.innerHTML;
            // Previous local bindings duplicated the delegated handler in global.js:
            // usersTable.querySelectorAll('select[data-auto-submit]').forEach((control) => {
            //     control.addEventListener('change', () => control.form?.requestSubmit());
            // });
            filterStaffRows();
            if (updateHistory) window.history.pushState({}, '', relativeUrl);
        } catch (error) {
            if (error.name !== 'AbortError') console.error('Unable to load users page:', error);
        } finally {
            if (activeUsersRequest === request) {
                activeUsersRequest = null;
                usersTable.removeAttribute('aria-busy');
                usersTable.classList.remove('is-loading');
            }
        }
    };

    usersTable?.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : event.target?.parentElement;
        const link = target?.closest('.au-pagination a, .ljs-pagination a');
        if (!link) return;
        event.preventDefault();
        loadUsersPage(link.href);
    });

    window.addEventListener('popstate', () => loadUsersPage(window.location.href, false));

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

    function syncTeamMemberPicker(form) {
        const leaderSelect = form.querySelector('.team-leader-select');
        if (!leaderSelect) return;

        const selectedLeaderId = leaderSelect.value;
        form.querySelectorAll('.member-checkbox').forEach((checkbox) => {
            const label = checkbox.closest('.au-member-choice');
            const isTaken = checkbox.dataset.taken === 'true';
            const isLeader = selectedLeaderId && checkbox.dataset.userid === selectedLeaderId;

            checkbox.disabled = isTaken || isLeader;
            label?.classList.toggle('is-disabled', isTaken);
            label?.classList.toggle('is-leader', Boolean(isLeader));

            if (isLeader) {
                checkbox.checked = false;
            }
        });
    }

    document.querySelectorAll('form .team-leader-select').forEach((leaderSelect) => {
        const form = leaderSelect.closest('form');
        if (!form) return;

        leaderSelect.addEventListener('change', () => syncTeamMemberPicker(form));
        form.addEventListener('reset', () => setTimeout(() => syncTeamMemberPicker(form), 0));
        syncTeamMemberPicker(form);
    });
});
