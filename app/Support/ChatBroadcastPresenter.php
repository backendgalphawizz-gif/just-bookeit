<?php

namespace App\Support;

use App\Models\ChatMessage;
use App\Models\Conversation;

class ChatBroadcastPresenter
{
    /** @return array<string, mixed> */
    public static function message(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'body' => $message->body,
            'sender_type' => $message->sender_type,
            'sender_id' => $message->sender_id,
            'attachment_url' => $message->attachmentUrl(),
            'attachment_type' => $message->attachmentType(),
            'attachment_name' => $message->attachmentDisplayName(),
            'is_edited' => $message->edited_at !== null,
            'sent_at' => $message->created_at?->format('g:i A'),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function thread(Conversation $conversation): array
    {
        $conversation->loadMissing(['customer', 'vendor', 'latestMessage']);

        $vendor = $conversation->vendor;
        $customer = $conversation->customer;
        $vendorName = $vendor?->brand_name ?? $vendor?->shop_name ?? 'Designer';
        $presence = app(\App\Services\ChatPresenceService::class);
        $customerOnline = $customer ? $presence->customerOnline((int) $customer->id) : false;
        $vendorOnline = $vendor ? $presence->vendorOnline((int) $vendor->id) : false;

        return [
            'id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'vendor_id' => $conversation->vendor_id,
            'customer_name' => $customer?->name ?? 'Customer',
            'vendor_name' => $vendorName,
            'preview' => WebChatLivePresenter::threadPreview($conversation->latestMessage),
            'time' => WebChatLivePresenter::threadTime($conversation->last_message_at),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'customer_is_online' => $customerOnline,
            'vendor_is_online' => $vendorOnline,
        ];
    }
}
