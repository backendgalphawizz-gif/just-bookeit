@extends('admin.layouts.app')
@section('title', 'Send notification')
@section('page_title', 'Send notification')
@section('page_subtitle', 'Logged for delivery — connect FCM/SMS/email provider for live send')
@section('back_href', route('admin.notifications.index'))
@section('content')
    <form method="POST" action="{{ route('admin.notifications.store') }}" class="jb-card max-w-2xl">
        @csrf
        <div class="jb-card-body space-y-6">
            @include('admin.partials.form-input', ['label' => 'Title', 'name' => 'title', 'value' => old('title'), 'required' => true, 'full' => true])
            @include('admin.partials.form-input', ['label' => 'Message', 'name' => 'message', 'type' => 'textarea', 'rows' => 5, 'value' => old('message'), 'required' => true, 'full' => true])
            <x-admin.form-select label="Channel" name="channel" :required="true">
                @foreach (['push' => 'Push notification', 'email' => 'Email', 'sms' => 'SMS'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('channel') === $val)>{{ $label }}</option>
                @endforeach
            </x-admin.form-select>
            <x-admin.form-select label="Audience" name="audience" :required="true">
                <option value="all_customers" @selected(old('audience') === 'all_customers')>All customers ({{ $customerCount }})</option>
                <option value="all_vendors" @selected(old('audience') === 'all_vendors')>All vendors ({{ $vendorCount }})</option>
                <option value="all_drivers" @selected(old('audience') === 'all_drivers')>All drivers ({{ $driverCount }})</option>
                <option value="customers" @selected(old('audience') === 'customers')>Active customers</option>
                <option value="vendors" @selected(old('audience') === 'vendors')>Active vendors</option>
                <option value="drivers" @selected(old('audience') === 'drivers')>Active drivers</option>
            </x-admin.form-select>
        </div>
        <div class="border-t border-slate-100 px-6 py-4 flex gap-3">
            <x-admin.button variant="primary" type="submit">Send</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.notifications.index')">Cancel</x-admin.button>
        </div>
    </form>
@endsection
