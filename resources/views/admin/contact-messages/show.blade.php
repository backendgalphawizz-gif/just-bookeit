@extends('admin.layouts.app')
@section('title', $message->subject)
@section('page_title', $message->subject)
@section('page_subtitle', 'Received {{ $message->created_at?->diffForHumans() ?? "recently" }}')
@section('back_href', route('admin.contact-messages.index'))
@section('content')
    <div class="jb-card max-w-3xl">
        <div class="jb-card-body space-y-5">
            <div class="flex flex-wrap items-center gap-2">
                @if ($message->isUnread())
                    <span class="jb-badge bg-amber-100 text-amber-800">Unread</span>
                @else
                    <span class="jb-badge bg-emerald-100 text-emerald-800">Read</span>
                @endif
                <span class="jb-badge bg-slate-100 text-slate-700">{{ $message->inquiryTypeLabel() }}</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">From</p>
                    <p class="mt-1">
                        <a href="mailto:{{ $message->email }}" class="text-sky-700 hover:underline">{{ $message->email }}</a>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Received</p>
                    <p class="mt-1">{{ $message->created_at?->format('M d, Y h:i A') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Inquiry type</p>
                    <p class="mt-1">{{ $message->inquiryTypeLabel() }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Read by</p>
                    <p class="mt-1">
                        @if ($message->read_at)
                            {{ $message->readByAdmin?->name ?? 'Admin' }}
                            <span class="text-slate-500 text-sm">({{ $message->read_at->format('M d, Y h:i A') }})</span>
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase text-slate-500">Subject</p>
                <p class="mt-1 text-slate-900 font-medium">{{ $message->subject }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase text-slate-500">Message</p>
                <p class="mt-1 text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $message->message }}</p>
            </div>

            <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100">
                <x-admin.button variant="secondary" :href="'mailto:'.$message->email.'?subject=Re: '.rawurlencode($message->subject)">
                    Reply by email
                </x-admin.button>
                @if (auth('admin')->user()->hasPermission('contact_messages', 'delete'))
                    <form
                        method="POST"
                        action="{{ route('admin.contact-messages.destroy', $message) }}"
                        class="inline-flex"
                        data-jb-confirm="This contact message will be permanently deleted."
                        data-jb-confirm-title="Delete message?"
                        data-jb-confirm-variant="error"
                        data-jb-confirm-label="Delete"
                    >
                        @csrf
                        @method('DELETE')
                        <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
