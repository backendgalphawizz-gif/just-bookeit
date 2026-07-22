@php
    $customer = $webCustomer ?? auth('customer')->user();
@endphp
<aside class="jbw-profile-sidebar">
    <div class="jbw-profile-card">
        <div class="jbw-profile-card-photo-wrap">
            @if ($customer->profileImageUrl())
                <img src="{{ $customer->profileImageUrl() }}" alt="" class="jbw-profile-card-photo">
            @else
                <span class="jbw-profile-card-photo jbw-profile-card-photo--fallback">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
            @endif
            <a href="{{ route('web.profile.edit') }}" class="jbw-profile-card-camera" aria-label="Edit profile photo">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
            </a>
        </div>
        <p class="jbw-profile-card-name">{{ $customer->name }}</p>
        @if ($customer->mobile)
            <p class="jbw-profile-card-meta">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <span>{{ $customer->mobile }}</span>
            </p>
        @endif
        @if ($customer->email)
            <p class="jbw-profile-card-meta">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <span>{{ $customer->email }}</span>
            </p>
        @endif
    </div>

    <nav class="jbw-profile-nav" aria-label="Profile">
        <a href="{{ route('web.profile.edit') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.profile.edit')])>
            <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </span>
            Edit Profile
        </a>
        <a href="{{ route('web.bookings.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.bookings.*')])>
            <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6v4H9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h4"/>
                </svg>
            </span>
            Booking History
        </a>
        <a href="{{ route('web.profile.measurements') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.profile.measurements*')])>
            <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20L20 4M7 17l2 2M10 14l2 2M13 11l2 2M16 8l2 2"/>
                </svg>
            </span>
            Measurements
        </a>
        <a href="{{ route('web.profile.addresses') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.profile.addresses*')])>
            <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            Saved Addresses
        </a>
        <a href="{{ route('web.chat.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>
            <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4-.8L3 20l1.2-3.2A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </span>
            Chat
        </a>
        <a href="{{ route('web.notifications.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.notifications.*')])>
            <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </span>
            Notifications
        </a>
        <a href="{{ route('web.contact') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.contact')])>
            <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 18v-6a9 9 0 0118 0v6"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>
                </svg>
            </span>
            Help &amp; Support
        </a>
        <a href="{{ route('web.faq') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.faq')])>
            <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.09 9a3 3 0 115.83 1c0 2-3 3-3 3"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 17h.01"/>
                </svg>
            </span>
            FAQs
        </a>

        <form method="POST" action="{{ route('web.logout') }}" class="jbw-profile-logout">
            @csrf
            <button type="submit">
                <span aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 20H6a2 2 0 01-2-2V6a2 2 0 012-2h7"/>
                    </svg>
                </span>
                Log Out
            </button>
        </form>
    </nav>
</aside>
