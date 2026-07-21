<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

// Private chat channels for web, vendor panel, and mobile apps.
// Auth: /broadcasting/auth (session) and /api/v1|v2/broadcasting/auth (Sanctum).
// Session panels may have both customer + vendor logged in — check both guards.

Broadcast::channel('chat.conversation.{conversationId}', function ($user, int $conversationId) {
    $conversation = Conversation::query()->find($conversationId);

    if (! $conversation) {
        return false;
    }

    $customer = Auth::guard('customer')->user() ?? ($user instanceof Customer ? $user : null);
    if ($customer instanceof Customer && (int) $conversation->customer_id === (int) $customer->id) {
        return true;
    }

    $vendor = Auth::guard('vendor')->user() ?? ($user instanceof Vendor ? $user : null);
    if ($vendor instanceof Vendor && (int) $conversation->vendor_id === (int) $vendor->id) {
        return true;
    }

    return false;
});

Broadcast::channel('chat.customer.{customerId}', function ($user, int $customerId) {
    $customer = Auth::guard('customer')->user() ?? ($user instanceof Customer ? $user : null);

    return $customer instanceof Customer && (int) $customer->id === (int) $customerId;
});

Broadcast::channel('chat.vendor.{vendorId}', function ($user, int $vendorId) {
    $vendor = Auth::guard('vendor')->user() ?? ($user instanceof Vendor ? $user : null);

    return $vendor instanceof Vendor && (int) $vendor->id === (int) $vendorId;
});
