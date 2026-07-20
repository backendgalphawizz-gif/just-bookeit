@php
    $headerCustomer = auth('customer')->user();
    $headerLoggedIn = $headerCustomer !== null;
    $headerRegistered = $headerLoggedIn && ! $headerCustomer->is_guest;
    $headerChatRedirect = route('web.chat.index');
    $headerCartCount = (int) ($webCartCount ?? 0);
    $headerCartSuffix = $headerCartCount > 0 ? ' ('.$headerCartCount.')' : '';
@endphp

<header class="jbw-header" x-data="{ mobileOpen: false, locationOpen: false, locationSearch: '', notificationOpen: false }" @keydown.escape.window="mobileOpen = false; locationOpen = false; notificationOpen = false">
    <div class="jbw-container jbw-header-inner">

        <a href="{{ route('web.home') }}" class="jbw-logo-link" aria-label="Just Book IT home">
            <x-web.logo variant="header" />
        </a>

        <nav class="jbw-nav" aria-label="Main">
            <a href="{{ route('web.home') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.home')])>Home</a>
            <a href="{{ route('web.services.index') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.services.*')])>Services</a>
            <a href="{{ route('web.catalog.index') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.catalog.*') && ! request()->routeIs('web.services.*')])>Categories</a>

            @if ($headerRegistered)
                <a href="{{ $headerChatRedirect }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>Chat</a>
            @endif
            @if ($headerLoggedIn && ! $headerRegistered)
                <a href="{{ route('web.register', ['redirect' => $headerChatRedirect]) }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>Chat</a>
            @endif
            @if (! $headerLoggedIn)
                <a href="{{ route('web.login', ['redirect' => $headerChatRedirect]) }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>Chat</a>
            @endif

            <a href="{{ route('web.contact') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.contact')])>Contact Us</a>
        </nav>

        <div class="jbw-header-tools">
            @include('web.partials.location-picker')
            <form method="GET" action="{{ route('web.catalog.index') }}" class="jbw-header-search headerinputradius" role="search">
                <input type="search" name="search" class="jbw-header-search-input" placeholder="Search outfits…" value="{{ request('search') }}" aria-label="Search catalog">
                <button type="submit" class="jbw-icon-btn" aria-label="Search">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="color: rgb(242, 81, 35);" stroke-width="2">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M20 20l-3-3"/>
                    </svg>
                </button>
            </form>
            @include('web.partials.notification-picker')

            @if ($headerRegistered)
                <a href="{{ route('web.cart.index') }}" class="jbw-icon-btn" aria-label="Cart" style="position:relative">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="color: rgb(242, 81, 35);" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    @if ($headerCartCount > 0)
                        <span style="position:absolute;top:-4px;right:-4px;min-width:1.1rem;height:1.1rem;padding:0 0.25rem;border-radius:999px;background:var(--c-primary);color:#fff;font-size:0.65rem;font-weight:700;display:flex;align-items:center;justify-content:center">{{ $headerCartCount > 99 ? '99+' : $headerCartCount }}</span>
                    @endif
                </a>
            @endif

            @if ($headerLoggedIn)
                <div class="jbw-profile-menu" x-data="{ open: false }">
                    <button type="button" class="jbw-avatar-btn" @click="open = !open" aria-label="Account menu">
                        @if ($headerCustomer->profileImageUrl())
                            <img src="{{ $headerCustomer->profileImageUrl() }}" alt="" class="jbw-avatar-img">
                        @else
                            <span class="jbw-avatar-fallback">{{ strtoupper(substr($headerCustomer->name, 0, 1)) }}</span>
                        @endif
                    </button>
                    <div class="jbw-profile-dropdown" x-show="open" x-cloak @click.outside="open = false">
                        <p class="jbw-profile-dropdown-name">{{ $headerCustomer->name }}</p>
                        @if ($headerCustomer->is_guest)
                            <p style="margin:0 0 0.5rem;font-size:0.75rem;color:var(--c-muted)">Browsing as guest</p>
                            <a href="{{ route('web.register', ['redirect' => url()->current()]) }}">Create account</a>
                            <a href="{{ route('web.login') }}">Sign in</a>
                        @else
                            <a href="{{ route('web.profile.edit') }}">My Profile</a>
                            <a href="{{ route('web.bookings.index') }}">Booking history</a>
                        @endif
                        <form method="POST" action="{{ route('web.logout') }}">@csrf<button type="submit">Log out</button></form>
                    </div>
                </div>
            @endif

            @if (! $headerLoggedIn)
                <a href="{{ route('web.login') }}" class="jbw-btn jbw-btn--sm jbw-btn--primary">Sign in</a>
            @endif

            <button type="button" class="jbw-mobile-toggle" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-label="Toggle menu">
                <svg x-show="!mobileOpen" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="mobileOpen" x-cloak width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <div
        class="jbw-mobile-nav"
        x-show="mobileOpen"
        x-cloak
        x-transition:enter="jbw-mnav-enter"
        x-transition:enter-start="jbw-mnav-enter-start"
        x-transition:enter-end="jbw-mnav-enter-end"
        x-transition:leave="jbw-mnav-enter"
        x-transition:leave-start="jbw-mnav-enter-end"
        x-transition:leave-end="jbw-mnav-enter-start"
        @click.outside="mobileOpen = false"
    >
        <nav class="jbw-mobile-nav-links">
            <a href="{{ route('web.home') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.home')]) @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Home
            </a>
            <a href="{{ route('web.services.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.services.*')]) @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h6"/></svg>
                Services
            </a>
            <a href="{{ route('web.catalog.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.catalog.*') && ! request()->routeIs('web.services.*')]) @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                </svg>
                Categories
            </a>

            @if ($headerRegistered)
                <a href="{{ route('web.cart.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.cart.*')]) @click="mobileOpen=false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    Cart{{ $headerCartSuffix }}
                </a>
                <a href="{{ route('web.bookings.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.bookings.*') && ! request()->routeIs('web.bookings.overview')]) @click="mobileOpen=false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    My bookings
                </a>
                <a href="{{ $headerChatRedirect }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.chat.*')]) @click="mobileOpen=false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    Chat
                </a>
            @endif

            @if ($headerLoggedIn && ! $headerRegistered)
                <a href="{{ route('web.register', ['redirect' => $headerChatRedirect]) }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.chat.*')]) @click="mobileOpen=false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    Chat
                </a>
            @endif

            @if (! $headerLoggedIn)
                <a href="{{ route('web.login', ['redirect' => $headerChatRedirect]) }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.chat.*')]) @click="mobileOpen=false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    Chat
                </a>
            @endif

            <a href="{{ route('web.contact') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.contact')]) @click="mobileOpen=false">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.09 9a3 3 0 115.82 1c0 2-3 3-3 3"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 17h.01"></path>
                    <circle cx="12" cy="12" r="10"></circle>
                </svg>
                Contact Us
            </a>
        </nav>

        @if ($headerLoggedIn)
            <div class="jbw-mobile-nav-user">
                @if ($headerCustomer->is_guest)
                    <a href="{{ route('web.register', ['redirect' => url()->current()]) }}" class="jbw-btn jbw-btn--primary jbw-btn--block" @click="mobileOpen=false">Create account</a>
                    <a href="{{ route('web.login') }}" class="jbw-mnav-link" @click="mobileOpen=false">Sign in</a>
                @else
                    <a href="{{ route('web.profile.edit') }}" class="jbw-mnav-link" @click="mobileOpen=false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 10-16 0" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        My Profile
                    </a>
                @endif
                <form method="POST" action="{{ route('web.logout') }}">@csrf
                    <button type="submit" class="jbw-mnav-link jbw-mnav-link--danger" style="width:100%;text-align:left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3"/>
                        </svg>
                        Log out
                    </button>
                </form>
            </div>
        @endif

        @if (! $headerLoggedIn)
            <div class="jbw-mobile-nav-user">
                <a href="{{ route('web.login') }}" class="jbw-btn jbw-btn--primary jbw-btn--block" @click="mobileOpen=false">Sign in</a>
            </div>
        @endif
    </div>
</header>
