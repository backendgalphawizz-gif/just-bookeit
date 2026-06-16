@props(['title', 'subtitle' => null, 'centered' => false])

<div class="jbw-auth-page">
    {{-- Left panel — fashion background + logo --}}
    <aside class="jbw-auth-page-brand" aria-label="Just Book IT">
        <div class="jbw-auth-brand-bg"></div>
        <div class="jbw-auth-brand-overlay"></div>
        <div class="jbw-auth-page-brand-inner">
            <a href="/" class="jbw-auth-brand-logo-link">
                <x-web.logo variant="auth" />
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
