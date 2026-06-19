<?php

namespace App\Support;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Support\Str;

class WebChatLivePresenter
{
    public static function message(ChatMessage $message, string $viewerRole): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'attachment_url' => $message->attachmentUrl(),
            'attachment_type' => $message->attachmentType(),
            'is_mine' => $message->sender_type === $viewerRole,
            'sent_at' => $message->created_at?->format('g:i A'),
        ];
    }

    /** @return array<string, mixed> */
    public static function vendorThread(Conversation $conversation): array
    {
        $customer = $conversation->customer;

        return [
            'id' => $conversation->id,
            'name' => $customer?->name ?? 'Customer',
            'preview' => Str::limit($conversation->latestMessage?->body ?? 'No messages yet', 52),
            'time' => $conversation->last_message_at?->format('g:i A'),
            'avatar_url' => $customer?->profileImageUrl(),
            'initial' => strtoupper(substr($customer?->name ?? 'C', 0, 1)),
            'url' => route('vendor.chat.index', array_filter([
                'chat' => $conversation->id,
                'search' => request('search'),
            ]), false),
        ];
    }

    /** @return array<string, mixed> */
    public static function customerThread(Conversation $conversation): array
    {
        $vendor = $conversation->vendor;
        $name = $vendor?->brand_name ?? $vendor?->shop_name ?? 'Designer';

        return [
            'id' => $conversation->id,
            'name' => $name,
            'preview' => Str::limit($conversation->latestMessage?->body ?? 'No messages yet', 52),
            'time' => $conversation->last_message_at?->format('g:i A') ?? '',
            'avatar_url' => $vendor?->profileImageUrl() ?: $vendor?->shopLogoUrl(),
            'initial' => strtoupper(substr($name, 0, 1)),
            'url' => route('web.chat.index', array_filter([
                'chat' => $conversation->id,
                'search' => request('search'),
            ]), false),
        ];
    }
}
