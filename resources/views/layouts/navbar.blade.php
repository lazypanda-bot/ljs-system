<header class="top-header">
    <div class="header-left">
        @if(!empty($pageLabel))
            <div class="page-label">
                <span class="page-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="8" height="8" rx="2" />
                        <rect x="13" y="3" width="8" height="8" rx="2" />
                        <rect x="3" y="13" width="8" height="8" rx="2" />
                        <rect x="13" y="13" width="8" height="8" rx="2" />
                    </svg>
                </span>
                <div>
                    <p class="page-label-title">{{ $pageLabel }}</p>
                    <p class="page-label-subtitle">Welcome back</p>
                </div>
            </div>
        @endif
    </div>

        <div class="header-right">
        @hasSection('navbar-actions')
            @yield('navbar-actions')
        @else
            @auth
                @if(!empty($roleToggle) && $roleToggle)
                    <form method="POST" action="{{ route('lead.toggle') }}" style="display:inline-block;margin-right:12px;">
                        @csrf
                        <button type="submit" class="notification-btn" aria-label="Toggle role">
                            <span style="font-size:13px;font-weight:700;color:var(--color-text-dark);">
                                {{ ucfirst($actingAs ?? 'broker') }} view
                            </span>
                        </button>
                    </form>
                @endif

                <button class="notification-btn" aria-label="Notifications">
                    <span class="notification-count">{{ 0 }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 17h5l-1.4-1.4A7.94 7.94 0 0 0 18 10V8a6 6 0 1 0-12 0v2c0 1.3-.3 2.6-.9 3.7L4 17h5" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                </button>

                <div class="profile-menu-wrap">
                    <button id="profileMenuButton" class="user-profile-nav" type="button" aria-expanded="false" aria-haspopup="true">
                        <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</div>
                        <div class="user-info-nav">
                            <span class="user-name">{{ Auth::user()->name ?? 'User' }}</span>
                            <span class="user-email">{{ Auth::user()->email ?? '' }}</span>
                        </div>
                        <span class="profile-arrow">▾</span>
                    </button>

                    <div id="profileMenu" class="profile-menu" aria-hidden="true">
                        <div class="profile-menu-header">
                            <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</div>
                            <div>
                                <div class="profile-menu-title">{{ Auth::user()->name ?? 'User' }}</div>
                                <div class="profile-menu-subtitle">{{ Auth::user()->email ?? '' }}</div>
                            </div>
                        </div>

                        <a href="#" class="profile-menu-item">Settings</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="profile-menu-item profile-menu-logout">Logout</button>
                        </form>
                    </div>
                </div>
            @endauth

            @guest
                <button class="notification-btn" aria-label="Notifications">
                    <span class="notification-count">0</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 17h5l-1.4-1.4A7.94 7.94 0 0 0 18 10V8a6 6 0 1 0-12 0v2c0 1.3-.3 2.6-.9 3.7L4 17h5" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                </button>

                <button id="openLoginModal" class="signin-btn" type="button">Sign in</button>
            @endguest
        @endif
    </div>
</header>

<div id="loginModalOverlay" class="login-modal-overlay" aria-hidden="true" data-open-if-error="{{ session()->has('login_error') ? 'true' : 'false' }}">
    <div class="login-modal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
        <button type="button" class="login-modal-close" aria-label="Close">×</button>
        <div class="login-modal-header" id="loginModalTitle">Please sign in</div>
        <p class="login-modal-note">Temporary admin account only. Use admin@example.com / password123.</p>

        @if(session('login_error'))
            <div class="login-modal-error">{{ session('login_error') }}</div>
        @endif

        <form method="POST" action="{{ route('temp.login') }}">
            @csrf

            <div class="form-group">
                <label for="loginEmail">Email</label>
                <input id="loginEmail" name="email" type="email" placeholder="Enter email" required />
            </div>

            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input id="loginPassword" name="password" type="password" placeholder="Enter password" required />
            </div>

            <button type="submit" class="login-submit-btn">Login</button>
        </form>
    </div>
</div>
