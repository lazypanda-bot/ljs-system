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
	$credentialLabel = $authUser?->role_name === 'Agent' ? 'Accreditation number' : 'PRC license number';
	$credentialPlaceholder = $authUser?->role_name === 'Agent' ? 'Accreditation number' : 'PRC license number';
	$missingCredential = $isProfessional && blank($profile?->license_number);
@endphp

@section('content')
	<div class="settings-page-wrapper">
		@if ($errors->any())
			<div class="settings-errors" role="alert">{{ $errors->first() }}</div>
		@endif
		<div class="settings-tab-bar">
			@if ($isClient)
				<button class="settings-tab-btn active" id="profile-tab" data-target="profile-section" type="button" role="tab" aria-controls="profile-section" aria-selected="true"><i class="fas fa-user tab-icon"></i> Account</button>
				<button class="settings-tab-btn" id="history-tab" data-target="history-section" type="button" role="tab" aria-controls="history-section" aria-selected="false"><i class="fas fa-calendar-check tab-icon"></i> Appointment History</button>
			@else
				<button class="settings-tab-btn active" id="profile-tab" data-target="profile-section" type="button" role="tab" aria-controls="profile-section" aria-selected="true"><i class="fas fa-user tab-icon"></i> Profile</button>
				<button class="settings-tab-btn" id="password-tab" data-target="password-section" type="button" role="tab" aria-controls="password-section" aria-selected="false"><i class="fas fa-lock tab-icon"></i> Password</button>
			@endif
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
							<div class="settings-avatar-preview role-{{ $authUser?->normalizedRole() }}" data-avatar-initial="{{ strtoupper(substr($authUser?->name ?? 'U', 0, 1)) }}">
								@if ($authUser?->profile_photo)
									<img src="{{ str_starts_with($authUser->profile_photo, 'http') ? $authUser->profile_photo : asset('storage/' . $authUser->profile_photo) }}" alt="Current profile photo">
								@else
									<span>{{ strtoupper(substr($authUser?->name ?? 'U', 0, 1)) }}</span>
								@endif
							</div>
							<div class="settings-avatar-actions">
								<button type="button" class="settings-avatar-edit" id="profilePhotoEdit">Edit</button>
								<button type="button" class="settings-avatar-remove" id="profilePhotoRemove" @if (! $authUser?->profile_photo) hidden @endif>Remove</button>
							</div>
							<input class="settings-photo-input" type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp">
							<input type="hidden" id="remove_profile_photo" name="remove_profile_photo" value="0">
							<p>JPG, PNG, or WebP, up to 5 MB.</p>
						</div>
						<div class="settings-profile-details">
							<div class="settings-form-grid">
								<div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="name" value="{{ old('name', $authUser?->name) }}" required minlength="2" maxlength="100" pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ .'\-]*" title="Letters, spaces, apostrophes, hyphens and periods only"></div>
								<div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" value="{{ old('email', $authUser?->email) }}" required></div>
							</div>
							<div class="form-group"><label for="contact_number">Contact Number</label><input type="tel" id="contact_number" name="contact_number" value="{{ old('contact_number', $authUser?->contact_number) }}" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" title="11 digits, e.g. 09171234567" placeholder="e.g. 09171234567"></div>
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
								@if ($missingCredential)
									<div class="settings-reminder" role="alert">
										<i class="fas fa-circle-exclamation" aria-hidden="true"></i>
										<div>
											<strong>Profile reminder</strong>
											<p>
												Please input your {{ strtolower($credentialLabel) }}.
											</p>
										</div>
									</div>
								@endif
								<div class="form-group">
									<label for="license_number">{{ $credentialLabel }}</label>
									<input type="text" id="license_number" name="license_number" value="{{ old('license_number', $profile?->license_number) }}" maxlength="100" pattern="[A-Za-z0-9\-\/ ]+" title="Letters, numbers, spaces, hyphens and slashes only" placeholder="{{ $credentialPlaceholder }}" required>
								</div>
								<div class="form-group">
									<label for="professional_bio">Professional bio</label>
									<textarea id="professional_bio" name="professional_bio" rows="4" maxlength="2000" placeholder="A short introduction for clients">{{ old('professional_bio', $profile?->professional_bio) }}</textarea>
								</div>
								<div class="form-group">
									<label for="credential_document">Credential document</label>
									@if ($profile?->document_file)
										<p class="settings-doc-on-file"><i class="fas fa-file-shield" aria-hidden="true"></i> A credential document is on file. <a href="{{ route('settings.credentials.download') }}" target="_blank" rel="noopener">View document</a></p>
										<small>Credentials are added when your account is created. Contact an admin to change it.</small>
									@else
										<input type="file" id="credential_document" name="credential_document" accept=".pdf,image/jpeg,image/png">
										<small>No credential has been added to your account yet. Upload a PDF, JPG, or PNG (up to 10 MB); it is stored privately and visible to admins.</small>
									@endif
								</div>
							@endif

							<div class="form-actions"><button type="submit" class="btn-green">Save Changes</button></div>
						</div>
					</div>
				</form>

				@if ($isClient)
					<div class="settings-subsection settings-password-block">
						<h3>Password</h3>
						<p class="section-subtitle">Change your account password securely.</p>
					</div>
					@include('pages.settings._password-form')
				@endif

				@include('pages.settings._delete-account')
			</section>
			@if ($isClient)
				<section id="history-section" class="settings-panel" role="tabpanel" aria-labelledby="history-tab" hidden>
					<div class="settings-history settings-history-tab">
						<div class="settings-subsection">
							<h3>Appointment history</h3>
							{{-- <p class="section-subtitle">All your appointments, displayed in {{ $authUser?->timezone ?? config('appointments.default_timezone') }}.</p> --}}
							{{-- <a href="{{ route('appointments.index') }}" class="settings-appointment-link">Open appointment schedule</a> --}}
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
				</section>
			@else
			<section id="password-section" class="settings-panel" role="tabpanel" aria-labelledby="password-tab" hidden>
				<div class="section-header-row"><div><h2>Password Settings</h2><p class="section-subtitle">Change your account password securely.</p></div></div>
				@include('pages.settings._password-form')
			</section>
			@endif
		</div>
	</div>
@endsection

@push('scripts')
	<script src="{{ asset('js/settings.js') }}?v={{ filemtime(public_path('js/settings.js')) }}"></script>
@endpush
