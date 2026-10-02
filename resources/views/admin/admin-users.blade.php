@extends('layouts.client')

@section('title', 'Admin Users')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

@section('content')
    <div class="listings-management-container staff-page-surface au-page-card">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @if(isset($editUser))
            <div class="au-edit-panel">
                <h3>Edit {{ $editUser->name }}</h3>
                <form method="POST" action="{{ route('admin.users.update', $editUser->user_id) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row"><div class="col-md-6"><label>Name</label><input class="form-control" name="name" value="{{ $editUser->name }}" required></div><div class="col-md-6"><label>Email</label><input class="form-control" type="email" name="email" value="{{ $editUser->email }}" required></div></div>
                    <div class="row"><div class="col-md-6"><label>Role</label><select class="form-select" name="role_name"><option {{ $editUser->role_name === 'Admin' ? 'selected' : '' }}>Admin</option><option {{ $editUser->role_name === 'Broker' ? 'selected' : '' }}>Broker</option><option {{ $editUser->role_name === 'Agent' ? 'selected' : '' }}>Agent</option><option {{ $editUser->role_name === 'Lead Broker' ? 'selected' : '' }}>Lead Broker</option></select></div><div class="col-md-6"><label>Status</label><select class="form-select" name="account_status"><option {{ $editUser->account_status === 'Active' ? 'selected' : '' }}>Active</option><option {{ $editUser->account_status === 'Inactive' ? 'selected' : '' }}>Inactive</option><option {{ $editUser->account_status === 'Suspended' ? 'selected' : '' }}>Suspended</option></select></div></div>
                    <label for="editCredentialDocument">Credential document (private, PDF/JPG/PNG, max 10 MB)</label>
                    <input class="form-control" id="editCredentialDocument" type="file" name="credential_document" accept=".pdf,.jpg,.jpeg,.png">
                    <button class="au-saveuser-btn" type="submit">Save Changes</button><a class="au-canceluser-btn" href="{{ route('admin.users.index') }}">Cancel</a>
                </form>
            </div>
        @endif
        <header class="staff-property-admin-header">
            <div><h2>Staff management</h2><p>Manage staff accounts and teams.</p></div>
            <button type="button" class="btn-add-property" id="openAddUserModal"><i class="fas fa-plus" aria-hidden="true"></i> Add User</button>
        </header>

        <div class="au-tabs-container staff-property-tabs staff-user-tabs" role="tablist" aria-label="Staff management views">
            <button type="button" class="au-tab-btn {{ isset($team) ? '' : 'active' }}" data-tab="all-users">All Users</button>
            <button type="button" class="au-tab-btn {{ isset($team) ? 'active' : '' }}" data-tab="teams">Teams</button>
        </div>

        <!-- All Users Section -->
        <div class="au-tab-content {{ isset($team) ? '' : 'active' }}" id="all-users">
            <div class="listings-toolbar" id="usersSearchWrapper" @if(isset($team)) hidden @endif>
                <div class="search-box">
                    <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                    <input id="searchUsers" type="search" aria-label="Search staff accounts by name" placeholder="Search by name...">
                </div>
            </div>
            <!-- Add New User Modal -->
            <div class="au-modal-overlay" id="addUserModalOverlay">
                {{-- @if ($errors->any())
                    <div style="color: red; margin-bottom: 15px;">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif --}}
                <div class="au-form-card">
                    <button type="button" class="au-modal-close" id="closeAddUserModal" aria-label="Close">&times;</button>
                    <h3>Add New User</h3>
                    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <h5>Personal Information</h5>
                                <div class="mb-3">
                                    <label for="fullName" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="fullName" name="name" value="{{ old('name') }}" placeholder="Enter full name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Enter email address" required>
                                </div>
                                <div class="mb-3"> 
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="account_status" required>
                                        <option value="">Select a status</option>
                                        <option value="Active" {{ old('account_status') === 'Active' ? 'selected' : '' }}>Active</option>
                                        <option value="Inactive" {{ old('account_status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Account Information -->
                            <div class="col-md-6">
                                <h5>Account Information</h5>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role_name" required>
                                        <option value="">Select a role</option>
                                        <option value="Lead Broker" {{ old('role_name') === 'Lead Broker' ? 'selected' : '' }}>Lead Broker</option>
                                        <option value="Admin" {{ old('role_name') === 'Admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="Broker" {{ old('role_name') === 'Broker' ? 'selected' : '' }}>Broker</option>
                                        <option value="Agent" {{ old('role_name') === 'Agent' ? 'selected' : '' }}>Agent</option>
                                    </select>
                                </div>

                                <!-- Initial Password Input Field -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">Initial Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter initial password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="credentialDocument" class="form-label">Credential document</label>
                                    <input type="file" class="form-control" id="credentialDocument" name="credential_document" accept=".pdf,.jpg,.jpeg,.png">
                                    <small class="text-muted">Private to Admins; PDF/JPG/PNG, max 10 MB.</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Submit Buttons (Ensure you have your submit button here) -->
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="au-table-container">
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
                            @if(isset($users) && $users->count() > 0)
                                @forelse($users as $user)
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
                                            @if($user->agent)
                                                @if ($user->agent->document_file)
                                                    <a href="{{ route('admin.users.credentials', $user) }}" class="badge bg-light text-primary border px-2 py-1">
                                                        <i class="fas fa-file-shield text-success" aria-hidden="true"></i> Download securely
                                                    </a>
                                                @else
                                                    <span class="text-muted">No documents</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-1">{{ $user->role_name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill px-3 py-1 bg-{{ $user->account_status == 'Active' ? 'success-subtle text-success' : ($user->account_status == 'Inactive' ? 'warning-subtle text-warning' : 'danger-subtle text-danger') }}">
                                                {{ $user->account_status }}
                                            </span>
                                        </td>
                                        <td class="text-muted">{{ $user->created_at->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <div class="au-action-group">
                                                <button class="au-deleteuser-btn" data-delete-user="{{ $user->user_id }}" type="button">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">No users found</td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">No users found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if(isset($users) && method_exists($users, 'hasPages') && $users->hasPages())
                    <nav aria-label="Page navigation" class="au-pagination">
                        {{ $users->links() }}
                    </nav>
                @endif
            </div>
        </div>

        <!-- Teams Section -->
        <div class="au-tab-content {{ isset($team) ? 'active' : '' }}" id="teams">
            @if (isset($team))
                @php($selectedMemberIds = old('member_ids', $team->members->pluck('user_id')->all()))
                <div class="au-modal-overlay au-team-edit-modal open" role="presentation">
                    <section class="au-form-card" role="dialog" aria-modal="true" aria-labelledby="editTeamHeading">
                        <a href="{{ route('admin.teams.index') }}" class="au-modal-close" aria-label="Close">&times;</a>
                        <h3 id="editTeamHeading">Edit Team</h3>
                        <p class="au-team-edit-description">Update the team name, leader, and members.</p>
                        <form method="POST" action="{{ route('admin.teams.update', $team->team_id) }}">
                            @csrf @method('PUT')
                            <label class="au-team-field"><span>Team name</span><input class="form-control" name="team_name" value="{{ old('team_name', $team->team_name) }}" maxlength="255" required></label>
                            <label class="au-team-field"><span>Team leader</span><select class="form-select" name="user_id" required>@foreach($allUsers as $user)<option value="{{ $user->user_id }}" @selected((int) old('user_id', $team->user_id) === (int) $user->user_id)>{{ $user->name }} ({{ $user->role_name }})</option>@endforeach</select></label>
                            <fieldset class="au-member-picker-fieldset"><legend>Team members</legend><div class="au-member-picker">
                                @foreach($allUsers as $user)
                                    <label class="au-member-choice"><input type="checkbox" name="member_ids[]" value="{{ $user->user_id }}" @checked(in_array($user->user_id, $selectedMemberIds))><span>{{ $user->name }}</span><small>{{ $user->role_name }}</small></label>
                                @endforeach
                            </div></fieldset>
                            <div class="au-form-actions"><button class="au-saveteam-btn" type="submit">Save Team</button><a class="au-cancelteam-btn" href="{{ route('admin.teams.index') }}">Cancel</a></div>
                        </form>
                    </section>
                </div>
            @endif
            <div class="au-section-header">
                <div>
                    <h2>Teams Management</h2>
                    <p class="text-muted mb-0">You can manage your teams here.</p>
                </div>
                <button class="au-addteam-toggle-btn" id="openAddTeamModal">
                    <i class="fas fa-plus me-1"></i> Add Team
                </button>
            </div>

            <!-- Add Team Modal -->
            <div class="au-modal-overlay" id="addTeamModalOverlay">
                <div class="au-form-card">
                    <button type="button" class="au-modal-close" id="closeAddTeamModal" aria-label="Close">&times;</button>
                    <h3>Add New Team</h3>
                    <form method="POST" action="{{ route('admin.teams.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="teamName" class="form-label">Team Name *</label>
                                    <input type="text" class="form-control" id="teamName" name="team_name" placeholder="Enter team name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="teamLeader" class="form-label">Team Leader *</label>
                                    <select class="form-select" id="teamLeader" name="user_id" required>
                                        <option value="">Select team leader</option>
                                        @foreach($allUsers as $user)
                                            <option value="{{ $user->user_id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <fieldset class="au-member-picker-fieldset"><legend>Team Members</legend><div class="au-member-picker">
                            @foreach($allUsers as $user)
                                <label class="au-member-choice"><input type="checkbox" name="member_ids[]" value="{{ $user->user_id }}"><span>{{ $user->name }}</span><small>{{ $user->role_name }}</small></label>
                            @endforeach
                        </div></fieldset>

                        <div class="au-form-actions">
                            <button type="submit" class="au-saveteam-btn">Create Team</button>
                            <button type="button" class="au-cancelteam-btn" id="cancelAddTeamForm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Teams Table -->
            <div class="au-table-container">
                <div class="staff-property-table-wrap">
                    <table class="staff-property-table au-team-table">
                        <thead>
                            <tr>
                                <th>TEAM ID</th>
                                <th>TEAM NAME</th>
                                <th>LEADER</th>
                                <th>MEMBERS</th>
                                <th>CREATED</th>
                                <th class="text-end">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($teams) && count($teams) > 0)
                                @forelse($teams as $team)
                                    <tr>
                                        <td class="fw-semibold text-muted">#{{ $team->team_id }}</td>
                                        <td class="fw-semibold text-dark">{{ $team->team_name ?? 'N/A' }}</td>
                                        <td>{{ $team->user->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge rounded-pill bg-info-subtle text-info px-3 py-1">{{ $team->members->count() }} members</span>
                                        </td>
                                        <td class="text-muted">{{ $team->created_at->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <div class="au-action-group">
                                                <a href="{{ route('admin.teams.edit', $team->team_id) }}" class="au-editteam-btn">
                                                    <i class="fas fa-pen text-primary"></i>
                                                </a>
                                                <button class="au-deleteteam-btn" data-delete-team="{{ $team->team_id }}" type="button">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">No teams found</td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No teams found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/admin-users.js') }}?v={{ filemtime(public_path('js/admin-users.js')) }}"></script>
@endsection