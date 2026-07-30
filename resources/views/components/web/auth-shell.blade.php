@props(['title', 'subtitle' => null, 'centered' => false])

<div class="jbw-auth-page">
    <div class="jbw-auth-stage">
        {{-- Left — logo --}}
        <aside class="jbw-auth-page-brand" aria-label="Just Book IT">
            <div class="jbw-auth-page-brand-inner">
                <a href="/" class="jbw-auth-brand-logo-link">
                    <img class="auth-logo" src="https://just-bookeit.developmentalphawizz.com/storage/logos/cSc7vM7AbLBl71T3uv0C2NgQLi1NcnbKhSFmHUsI.png" alt="Just Book It">
                </a>
            </div>
        </aside>

        {{-- Right — form card --}}
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
</div>
