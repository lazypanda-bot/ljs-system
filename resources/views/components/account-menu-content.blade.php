<div class="account-menu-header">
    <span class="account-menu-avatar role-{{ auth()->user()->normalizedRole() }}">
        @if (auth()->user()->profile_photo)
            <img src="{{ str_starts_with(auth()->user()->profile_photo, 'http') ? auth()->user()->profile_photo : asset('storage/' . auth()->user()->profile_photo) }}" alt="">
        @else
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        @endif
    </span>
    <div class="account-menu-identity">
        <strong>{{ auth()->user()->name }}</strong>
        <span>{{ auth()->user()->email }}</span>
    </div>
</div>
<a href="{{ route('app.page', ['page' => 'settings']) }}" class="account-menu-item"><i data-lucide="settings" aria-hidden="true"></i> Settings</a>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="account-menu-item account-menu-logout"><i data-lucide="log-out" aria-hidden="true"></i> Logout</button>
</form>
