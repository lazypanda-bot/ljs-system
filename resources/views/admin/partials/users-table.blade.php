<div class="au-table-container" id="usersTableContent">
    <div class="staff-property-table-wrap">
        <table class="staff-property-table au-users-table">
            <thead>
                <tr>
                    <th>USER</th>
                    <th>EMAIL</th>
                    <th>CONTACT NUMBER</th>
                    <th>CREDENTIALS</th>
                    <th>ROLE</th>
                    <th>STATUS</th>
                    <th>DATE JOINED</th>
                    <th class="text-end">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @if ($users && $users->count() > 0)
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <div class="au-user-cell">
                                    @if ($user->profile_photo)
                                        <img src="{{ str_starts_with($user->profile_photo, 'http') ? $user->profile_photo : asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}" class="rounded-circle shadow-sm" width="40" height="40">
                                    @else
                                        <span class="au-user-avatar role-{{ $user->normalizedRole() }}" aria-label="{{ $user->name }}">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                    <span class="fw-semibold text-dark">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="text-muted">{{ $user->email }}</td>
                            <td>{{ $user->contact_number }}</td>
                            <td>
                                @php($credentialFile = $user->professionalProfile?->document_file)
                                @if ($credentialFile)
                                    <button type="button" class="au-view-credential-btn" data-view-credential="{{ route('admin.users.credentials', $user) }}" data-credential-type="{{ strtolower(pathinfo($credentialFile, PATHINFO_EXTENSION)) === 'pdf' ? 'pdf' : 'image' }}" data-credential-owner="{{ $user->name }}">
                                        <i class="fas fa-file-shield" aria-hidden="true"></i><span>View credential</span>
                                    </button>
                                @else
                                    <span class="text-muted">No documents</span>
                                @endif
                            </td>
                            <td><span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-1">{{ $user->role_name }}</span></td>
                            <td>
                                {{-- Previous self-only restriction retained for reference:
                                @if ((int) $user->user_id === (int) auth()->id())
                                    <span class="badge rounded-pill px-3 py-1 bg-success-subtle text-success">{{ $user->account_status }}</span>
                                --}}
                                @php($protectedLeadBroker = auth()->user()?->normalizedRole() === 'admin' && $user->normalizedRole() === 'lead_broker')
                                @if ((int) $user->user_id === (int) auth()->id() || $protectedLeadBroker)
                                    <span class="badge rounded-pill px-3 py-1 {{ $user->account_status === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}" @if ($protectedLeadBroker) title="Only a Lead Broker can change this account's status." @endif>{{ $user->account_status }}</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.update', $user->user_id) }}" class="au-status-form">
                                        @csrf @method('PUT')
                                        <select name="account_status" class="au-status-select status-{{ strtolower($user->account_status) }}" aria-label="Account status for {{ $user->name }}" data-auto-submit>
                                            @foreach (['Active', 'Inactive'] as $statusOption)
                                                <option value="{{ $statusOption }}" @selected($user->account_status === $statusOption)>{{ $statusOption }}</option>
                                            @endforeach
                                            @unless (in_array($user->account_status, ['Active', 'Inactive'], true))
                                                <option value="{{ $user->account_status }}" selected disabled>{{ $user->account_status }}</option>
                                            @endunless
                                        </select>
                                    </form>
                                @endif
                            </td>
                            <td class="text-muted">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="text-end">
                                <div class="au-action-group">
                                    <button class="au-deleteuser-btn" data-delete-user="{{ $user->user_id }}" type="button" aria-label="Delete {{ $user->name }}">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="8" class="text-center text-muted py-5">No users found</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    @if ($users && $users->hasPages())
        {{ $users->links('pagination.ljs') }}
    @endif
</div>
