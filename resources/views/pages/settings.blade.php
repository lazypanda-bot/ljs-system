@extends('layouts.client')

@section('title', $pageTitle ?? 'Settings')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endpush

@section('content')
	<div class="settings-page-wrapper">
		<div class="settings-tab-bar">
			<button class="settings-tab-btn active" data-target="profile-section" type="button"><i class="fas fa-user tab-icon"></i> Profile</button>
			<button class="settings-tab-btn" data-target="password-section" type="button"><i class="fas fa-lock tab-icon"></i> Password</button>
		</div>
		<div class="settings-main-card">
			<section id="profile-section" class="settings-panel active">
				<div class="section-header-row"><div><h2>Profile Settings</h2><p class="section-subtitle">Update your account's profile information and email address.</p></div></div>
				<form id="profile-form" class="settings-form" method="POST" action="{{ route('settings.profile.update') }}">
					@csrf
					<div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" value="{{ auth()->user()?->name }}" required></div>
					<div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" value="{{ auth()->user()?->email }}" required></div>
					<div class="form-group"><label for="contact_number">Contact Number</label><input type="tel" id="contact_number" name="contact_number" value="{{ auth()->user()?->contact_number }}" maxlength="20" placeholder="Add your contact number"></div>
					<div class="form-actions"><button type="submit" class="btn-green">Save Changes</button></div>
				</form>
				<div class="danger-zone-box"><h3>Delete Account</h3><p class="section-subtitle">Once your account is deleted, all of its resources and data will be permanently deleted.</p><button type="button" class="btn-danger-outline">Delete Account</button></div>
			</section>
			<section id="password-section" class="settings-panel">
				<div class="section-header-row"><div><h2>Password Settings</h2><p class="section-subtitle">Change your account password securely.</p></div></div>
				<form id="password-form" class="settings-form">
					@foreach ([['current_password', 'Current Password', 'Enter your current password'], ['password', 'New Password', 'Enter your new password'], ['password_confirmation', 'Confirm New Password', 'Confirm new password']] as [$field, $label, $placeholder])
						<div class="form-group"><label for="{{ $field }}">{{ $label }}</label><div class="password-input-wrapper"><input type="password" id="{{ $field }}" name="{{ $field }}" placeholder="{{ $placeholder }}" required><button type="button" class="toggle-password" data-target="{{ $field }}" aria-label="Show {{ strtolower($label) }}"><i class="fas fa-eye"></i></button></div></div>
					@endforeach
					<div class="password-requirements-box"><div class="req-title"><i class="fas fa-shield-alt"></i> Password Requirements:</div><ul><li id="req-length">Minimum 8 characters</li><li id="req-upper">At least 1 uppercase letter</li><li id="req-number">At least 1 number</li><li id="req-special">At least 1 special character</li></ul></div>
					<div class="form-actions"><button type="submit" class="btn-green">Save Password</button></div>
				</form>
			</section>
		</div>
	</div>
@endsection

@push('scripts')
	<script src="{{ asset('js/settings.js') }}"></script>
@endpush
