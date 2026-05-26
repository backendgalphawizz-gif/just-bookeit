<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('admin.partials.document-head', ['branding' => $adminBranding ?? []])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('admin.partials.admin-theme-vars')
</head>
<body class="jb-admin-shell" x-data="{ sidebarOpen: false }">
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="jb-sidebar transition-transform duration-200 ease-out"
    >
        <div class="jb-sidebar-brand shrink-0">
            <div class="jb-sidebar-brand-row">
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="jb-sidebar-brand-link {{ !empty($adminBranding['logo_url']) ? 'jb-sidebar-brand-link--logo' : '' }}"
                >
                    @if (!empty($adminBranding['logo_url']))
                        <img
                            src="{{ $adminBranding['logo_url'] }}"
                            alt="{{ $adminBranding['name'] }}"
                            class="jb-sidebar-brand-logo"
                        >
                    @else
                        <p class="jb-sidebar-brand-title">{{ $adminBranding['name'] }}</p>
                        <p class="jb-sidebar-brand-sub">Administration</p>
                    @endif
                </a>
                <button type="button" class="jb-sidebar-close shrink-0 rounded-lg p-2 text-slate-400 hover:bg-slate-800 lg:hidden" @click="sidebarOpen = false" aria-label="Close menu">✕</button>
            </div>
        </div>

        <nav class="jb-sidebar-nav px-2 pb-4">
            @foreach ($adminMenu as $group)
                <p class="jb-nav-group-label">{{ $group['name'] }}</p>
                @foreach ($group['items'] as $item)
                    @if ($item['enabled'])
                        <a href="{{ route($item['route']) }}" class="jb-nav-link {{ $item['active'] ? 'jb-nav-link--active' : '' }}">
                            @include('admin.partials.nav-icon', ['icon' => $item['icon']])
                            <span>{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span class="jb-nav-badge">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @else
                        <span class="jb-nav-link--disabled" title="Coming soon">
                            @include('admin.partials.nav-icon', ['icon' => $item['icon']])
                            <span>{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span class="jb-nav-badge bg-slate-700">{{ $item['badge'] }}</span>
                            @endif
                        </span>
                    @endif
                @endforeach
            @endforeach
        </nav>
    </aside>

    <div class="jb-main-column">
        <header class="jb-topbar shrink-0">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <button type="button" class="jb-btn jb-btn-secondary jb-btn-sm shrink-0 lg:hidden" @click="sidebarOpen = true" aria-label="Open menu">☰</button>
                <div class="min-w-0">
                    <h1 class="jb-topbar-title truncate">@yield('page_title', 'Dashboard')</h1>
                    @hasSection('page_subtitle')
                        <p class="jb-topbar-sub truncate">@yield('page_subtitle')</p>
                    @endif
                </div>
            </div>
            <div class="jb-topbar-actions">
                @yield('header_actions')
                @include('admin.partials.profile-menu')
            </div>
        </header>

        <main class="jb-main flex-1">
            @yield('content')
        </main>
    </div>

    @include('admin.components.alert')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
