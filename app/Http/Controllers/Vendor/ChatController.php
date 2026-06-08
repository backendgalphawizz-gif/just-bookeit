<?php

namespace App\Http\Controllers\Vendor;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends VendorController
{
    public function index(Request $request): View
    {
        $vendor = $this->vendor();

        $conversations = $vendor->conversations()
            ->with(['customer', 'latestMessage'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('customer', fn ($c) => $c->where('name', 'like', $term));
            })
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        return view('vendor.chat.index', compact('conversations'));
    }

    public function show(Conversation $chat): View
    {
        abort_unless($chat->vendor_id === $this->vendor()->id, 403);

        $chat->load('customer');
        $messages = $chat->messages()->orderBy('id')->paginate(50);

        $chat->messages()
            ->where('sender_type', ChatMessage::SENDER_CUSTOMER)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('vendor.chat.show', compact('chat', 'messages'));
    }

    public function sendMessage(Request $request, Conversation $chat): RedirectResponse
    {
        abort_unless($chat->vendor_id === $this->vendor()->id, 403);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $vendor = $this->vendor();

        $message = $chat->messages()->create([
            'sender_type' => ChatMessage::SENDER_VENDOR,
            'sender_id' => $vendor->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment')
                ? StoresUploadedFiles::store($request->file('attachment'), 'chat/attachments')
                : null,
        ]);

        $chat->update(['last_message_at' => $message->created_at]);

        return back()->with('success', 'Message sent.');
    }
}
