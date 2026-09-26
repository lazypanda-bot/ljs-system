@extends('layouts.auth')

@section('title', 'Set Password')

@section('content')
    <x-auth-card>
        <div class="login-header">
            <img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty" class="login-logo">
            <h1>Set your password</h1>
            <p>Complete your LJS Realty account setup.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ request()->fullUrl() }}" class="login-form">
            @csrf
            <div class="form-group">
                <label for="password" class="form-label">New password</label>
                <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" minlength="8" required>
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" minlength="8" required>
            </div>
            <button class="btn-login" type="submit">Set password</button>
        </form>
    </x-auth-card>
@endsection
