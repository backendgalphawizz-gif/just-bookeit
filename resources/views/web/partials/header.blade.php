<header class="jbw-header" x-data="{ mobileOpen: false, locationOpen: false, locationSearch: '', notificationOpen: false }" @keydown.escape.window="mobileOpen = false; locationOpen = false; notificationOpen = false">
    <div class="jbw-container jbw-header-inner">

        <a href="{{ route('web.home') }}" class="jbw-logo-link" aria-label="Just Book IT home">
            <x-web.logo variant="header" />
             <!-- <img src="../../../../assets/frontend/images/bookitlogo.png" /> -->
        </a>

        <nav class="jbw-nav" aria-label="Main">
            <a href="{{ route('web.home') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.home')])>Home</a>
            <a href="{{ route('web.services.index') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.services.*')])>Services</a>
            <a href="{{ route('web.catalog.index') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.catalog.*') && ! request()->routeIs('web.services.*')])>Categories</a>
            @auth('customer')
                @unless ($webCustomer->is_guest)
                    <a href="{{ route('web.bookings.index') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.bookings.*') && ! request()->routeIs('web.bookings.overview')])>My bookings</a>
                    <a href="{{ route('web.chat.index') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>Chat</a>
                @else
                    <a href="{{ route('web.register', ['redirect' => route('web.chat.index')]) }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>Chat</a>
                @endunless
            @else
                <a href="{{ route('web.login', ['redirect' => route('web.chat.index')]) }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>Chat</a>
            @endauth
            <a href="{{ route('web.contact') }}" @class(['jbw-nav-link', 'is-active' => request()->routeIs('web.contact')])>Contact</a>
        </nav>

        <div class="jbw-header-tools">
            @include('web.partials.location-picker')
            <form method="GET" action="{{ route('web.catalog.index') }}" class="jbw-header-search headerinputradius " role="search">
                <input type="search" name="search" class="jbw-header-search-input " placeholder="Search outfits…" value="{{ request('search') }}" aria-label="Search catalog">
                <button type="submit" class="jbw-icon-btn" aria-label="Search">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="color: rgb(242, 81, 35);" stroke-width="2">
    <circle cx="11" cy="11" r="7"/>
    <path d="M20 20l-3-3"/>
</svg>
                </button>
            </form>
            @include('web.partials.notification-picker')
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
                        @if ($webCustomer->is_guest)
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
            <a href="{{ route('web.services.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.services.*')]) @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h6"/></svg>
                Services
            </a>
            <a href="{{ route('web.catalog.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.catalog.*') && ! request()->routeIs('web.services.*')]) @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Categories
            </a>
            @auth('customer')
                @unless ($webCustomer->is_guest)
                    <a href="{{ route('web.bookings.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.bookings.*') && ! request()->routeIs('web.bookings.overview')]) @click="mobileOpen=false">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        My bookings
                    </a>
                    <a href="{{ route('web.chat.index') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.chat.*')]) @click="mobileOpen=false">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        Chat
                    </a>
                @else
                    <a href="{{ route('web.register', ['redirect' => route('web.chat.index')]) }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.chat.*')]) @click="mobileOpen=false">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        Chat
                    </a>
                @endunless
            @else
                <a href="{{ route('web.login', ['redirect' => route('web.chat.index')]) }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.chat.*')]) @click="mobileOpen=false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    Chat
                </a>
            @endauth
            <a href="{{ route('web.contact') }}" @class(['jbw-mnav-link', 'is-active' => request()->routeIs('web.contact')]) @click="mobileOpen=false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.27 9.5a19.79 19.79 0 01-3-8.59A2 2 0 012.22 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 9.91a16 16 0 006.2 6.2l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                Contact
            </a>
        </nav>
        @auth('customer')
            <div class="jbw-mobile-nav-user">
                @if ($webCustomer->is_guest)
                    <a href="{{ route('web.register', ['redirect' => url()->current()]) }}" class="jbw-btn jbw-btn--primary jbw-btn--block" @click="mobileOpen=false">Create account</a>
                    <a href="{{ route('web.login') }}" class="jbw-mnav-link" @click="mobileOpen=false">Sign in</a>
                @else
                    <a href="{{ route('web.profile.edit') }}" class="jbw-mnav-link" @click="mobileOpen=false">My Profile</a>
                    <a href="{{ route('web.bookings.index') }}" class="jbw-mnav-link" @click="mobileOpen=false">My Bookings</a>
                @endif
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
