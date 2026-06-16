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
            <span>✎</span> Edit Profile
        </a>
        <a href="{{ route('web.bookings.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.bookings.*')])>
            <span>📋</span> Booking History
        </a>
        <a href="{{ route('web.profile.measurements') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.profile.measurements*')])>
            <span>📏</span> Measurements
        </a>
        <a href="{{ route('web.profile.addresses') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.profile.addresses')])>
            <span>📍</span> Saved Addresses
        </a>
        <a href="{{ route('web.chat.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.chat.*')])>
            <span>💬</span> Chat
        </a>
        <a href="{{ route('web.notifications.index') }}" @class(['jbw-profile-nav-link', 'is-active' => request()->routeIs('web.notifications.*')])>
            <span>🔔</span> Notifications
        </a>
        <a href="{{ route('web.contact') }}" class="jbw-profile-nav-link"><span>?</span> Help &amp; Support</a>
        <form method="POST" action="{{ route('web.logout') }}" class="jbw-profile-logout">@csrf
            <button type="submit"><span>⏻</span> Log Out</button>
        </form>
    </nav>
</aside>
