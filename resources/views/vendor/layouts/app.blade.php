<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Vendor Panel') — Just Book IT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('vendor.partials.styles')
    @include('partials.panel-lightbox-assets')
</head>
<body class="vp-body" x-data="{ sidebarOpen: false, productsOpen: {{ request()->routeIs('vendor.products.*') ? 'true' : 'false' }} }">
<div class="vp-shell">
    <div class="vp-overlay lg:hidden" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

    <aside class="vp-sidebar" :class="{ 'is-open': sidebarOpen }" id="vp-sidebar">
        <div class="vp-sidebar-brand">
            @include('vendor.partials.logo')
        </div>

        <nav class="vp-nav">
            @foreach ($vendorMenu as $item)
                @if (!empty($item['children']))
                    <button type="button" class="vp-nav-group-btn" :class="{ 'vp-nav-group-btn--open': productsOpen }" @click="productsOpen = !productsOpen">
                        <span style="display:flex;align-items:center;gap:.75rem;">
                            @include('vendor.partials.nav-icon', ['icon' => $item['icon'] ?? 'products'])
                            {{ $item['label'] }}
                        </span>
                        <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="vp-nav-sub" x-show="productsOpen" x-cloak>
                        @foreach ($item['children'] as $child)
                            <a href="{{ route($child['route'], $child['params'] ?? []) }}"
                               class="vp-nav-link {{ !empty($child['active']) ? 'vp-nav-link--active' : '' }}"
                               @click="sidebarOpen = false">
                                {{ $child['label'] }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <a href="{{ route($item['route']) }}"
                       class="vp-nav-link {{ !empty($item['active']) ? 'vp-nav-link--active' : '' }}"
                       @click="sidebarOpen = false">
                        @include('vendor.partials.nav-icon', ['icon' => $item['icon'] ?? 'dashboard'])
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="vp-sidebar-foot">
            <form method="POST" action="{{ route('vendor.logout') }}"
                  data-vp-confirm="You will be signed out of the vendor panel."
                  data-vp-confirm-title="Log out?"
                  data-vp-confirm-label="Log out">@csrf
                <button type="submit" class="vp-btn vp-btn--ghost vp-btn--block">
                    @include('vendor.partials.nav-icon', ['icon' => 'logout'])
                    Logout
                </button>
            </form>
            <p>© {{ date('Y') }} Vendor Panel</p>
        </div>
    </aside>

    <div class="vp-main">
        <header class="vp-topbar">
            <div class="vp-topbar-left">
                <button type="button" class="vp-menu-btn" @click="sidebarOpen = true" aria-label="Open menu">
                    <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <div class="vp-topbar-user">
                    <div class="vp-avatar">
                        @if ($vendorUser?->avatarUrl())
                            <img src="{{ $vendorUser->avatarUrl() }}" alt="{{ $vendorUser->displayName() }}">
                        @else
                            {{ $vendorUser?->avatarInitial() ?? 'V' }}
                        @endif
                    </div>
                    <div>
                        <div class="vp-user-name">{{ $vendorUser?->displayName() }}</div>
                        <div class="vp-user-greet">Hello! 👋</div>
                    </div>
                </div>
            </div>
            <div class="vp-topbar-right">
                <div class="vp-topbar-wallets">
                    <a href="{{ route('vendor.payments.index') }}" class="vp-topbar-wallet vp-topbar-wallet--digital" title="Payments held for 15 days">
                        <span class="vp-topbar-wallet-label">Digital</span>
                        <span class="vp-topbar-wallet-value">₹{{ number_format($vendorUser?->digital_wallet_balance ?? 0, 0) }}</span>
                    </a>
                    <a href="{{ route('vendor.payments.index') }}" class="vp-topbar-wallet vp-topbar-wallet--actual" title="Available for withdrawal">
                        <span class="vp-topbar-wallet-label">Actual</span>
                        <span class="vp-topbar-wallet-value">₹{{ number_format($vendorUser?->wallet_balance ?? 0, 0) }}</span>
                    </a>
                </div>
                <button type="button" class="vp-icon-btn" aria-label="Notifications">
                    @include('vendor.partials.nav-icon', ['icon' => 'bell'])
                </button>
            </div>
        </header>

        <main class="vp-content">
            @yield('content')
        </main>
    </div>
</div>

@include('vendor.partials.alert')
@include('vendor.partials.global-confirm')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
<script defer src="{{ asset('js/vendor-panel.js') }}"></script>
@stack('scripts')
</body>
</html>
