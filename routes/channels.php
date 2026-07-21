<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Support\Facades\Broadcast;

// Private chat channels for web, vendor panel, and mobile apps.
// Auth: /broadcasting/auth (session) and /api/v1|v2/broadcasting/auth (Sanctum).

Broadcast::channel('chat.conversation.{conversationId}', function ($user, int $conversationId) {
    $conversation = Conversation::query()->find($conversationId);

    if (! $conversation) {
        return false;
    }

    if ($user instanceof Customer) {
        return (int) $conversation->customer_id === (int) $user->id;
    }

    if ($user instanceof Vendor) {
        return (int) $conversation->vendor_id === (int) $user->id;
    }

    return false;
});

Broadcast::channel('chat.customer.{customerId}', function ($user, int $customerId) {
    return $user instanceof Customer && (int) $user->id === (int) $customerId;
});

Broadcast::channel('chat.vendor.{vendorId}', function ($user, int $vendorId) {
    return $user instanceof Vendor && (int) $user->id === (int) $vendorId;
});
