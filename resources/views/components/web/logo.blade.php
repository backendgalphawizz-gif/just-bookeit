@php
    $isDark = in_array($variant, ['footer', 'auth'], true);
    $bColor = $isDark ? '#ffffff' : '#1a2f38';
    $mutedColor = $isDark ? 'rgb(255 255 255 / 0.88)' : '#1a2f38';
    $accent = '#f25123';
    $rootClass = match ($variant) {
        'footer' => 'jbw-logo jbw-logo--footer',
        'auth' => 'jbw-logo jbw-logo--auth',
        'mark' => 'jbw-logo jbw-logo--mark',
        default => 'jbw-logo jbw-logo--header',
    };
    $mediaClass = match ($variant) {
        'footer' => 'jbw-logo-media jbw-logo-media--footer',
        'auth' => 'jbw-logo-media jbw-logo-media--auth',
        'mark' => 'jbw-logo-media jbw-logo-media--mark',
        default => 'jbw-logo-media jbw-logo-media--header',
    };
@endphp

<span {{ $attributes->merge(['class' => $rootClass . ($logoUrl ? ' jbw-logo--image' : ' jbw-logo--fallback')]) }}>
    @if ($logoUrl)
        <span class="{{ $mediaClass }}">
            <img
                src="{{ $logoUrl }}"
                alt="{{ $platformName }}"
                class="jbw-logo-image"
                decoding="async"
                fetchpriority="high"
            >
        </span>
    @else
        <svg class="jbw-logo-mark" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path
                d="M12 10 H26"
                stroke="{{ $accent }}"
                stroke-width="4"
                stroke-linecap="round"
            />
            <path
                d="M12 10 V30 C12 37 16 40 22 40 C25 40 27.5 39 29 37"
                stroke="{{ $accent }}"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <path
                d="M32 10 V40"
                stroke="{{ $bColor }}"
                stroke-width="4"
                stroke-linecap="round"
            />
            <path
                d="M32 10 H42 C45.5 10 48 12.5 48 16 C48 19 46 21.5 43 22 C46.5 22.5 49 25 49 28.5 C49 33 45.5 40 40 40 H32"
                stroke="{{ $bColor }}"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <path
                d="M32 23 H41.5 C44 23 46 21.5 46 19 C46 16.5 44 15 41.5 15 H32"
                stroke="{{ $bColor }}"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>

        @if ($variant !== 'mark')
            <span class="jbw-logo-text">
                <span style="color: {{ $mutedColor }}">Just </span><span class="jbw-logo-text-accent" style="color: {{ $accent }}">Book</span><span style="color: {{ $mutedColor }}"> IT</span>
            </span>
        @endif
    @endif
</span>
