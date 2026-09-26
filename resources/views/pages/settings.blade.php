@extends('layouts.client')

@section('title', $pageTitle ?? 'Settings')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endpush

@section('content')
	<div class="settings-page-wrapper">
		@if ($errors->any())
			<div class="settings-errors" role="alert">{{ $errors->first() }}</div>
		@endif
		<div class="settings-tab-bar">
			<button class="settings-tab-btn active" data-target="profile-section" type="button"><i class="fas fa-user tab-icon"></i> Profile</button>
			<button class="settings-tab-btn" data-target="password-section" type="button"><i class="fas fa-lock tab-icon"></i> Password</button>
		</div>
		<div class="settings-main-card">
			<section id="profile-section" class="settings-panel active">
				<div class="section-header-row"><div><h2>Profile Settings</h2><p class="section-subtitle">Update your account's profile information and email address.</p></div></div>
				<form id="profile-form" class="settings-form" method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data">
					@csrf
					<div class="settings-avatar-editor">
						<div class="settings-avatar-preview role-{{ auth()->user()?->normalizedRole() }}">
							@if (auth()->user()?->profile_photo)
								<img src="{{ str_starts_with(auth()->user()->profile_photo, 'http') ? auth()->user()->profile_photo : asset('storage/' . auth()->user()->profile_photo) }}" alt="Current profile photo">
							@else
								<span>{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}</span>
							@endif
						</div>
						<div><label for="profile_photo">Profile photo</label><input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, or WebP, up to 5 MB.</small></div>
					</div>
					<div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" value="{{ auth()->user()?->name }}" required></div>
					<div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" value="{{ auth()->user()?->email }}" required></div>
					<div class="form-group"><label for="contact_number">Contact Number</label><input type="tel" id="contact_number" name="contact_number" value="{{ auth()->user()?->contact_number }}" maxlength="20" placeholder="Add your contact number"></div>
					<div class="form-actions"><button type="submit" class="btn-green">Save Changes</button></div>
				</form>
				<div class="danger-zone-box">
					<h3>Delete Account</h3>
					<p class="section-subtitle">Your account will be deactivated. Enter your password and type DELETE to confirm.</p>
					<form method="POST" action="{{ route('settings.account.destroy') }}" class="settings-form" data-confirm-message="Deactivate your account? You will be signed out immediately.">
						@csrf @method('DELETE')
						<div class="form-group"><label for="delete_current_password">Current Password</label><input type="password" id="delete_current_password" name="current_password" required autocomplete="current-password"></div>
						<div class="form-group"><label for="delete_confirmation">Type DELETE to confirm</label><input type="text" id="delete_confirmation" name="confirmation" required autocomplete="off"></div>
						<button type="submit" class="btn-danger-outline">Deactivate Account</button>
					</form>
				</div>
			</section>
			<section id="password-section" class="settings-panel">
				<div class="section-header-row"><div><h2>Password Settings</h2><p class="section-subtitle">Change your account password securely.</p></div></div>
				<form id="password-form" class="settings-form" method="POST" action="{{ route('settings.password.update') }}">
					@csrf
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
