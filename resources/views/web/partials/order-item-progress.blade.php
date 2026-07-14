@php
    $steps = $order->trackBookingSteps();
    $doneCount = collect($steps)->where('state', 'done')->count();
    $totalSteps = max(1, count($steps));
    $percent = (int) round(($doneCount / $totalSteps) * 100);
    $rental = $order->rentalTrackingSummary();
@endphp

<div class="jbw-item-progress">
    <div class="jbw-item-progress-head">
        <span class="jbw-item-progress-label">Order progress</span>
        <span class="jbw-status jbw-status--{{ match($order->status) {
            'new', 'pending_acceptance' => 'new',
            'in_progress', 'accepted' => 'in_progress',
            'delivered' => 'delivered',
            'cancelled', 'refunded' => 'cancelled',
            default => 'default',
        } }}">{{ $order->statusLabel() }}</span>
    </div>
    <div class="jbw-item-progress-bar" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <span class="jbw-item-progress-fill" style="width:{{ $percent }}%"></span>
    </div>
    <ol class="jbw-item-progress-steps">
        @foreach ($steps as $step)
            <li class="jbw-item-progress-step jbw-item-progress-step--{{ $step['state'] }}">
                <span class="jbw-item-progress-dot" aria-hidden="true"></span>
                <span>{{ $step['label'] }}</span>
            </li>
        @endforeach
    </ol>
    @if ($rental && $rental['progress_percent'] !== null && $order->status !== 'cancelled')
        <div class="jbw-item-rental-progress">
            <div class="jbw-item-progress-head">
                <span class="jbw-item-progress-label">Rental period</span>
                <span class="jbw-item-progress-meta">{{ $rental['progress_percent'] }}%</span>
            </div>
            <div class="jbw-item-progress-bar jbw-item-progress-bar--rental" role="progressbar" aria-valuenow="{{ $rental['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                <span class="jbw-item-progress-fill" style="width:{{ $rental['progress_percent'] }}%"></span>
            </div>
            @if ($rental['start_date'] && $rental['end_date'])
                <p class="jbw-item-progress-dates">{{ $rental['start_date'] }} – {{ $rental['end_date'] }}</p>
            @endif
        </div>
    @endif
</div>
