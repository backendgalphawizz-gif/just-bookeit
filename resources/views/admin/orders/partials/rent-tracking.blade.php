{{-- Rental orders only — never rendered for purchase (sale) bookings. --}}
@if ($order->isRental())
@php
    $rental = $order->rentalTrackingSummary();
@endphp
@if ($rental)
    <div class="jb-booking-card jb-rent-tracking">
        <div class="jb-rent-tracking-head">
            <div>
                <h3 class="jb-booking-card-title mb-0">Rent tracking</h3>
                <p class="jb-rent-tracking-phase jb-rent-tracking-phase--{{ $rental['phase'] }}">{{ $rental['phase_label'] }}</p>
            </div>
            @if ($rental['duration_days'])
                <div class="jb-rent-duration-badge" title="Total rental duration">
                    <span class="jb-rent-duration-badge__value">{{ $rental['duration_days'] }}</span>
                    <span class="jb-rent-duration-badge__label">days</span>
                </div>
            @endif
        </div>

        <div class="jb-rent-tracking-stats">
            <div class="jb-rent-stat">
                <span class="jb-rent-stat__label">Rental duration</span>
                <strong class="jb-rent-stat__value">
                    @if ($rental['duration_days'])
                        {{ $rental['duration_days'] }} days
                    @else
                        —
                    @endif
                </strong>
            </div>
            <div class="jb-rent-stat">
                <span class="jb-rent-stat__label">Days elapsed</span>
                <strong class="jb-rent-stat__value">
                    @if ($rental['days_elapsed'] !== null)
                        {{ $rental['days_elapsed'] }} / {{ $rental['duration_days'] ?? '—' }}
                    @else
                        —
                    @endif
                </strong>
            </div>
            <div class="jb-rent-stat">
                <span class="jb-rent-stat__label">Days remaining</span>
                <strong class="jb-rent-stat__value">{{ $rental['days_remaining'] ?? '—' }}</strong>
            </div>
            <div class="jb-rent-stat">
                <span class="jb-rent-stat__label">Return due</span>
                <strong class="jb-rent-stat__value">{{ $rental['return_due_date'] ?? '—' }}</strong>
            </div>
        </div>

        @if ($rental['progress_percent'] !== null)
            <div class="jb-rent-progress">
                <div class="jb-rent-progress__meta">
                    <span>Rental progress</span>
                    <span>{{ $rental['progress_percent'] }}%</span>
                </div>
                <div class="jb-rent-progress__bar" role="progressbar" aria-valuenow="{{ $rental['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                    <span class="jb-rent-progress__fill" style="width: {{ $rental['progress_percent'] }}%"></span>
                </div>
            </div>
        @endif

        <dl class="jb-rent-date-grid">
            <div><dt>Start date</dt><dd>{{ $rental['start_date'] ?? '—' }}</dd></div>
            <div><dt>End date</dt><dd>{{ $rental['end_date'] ?? '—' }}</dd></div>
            @if ($rental['event_date'])
                <div><dt>Event date</dt><dd>{{ $rental['event_date'] }}</dd></div>
            @endif
        </dl>

        <ol class="jb-booking-track jb-rent-tracking-timeline">
            @foreach ($order->trackRentalSteps() as $step)
                <li class="jb-booking-track-step jb-booking-track-step--{{ $step['state'] }}">
                    <span class="jb-booking-track-marker" aria-hidden="true">
                        @if ($step['state'] === 'done')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </span>
                    <div class="jb-booking-track-body">
                        <p class="jb-booking-track-label">{{ $step['label'] }}</p>
                        @if ($step['time'])
                            <p class="jb-booking-track-time">{{ $step['time'] }}</p>
                        @endif
                        @if (! empty($step['detail']))
                            <p class="jb-rent-track-detail">{{ $step['detail'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endif
@endif
