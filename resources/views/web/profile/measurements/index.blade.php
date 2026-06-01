@extends('web.layouts.profile')

@section('title', 'Measurements')
@section('page_title', 'Measurements')
@section('page_subtitle', 'View and manage your measurements.')

@section('content')
    <div class="jbw-card">
        <div style="display:flex;justify-content:space-between;align-items:start;gap:1rem;margin-bottom:1rem">
            <div>
                <p style="font-weight:800;margin:0">{{ $customer->name }}</p>
                <p style="font-size:0.8125rem;color:var(--jbw-muted);margin:0.25rem 0 0">Default profile</p>
            </div>
            <a href="{{ route('web.profile.measurements.create') }}" style="font-size:0.8125rem;font-weight:700;color:var(--jbw-primary)">Edit</a>
        </div>
        <div class="jbw-measures">
            <div class="jbw-measure"><span class="jbw-measure-label">Height</span><span class="jbw-measure-value">—</span></div>
            <div class="jbw-measure"><span class="jbw-measure-label">Chest</span><span class="jbw-measure-value">—</span></div>
            <div class="jbw-measure"><span class="jbw-measure-label">Waist</span><span class="jbw-measure-value">—</span></div>
        </div>
        <p style="margin:1rem 0 0"><a href="{{ route('web.profile.measurements.create') }}" style="color:var(--jbw-primary);font-weight:700;font-size:0.875rem">View full profile →</a></p>
    </div>

    <a href="{{ route('web.profile.measurements.create') }}" class="jbw-add-card" style="margin-top:1rem">
        <div style="text-align:center">
            <span style="font-size:2rem;color:var(--jbw-primary)">+</span>
            <strong>Add new profile</strong>
            <span style="font-size:0.8125rem">Create measurements for a guest or new look</span>
        </div>
    </a>
@endsection
