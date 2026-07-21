<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Support\ChatBroadcastPresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
        $this->message->loadMissing('conversation.customer', 'conversation.vendor', 'conversation.latestMessage');
    }

    /** @return array<int, \Illuminate\Broadcasting\PrivateChannel> */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;

        $channels = [
            new PrivateChannel('chat.conversation.'.$this->message->conversation_id),
        ];

        if ($conversation instanceof Conversation) {
            $channels[] = new PrivateChannel('chat.customer.'.$conversation->customer_id);
            $channels[] = new PrivateChannel('chat.vendor.'.$conversation->vendor_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'chat.message.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $conversation = $this->message->conversation;

        return [
            'event' => 'updated',
            'conversation_id' => $this->message->conversation_id,
            'message' => ChatBroadcastPresenter::message($this->message),
            'thread' => $conversation ? ChatBroadcastPresenter::thread($conversation) : null,
        ];
    }
}
