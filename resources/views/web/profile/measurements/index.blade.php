@extends('web.layouts.profile')

@section('title', 'Measurements')

@section('content')
@php
    $fieldMap = \App\Support\WebMeasurementForm::labelToField();
@endphp

<div class="jbw-card jbw-profile-panel">
    <div class="jbw-profile-panel-head">
        <h2 class="jbw-profile-panel-title">Measurements</h2>
        <p class="jbw-profile-panel-sub">View and manage your measurement profiles.</p>
    </div>

    <div class="jbw-measure-list">
        @forelse ($profiles as $profile)
            @php
                $values = \App\Support\WebMeasurementForm::valuesFromProfile($profile);
                $typeSections = \App\Support\WebMeasurementForm::sectionsForType($profile->measurement_type);
                $previewFields = collect($typeSections)->flatten()->take(6);
            @endphp
            <div class="jbw-measure-profile-card">
                <div class="jbw-measure-profile-head">
                    <div>
                        <p class="jbw-measure-profile-name">{{ $profile->name }}</p>
                        <p class="jbw-measure-profile-meta">
                            {{ ucfirst($profile->measurement_type ?? 'women') }} profile
                            · ID #{{ $profile->id }}
                        </p>
                    </div>
                    <div class="jbw-measure-profile-actions">
                        <a href="{{ route('web.profile.measurements.edit', $profile) }}" class="jbw-measure-profile-link">Edit</a>
                        @if ($profiles->count() > 1)
                            <form method="POST" action="{{ route('web.profile.measurements.destroy', $profile) }}" onsubmit="return confirm('Remove this measurement profile?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="jbw-measure-profile-link jbw-measure-profile-link--danger">Remove</button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="jbw-measures">
                    @foreach ($previewFields as $label)
                        @php $key = $fieldMap[$label]; @endphp
                        <div class="jbw-measure">
                            <span class="jbw-measure-label">{{ $label }}</span>
                            <span class="jbw-measure-value">{{ filled($values[$key] ?? null) ? $values[$key] : '—' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="jbw-bh-card">
                <p style="margin:0;color:var(--c-muted)">No measurement profiles yet. Add one for faster bookings and a better fit.</p>
            </div>
        @endforelse

        <a href="{{ route('web.profile.measurements.create') }}" class="jbw-add-card">
            <div style="text-align:center">
                <span style="font-size:2rem;color:var(--c-primary)">+</span>
                <strong>{{ $profiles->isEmpty() ? 'Add measurement profile' : 'Add another profile' }}</strong>
            </div>
        </a>
    </div>
</div>
@endsection
