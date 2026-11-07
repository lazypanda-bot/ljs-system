@extends('layouts.client')

@section('title', 'Admin Users')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

@section('content')
    <div data-page-context="users" class="listings-management-container staff-page-surface au-page-card">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @php($userFailed = $errors->any() && old('role_name') !== null)
        @php($teamFailed = $errors->any() && old('role_name') === null && old('user_id') !== null && ! isset($team))
        {{-- Previous default: @php($showTeams = isset($team) || $teamFailed) --}}
        @php($showTeams = isset($team) || $teamFailed || request('tab') === 'teams')
        <header class="staff-property-admin-header">
            <div><h2>Staff management</h2><p>Manage staff accounts and teams.</p></div>
        </header>

        <div class="au-tabs-container staff-property-tabs staff-user-tabs" role="tablist" aria-label="Staff management views">
            <button type="button" class="au-tab-btn {{ $showTeams ? '' : 'active' }}" data-tab="all-users">All Users</button>
            <button type="button" class="au-tab-btn {{ $showTeams ? 'active' : '' }}" data-tab="teams">Teams</button>
        </div>

        <div class="listings-toolbar" id="usersSearchWrapper">
            <div class="search-box">
                <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                <input id="searchUsers" type="search" aria-label="Search users or teams" placeholder="{{ $showTeams ? 'Search teams...' : 'Search by name...' }}">
            </div>
            <button type="button" class="btn-add-property" id="openAddUserModal" @if($showTeams) hidden @endif><i class="fas fa-plus" aria-hidden="true"></i> Add User</button>
            <button type="button" class="btn-add-property" id="openAddTeamModal" @if(! $showTeams) hidden @endif><i class="fas fa-plus" aria-hidden="true"></i> Add Team</button>
        </div>

        <!-- All Users Section -->
        <div class="au-tab-content {{ $showTeams ? '' : 'active' }}" id="all-users">
            <!-- Add New User Modal -->
            <div class="au-modal-overlay {{ $userFailed ? 'open' : '' }}" id="addUserModalOverlay">
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
                    @if ($userFailed)
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <h5>Personal Information</h5>
                                <div class="mb-3">
                                    <label for="fullName" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="fullName" name="name" value="{{ old('name') }}" placeholder="Enter full name" required minlength="2" maxlength="100" pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ .'\-]*" title="Letters, spaces, apostrophes, hyphens and periods only">
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
                                        @if (auth()->user()?->normalizedRole() === 'lead_broker')
                                            <option value="Admin" {{ old('role_name') === 'Admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="Lead Broker" {{ old('role_name') === 'Lead Broker' ? 'selected' : '' }}>Lead Broker</option>
                                            <option value="Broker" {{ old('role_name') === 'Broker' ? 'selected' : '' }}>Broker</option>
                                            <option value="Agent" {{ old('role_name') === 'Agent' ? 'selected' : '' }}>Agent</option>
                                        @else
                                            <option value="Broker" {{ old('role_name') === 'Broker' ? 'selected' : '' }}>Broker</option>
                                            <option value="Agent" {{ old('role_name') === 'Agent' ? 'selected' : '' }}>Agent</option>
                                        @endif
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

            @include('admin.partials.users-table', ['users' => $users ?? null])
        </div>

        <!-- Teams Section -->
        <div class="au-tab-content {{ $showTeams ? 'active' : '' }}" id="teams">
            @if (isset($team))
                @php($selectedMemberIds = old('member_ids', $team->members->pluck('user_id')->all()))
                @php($currentTeamUserIds = $team->members->pluck('user_id')->push($team->user_id)->unique()->all())
                <div class="au-modal-overlay au-team-edit-modal open" role="presentation">
                    <section class="au-form-card" role="dialog" aria-modal="true" aria-labelledby="editTeamHeading">
                        <h3 id="editTeamHeading">Edit Team</h3>
                        <p class="au-team-edit-description">Update the team name, leader, and members.</p>
                        <form method="POST" action="{{ route('admin.teams.update', $team->team_id) }}">
                            @csrf @method('PUT')
                                <label class="au-team-field"><span>Team name</span><input class="form-control" name="team_name" value="{{ old('team_name', $team->team_name) }}" maxlength="255" required></label>
                                <label class="au-team-field"><span>Team leader</span><select class="form-select team-leader-select" name="user_id" required>
                                    @foreach($allUsers->whereIn('role_name', ['Broker', 'Lead Broker']) as $user)
                                        @php($isTakenOutsideTeam = in_array($user->user_id, $takenUserIds ?? [], true) && ! in_array($user->user_id, $currentTeamUserIds, true))
                                        <option value="{{ $user->user_id }}" @selected((int) old('user_id', $team->user_id) === (int) $user->user_id) @disabled($isTakenOutsideTeam)>
                                            {{ $user->name }} ({{ $user->role_name }}){{ $isTakenOutsideTeam ? ' - Already in team' : '' }}
                                        </option>
                                    @endforeach</select>
                                </label>
                            <fieldset class="au-member-picker-fieldset"><legend>Team members</legend>
                                <div class="au-member-picker">
                                    @foreach($allUsers as $user)
                                        @php($isTakenOutsideTeam = in_array($user->user_id, $takenUserIds ?? [], true) && ! in_array($user->user_id, $currentTeamUserIds, true))
                                        <label class="au-member-choice {{ $isTakenOutsideTeam ? 'is-disabled' : '' }}">
                                            <input type="checkbox" class="member-checkbox" name="member_ids[]" value="{{ $user->user_id }}" data-userid="{{ $user->user_id }}" data-taken="{{ $isTakenOutsideTeam ? 'true' : 'false' }}"
                                                @checked(in_array($user->user_id, $selectedMemberIds))
                                                @disabled($isTakenOutsideTeam)>
                                            <span>{{ $user->name }}</span>
                                            <small>{{ $isTakenOutsideTeam ? $user->role_name . ' - Already in team' : $user->role_name }}</small>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div class="au-form-actions"><button class="au-saveteam-btn" type="submit">Save Team</button><a class="au-cancelteam-btn" href="{{ route('admin.teams.index') }}">Cancel</a></div>
                        </form>
                    </section>
                </div>
            @endif
            
            {{-- WORKING --}}
            <!-- Add Team Modal -->
            <div class="au-modal-overlay {{ $teamFailed ? 'open' : '' }}" id="addTeamModalOverlay">
                <div class="au-form-card">
                    <h3>Add New Team</h3>
                    @if ($teamFailed)
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif
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
                                    <select class="form-select team-leader-select" id="teamLeader" name="user_id" required>
                                        <option value="">Select team leader (Broker or Lead Broker)</option>
                                        @foreach($allUsers->whereIn('role_name', ['Broker', 'Lead Broker']) as $user)
                                            @php($isTaken = in_array($user->user_id, $takenUserIds ?? [], true))
                                            <option value="{{ $user->user_id }}" @selected((int) old('user_id') === (int) $user->user_id) @disabled($isTaken)>
                                                {{ $user->name }}{{ $isTaken ? ' - Already in team' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <fieldset class="au-member-picker-fieldset"><legend>Team Members</legend>
                            <div class="au-member-picker">
                                @foreach($allUsers as $user)
                                    @php($isTaken = in_array($user->user_id, $takenUserIds ?? [], true))
                                    <label class="au-member-choice {{ $isTaken ? 'is-disabled' : '' }}">
                                        <input type="checkbox" class="member-checkbox" name="member_ids[]" value="{{ $user->user_id }}" data-userid="{{ $user->user_id }}" data-taken="{{ $isTaken ? 'true' : 'false' }}"
                                            @checked(in_array($user->user_id, old('member_ids', [])))
                                            @disabled($isTaken)>
                                        <span>{{ $user->name }}</span>
                                        <small>{{ $isTaken ? $user->role_name . ' - Already in team' : $user->role_name }}</small>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

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
                                            <button type="button" class="au-members-trigger" data-team-members='@json($team->members->map(fn ($member) => ['name' => $member->user?->name ?? 'Unknown', 'role' => $member->user?->role_name, 'status' => $member->status])->values())' data-team-name="{{ $team->team_name ?? 'Team' }}" data-team-leader="{{ $team->user?->name }}">{{ $team->members->count() }} members</button>
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

    <div class="au-modal-overlay" id="teamMembersOverlay" aria-hidden="true">
        <div class="au-form-card" role="dialog" aria-modal="true" aria-labelledby="teamMembersTitle">
            <button type="button" class="au-modal-close" id="closeTeamMembers" aria-label="Close">&times;</button>
            <h3 id="teamMembersTitle">Team members</h3>
            <p class="text-muted mb-0" id="teamMembersLeader"></p>
            <ul class="au-members-list" id="teamMembersList"></ul>
        </div>
    </div>

    <div class="au-modal-overlay" id="credentialViewerOverlay" aria-hidden="true">
        <div class="au-form-card au-credential-viewer" role="dialog" aria-modal="true" aria-labelledby="credentialViewerTitle">
            <button type="button" class="au-modal-close" id="closeCredentialViewer" aria-label="Close">&times;</button>
            <h3 id="credentialViewerTitle">Credential</h3>
            <img id="credentialViewerImage" alt="Uploaded credential" hidden>
            <iframe id="credentialViewerPdf" title="Uploaded credential" hidden></iframe>
        </div>
    </div>

    <script src="{{ asset('js/admin-users.js') }}?v={{ filemtime(public_path('js/admin-users.js')) }}"></script>
@endsection
