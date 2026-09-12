@extends('layouts.client')

@section('title', 'Admin Users')

@section('content')
    <div class="au-page-card">
        <!-- Toggle Tabs -->
        <div class="au-tabs-container">
            <button class="au-tab-btn active" data-tab="all-users">
                <i class="fas fa-users me-2"></i>All Users
            </button>
            <button class="au-tab-btn" data-tab="teams">
                <i class="fas fa-layer-group me-2"></i>Teams
            </button>
        </div>

        <!-- All Users Section -->
        <div class="au-tab-content active" id="all-users">
            <div class="au-section-header">
                <div>
                    <h2>User Management</h2>
                    <p class="text-muted mb-0">You can manage your system users here.</p>
                </div>
                <button class="au-adduser-toggle-btn" id="openAddUserModal">
                    <i class="fas fa-plus me-1"></i> Add User
                </button>
            </div>

            <!-- Add User Modal -->
            <div class="au-modal-overlay" id="addUserModalOverlay">
                <div class="au-form-card">
                    <button type="button" class="au-modal-close" id="closeAddUserModal" aria-label="Close">&times;</button>
                    <h3>Add New User</h3>
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <h5>Personal Information</h5>
                                <div class="mb-3">
                                    <label for="fullName" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="fullName" name="name" placeholder="Enter full name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contactNumber" class="form-label">Contact Number *</label>
                                    <input type="tel" class="form-control" id="contactNumber" name="contact_number" placeholder="Enter contact number" required>
                                </div>
                            </div>

                            <!-- Account Information -->
                            <div class="col-md-6">
                                <h5>Account Information</h5>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role_name" required>
                                        <option value="">Select a role</option>
                                        <option value="Lead Broker">Lead Broker</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Broker">Broker</option>
                                        <option value="Agent">Agent</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="account_status" required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                        <option value="Suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dateJoined" class="form-label">Date Joined</label>
                                    <input type="date" class="form-control" id="dateJoined" name="date_joined">
                                </div>
                            </div>
                        </div>

                        <div class="au-form-actions">
                            <button type="submit" class="au-saveuser-btn">Save User</button>
                            <button type="button" class="au-canceluser-btn" id="cancelAddUserForm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="au-table-container">
                <div class="au-search-wrapper">
                    <input type="text" class="form-control au-search-input" id="searchUsers" placeholder="🔍 Search by name...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>USER</th>
                                <th>EMAIL</th>
                                <th>CONTACT NUMBER</th>
                                <th>PASSWORD</th>
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
                                                <img src="{{ $user->profile_photo ?? 'https://via.placeholder.com/40' }}" alt="{{ $user->name }}" class="rounded-circle shadow-sm" width="40" height="40">
                                                <span class="fw-semibold text-dark">{{ $user->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted">{{ $user->email }}</td>
                                        <td>{{ $user->contact_number }}</td>
                                        <td><i class="fas fa-lock text-muted"></i></td>
                                        <td>
                                            @if($user->agent)
                                                <a href="#" class="badge bg-light text-primary border px-2 py-1">
                                                    <i class="fas fa-file-pdf text-danger"></i> Agent documents.pdf
                                                </a>
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
                                                <a href="{{ route('admin.users.edit', $user->user_id) }}" class="au-edituser-btn">
                                                    <i class="fas fa-pen text-primary"></i>
                                                </a>
                                                <button class="au-deleteuser-btn" onclick="deleteUser({{ $user->user_id }})">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">No users found</td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">No users found</td>
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
        <div class="au-tab-content" id="teams">
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

                        <div class="au-form-actions">
                            <button type="submit" class="au-saveteam-btn">Create Team</button>
                            <button type="button" class="au-cancelteam-btn" id="cancelAddTeamForm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Teams Table -->
            <div class="au-table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
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
                                                <button class="au-deleteteam-btn" onclick="deleteTeam({{ $team->team_id }})">
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

    <link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">
    <script src="{{ asset('js/admin-users.js') }}"></script>
@endsection