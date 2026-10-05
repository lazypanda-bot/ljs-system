<form id="password-form" class="settings-form" method="POST" action="{{ route('settings.password.update') }}">
	@csrf
	@foreach ([['current_password', 'Current Password', 'Enter your current password'], ['password', 'New Password', 'Enter your new password'], ['password_confirmation', 'Confirm New Password', 'Confirm new password']] as [$field, $label, $placeholder])
		<div class="form-group"><label for="{{ $field }}">{{ $label }}</label><div class="password-input-wrapper"><input type="password" id="{{ $field }}" name="{{ $field }}" placeholder="{{ $placeholder }}" required><button type="button" class="toggle-password" data-target="{{ $field }}" aria-label="Show {{ strtolower($label) }}"><i class="fas fa-eye"></i></button></div></div>
	@endforeach
	<div class="password-requirements-box"><div class="req-title"><i class="fas fa-shield-alt"></i> Password Requirements:</div><ul><li id="req-length">Minimum 8 characters</li><li id="req-upper">At least 1 uppercase letter</li><li id="req-number">At least 1 number</li><li id="req-special">At least 1 special character</li></ul></div>
	<div class="form-actions"><button type="submit" class="btn-green">Save Password</button></div>
</form>
