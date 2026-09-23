@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<x-auth-card>
    <div class="login-header">
        <img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty" class="login-logo">
        <h1>LJS Realty</h1>
        <p>Find a place to call home.</p>
    </div>

    <div class="auth-tabs" role="tablist">
        <button class="auth-tab active" type="button" data-auth-panel="login-panel" role="tab" aria-selected="true">Login</button>
        <button class="auth-tab" type="button" data-auth-panel="register-panel" role="tab" aria-selected="false">Register</button>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Login failed.</strong>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    <div class="test-credentials-info" id="loginCredentials"><div class="alert alert-info"><strong>Test credentials</strong><hr><div class="credentials-list"><div class="credential-item"><span class="badge bg-danger">Admin</span><code>admin@testing.com</code> / <code>admin</code></div></div></div></div>

    <form method="POST" action="{{ route('login') }}" class="login-form auth-panel active" id="login-panel">
        @csrf
        <div class="form-group"><label for="email" class="form-label">Email Address</label><input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" required autofocus></div>
        <div class="form-group"><label for="password" class="form-label">Password</label><div class="input-group"><input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required><button class="btn btn-outline-secondary" type="button" id="togglePassword">Show</button></div></div>
        <div class="form-group"><label class="form-check-label"><input type="checkbox" name="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}> Remember me</label></div>
        <button type="submit" class="btn-login">Sign In</button>
    </form>

    <form method="POST" action="{{ route('register') }}" class="login-form auth-panel" id="register-panel" hidden>
        @csrf
        <div class="form-group"><label for="registerName" class="form-label">Full Name</label><input type="text" class="form-control" id="registerName" name="name" value="{{ old('name') }}" placeholder="Enter your full name" required autocomplete="name"></div>
        <div class="form-group"><label for="registerEmail" class="form-label">Email Address</label><input type="email" class="form-control" id="registerEmail" name="email" value="{{ old('email') }}" placeholder="Enter your email" required autocomplete="email"></div>
        <div class="form-group"><label for="registerPassword" class="form-label">Password</label><div class="input-group"><input type="password" class="form-control" id="registerPassword" name="password" placeholder="At least 8 characters" required autocomplete="new-password"><button class="btn btn-outline-secondary password-toggle" type="button" data-password-target="registerPassword">Show</button></div></div>
        <div class="form-group"><label for="passwordConfirmation" class="form-label">Confirm Password</label><input type="password" class="form-control" id="passwordConfirmation" name="password_confirmation" placeholder="Repeat your password" required autocomplete="new-password"></div>
        <button type="submit" class="btn-login">Create Account</button>
    </form>

    <div class="login-footer"><p class="text-muted text-center">© 2026 LJS Realty. All rights reserved.</p></div>
</x-auth-card>
@endsection

@push('scripts')
<script src="{{ asset('js/auth-login.js') }}?v={{ filemtime(public_path('js/auth-login.js')) }}" defer></script>
@endpush
