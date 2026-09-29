<header class="top-header">
    <div class="header-left">
        @if(!empty($pageLabel))
            <div class="page-label">
                <span class="page-icon" aria-hidden="true">
                    <i class="fas fa-chart-pie"></i>
                </span>
                <div>
                    <p class="page-label-title">{{ $pageLabel }}</p>
                    <p class="page-label-subtitle">Welcome back, {{ Auth::user()->name ?? 'User' }}</p>
                </div>
            </div>
        {{-- @else
            <nav class="navbar-links">
                <a href="{{ url('/home') }}" class="{{ request()->is('/') ? 'active' : '' }}">Home</a>
                <a href="{{ url('/properties*') }}" class="{{ request()->is('properties*') ? 'active' : '' }}">Properties</a>
                <a href="{{ url('/agents*') }}" class="{{ request()->is('agents*') ? 'active' : '' }}">Agents</a>
                <a href="{{ url('/about*') }}" class="{{ request()->is('about*') ? 'active' : '' }}">About Us</a>
            </nav> --}}
        @endif
    </div>

    <div class="header-right">
        @hasSection('navbar-actions')
            @yield('navbar-actions')
        @else
            @auth
                @if(!empty($roleToggle) && $roleToggle)
                    <form method="POST" action="{{ route('lead.toggle') }}" class="role-toggle-form">
                        @csrf
                        <button type="submit" class="role-toggle-btn" aria-label="Toggle role">
                            {{ ucfirst($actingAs ?? 'broker') }} view
                        </button>
                    </form>
                @endif

                <div class="notification-menu-wrap">
                    <button class="notification-btn" type="button" data-notification-count-url="{{ route('notifications.count') }}" id="notificationMenuButton" aria-label="Notifications" aria-expanded="false">
                        @if (($unreadNotificationCount ?? 0) > 0)
                            <span class="notification-count" data-notification-count>{{ $unreadNotificationCount }}</span>
                        @endif
                        <i class="fas fa-bell" aria-hidden="true"></i>
                    </button>
                    <div class="notification-menu" id="notificationMenu" aria-hidden="true">
                        <div class="notification-menu-title">Notifications <span>{{ $unreadNotificationCount ?? 0 }} new</span></div>
                        @forelse ($recentNotifications ?? [] as $notification)
                            <a href="{{ route('notifications.open', $notification) }}" class="notification-item notification-{{ $notification->type }}">
                                <span class="notification-item-icon">{{ $notification->type === 'message' ? 'M' : ($notification->type === 'team' ? 'T' : 'L') }}</span>
                                <span><strong>{{ $notification->title }}</strong><small>{{ $notification->body }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span>
                            </a>
                        @empty
                            <p class="notification-empty">No new notifications.</p>
                        @endforelse
                        <a href="{{ route('notifications.index') }}" class="notification-view-all">View all notifications</a>
                    </div>
                </div>

                <div class="profile-menu-wrap">
                    <button id="profileMenuButton" class="user-profile-nav" type="button" aria-expanded="false" aria-haspopup="true">
                        <div class="avatar role-{{ Auth::user()->normalizedRole() }}">
                            @if (Auth::user()->profile_photo)
                                <img src="{{ str_starts_with(Auth::user()->profile_photo, 'http') ? Auth::user()->profile_photo : asset('storage/' . Auth::user()->profile_photo) }}" alt="">
                            @else
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            @endif
                        </div>
                        <div class="user-info-nav">
                            <span class="user-name">{{ Auth::user()->name ?? 'User' }}</span>
                            <span class="user-email">{{ Auth::user()->email ?? '' }}</span>
                        </div>
                        <span class="profile-arrow"><i class="fas fa-chevron-down" aria-hidden="true"></i></span>
                    </button>

                    <div id="profileMenu" class="profile-menu" aria-hidden="true">
                        <div class="profile-menu-header">
                            <div class="avatar role-{{ Auth::user()->normalizedRole() }}">
                                @if (Auth::user()->profile_photo)
                                    <img src="{{ str_starts_with(Auth::user()->profile_photo, 'http') ? Auth::user()->profile_photo : asset('storage/' . Auth::user()->profile_photo) }}" alt="">
                                @else
                                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                                @endif
                            </div>
                            <div>
                                <div class="profile-menu-title">{{ Auth::user()->name ?? 'User' }}</div>
                                <div class="profile-menu-subtitle">{{ Auth::user()->email ?? '' }}</div>
                            </div>
                        </div>

                        <a href="{{ route('app.page', ['page' => 'settings']) }}" class="profile-menu-item">Settings</a>
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
                    <i class="fas fa-bell" aria-hidden="true"></i>
                </button>

                <button id="openLoginModal" class="signin-btn" type="button">Sign in</button>
            @endguest
        @endif
    </div>
</header>


