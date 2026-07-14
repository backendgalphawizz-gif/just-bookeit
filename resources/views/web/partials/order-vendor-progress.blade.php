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
@endphp

<div class="jbw-order-track-wrap">
    <div class="jbw-order-track-top">
        <span class="jbw-order-track-title">Fulfillment status</span>
        <span class="jbw-status jbw-status--{{ $statusClass }}">{{ $order->statusLabel() }}</span>
    </div>

    <div class="jbw-order-track-bar" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Order progress {{ $percent }} percent">
        <span class="jbw-order-track-bar-fill" style="width:{{ $percent }}%"></span>
    </div>

    <ol class="jbw-order-track-steps">
        @foreach ($steps as $index => $step)
            <li class="jbw-order-track-step jbw-order-track-step--{{ $step['state'] }}{{ $loop->last ? ' jbw-order-track-step--last' : '' }}">
                <div class="jbw-order-track-step-inner">
                    <span class="jbw-order-track-marker" aria-hidden="true">
                        @if ($step['state'] === 'done')
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                    <span class="jbw-order-track-label">{{ $step['label'] }}</span>
                </div>
            </li>
        @endforeach
    </ol>

    @if ($rental && $order->status !== 'cancelled' && $order->status !== 'refunded')
        <div class="jbw-order-rental-strip">
            <div class="jbw-order-rental-strip-head">
                <span>Rental timeline</span>
                @if ($rental['progress_percent'] !== null)
                    <strong>{{ $rental['progress_percent'] }}% complete</strong>
                @endif
            </div>
            @if ($rental['start_date'] && $rental['end_date'])
                <p class="jbw-order-rental-dates">{{ $rental['start_date'] }} – {{ $rental['end_date'] }}</p>
            @endif
            @if ($rental['progress_percent'] !== null)
                <div class="jbw-order-track-bar jbw-order-track-bar--sub" role="progressbar" aria-valuenow="{{ $rental['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                    <span class="jbw-order-track-bar-fill" style="width:{{ $rental['progress_percent'] }}%"></span>
                </div>
            @endif
            @if ($rental['phase_label'])
                <p class="jbw-order-rental-phase">{{ $rental['phase_label'] }}</p>
            @endif
        </div>
    @endif
</div>
