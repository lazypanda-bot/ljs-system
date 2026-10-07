@extends('layouts.auth')

@section('title', 'Verify Login')

@section('content')
<x-auth-card container-class="otp-container" card-class="otp-card">
    <div class="login-header">
        <img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty" class="login-logo">
        <h1>LJS Realty</h1>
        <p>Verify your login to continue.</p>
    </div>

    @if (session('status'))
        <div class="otp-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="otp-error">{{ $errors->first('code') ?: $errors->first('email') }}</div>
    @endif

    <form method="POST" action="{{ route('login.otp.verify') }}" class="login-form otp-form">
        @csrf
        <div class="form-group">
            <label for="otpCode" class="form-label">Verification code</label>
            <input id="otpCode" name="code" class="otp-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
        </div>
        <p class="text-muted text-center" style="margin: 0 0 14px; font-size: 0.82rem;">Please check your inbox or spam folder for the verification code.</p>
        <button type="submit" class="btn-login">Verify and continue</button>
    </form>

    <form method="POST" action="{{ route('login.otp.resend') }}" class="otp-resend-form">
        @csrf
        <button type="submit" class="otp-resend">Resend code</button>
    </form>
    <a href="{{ route('login') }}" class="otp-back">Back to login</a>

    <div class="login-footer">
        <p class="text-muted text-center">© 2026 LJS Realty. All rights reserved.</p>
    </div>
</x-auth-card>
@endsection
