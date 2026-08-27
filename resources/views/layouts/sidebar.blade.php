<aside data-sidebar class="sidebar">
    <div class="sidebar-top">
        <div class="brand-block">
            <div class="brand-logo">
                <img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS logo" />
            </div>
        </div>
    </div>

    <nav class="nav-menu">
        @php
            $rolePages = [
                'admin' => ['dashboard', 'users', 'messages', 'property', 'appointments', 'review', 'report', 'settings'],
                'broker' => ['dashboard', 'agents', 'messages', 'listings', 'appointments', 'review', 'report', 'settings'],
                'agent' => ['dashboard', 'messages', 'listings', 'appointment', 'review', 'report', 'settings'],
            ];

            $user = Auth::user();
            $role = 'guest';
            if (!empty($user?->role_id)) {
                $role = DB::table('roles')->where('role_id', $user->role_id)->value('role_name') ?? 'guest';
            }
            $acting = ($role === 'lead_broker') ? session('acting_as', 'broker') : strtolower($role);
            
            $pages = $rolePages[$acting] ?? [];
            
            // Build the links array dynamically for your existing loop
            $sidebarLinks = [];
            foreach ($pages as $p) {
                $sidebarLinks[] = [
                    'label' => ucfirst(str_replace(['-', '_'], ' ', $p)),
                    'route' => 'app.page',
                    'url' => route('app.page', ['page' => $p]),
                    'icon' => $p, // Matches switch cases
                ];
            }

            $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
            $currentUrl = url()->current();
        @endphp

        @foreach($sidebarLinks as $link)
            @php
                $isActive = false;
                if (! empty($link['route']) && $currentRoute === $link['route'] && request()->route('page') === basename($link['url'])) {
                    $isActive = true;
                } elseif (! empty($link['url']) && $link['url'] !== '#' && url($link['url']) === $currentUrl) {
                    $isActive = true;
                }
            @endphp
            <a href="{{ $link['url'] }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                <span class="nav-icon" aria-hidden="true">
                    @switch($link['icon'])
                        @case('dashboard')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                                stroke-linejoin="round" class="lucide lucide-layout-dashboard-icon lucide-layout-dashboard">
                                <rect width="7" height="9" x="3" y="3" rx="1"/>
                                <rect width="7" height="5" x="14" y="3" rx="1"/>
                                <rect width="7" height="9" x="14" y="12" rx="1"/>
                                <rect width="7" height="5" x="3" y="16" rx="1"/>
                            </svg>
                        @break
                        @case('users')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                            stroke-linejoin="round" class="lucide lucide-users-round-icon lucide-users-round">
                            <path d="M18 21a8 8 0 0 0-16 0"/>
                            <circle cx="10" cy="8" r="5"/>
                            <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/>
                        </svg>
                        @break
                        @case('messages')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                                stroke-linejoin="round" class="lucide lucide-message-circle-more-icon lucide-message-circle-more">
                                <path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/>
                                <path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/>
                            </svg>
                        @break
                        @case('property')
                        @case('listings')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                                stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                                <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            </svg>
                        @break
                        @case('appointment')
                        @case('appointments')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                class="lucide lucide-calendar-clock-icon lucide-calendar-clock"><path d="M16 14v2.2l1.6 1"/>
                                <path d="M16 2v3"/><path d="M21 7.338V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h2.338"/>
                                <path d="M3 9h5.859"/><path d="M8 2v3"/><circle cx="16" cy="16" r="6"/>
                            </svg>
                        @break
                        @case('review')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                class="lucide lucide-user-star-icon lucide-user-star">
                                <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/>
                                <path d="M8 15H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/>
                            </svg>
                        @break
                        @case('report')
                        @case('reports')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                class="lucide lucide-clipboard-list-icon lucide-clipboard-list">
                                <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                <path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>
                            </svg>
                        @break
                        @case('settings')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                class="lucide lucide-settings-icon lucide-settings">
                                <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        @break
                        @default
                            {{-- fallback icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                class="lucide lucide-ban-icon lucide-ban"><circle cx="12" cy="12" r="10"/>
                                <path d="M4.929 4.929 19.07 19.071"/>
                            </svg>
                    @endswitch
                </span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>