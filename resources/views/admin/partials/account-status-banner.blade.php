@props([
    'tone' => 'orange',
    'title',
    'reason' => null,
    'emptyReason' => 'No reason recorded.',
    'meta' => [],
    'actionRoute' => null,
    'actionLabel' => null,
    'showAction' => false,
])

@php
    $palette = match ($tone) {
        'orange' => [
            'card' => 'border-orange-200 bg-orange-50/80',
            'title' => 'text-orange-800',
            'reason' => 'text-orange-950',
            'meta' => 'text-orange-900/80',
            'metaLabel' => 'text-orange-900',
        ],
        default => [
            'card' => 'border-rose-200 bg-rose-50/80',
            'title' => 'text-rose-800',
            'reason' => 'text-rose-950',
            'meta' => 'text-rose-900/80',
            'metaLabel' => 'text-rose-900',
        ],
    };
@endphp

<div {{ $attributes->class(['jb-account-status-banner', 'jb-card', 'mb-6', $palette['card']]) }}>
    <div class="jb-card-body">
        <div class="jb-account-status-banner__layout">
            <div class="jb-account-status-banner__content">
                <p class="jb-account-status-banner__title {{ $palette['title'] }}">{{ $title }}</p>
                <p class="jb-account-status-banner__reason {{ $palette['reason'] }}">
                    {{ filled($reason) ? $reason : $emptyReason }}
                </p>
                @if (count($meta) > 0)
                    <dl class="jb-account-status-banner__meta {{ $palette['meta'] }}">
                        @foreach ($meta as $item)
                            <div>
                                <dt class="{{ $palette['metaLabel'] }}">{{ $item['label'] }}</dt>
                                <dd>{{ $item['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
            @if ($showAction && $actionRoute && $actionLabel)
                <div class="jb-account-status-banner__actions">
                    <form method="POST" action="{{ $actionRoute }}">
                        @csrf
                        <x-admin.button variant="success" type="submit">{{ $actionLabel }}</x-admin.button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
