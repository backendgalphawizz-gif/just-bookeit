@php
    $admin = auth('admin')->user();
@endphp
<div
    class="jb-profile-menu"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    <button
        type="button"
        class="jb-profile-trigger"
        :class="open && 'jb-profile-trigger--open'"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="Account menu"
    >
        @if ($admin->avatar_url)
            <img src="{{ $admin->avatar_url }}" alt="" class="jb-profile-avatar">
        @else
            <span class="jb-profile-avatar jb-profile-avatar--initials">{{ $admin->initials() }}</span>
        @endif
        <svg class="jb-profile-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="jb-profile-dropdown-enter"
        x-transition:enter-start="jb-profile-dropdown-enter-start"
        x-transition:enter-end="jb-profile-dropdown-enter-end"
        x-transition:leave="jb-profile-dropdown-leave"
        x-transition:leave-start="jb-profile-dropdown-leave-start"
        x-transition:leave-end="jb-profile-dropdown-leave-end"
        class="jb-profile-dropdown"
        role="menu"
    >
        <div class="jb-profile-dropdown-header">
            @if ($admin->avatar_url)
                <img src="{{ $admin->avatar_url }}" alt="" class="jb-profile-dropdown-avatar">
            @else
                <span class="jb-profile-dropdown-avatar jb-profile-avatar--initials">{{ $admin->initials() }}</span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">{{ $admin->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ $admin->email }}</p>
            </div>
        </div>
        <div class="jb-profile-dropdown-body">
            <a href="{{ route('admin.profile.edit') }}" class="jb-profile-dropdown-item" role="menuitem" @click="open = false">
                <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0v.75H4.5v-.75z" />
                </svg>
                Profile
            </a>
            <button
                type="button"
                class="jb-profile-dropdown-item jb-profile-dropdown-item--danger w-full"
                role="menuitem"
                @click="open = false; $store.jbConfirm.ask($refs.logoutForm, { title: 'Log out?', message: 'You will be signed out of your admin account.', confirmLabel: 'Log out' })"
            >
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
                Logout
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.logout') }}" x-ref="logoutForm" class="hidden">
        @csrf
    </form>
</div>
