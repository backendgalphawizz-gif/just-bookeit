@props(['title', 'subtitle' => null, 'centered' => false])

<div class="jbw-auth-page">
    {{-- Left panel — fashion background + logo --}}
    <aside class="jbw-auth-page-brand" aria-label="Just Book IT">
        <div class="jbw-auth-brand-bg"></div>
        <div class="jbw-auth-brand-overlay"></div>
        <div class="jbw-auth-page-brand-inner">
            <a href="/" class="jbw-auth-brand-logo-link">
                <svg viewBox="0 0 88 56" fill="none" xmlns="http://www.w3.org/2000/svg" class="jbw-auth-brand-svg" aria-hidden="true">
                    <path d="M10 8 H34" stroke="#f25123" stroke-width="8" stroke-linecap="round"/>
                    <path d="M10 8 V38 C10 48 16 52 26 52 C30 52 34 50 37 47" stroke="#f25123" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M46 8 V52" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
                    <path d="M46 8 H64 C72 8 78 14 78 22 C78 28 74 32 68 34 C76 36 82 42 82 50 C82 58 74 52 64 52 H46" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M46 34 H64 C70 34 76 30 76 24 C76 18 70 14 64 14 H46" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="jbw-auth-brand-name">Just Book IT</span>
            </a>
            <div class="jbw-auth-brand-tagline">
                <p class="jbw-auth-brand-quote">"Look extraordinary<br>for every occasion."</p>
                <p class="jbw-auth-brand-sub">India's premier fashion rental platform</p>
            </div>
        </div>
    </aside>

    {{-- Right panel — form card --}}
    <div class="jbw-auth-page-form">
        <div @class(['jbw-auth-card', 'jbw-auth-card--centered' => $centered])>
            <h1 class="jbw-auth-title">{{ $title }}</h1>
            @if ($subtitle)
                <p class="jbw-auth-sub">{{ $subtitle }}</p>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
