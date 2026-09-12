@extends('layouts.client')

@section('title', 'Login')

@section('content')
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h1>LJS Reality</h1>
            <p>Property Management System</p>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Login Failed!</strong>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Test Credentials Info -->
        <div class="test-credentials-info">
            <div class="alert alert-info">
                <strong> Test Credentials (For Testing)</strong>
                <hr>
                <div class="credentials-list">
                    <div class="credential-item">
                        <span class="badge bg-primary">Lead Broker</span>
                        <code>leadbroker@testing.com</code> / <code>password</code>
                    </div>
                    <div class="credential-item">
                        <span class="badge bg-danger">Admin</span>
                        <code>admin@testing.com</code> / <code>password</code>
                    </div>
                    <div class="credential-item">
                        <span class="badge bg-success">Broker</span>
                        <code>broker@testing.com</code> / <code>password</code>
                    </div>
                    <div class="credential-item">
                        <span class="badge bg-warning">Agent</span>
                        <code>agent@testing.com</code> / <code>password</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" 
                       class="form-control @error('email') is-invalid @enderror" 
                       id="email" 
                       name="email" 
                       value="{{ old('email') }}"
                       placeholder="Enter your email"
                       required 
                       autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           id="password" 
                           name="password" 
                           placeholder="Enter your password"
                           required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback" style="display: block;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-check-label">
                    <input type="checkbox" name="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                    Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="login-footer">
            <p class="text-muted text-center">© 2026 LJS Reality. All rights reserved.</p>
        </div>
    </div>
</div>

<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .login-container {
        width: 100%;
        max-width: 450px;
        padding: 20px;
    }

    .login-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        padding: 40px;
        animation: slideUp 0.5s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .login-header h1 {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 5px;
        font-weight: 700;
    }

    .login-header p {
        color: #6c757d;
        font-size: 0.95rem;
    }

    .test-credentials-info {
        margin-bottom: 25px;
    }

    .test-credentials-info .alert {
        border-left: 4px solid #17a2b8;
        padding: 15px;
        margin-bottom: 0;
    }

    .test-credentials-info strong {
        display: block;
        margin-bottom: 10px;
        font-size: 0.95rem;
    }

    .test-credentials-info hr {
        margin: 10px 0;
        opacity: 0.5;
    }

    .credentials-list {
        font-size: 0.9rem;
    }

    .credential-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px solid #dee2e6;
    }

    .credential-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .credential-item .badge {
        min-width: 80px;
        text-align: center;
        font-size: 0.8rem;
    }

    .credential-item code {
        background: #f8f9fa;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #e83e8c;
    }

    .login-form {
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: 600;
        color: #212529;
        margin-bottom: 8px;
        display: block;
    }

    .form-control,
    .form-check-input {
        border-radius: 6px;
        border: 1px solid #ced4da;
        padding: 10px 12px;
        font-size: 0.95rem;
    }

    .form-control:focus,
    .form-check-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .form-control.is-invalid {
        border-color: #dc3545;
    }

    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 5px;
    }

    .input-group {
        display: flex;
        gap: 0;
    }

    .input-group .form-control {
        border-radius: 6px 0 0 6px;
    }

    .input-group .btn {
        border-radius: 0 6px 6px 0;
        border: 1px solid #ced4da;
        background: white;
        color: #6c757d;
    }

    .input-group .btn:hover {
        background: #f8f9fa;
    }

    .form-check-label {
        font-size: 0.95rem;
        color: #6c757d;
        margin-bottom: 0;
        cursor: pointer;
    }

    .btn-login {
        width: 100%;
        padding: 10px 20px;
        font-size: 1rem;
        font-weight: 600;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 6px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .login-footer {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #dee2e6;
    }

    .alert {
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .alert-info {
        background-color: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }

    @media (max-width: 480px) {
        .login-card {
            padding: 25px;
        }

        .login-header h1 {
            font-size: 1.5rem;
        }

        .test-credentials-info .alert {
            font-size: 0.85rem;
        }

        .credentials-list {
            font-size: 0.8rem;
        }
    }
</style>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
</script>
@endsection
