@extends('web.layouts.app')

@section('title', 'Dispute · '.$order->order_number)

@section('content')
<div class="jbw-container">
    <p style="margin-bottom:1rem"><a href="{{ route('web.bookings.show', $order) }}" class="jbw-back-link">← Back to booking</a></p>

    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:0.75rem;margin-bottom:1.25rem">
        <div>
            <h1 class="jbw-page-title" style="font-size:1.25rem;margin:0">{{ $dispute->subject }}</h1>
            <p class="jbw-page-subtitle">
                #{{ $order->order_number }}
                @if ($order->category)
                    · {{ $order->category->name }}
                @endif
            </p>
        </div>
        <span class="jbw-status jbw-status--{{ $dispute->isChatOpen() ? 'new' : 'cancelled' }}">
            {{ $dispute->isChatOpen() ? 'Open' : ucfirst(str_replace('_', ' ', $dispute->status)) }}
        </span>
    </div>

    <div class="jbw-booking-card" style="margin-bottom:1rem">
        <h3 class="jbw-booking-card-title">Conversation with support</h3>
        <div id="jbw-dispute-thread" style="max-height:24rem;overflow-y:auto;display:flex;flex-direction:column;gap:0.75rem;margin-top:0.75rem">
            @forelse ($dispute->messages as $message)
                <div style="max-width:85%;{{ $message->isFromAdmin() ? '' : 'margin-left:auto;' }}">
                    <p style="margin:0 0 0.25rem;font-size:0.6875rem;font-weight:700;color:var(--jbw-muted);text-transform:uppercase">
                        {{ $message->isFromAdmin() ? 'Support team' : 'You' }}
                        · {{ $message->created_at->format('M d, g:i A') }}
                    </p>
                    <div style="padding:0.75rem 0.9rem;border-radius:0.75rem;background:{{ $message->isFromAdmin() ? '#f8fafc' : '#fff7ed' }};border:1px solid {{ $message->isFromAdmin() ? '#e2e8f0' : '#fed7aa' }}">
                        @if ($message->body)
                            <p style="margin:0;white-space:pre-wrap;line-height:1.5">{{ $message->body }}</p>
                        @endif
                        @if ($message->attachmentUrl())
                            @include('partials.chat-attachment-media', [
                                'url' => $message->attachmentUrl(),
                                'path' => $message->attachment_path,
                                'type' => $message->attachmentType(),
                                'class' => '',
                            ])
                        @endif
                    </div>
                </div>
            @empty
                <p style="margin:0;color:var(--jbw-muted);font-size:0.875rem">No messages yet. Send the first message below.</p>
            @endforelse
        </div>
    </div>

    @if ($dispute->isChatOpen())
        <div class="jbw-booking-card">
            <h3 class="jbw-booking-card-title">Send a message</h3>
            <form method="POST" action="{{ route('web.bookings.dispute.messages', $order) }}" enctype="multipart/form-data" style="margin-top:0.75rem" data-chat-compose>
                @csrf
                <textarea name="body" rows="3" class="jbw-input" placeholder="Describe your issue... (Enter to send, Shift+Enter for new line)" style="width:100%;resize:vertical" data-chat-input>{{ old('body') }}</textarea>
                @error('body')
                    <p style="margin:0.35rem 0 0;font-size:0.75rem;color:#e11d48">{{ $message }}</p>
                @enderror
                <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;margin-top:0.75rem">
                    <input type="file" name="attachment" accept="{{ \App\Support\ChatAttachmentSupport::acceptAttribute() }}">
                    <button type="submit" class="jbw-btn jbw-btn--primary">Send message</button>
                </div>
            </form>
        </div>
    @else
        <div class="jbw-booking-card">
            <p style="margin:0;color:var(--jbw-muted);line-height:1.6">
                This dispute is closed. You can no longer send messages.
                @if ($dispute->resolution_note)
                    <br><br><strong>Resolution:</strong> {{ $dispute->resolution_note }}
                @endif
            </p>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const thread = document.getElementById('jbw-dispute-thread');
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }
    });
</script>
@endpush
@endsection
