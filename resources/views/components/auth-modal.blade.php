<div id="loginModalOverlay" class="login-modal-overlay" aria-hidden="true" data-open-if-error="{{ session('auth_modal_error') ? 'true' : 'false' }}">
    <section class="login-modal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
        <button type="button" class="login-modal-close" aria-label="Close">&times;</button>

        <div class="auth-modal-brand">
            <img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty" class="auth-panel-logo">
            <div class="auth-modal-copy">
                <h2 id="loginModalTitle">Login</h2>
                <p id="authModalSubtitle">Access your LJS Realty account.</p>
            </div>
        </div>

        <div class="auth-tabs" role="tablist" data-auth-tabs>
            <button class="auth-tab active" type="button" data-auth-panel="modal-login-panel" data-auth-title="Login" data-auth-subtitle="Access your LJS Realty account." role="tab" aria-selected="true">Login</button>
            <button class="auth-tab" type="button" data-auth-panel="modal-register-panel" data-auth-title="Register" data-auth-subtitle="Create your client account to get started." role="tab" aria-selected="false">Register</button>
        </div>

        <form method="POST" action="{{ route('login') }}" class="auth-panel active" id="modal-login-panel">
            @csrf
            <div class="form-group">
                <label for="modalLoginEmail">Email Address</label>
                <input id="modalLoginEmail" name="email" type="email" value="{{ old('email') }}" placeholder="Enter your email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="modalLoginPassword">Password</label>
                <div class="password-field"><input id="modalLoginPassword" name="password" type="password" placeholder="Enter your password" required autocomplete="current-password"><button type="button" class="password-toggle" data-password-target="modalLoginPassword" aria-label="Show password">Show</button></div>
            </div>
            <div class="auth-form-options"><label class="remember-me"><input type="checkbox" name="remember" value="1"> Remember me</label><button type="button" class="forgot-password-link" id="authForgotLink">Forgot password?</button></div>
            <div class="auth-login-status" id="authLoginStatus" role="alert" aria-live="polite"></div>
            <button type="submit" class="login-submit-btn">Login</button>
        </form>

        <form method="POST" action="{{ route('register') }}" class="auth-panel" id="modal-register-panel" hidden>
            @csrf
            <div class="form-group">
                <label for="modalRegisterName">Full Name</label>
                <input id="modalRegisterName" name="name" type="text" value="{{ old('name') }}" placeholder="Enter your full name" required minlength="2" maxlength="100" pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ .'\-]*" title="Letters, spaces, apostrophes, hyphens and periods only" autocomplete="name">
                @error('name')<span class="auth-input-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="modalRegisterEmail">Email Address</label>
                <input id="modalRegisterEmail" name="email" type="email" value="{{ old('email') }}" placeholder="Enter your email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="modalRegisterPassword">Password</label>
                <div class="password-field"><input id="modalRegisterPassword" name="password" type="password" placeholder="At least 8 characters" required autocomplete="new-password"><button type="button" class="password-toggle" data-password-target="modalRegisterPassword" aria-label="Show password">Show</button></div>
            </div>
            <div class="form-group">
                <label for="modalRegisterPasswordConfirmation">Confirm Password</label>
                <div class="password-field"><input id="modalRegisterPasswordConfirmation" name="password_confirmation" type="password" placeholder="Repeat your password" required autocomplete="new-password"><button type="button" class="password-toggle" data-password-target="modalRegisterPasswordConfirmation" aria-label="Show password">Show</button></div>
                @error('password')<span class="auth-input-error">{{ $message }}</span>@enderror
            </div>
            <div class="auth-login-status" id="authRegisterStatus" role="alert" aria-live="polite"></div>
            <button type="submit" class="login-submit-btn">Create Account</button>
        </form>

        <form method="POST" action="{{ route('login.otp.verify') }}" class="auth-panel" id="modal-otp-panel" hidden>
            @csrf
            <div class="form-group">
                <label for="modalOtpCode">Verification code</label>
                <input id="modalOtpCode" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter the 6-digit code" required autocomplete="one-time-code">
            </div>
            <div class="auth-otp-status" id="authOtpStatus" role="status" aria-live="polite"></div>
            <button type="submit" class="login-submit-btn">Verify and continue</button>
            <button type="button" class="auth-otp-resend" id="authOtpResend">Resend code</button>
            <button type="button" class="auth-otp-back" id="authOtpBack">Back to login</button>
        </form>

        <form method="POST" action="{{ route('password.forgot') }}" class="auth-panel" id="modal-forgot-panel" hidden>
            @csrf
            <div class="form-group">
                <label for="modalForgotEmail">Email Address</label>
                <input id="modalForgotEmail" name="email" type="email" placeholder="Enter your account email" required autocomplete="email">
            </div>
            <div class="auth-otp-status" id="authForgotStatus" role="status" aria-live="polite"></div>
            <button type="submit" class="login-submit-btn">Send reset code</button>
            <button type="button" class="auth-otp-back" data-auth-back-to-login>Back to login</button>
        </form>

        <form method="POST" action="{{ route('password.reset') }}" class="auth-panel" id="modal-reset-panel" hidden>
            @csrf
            <div class="form-group">
                <label for="modalResetCode">Verification code</label>
                <input id="modalResetCode" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter the 6-digit code" required autocomplete="one-time-code">
            </div>
            <div class="form-group">
                <label for="modalResetPassword">New Password</label>
                <div class="password-field"><input id="modalResetPassword" name="password" type="password" placeholder="At least 8 characters" required minlength="8" autocomplete="new-password"><button type="button" class="password-toggle" data-password-target="modalResetPassword" aria-label="Show password">Show</button></div>
            </div>
            <div class="form-group">
                <label for="modalResetPasswordConfirmation">Confirm New Password</label>
                <div class="password-field"><input id="modalResetPasswordConfirmation" name="password_confirmation" type="password" placeholder="Repeat your new password" required minlength="8" autocomplete="new-password"><button type="button" class="password-toggle" data-password-target="modalResetPasswordConfirmation" aria-label="Show password">Show</button></div>
            </div>
            <div class="auth-otp-status" id="authResetStatus" role="status" aria-live="polite"></div>
            <button type="submit" class="login-submit-btn">Change password</button>
            <button type="button" class="auth-otp-back" data-auth-back-to-login>Back to login</button>
        </form>
    </section>
</div>