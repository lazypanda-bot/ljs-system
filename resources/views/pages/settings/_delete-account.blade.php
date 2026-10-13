<div class="danger-zone-box">
	<h3>Delete Account</h3>
	<p class="section-subtitle">Your account will be deactivated and you will be signed out.</p>
	<button type="button" class="btn-danger-outline" data-open-delete-account>Delete Account</button>
</div>

<div class="settings-modal {{ ($errors->has('current_password') || $errors->has('confirmation')) && old('confirmation') !== null ? 'open' : '' }}" id="deleteAccountModal" aria-hidden="true">
	<div class="settings-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteAccountTitle">
		<h3 id="deleteAccountTitle">Delete your account?</h3>
		<p class="section-subtitle">Enter your password and type DELETE to confirm.</p>
		<form method="POST" action="{{ route('settings.account.destroy') }}" class="settings-form" data-confirm-message="Deactivate your account? You will be signed out immediately.">
			@csrf @method('DELETE')
			<div class="form-group"><label for="delete_current_password">Current Password</label><input type="password" id="delete_current_password" name="current_password" required autocomplete="current-password"></div>
			<div class="form-group"><label for="delete_confirmation">Type DELETE to confirm</label><input type="text" id="delete_confirmation" name="confirmation" required autocomplete="off"></div>
			<div class="settings-modal-actions"><button type="button" class="btn-cancel-outline" data-close-delete-account>Cancel</button><button type="submit" class="btn-danger-outline">Deactivate Account</button></div>
		</form>
	</div>
</div>
