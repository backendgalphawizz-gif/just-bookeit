<div class="jb-dispute-chat">
    <div class="jb-dispute-chat__head">
        <div>
            <h3 class="jb-dispute-chat__title">Chat with customer</h3>
            <p class="jb-dispute-chat__sub">
                @if ($dispute->isChatOpen())
                    Discuss with {{ $dispute->order->customer->name }} to resolve this dispute.
                @else
                    This conversation is closed.
                @endif
            </p>
        </div>
        @if ($dispute->isChatOpen())
            <span class="jb-dispute-chat__badge jb-dispute-chat__badge--open">Open</span>
        @else
            <span class="jb-dispute-chat__badge jb-dispute-chat__badge--closed">Closed</span>
        @endif
    </div>

    <div class="jb-dispute-chat__thread" id="jb-dispute-chat-thread">
        @forelse ($dispute->messages as $message)
            <div @class([
                'jb-dispute-chat__msg',
                'jb-dispute-chat__msg--admin' => $message->isFromAdmin(),
                'jb-dispute-chat__msg--customer' => ! $message->isFromAdmin(),
            ])>
                <p class="jb-dispute-chat__msg-meta">
                    {{ $message->senderLabel() }}
                    · {{ $message->created_at->format('M d, Y · g:i A') }}
                </p>
                @if ($message->body)
                    <p class="jb-dispute-chat__msg-body">{{ $message->body }}</p>
                @endif
                @if ($message->attachmentUrl())
                    <a href="{{ $message->attachmentUrl() }}" target="_blank" rel="noopener" class="jb-dispute-chat__attachment">
                        <img src="{{ $message->attachmentUrl() }}" alt="Attachment" class="panel-lightbox-trigger">
                    </a>
                @endif
            </div>
        @empty
            <p class="jb-dispute-chat__empty">
                @if ($dispute->isChatOpen())
                    No messages yet. Send the first message to start resolving this dispute with the customer.
                @else
                    No messages were recorded for this dispute.
                @endif
            </p>
        @endforelse
    </div>

    @if ($dispute->isChatOpen() && auth('admin')->user()->hasPermission('disputes', 'edit'))
        <form method="POST" action="{{ route('admin.disputes.messages', $dispute) }}" enctype="multipart/form-data" class="jb-dispute-chat__compose">
            @csrf
            <label class="jb-label" for="dispute-chat-body">Your message</label>
            <textarea id="dispute-chat-body" name="body" rows="3" class="jb-textarea" placeholder="Type your reply to the customer...">{{ old('body') }}</textarea>
            @error('body')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
            <div class="jb-dispute-chat__compose-actions">
                <input type="file" name="attachment" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif" class="jb-input">
                <x-admin.button variant="primary" type="submit" size="sm">Send message</x-admin.button>
            </div>
        </form>

        <div class="jb-dispute-chat__resolve">
            <form
                method="POST"
                action="{{ route('admin.disputes.resolve', $dispute) }}"
                data-jb-confirm="Mark this dispute as resolved? The customer will no longer be able to chat on this dispute."
                data-jb-confirm-title="Resolve dispute"
                data-jb-confirm-variant="success"
                data-jb-confirm-label="Resolve & close chat"
            >
                @csrf
                <label class="jb-label" for="resolution_note">Resolution note (optional)</label>
                <textarea id="resolution_note" name="resolution_note" rows="2" class="jb-textarea" placeholder="Summary of how this dispute was resolved...">{{ old('resolution_note', $dispute->resolution_note) }}</textarea>
                <div class="jb-dispute-chat__resolve-actions">
                    <x-admin.button variant="success" type="submit" size="sm">Resolve & close chat</x-admin.button>
                </div>
            </form>
            <form
                method="POST"
                action="{{ route('admin.disputes.close', $dispute) }}"
                class="jb-dispute-chat__close-form"
                data-jb-confirm="Close this dispute without marking it resolved? Chat will be disabled."
                data-jb-confirm-title="Close dispute"
                data-jb-confirm-variant="warning"
                data-jb-confirm-label="Close dispute"
            >
                @csrf
                <x-admin.button variant="secondary" type="submit" size="sm">Close without resolving</x-admin.button>
            </form>
        </div>
    @else
        <div class="jb-dispute-chat__closed-note">
            @if ($dispute->status === 'resolved')
                <p><strong>Dispute resolved.</strong> Chat is closed. The customer can no longer send messages on this dispute.</p>
            @elseif ($dispute->status === 'closed')
                <p><strong>Dispute closed.</strong> Chat is no longer available.</p>
            @endif
            @if ($dispute->resolution_note)
                <p class="jb-dispute-chat__resolution-note"><span>Resolution note:</span> {{ $dispute->resolution_note }}</p>
            @endif
        </div>

        @if ($dispute->status === 'resolved' && auth('admin')->user()->hasPermission('disputes', 'edit'))
            <form
                method="POST"
                action="{{ route('admin.disputes.close', $dispute) }}"
                class="jb-dispute-chat__resolve-actions"
                data-jb-confirm="Close this dispute permanently?"
                data-jb-confirm-title="Close dispute"
                data-jb-confirm-variant="warning"
                data-jb-confirm-label="Close dispute"
            >
                @csrf
                <x-admin.button variant="secondary" type="submit" size="sm">Close dispute</x-admin.button>
            </form>
        @endif
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const thread = document.getElementById('jb-dispute-chat-thread');
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }

        const composeForm = document.querySelector('.jb-dispute-chat__compose');
        const textarea = document.getElementById('dispute-chat-body');
        if (! composeForm || ! textarea) {
            return;
        }

        textarea.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || event.shiftKey) {
                return;
            }

            event.preventDefault();

            const hasText = textarea.value.trim().length > 0;
            const fileInput = composeForm.querySelector('input[type="file"]');
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

            if (hasText || hasFile) {
                composeForm.requestSubmit();
            }
        });
    });
</script>
@endpush
