@extends('admin.layouts.app')
@section('title', $message->subject)
@section('page_title', 'Contact message')
@section('page_subtitle')
    Received {{ $message->created_at?->diffForHumans() ?? 'recently' }}
@endsection
@section('back_href', route('admin.contact-messages.index'))

@section('header_actions')
    <x-admin.button
        variant="primary"
        :href="'mailto:'.$message->email.'?subject='.rawurlencode('Re: '.$message->subject)"
    >
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
@endsection

@section('content')
    <div class="jb-contact-msg">
        <div class="jb-contact-msg__hero">
            <div class="jb-contact-msg__hero-top">
                <div class="jb-contact-msg__badges">
                    @if ($message->isUnread())
                        <span class="jb-badge bg-amber-100 text-amber-800">Unread</span>
                    @else
                        <span class="jb-badge bg-emerald-100 text-emerald-800">Read</span>
                    @endif
                    <span class="jb-badge bg-slate-100 text-slate-700">{{ $message->inquiryTypeLabel() }}</span>
                </div>
                <p class="jb-contact-msg__id">#{{ $message->id }}</p>
            </div>

            <h2 class="jb-contact-msg__subject">{{ $message->subject }}</h2>

            <div class="jb-contact-msg__from">
                <span class="jb-contact-msg__avatar" aria-hidden="true">
                    {{ strtoupper(substr($message->email, 0, 1)) }}
                </span>
                <div>
                    <a href="mailto:{{ $message->email }}" class="jb-contact-msg__email">{{ $message->email }}</a>
                    <p class="jb-contact-msg__from-meta">
                        {{ $message->created_at?->format('D, M j, Y · g:i A') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="jb-detail-grid">
            <div class="jb-detail-card lg:col-span-2 jb-contact-msg__body-card">
                <h2>Message</h2>
                <div class="jb-contact-msg__body">
                    {{ $message->message }}
                </div>
            </div>

            <div class="jb-detail-card">
                <h2>Details</h2>
                <dl class="jb-dl">
                    <div>
                        <dt>Inquiry type</dt>
                        <dd>{{ $message->inquiryTypeLabel() }}</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>
                            @if ($message->isUnread())
                                <span class="jb-badge bg-amber-100 text-amber-800">Unread</span>
                            @else
                                <span class="jb-badge bg-emerald-100 text-emerald-800">Read</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Received</dt>
                        <dd>{{ $message->created_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Read by</dt>
                        <dd>
                            @if ($message->read_at)
                                {{ $message->readByAdmin?->name ?? 'Admin' }}
                                <span class="block text-xs font-normal text-slate-500 mt-0.5">
                                    {{ $message->read_at->format('M d, Y h:i A') }}
                                </span>
                            @else
                                Not read yet
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Reply to</dt>
                        <dd>
                            <a href="mailto:{{ $message->email }}" class="jb-link break-all">{{ $message->email }}</a>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .jb-contact-msg {
        display: grid;
        gap: 1.25rem;
    }

    .jb-contact-msg__hero {
        border-radius: 1rem;
        border: 1px solid rgb(226 232 240 / 0.9);
        background:
            linear-gradient(135deg, rgb(255 247 237 / 0.95) 0%, #fff 42%, #fff 100%);
        padding: 1.5rem 1.5rem 1.35rem;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
    }

    .jb-contact-msg__hero-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }

    .jb-contact-msg__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .jb-contact-msg__id {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgb(148 163 184);
        letter-spacing: 0.04em;
    }

    .jb-contact-msg__subject {
        margin: 0 0 1.1rem;
        font-size: clamp(1.25rem, 2.2vw, 1.65rem);
        font-weight: 700;
        color: rgb(15 23 42);
        line-height: 1.25;
        letter-spacing: -0.02em;
    }

    .jb-contact-msg__from {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .jb-contact-msg__avatar {
        display: grid;
        place-items: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #f25123 0%, #f8a08a 100%);
        color: #fff;
        font-weight: 800;
        font-size: 1rem;
        flex-shrink: 0;
        box-shadow: 0 8px 18px -10px rgb(242 81 35 / 0.7);
    }

    .jb-contact-msg__email {
        display: inline-block;
        font-weight: 700;
        color: rgb(15 23 42);
        text-decoration: none;
        word-break: break-all;
    }

    .jb-contact-msg__email:hover {
        color: #f25123;
    }

    .jb-contact-msg__from-meta {
        margin: 0.15rem 0 0;
        font-size: 0.8125rem;
        color: rgb(100 116 139);
    }

    .jb-contact-msg__body-card h2 {
        margin-bottom: 0;
    }

    .jb-contact-msg__body {
        margin-top: 1rem;
        padding: 1.15rem 1.25rem;
        border-radius: 0.85rem;
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240 / 0.9);
        color: rgb(30 41 59);
        white-space: pre-wrap;
        line-height: 1.7;
        font-size: 0.95rem;
        min-height: 8rem;
    }

    @media (max-width: 640px) {
        .jb-contact-msg__hero {
            padding: 1.15rem;
        }
    }
</style>
@endpush
