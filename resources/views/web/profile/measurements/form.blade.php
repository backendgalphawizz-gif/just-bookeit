@extends('web.layouts.app')

@section('title', 'My Measurements')

@section('content')
<div class="jbw-container jbw-measure-page">
    <div class="jbw-measure-topbar">
        <a href="{{ route('web.profile.measurements') }}" class="jbw-measure-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            My Measurements
        </a>
        <a href="{{ route('web.home') }}" class="jbw-measure-skip">Skip for now</a>
    </div>

    <div class="jbw-measure-card">
        @php
            $sections = [
                'Upper Body' => ['Blouse length', 'Shoulder', 'Arm hole', 'Chest', 'Waist', 'Dot point'],
                'Sleeves & Neck' => ['Sleeve length', 'Sleeve loose', 'Front neck', 'Back neck'],
                'Lower Body' => ['Hip', 'Seat', 'Bottom length', 'Leg loose', 'Thigh', 'Knees'],
                'Full Lengths' => ['Top length', 'Half length', 'Slit'],
            ];
        @endphp

        <form method="POST" action="#">
            @foreach ($sections as $title => $fields)
                <div class="jbw-measure-section">
                    <h2 class="jbw-measure-section-title">{{ $title }}</h2>
                    <div class="jbw-measure-form-grid">
                        @foreach ($fields as $field)
                            <div class="jbw-field">
                                <label class="jbw-label">{{ $field }}</label>
                                <input type="text" class="jbw-input jbw-input--measure" placeholder="—">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="jbw-measure-actions">
                <a href="{{ route('web.home') }}" class="jbw-btn jbw-btn--primary jbw-btn--cta">Save &amp; continue</a>
            </div>
        </form>
    </div>
</div>
@endsection
