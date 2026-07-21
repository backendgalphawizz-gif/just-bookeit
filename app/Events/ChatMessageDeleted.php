<?php

namespace App\Events;

use App\Models\Conversation;
use App\Support\ChatBroadcastPresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $messageId,
        public ?Conversation $conversation = null,
    ) {
        $this->conversation?->loadMissing(['customer', 'vendor', 'latestMessage']);
    }

    /** @return array<int, \Illuminate\Broadcasting\PrivateChannel> */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('chat.conversation.'.$this->conversationId),
        ];

        if ($this->conversation) {
            $channels[] = new PrivateChannel('chat.customer.'.$this->conversation->customer_id);
            $channels[] = new PrivateChannel('chat.vendor.'.$this->conversation->vendor_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'chat.message.deleted';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'event' => 'deleted',
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'thread' => $this->conversation ? ChatBroadcastPresenter::thread($this->conversation) : null,
        ];
    }
}
