@if ($booking->isRental())
@php
    $rental = $booking->rentalTrackingSummary();
@endphp
@if ($rental)
    <div class="vp-booking-card vp-rent-tracking">
        <div class="vp-rent-tracking-head">
            <div>
                <h3 class="vp-booking-card-title vp-booking-card-title--flush">Rent tracking</h3>
                <p class="vp-rent-tracking-phase vp-rent-tracking-phase--{{ $rental['phase'] }}">{{ $rental['phase_label'] }}</p>
            </div>
            @if ($rental['duration_days'])
                <div class="vp-rent-duration-badge" title="Total rental duration">
                    <span class="vp-rent-duration-badge__value">{{ $rental['duration_days'] }}</span>
                    <span class="vp-rent-duration-badge__label">days</span>
                </div>
            @endif
        </div>

        <div class="vp-rent-tracking-stats">
            <div class="vp-rent-stat">
                <span class="vp-rent-stat__label">Rental duration</span>
                <strong class="vp-rent-stat__value">{{ $rental['duration_days'] ? $rental['duration_days'].' days' : '—' }}</strong>
            </div>
            <div class="vp-rent-stat">
                <span class="vp-rent-stat__label">Days elapsed</span>
                <strong class="vp-rent-stat__value">
                    @if ($rental['days_elapsed'] !== null)
                        {{ $rental['days_elapsed'] }} / {{ $rental['duration_days'] ?? '—' }}
                    @else
                        —
                    @endif
                </strong>
            </div>
            <div class="vp-rent-stat">
                <span class="vp-rent-stat__label">Days remaining</span>
                <strong class="vp-rent-stat__value">{{ $rental['days_remaining'] ?? '—' }}</strong>
            </div>
            <div class="vp-rent-stat">
                <span class="vp-rent-stat__label">Return due</span>
                <strong class="vp-rent-stat__value">{{ $rental['return_due_date'] ?? '—' }}</strong>
            </div>
        </div>

        @if ($rental['progress_percent'] !== null)
            <div class="vp-rent-progress">
                <div class="vp-rent-progress__meta">
                    <span>Rental progress</span>
                    <span>{{ $rental['progress_percent'] }}%</span>
                </div>
                <div class="vp-rent-progress__bar" role="progressbar" aria-valuenow="{{ $rental['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                    <span class="vp-rent-progress__fill" style="width: {{ $rental['progress_percent'] }}%"></span>
                </div>
            </div>
        @endif

        <dl class="vp-rent-date-grid">
            <div><dt>Start date</dt><dd>{{ $rental['start_date'] ?? '—' }}</dd></div>
            <div><dt>End date</dt><dd>{{ $rental['end_date'] ?? '—' }}</dd></div>
            @if ($rental['event_date'])
                <div><dt>Event date</dt><dd>{{ $rental['event_date'] }}</dd></div>
            @endif
        </dl>

        <ol class="vp-booking-track vp-rent-tracking-timeline">
            @foreach ($booking->trackRentalSteps() as $step)
                <li class="vp-booking-track-step vp-booking-track-step--{{ $step['state'] }}">
                    <span class="vp-booking-track-marker" aria-hidden="true">
                        @if ($step['state'] === 'done')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </span>
                    <div class="vp-booking-track-body">
                        <p class="vp-booking-track-label">{{ $step['label'] }}</p>
                        @if ($step['time'])
                            <p class="vp-booking-track-time">{{ $step['time'] }}</p>
                        @endif
                        @if (! empty($step['detail']))
                            <p class="vp-rent-track-detail">{{ $step['detail'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endif
@endif
