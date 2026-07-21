<?php

namespace App\Support;

use App\Models\ChatMessage;
use App\Models\Conversation;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class WebChatLivePresenter
{
    public static function message(ChatMessage $message, string $viewerRole): array
    {
        $isMine = $message->sender_type === $viewerRole;
        $payload = [
            'id' => $message->id,
            'body' => $message->body,
            'attachment_url' => $message->attachmentUrl(),
            'attachment_type' => $message->attachmentType(),
            'attachment_name' => $message->attachmentDisplayName(),
            'is_mine' => $isMine,
            'is_edited' => $message->edited_at !== null,
            'can_edit' => $isMine && filled($message->body),
            'can_delete' => $isMine,
            'sent_at' => $message->created_at?->format('g:i A'),
        ];

        if ($isMine) {
            $payload['update_url'] = $viewerRole === ChatMessage::SENDER_CUSTOMER
                ? route('web.chat.messages.update', [$message->conversation_id, $message], false)
                : route('vendor.chat.messages.update', [$message->conversation_id, $message], false);
            $payload['delete_url'] = $viewerRole === ChatMessage::SENDER_CUSTOMER
                ? route('web.chat.messages.destroy', [$message->conversation_id, $message], false)
                : route('vendor.chat.messages.destroy', [$message->conversation_id, $message], false);
        }

        return $payload;
    }

    public static function threadPreview(?ChatMessage $message): string
    {
        if (! $message) {
            return 'No messages yet';
        }

        if (filled($message->body)) {
            return Str::limit($message->body, 52);
        }

        return match ($message->attachmentType()) {
            'video' => 'Video',
            'image' => 'Photo',
            'file' => Str::limit($message->attachmentDisplayName() ?? 'Attachment', 52),
            default => $message->attachment_path ? 'Attachment' : 'No messages yet',
        };
    }

    public static function threadTime(?CarbonInterface $at): string
    {
        if (! $at) {
            return '';
        }

        if ($at->isToday()) {
            return $at->format('g:i A');
        }

        if ($at->isYesterday()) {
            return 'Yesterday';
        }

        if ($at->isCurrentYear()) {
            return $at->format('M j');
        }

        return $at->format('M j, Y');
    }

    /** @return array<string, mixed> */
    public static function vendorThread(Conversation $conversation): array
    {
        $customer = $conversation->customer;
        $isOnline = $customer
            ? app(\App\Services\ChatPresenceService::class)->customerOnline((int) $customer->id)
            : false;

        return [
            'id' => $conversation->id,
            'name' => $customer?->name ?? 'Customer',
            'preview' => self::threadPreview($conversation->latestMessage),
            'time' => self::threadTime($conversation->last_message_at),
            'avatar_url' => $customer?->profileImageUrl(),
            'initial' => strtoupper(substr($customer?->name ?? 'C', 0, 1)),
            'is_online' => $isOnline,
            'online_status' => $isOnline ? 'online' : 'offline',
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
        $isOnline = $vendor
            ? app(\App\Services\ChatPresenceService::class)->vendorOnline((int) $vendor->id)
            : false;

        return [
            'id' => $conversation->id,
            'name' => $name,
            'preview' => self::threadPreview($conversation->latestMessage),
            'time' => self::threadTime($conversation->last_message_at),
            'avatar_url' => $vendor?->profileImageUrl() ?: $vendor?->shopLogoUrl(),
            'initial' => strtoupper(substr($name, 0, 1)),
            'is_online' => $isOnline,
            'online_status' => $isOnline ? 'online' : 'offline',
            'url' => route('web.chat.index', array_filter([
                'chat' => $conversation->id,
                'search' => request('search'),
            ]), false),
        ];
    }
}
