@props([
    'imageUrl' => null,
    'fallbackUrl' => null,
    'title' => '',
    'subtitle' => null,
])

<div class="jb-actor-profile">
    @include('admin.partials.actor-avatar', [
        'imageUrl' => $imageUrl,
        'fallbackUrl' => $fallbackUrl,
        'label' => $title,
        'size' => 'lg',
    ])
    <div class="min-w-0 flex-1">
        <p class="truncate text-lg font-bold text-slate-900" title="{{ $title }}">{{ $title }}</p>
        @if ($subtitle)
            <p class="mt-0.5 truncate text-sm text-slate-500" title="{{ $subtitle }}">{{ $subtitle }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div class="mt-2">{{ $slot }}</div>
        @endif
    </div>
</div>
