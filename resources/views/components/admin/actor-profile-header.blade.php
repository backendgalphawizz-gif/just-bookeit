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
    <div class="min-w-0">
        <p class="text-lg font-bold text-slate-900">{{ $title }}</p>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div class="mt-2">{{ $slot }}</div>
        @endif
    </div>
</div>
