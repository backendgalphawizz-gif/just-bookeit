@extends('web.layouts.app')

@section('title', 'My Measurements')

@section('content')
<div class="jbw-container jbw-measure-page">
    <div class="jbw-measure-topbar">
        <a href="{{ route('web.profile.measurements') }}" class="jbw-measure-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            My Measurements
        </a>
        <a href="{{ route('web.catalog.index') }}" class="jbw-measure-skip">Skip for now</a>
    </div>

    <div class="jbw-measure-card">
        <form method="POST" action="{{ route('web.profile.measurements.store') }}">
            @csrf

            <div class="jbw-measure-section">
                <h2 class="jbw-measure-section-title">Profile</h2>
                <div class="jbw-measure-form-grid">
                    <div class="jbw-field">
                        <label class="jbw-label" for="name">Profile name</label>
                        <input type="text" id="name" name="name" class="jbw-input" value="{{ old('name', $profile?->name ?? 'Default profile') }}">
                    </div>
                    <div class="jbw-field">
                        <label class="jbw-label" for="measurement_type">Type</label>
                        <select id="measurement_type" name="measurement_type" class="jbw-select">
                            @foreach (['women' => 'Women', 'men' => 'Men', 'kid' => 'Kids'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('measurement_type', $profile?->measurement_type ?? 'women') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @php
                $fieldMap = \App\Support\WebMeasurementForm::labelToField();
            @endphp

            @foreach ($sections as $title => $fields)
                <div class="jbw-measure-section">
                    <h2 class="jbw-measure-section-title">{{ $title }}</h2>
                    <div class="jbw-measure-form-grid">
                        @foreach ($fields as $field)
                            @php $key = $fieldMap[$field]; @endphp
                            <div class="jbw-field">
                                <label class="jbw-label" for="{{ $key }}">{{ $field }}</label>
                                <input
                                    type="text"
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    class="jbw-input jbw-input--measure"
                                    value="{{ old($key, $values[$key] ?? '') }}"
                                    placeholder="—"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="jbw-measure-actions">
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--cta">SAVE & CONTINUE</button>
            </div>
        </form>
    </div>
</div>
@endsection
