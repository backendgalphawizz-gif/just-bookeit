@php
    /** @var \App\Models\Order $order */
    /** @var \App\Models\OrderItem|null $orderItem */
    $orderItem = $orderItem ?? null;
    $status = $orderItem
        ? $orderItem->statusForDisplay($order)
        : ($order->statusForTracking() ?? $order->status);

    $steps = [
        ['keys' => ['new', 'pending_acceptance', 'accepted'], 'label' => 'In Progress'],
        ['keys' => ['in_progress'], 'label' => 'In Transit'],
        ['keys' => ['delivered', 'rental_active', 'rework'], 'label' => 'Delivered'],
        ['keys' => ['re_intransit', 'returned'], 'label' => 'Picked Up'],
        ['keys' => ['re_delivered', 'completed'], 'label' => 'Returned'],
    ];

    $ranks = array_flip(\App\Models\Order::STATUSES);
    $currentRank = $ranks[$status === 'pending_acceptance' ? 'new' : $status] ?? 0;
    $isCancelled = in_array($status, ['cancelled', 'refunded'], true);

    $resolved = collect($steps)->map(function (array $step) use ($ranks, $currentRank, $isCancelled): array {
        $stepRank = max(array_map(fn (string $key) => $ranks[$key] ?? 0, $step['keys']));
        $minRank = min(array_map(fn (string $key) => $ranks[$key] ?? 0, $step['keys']));

        if ($isCancelled) {
            $state = 'cancelled';
        } elseif ($currentRank >= $stepRank) {
            $state = 'done';
        } elseif ($currentRank >= $minRank && $currentRank <= $stepRank) {
            $state = 'current';
        } else {
            $state = 'upcoming';
        }

        return ['label' => $step['label'], 'state' => $state];
    })->all();
@endphp

<ol class="jbw-figma-stepper" aria-label="Booking status">
    @foreach ($resolved as $index => $step)
        <li @class(['jbw-figma-step', 'jbw-figma-step--'.$step['state']])>
            <span class="jbw-figma-step-dot" aria-hidden="true">
                @if ($step['state'] === 'done')
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                @endif
            </span>
            <span class="jbw-figma-step-label">{{ $step['label'] }}</span>
            @if ($index < count($resolved) - 1)
                <span class="jbw-figma-step-line" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
