<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\DisputeMessage;
use App\Models\Order;
use App\Support\Api\CustomerApiPresenter;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeController extends ApiController
{
    public function show(Request $request, Order $booking): JsonResponse
    {
        $customer = $request->user('sanctum');
        abort_unless($booking->customer_id === $customer->id, 403);

        $dispute = $booking->dispute;
        if (! $dispute) {
            return $this->error('No dispute found for this booking.', 404);
        }

        $dispute->load('messages');

        $dispute->messages()
            ->where('sender_type', DisputeMessage::SENDER_ADMIN)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success([
            'dispute' => [
                'id' => $dispute->id,
                'subject' => $dispute->subject,
                'status' => $dispute->status,
                'chat_open' => $dispute->isChatOpen(),
                'resolution_note' => $dispute->resolution_note,
            ],
            'messages' => $dispute->messages->map(fn (DisputeMessage $message) => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'body' => $message->body,
                'attachment_url' => $message->attachmentUrl(),
                'created_at' => $message->created_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function sendMessage(Request $request, Order $booking): JsonResponse
    {
        $customer = $request->user('sanctum');
        abort_unless($booking->customer_id === $customer->id, 403);

        $dispute = $booking->dispute;
        if (! $dispute) {
            return $this->error('No dispute found for this booking.', 404);
        }

        if (! $dispute->isChatOpen()) {
            return $this->error('This dispute chat is closed.', 403);
        }

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:4096'],
        ]);

        if (blank($data['body']) && ! $request->hasFile('attachment')) {
            return $this->error('Enter a message or attach an image.', 422);
        }

        $message = $dispute->messages()->create([
            'sender_type' => DisputeMessage::SENDER_CUSTOMER,
            'sender_id' => $customer->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment')
                ? StoresUploadedFiles::store($request->file('attachment'), 'disputes/chat')
                : null,
        ]);

        return $this->success([
            'message' => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'body' => $message->body,
                'attachment_url' => $message->attachmentUrl(),
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 'Message sent.', 201);
    }
}
