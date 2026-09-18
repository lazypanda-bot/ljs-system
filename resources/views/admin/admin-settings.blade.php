@extends('layouts.client')

@section('title', 'Admin Settings')

@section('content')
<div class="settings-page-wrapper">
    <!-- Top Horizontal Tab Bar -->
    <div class="settings-tab-bar">
        <button class="settings-tab-btn active" data-target="profile-section">
            <svg class="tab-icon" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Profile
        </button>
        <button class="settings-tab-btn" data-target="password-section">
            <svg class="tab-icon" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Password
        </button>
    </div>

    <!-- Main Card Container -->
    <div class="settings-main-card">
        <!-- Profile Tab Content -->
        <section id="profile-section" class="settings-panel active">
            <div class="section-header-row">
                <div>
                    <h2>Profile Settings</h2>
                    <p class="section-subtitle">Update your account's profile information and email address.</p>
                </div>
            </div>

            <form id="profile-form" class="settings-form">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="{{ auth()->user()->name ?? 'Admin User' }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ auth()->user()->email ?? 'admin@ljs.com' }}" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-green">Save Changes</button>
                </div>
            </form>

            <div class="danger-zone-box">
                <h3>Delete Account</h3>
                <p class="section-subtitle">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
                <button type="button" class="btn-danger-outline">Delete Account</button>
            </div>
        </section>

        <!-- Password Tab Content -->
        <section id="password-section" class="settings-panel">
            <div class="section-header-row">
                <div>
                    <h2>Password Settings</h2>
                    <p class="section-subtitle">Change your account password securely.</p>
                </div>
            </div>

            <form id="password-form" class="settings-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="current_password" name="current_password" placeholder="Enter your current password" required>
                        <button type="button" class="toggle-password" data-target="current_password">👁</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your new password" required>
                        <button type="button" class="toggle-password" data-target="password">👁</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password" required>
                        <button type="button" class="toggle-password" data-target="password_confirmation">👁</button>
                    </div>
                </div>

                <div class="password-requirements-box">
                    <div class="req-title">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Password Requirements:
                    </div>
                    <ul>
                        <li id="req-length">Minimum 8 characters</li>
                        <li id="req-upper">At least 1 uppercase letter</li>
                        <li id="req-number">At least 1 number</li>
                        <li id="req-special">At least 1 special character</li>
                    </ul>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-green">Save Password</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/settings.js') }}"></script>
@endpush