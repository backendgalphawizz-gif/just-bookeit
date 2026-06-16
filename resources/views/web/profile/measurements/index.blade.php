@extends('web.layouts.profile')

@section('title', 'Measurements')
@section('page_title', 'Measurements')
@section('page_subtitle', 'View and manage your measurements.')

@section('content')
    @forelse ($profiles as $profile)
        <div class="jbw-card" style="margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between;align-items:start;gap:1rem;margin-bottom:1rem">
                <div>
                    <p style="font-weight:800;margin:0">{{ $profile->name }}</p>
                    <p style="font-size:0.8125rem;color:var(--jbw-muted);margin:0.25rem 0 0">{{ ucfirst($profile->measurement_type ?? 'women') }} profile</p>
                </div>
                <a href="{{ route('web.profile.measurements.create') }}" style="font-size:0.8125rem;font-weight:700;color:var(--jbw-primary)">Edit</a>
            </div>
            <div class="jbw-measures">
                <div class="jbw-measure"><span class="jbw-measure-label">Height</span><span class="jbw-measure-value">{{ $profile->height_cm ?? '—' }}</span></div>
                <div class="jbw-measure"><span class="jbw-measure-label">Chest</span><span class="jbw-measure-value">{{ $profile->chest_cm ?? ($profile->apiMeasurementFields()['chest'] ?? '—') }}</span></div>
                <div class="jbw-measure"><span class="jbw-measure-label">Waist</span><span class="jbw-measure-value">{{ $profile->waist_cm ?? ($profile->apiMeasurementFields()['waist'] ?? '—') }}</span></div>
            </div>
        </div>
    @empty
        <div class="jbw-card">
            <p style="margin:0;color:var(--jbw-muted)">No measurement profile yet. Add one for faster bookings and better fit.</p>
        </div>
    @endforelse

    <a href="{{ route('web.profile.measurements.create') }}" class="jbw-add-card" style="margin-top:1rem">
        <div style="text-align:center">
            <span style="font-size:2rem;color:var(--jbw-primary)">+</span>
            <strong>{{ $profiles->isEmpty() ? 'Add measurement profile' : 'Update measurements' }}</strong>
        </div>
    </a>
@endsection
