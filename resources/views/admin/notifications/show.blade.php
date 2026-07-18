@extends('admin.layouts.app')
@section('title', $notification->title)
@section('page_title', $notification->title)
@section('page_subtitle')
    Sent {{ $notification->sent_at?->diffForHumans() ?? 'recently' }}
@endsection
@section('back_href', route('admin.notifications.index'))
@section('content')
    <div class="jb-card max-w-2xl">
        <div class="jb-card-body space-y-4">
            <div><p class="text-xs font-semibold uppercase text-slate-500">Message</p><p class="mt-1 text-slate-800 whitespace-pre-wrap">{{ $notification->message }}</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Channel</p><p class="mt-1">{{ strtoupper($notification->channel) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Audience</p><p class="mt-1">{{ str_replace('_', ' ', $notification->audience) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Recipients</p><p class="mt-1">{{ $notification->recipients_count }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Sent by</p><p class="mt-1">{{ $notification->admin?->name ?? 'System' }}</p></div>
            </div>
        </div>
    </div>
@endsection
