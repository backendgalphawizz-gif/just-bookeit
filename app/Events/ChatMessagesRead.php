<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<int>  $messageIds
     */
    public function __construct(
        public int $conversationId,
        public array $messageIds,
        public string $readerRole,
        public int $customerId,
        public int $vendorId,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.conversation.'.$this->conversationId),
            new PrivateChannel('chat.customer.'.$this->customerId),
            new PrivateChannel('chat.vendor.'.$this->vendorId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.messages.read';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'event' => 'read',
            'conversation_id' => $this->conversationId,
            'message_ids' => array_values(array_map('intval', $this->messageIds)),
            'reader_role' => $this->readerRole,
        ];
    }
}
