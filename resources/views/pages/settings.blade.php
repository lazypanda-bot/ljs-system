@extends(auth()->user()?->normalizedRole() === 'client' ? 'layouts.app' : 'layouts.client')

@section('title', $pageTitle ?? 'Settings')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/settings.css') }}?v={{ filemtime(public_path('css/settings.css')) }}">
@endpush

@php
	$authUser = auth()->user();
	$isProfessional = $authUser?->isProfessional();
	$isClient = $authUser?->normalizedRole() === 'client';
	$profile = $authUser?->professionalProfile;
@endphp

@section('content')
	<div class="settings-page-wrapper">
		@if ($errors->any())
			<div class="settings-errors" role="alert">{{ $errors->first() }}</div>
		@endif
		<div class="settings-tab-bar">
			<button class="settings-tab-btn active" id="profile-tab" data-target="profile-section" type="button" role="tab" aria-controls="profile-section" aria-selected="true"><i class="fas fa-user tab-icon"></i> Profile</button>
			<button class="settings-tab-btn" id="password-tab" data-target="password-section" type="button" role="tab" aria-controls="password-section" aria-selected="false"><i class="fas fa-lock tab-icon"></i> Password</button>
		</div>
		<div class="settings-main-card">
			<section id="profile-section" class="settings-panel active" role="tabpanel" aria-labelledby="profile-tab">
				<div class="section-header-row">
					<div>
						<h2>Profile Settings</h2>
						<p class="section-subtitle">Keep your account details current. Fields below match what your role uses in the system.</p>
					</div>
				</div>
				<form id="profile-form" class="settings-form" method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data">
					@csrf
					<div class="settings-profile-layout">
						<div class="settings-avatar-editor">
							<div class="settings-avatar-preview role-{{ $authUser?->normalizedRole() }}">
								@if ($authUser?->profile_photo)
									<img src="{{ str_starts_with($authUser->profile_photo, 'http') ? $authUser->profile_photo : asset('storage/' . $authUser->profile_photo) }}" alt="Current profile photo">
								@else
									<span>{{ strtoupper(substr($authUser?->name ?? 'U', 0, 1)) }}</span>
								@endif
							</div>
							<button type="button" class="settings-avatar-edit" id="profilePhotoEdit">Edit</button>
							<input class="settings-photo-input" type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp">
							<p>JPG, PNG, or WebP, up to 5 MB.</p>
						</div>
						<div class="settings-profile-details">
							<div class="settings-form-grid">
								<div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="name" value="{{ old('name', $authUser?->name) }}" required></div>
								<div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" value="{{ old('email', $authUser?->email) }}" required></div>
							</div>
							<div class="form-group"><label for="contact_number">Contact Number</label><input type="tel" id="contact_number" name="contact_number" value="{{ old('contact_number', $authUser?->contact_number) }}" maxlength="20" placeholder="Add your contact number"></div>
							<div class="form-group">
								<label for="timezone">Time zone</label>
								@php
									$timezoneValue = old('timezone', $authUser?->timezone ?? config('appointments.default_timezone'));
									$timezoneOptions = [
										'Asia/Manila' => 'Philippine Time',
										'America/Los_Angeles' => 'Pacific Time',
										'America/Denver' => 'Mountain Time',
										'America/Chicago' => 'Central Time',
										'America/New_York' => 'Eastern Time',
										'Europe/London' => 'United Kingdom Time',
										'Europe/Paris' => 'Central European Time',
										'Australia/Sydney' => 'Australian Eastern Time',
										'UTC' => 'UTC',
									];
									if (! array_key_exists($timezoneValue, $timezoneOptions)) {
										$timezoneOptions[$timezoneValue] = 'Current saved time zone';
									}
								@endphp
								<select id="timezone" name="timezone" required>
									@foreach ($timezoneOptions as $timezoneId => $timezoneLabel)
										<option value="{{ $timezoneId }}" @selected($timezoneValue === $timezoneId)>{{ $timezoneLabel }}</option>
									@endforeach
								</select>
								<small>Used to display your appointment times locally.</small>
							</div>

							@if ($isProfessional)
								<div class="settings-subsection">
									<h3>Professional credentials</h3>
									<p class="section-subtitle">License, experience, and documents shown on your public profile and used for verification.</p>
								</div>
								<div class="settings-form-grid">
									<div class="form-group">
										<label for="license_number">License / PRC number</label>
										<input type="text" id="license_number" name="license_number" value="{{ old('license_number', $profile?->license_number) }}" maxlength="100" placeholder="PRC or license number">
									</div>
									<div class="form-group">
										<label for="years_experience">Years of experience</label>
										<input type="number" id="years_experience" name="years_experience" min="0" max="80" value="{{ old('years_experience', $profile?->years_experience) }}" placeholder="0">
									</div>
								</div>
								<div class="form-group">
									<label for="professional_bio">Professional bio</label>
									<textarea id="professional_bio" name="professional_bio" rows="4" maxlength="2000" placeholder="A short introduction for clients">{{ old('professional_bio', $profile?->professional_bio) }}</textarea>
								</div>
								<div class="form-group">
									<label for="credential_document">Credential document</label>
									<input type="file" id="credential_document" name="credential_document" accept=".pdf,image/jpeg,image/png">
									<small>
										PDF, JPG, or PNG, up to 10 MB. Stored privately.
										@if ($profile?->document_file)
											<span class="settings-doc-on-file">A document is on file.</span>
											<a href="{{ route('settings.credentials.download') }}">Download current file</a>
										@endif
									</small>
								</div>
							@endif

							<div class="form-actions"><button type="submit" class="btn-green">Save Changes</button></div>
						</div>
					</div>
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
				@if ($isClient)
					<div class="settings-history">
						<div class="settings-subsection">
							<h3>Appointment history</h3>
								<p class="section-subtitle">Your scheduled appointments, displayed in {{ $authUser?->timezone ?? config('appointments.default_timezone') }}.</p>
								<a href="{{ route('appointments.index') }}" class="settings-appointment-link">Open appointment schedule</a>
						</div>
						@forelse ($appointments ?? [] as $appointment)
							<div class="settings-appointment-item">
								<div>
									<p class="settings-appointment-title">{{ $appointment->assignment?->property?->property_name ?? 'Property viewing' }}</p>
									<small>{{ $appointment->startInTimezone($authUser?->timezone ?? config('appointments.default_timezone'))->format('M j, Y · g:i A') }}{{ $appointment->appointment_type ? ' · ' . $appointment->appointment_type : '' }}</small>
								</div>
								<span class="settings-badge settings-badge-{{ strtolower($appointment->appointment_status ?? 'pending') }}">{{ $appointment->appointment_status ?? 'Pending' }}</span>
							</div>
						@empty
							<p class="settings-empty">No appointments booked yet.</p>
						@endforelse
					</div>
				@endif
			</section>
			<section id="password-section" class="settings-panel" role="tabpanel" aria-labelledby="password-tab" hidden>
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
	<script src="{{ asset('js/settings.js') }}?v={{ filemtime(public_path('js/settings.js')) }}"></script>
@endpush
