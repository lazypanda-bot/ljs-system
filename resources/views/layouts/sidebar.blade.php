<aside data-sidebar id="appSidebar" class="sidebar">
    <div class="sidebar-top"><div class="brand-block"><div class="brand-logo"><img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS logo"></div></div></div>
    <nav class="nav-menu">
        @php
            $rolePages = config('roles.pages');
            $user = Auth::user();
            $acting = $user?->actingRole();
            $pages = $acting ? ($rolePages[$acting] ?? $rolePages['admin']) : [];
            $iconMap = ['dashboard' => 'fa-chart-pie', 'users' => 'fa-users', 'agents' => 'fa-user-tie', 'messages' => 'fa-comments', 'property' => 'fa-building', 'listings' => 'fa-building', 'appointment' => 'fa-calendar-check', 'appointments' => 'fa-calendar-check', 'review' => 'fa-star', 'report' => 'fa-chart-column', 'reports' => 'fa-chart-column', 'settings' => 'fa-gear'];
            $labelMap = ['property' => 'Properties', 'appointments' => 'Appointments', 'appointment' => 'Appointments', 'review' => 'Reviews', 'report' => 'Reports'];
            $sidebarLinks = [];
            foreach ($pages as $page) {
                if ($page === 'settings') continue;
                $sidebarLinks[] = ['label' => $labelMap[$page] ?? ucfirst(str_replace(['-', '_'], ' ', $page)), 'url' => route('app.page', ['page' => $page]), 'icon' => $page];
            }
            $currentPage = request()->route('page');
            if (request()->is('app/users') || request()->routeIs('admin.users.*')) $currentPage = 'users';
            if (request()->routeIs('app.messages') || request()->routeIs('messages.inbox')) $currentPage = 'messages';
            if (request()->routeIs('appointments.index')) $currentPage = 'appointments';
        @endphp
        @foreach ($sidebarLinks as $link)
            <a href="{{ $link['url'] }}" class="nav-link {{ $currentPage === $link['icon'] ? 'active' : '' }}">
                <span class="nav-icon" aria-hidden="true"><i class="fas {{ $iconMap[$link['icon']] ?? 'fa-circle' }}"></i></span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
