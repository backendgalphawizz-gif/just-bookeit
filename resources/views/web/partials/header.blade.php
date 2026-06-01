<header class="jbw-header" x-data="{ mobileOpen: false }" @keydown.escape.window="mobileOpen = false">
    <div class="jbw-container jbw-header-inner">

        <a href="{{ route('web.home') }}" class="jbw-logo" aria-label="Just Book IT home">
            <svg class="jbw-logo-svg" viewBox="0 0 88 56" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M10 8 H34" stroke="#f25123" stroke-width="8" stroke-linecap="round"/>
                <path d="M10 8 V38 C10 48 16 52 26 52 C30 52 34 50 37 47" stroke="#f25123" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M46 8 V52" stroke="#1a2f38" stroke-width="8" stroke-linecap="round"/>
                <path d="M46 8 H64 C72 8 78 14 78 22 C78 28 74 32 68 34 C76 36 82 42 82 50 C82 58 74 52 64 52 H46" stroke="#1a2f38" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M46 34 H64 C70 34 76 30 76 24 C76 18 70 14 64 14 H46" stroke="#1a2f38" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="jbw-logo-wordmark">Just Book IT</span>
        </a>

        <nav class="jbw-nav" aria-label="Main">
            <a href="{{ route('web.home') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.home')])>Home</a>
            <a href="{{ route('web.catalog.index') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.catalog.*') || request()->routeIs('web.vendors.*')])>Services</a>
            <a href="{{ route('web.catalog.index') }}" class="jbw-nav-link">Categories</a>
            <a href="#" class="jbw-nav-link">Chat</a>
            <a href="#" class="jbw-nav-link">Contact Us</a>
        </nav>

        <div class="jbw-header-tools">
            <button type="button" class="jbw-location-btn" aria-label="Location">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 21s7-4.5 7-10a7 7 0 10-14 0c0 5.5 7 10 7 10z"/><circle cx="12" cy="11" r="2.5"/></svg>
                <span>{{ $webCustomer?->city ?? 'Mumbai, India' }}</span>
            </button>
            <a href="{{ route('web.catalog.index') }}" class="jbw-icon-btn" aria-label="Search">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
            </a>
            <button type="button" class="jbw-icon-btn" aria-label="Notifications">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
            </button>
            @auth('customer')
                <div class="jbw-profile-menu" x-data="{ open: false }">
                    <button type="button" class="jbw-avatar-btn" @click="open = !open" aria-label="Account menu">
                        @if ($webCustomer->profileImageUrl())
                            <img src="{{ $webCustomer->profileImageUrl() }}" alt="" class="jbw-avatar-img">
                        @else
                            <span class="jbw-avatar-fallback">{{ strtoupper(substr($webCustomer->name, 0, 1)) }}</span>
                        @endif
                    </button>
                    <div class="jbw-profile-dropdown" x-show="open" x-cloak @click.outside="open = false">
                        <p class="jbw-profile-dropdown-name">{{ $webCustomer->name }}</p>
                        <a href="{{ route('web.profile.edit') }}">My Profile</a>
                        <a href="{{ route('web.bookings.index') }}">Booking history</a>
                        <form method="POST" action="{{ route('web.logout') }}">@csrf<button type="submit">Log out</button></form>
                    </div>
                </div>
            @else
                <a href="{{ route('web.login') }}" class="jbw-btn jbw-btn--sm jbw-btn--primary">Sign in</a>
            @endauth
            <button
                type="button"
                class="jbw-mobile-toggle"
                @click="mobileOpen = !mobileOpen"
                :aria-expanded="mobileOpen"
                aria-label="Toggle menu"
            >
                <svg x-show="!mobileOpen" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="mobileOpen" x-cloak width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile nav drawer --}}
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
            <a href="{{ route('web.catalog.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.catalog.*')]) @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Services
            </a>
            <a href="{{ route('web.catalog.index') }}" class="jbw-mnav-link" @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Categories
            </a>
            <a href="#" class="jbw-mnav-link" @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                Chat
            </a>
            <a href="#" class="jbw-mnav-link" @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.27 9.5a19.79 19.79 0 01-3-8.59A2 2 0 012.22 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 9.91a16 16 0 006.2 6.2l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                Contact Us
            </a>
        </nav>
        @auth('customer')
            <div class="jbw-mobile-nav-user">
                <a href="{{ route('web.profile.edit') }}" class="jbw-mnav-link" @click="mobileOpen=false">My Profile</a>
                <a href="{{ route('web.bookings.index') }}" class="jbw-mnav-link" @click="mobileOpen=false">My Bookings</a>
                <form method="POST" action="{{ route('web.logout') }}">@csrf
                    <button type="submit" class="jbw-mnav-link jbw-mnav-link--danger" style="width:100%;text-align:left">Log out</button>
                </form>
            </div>
        @else
            <div class="jbw-mobile-nav-user">
                <a href="{{ route('web.login') }}" class="jbw-btn jbw-btn--primary jbw-btn--block" @click="mobileOpen=false">Sign in</a>
            </div>
        @endauth
    </div>
</header>
