@php
    $alerts = [];
    if (session('success')) {
        $alerts[] = ['type' => 'success', 'title' => 'Success!', 'message' => session('success')];
    }
    if (session('error')) {
        $alerts[] = ['type' => 'error', 'title' => 'Something went wrong', 'message' => session('error')];
    }
    if (session('info')) {
        $alerts[] = ['type' => 'warning', 'title' => 'Notice', 'message' => session('info')];
    }
    if (isset($errors) && $errors->any()) {
        $alerts[] = [
            'type' => 'warning',
            'title' => 'Please check the form',
            'message' => $errors->count() === 1
                ? $errors->first()
                : 'Please fix '.$errors->count().' errors in the form below.',
        ];
    }
@endphp

@if (count($alerts) > 0)
    @foreach ($alerts as $alert)
        <div
            class="vp-modal-alert"
            x-data="{ show: true }"
            x-show="show"
            x-cloak
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="vp-alert-title-{{ $loop->index }}"
            aria-describedby="vp-alert-desc-{{ $loop->index }}"
        >
            <div
                class="vp-modal-alert-backdrop"
                x-show="show"
                x-transition:enter="vp-modal-enter"
                x-transition:enter-start="vp-modal-enter-start"
                x-transition:enter-end="vp-modal-enter-end"
                x-transition:leave="vp-modal-leave"
                x-transition:leave-start="vp-modal-leave-start"
                x-transition:leave-end="vp-modal-leave-end"
                @click="show = false"
            ></div>

            <div
                class="vp-modal-alert-card"
                x-show="show"
                x-transition:enter="vp-modal-card-enter"
                x-transition:enter-start="vp-modal-card-enter-start"
                x-transition:enter-end="vp-modal-card-enter-end"
                x-transition:leave="vp-modal-card-leave"
                x-transition:leave-start="vp-modal-card-leave-start"
                x-transition:leave-end="vp-modal-card-leave-end"
                @click.stop
            >
                <div class="vp-modal-alert-icon-wrap vp-modal-alert-icon-wrap--{{ $alert['type'] }}">
                    <div class="vp-modal-alert-icon-ring"></div>
                    <div class="vp-modal-alert-icon">
                        @if ($alert['type'] === 'success')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        @elseif ($alert['type'] === 'error')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
                            </svg>
                        @endif
                    </div>
                </div>

                <h2 id="vp-alert-title-{{ $loop->index }}" class="vp-modal-alert-title">{{ $alert['title'] }}</h2>
                <p id="vp-alert-desc-{{ $loop->index }}" class="vp-modal-alert-message">{{ $alert['message'] }}</p>

                <button type="button" class="vp-modal-alert-btn" @click="show = false">OK</button>
            </div>
        </div>
    @endforeach
@endif
