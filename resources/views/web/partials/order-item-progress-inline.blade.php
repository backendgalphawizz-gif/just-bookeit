@php
    $steps = $order->trackBookingSteps();
    $doneCount = collect($steps)->where('state', 'done')->count();
    $totalSteps = max(1, count($steps));
    $percent = (int) round(($doneCount / $totalSteps) * 100);
    $rental = $order->rentalTrackingSummary();
    $statusClass = match($order->status) {
        'new', 'pending_acceptance' => 'new',
        'in_progress', 'accepted' => 'in_progress',
        'delivered' => 'delivered',
        'cancelled', 'refunded' => 'cancelled',
        default => 'default',
    };
    $compact = !empty($compact);
@endphp

<div class="jbw-order-item-progress{{ $compact ? ' jbw-order-item-progress--compact' : '' }}">
    <div class="jbw-order-item-progress-head">
        <span class="jbw-order-item-progress-label">Item status</span>
        <span class="jbw-status jbw-status--{{ $statusClass }} jbw-status--sm">{{ $order->statusLabel() }}</span>
    </div>

    <div class="jbw-order-item-progress-bar" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <span class="jbw-order-item-progress-fill" style="width:{{ $percent }}%"></span>
    </div>

    <ol class="jbw-order-item-steps">
        @foreach ($steps as $index => $step)
            <li class="jbw-order-item-step jbw-order-item-step--{{ $step['state'] }}{{ $loop->last ? ' is-last' : '' }}" title="{{ $step['label'] }}">
                <span class="jbw-order-item-step-dot" aria-hidden="true">
                    @if ($step['state'] === 'done')
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </span>
                <span class="jbw-order-item-step-text">{{ $step['label'] }}</span>
            </li>
        @endforeach
    </ol>

    @if ($rental && $order->status !== 'cancelled' && $order->status !== 'refunded')
        <div class="jbw-order-item-rental">
            <div class="jbw-order-item-rental-head">
                <span>Rental</span>
                @if ($rental['progress_percent'] !== null)
                    <span>{{ $rental['progress_percent'] }}%</span>
                @endif
            </div>
            @if ($rental['start_date'] && $rental['end_date'])
                <p class="jbw-order-item-rental-dates">{{ $rental['start_date'] }} – {{ $rental['end_date'] }}</p>
            @endif
            @if ($rental['progress_percent'] !== null)
                <div class="jbw-order-item-progress-bar jbw-order-item-progress-bar--thin" role="progressbar" aria-valuenow="{{ $rental['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                    <span class="jbw-order-item-progress-fill" style="width:{{ $rental['progress_percent'] }}%"></span>
                </div>
            @endif
        </div>
    @endif
</div>
