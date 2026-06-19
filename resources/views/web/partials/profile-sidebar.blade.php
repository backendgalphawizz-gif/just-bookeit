@php
    $customer = $webCustomer ?? auth('customer')->user();
@endphp
<aside class="jbw-profile-sidebar">
    <div class="jbw-profile-card">
        @if ($customer->profileImageUrl())
            <img src="{{ $customer->profileImageUrl() }}" alt="" class="jbw-profile-card-photo">
        @else
            <span class="jbw-profile-card-photo jbw-profile-card-photo--fallback">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
        @endif
        <p class="jbw-profile-card-name">{{ $customer->name }}</p>
        <p class="jbw-profile-card-meta">{{ $customer->mobile }}</p>
        @if ($customer->email)
            <p class="jbw-profile-card-meta">{{ $customer->email }}</p>
        @endif
    </div>
    <nav class="jbw-profile-nav" aria-label="Profile">
        <a href="{{ route('web.profile.edit') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.profile.edit')])>
           <span>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M11 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5"/>
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
    </svg>
</span> Edit Profile
        </a>
        <a href="{{ route('web.bookings.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.bookings.*')])>
           <span>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 3h6v4H9z"/>
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 12h6M9 16h4"/>
    </svg>
</span> Booking History
        </a>
        <a href="{{ route('web.profile.measurements') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.profile.measurements*')])>
            <span>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M4 20L20 4M7 17l2 2M10 14l2 2M13 11l2 2M16 8l2 2"/>
    </svg>
</span> Measurements
        </a>
        <a href="{{ route('web.profile.addresses') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.profile.addresses')])>
            <span>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
</span> Saved Addresses
        </a>
        <a href="{{ route('web.chat.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>
            <span>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4-.8L3 20l1.2-3.2A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
</span> Chat
        </a>
        <a href="{{ route('web.notifications.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.notifications.*')])>
            <span>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
                 a6.002 6.002 0 00-4-5.659V5
                 a2 2 0 10-4 0v0.341C7.67 6.165 6 8.388 6 11v3.159
                 c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1
                 a3 3 0 11-6 0v-1m6 0H9" />
    </svg>
</span> Notifications
        </a>
        <!-- <a href="{{ route('web.contact') }}" class="jbw-profile-nav-link"><span>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M9.09 9a3 3 0 115.82 1c0 2-3 3-3 3" />
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 17h.01" />
        <circle cx="12" cy="12" r="10" />
    </svg>
</span> Help &amp; Support</a> -->
        <form method="POST" action="{{ route('web.logout') }}" class="jbw-profile-logout">@csrf
            <button type="submit"><span>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M17 16l4-4m0 0l-4-4m4 4H9" />
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M13 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h7" />
    </svg>
</span> Log Out</button>
        </form>
    </nav>
</aside>
