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
            $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
            $currentUrl = url()->current();
        @endphp
        @foreach($sidebarLinks as $link)
            @php
                $isActive = false;
                if (! empty($link['route']) && $currentRoute === $link['route']) {
                    $isActive = true;
                } elseif (! empty($link['url']) && $link['url'] !== '#' && url($link['url']) === $currentUrl) {
                    $isActive = true;
                }
            @endphp
            <a href="{{ $link['url'] }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                <span class="nav-icon" aria-hidden="true">
                    @switch($link['icon'])
                        @case('dashboard')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="8" height="8" rx="2" />
                                <rect x="13" y="3" width="8" height="8" rx="2" />
                                <rect x="3" y="13" width="8" height="8" rx="2" />
                                <rect x="13" y="13" width="8" height="8" rx="2" />
                            </svg>
                        @break
                        @case('users')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        @break
                        @case('messages')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z" />
                            </svg>
                        @break
                        @case('property')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 11L12 2l9 9" />
                                <path d="M9 22V12h6v10" />
                                <path d="M21 22H3" />
                            </svg>
                        @break
                        @case('appointment')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="16" rx="2" />
                                <path d="M16 3v4" />
                                <path d="M8 3v4" />
                                <path d="M3 11h18" />
                            </svg>
                        @break
                        @case('review')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15 8.9 22 9.3 17 14.2 18.5 21.1 12 17.8 5.5 21.1 7 14.2 2 9.3 9 8.9 12 2" />
                            </svg>
                        @break
                        @case('report')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h12l4 4v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                                <path d="M14 4v4h4" />
                                <path d="M8 14h8" />
                                <path d="M8 18h8" />
                                <path d="M8 10h4" />
                            </svg>
                        @break
                        @default
                            {{-- fallback icon --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                            </svg>
                    @endswitch
                </span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
