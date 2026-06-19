<?php

namespace App\Http\Controllers\Vendor;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\ChatLiveService;
use App\Support\ChatAttachmentSupport;
use App\Support\StoresUploadedFiles;
use App\Support\WebChatLivePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends VendorController
{
    public function index(Request $request): View|RedirectResponse
    {
        $vendor = $this->vendor();

        $conversations = $vendor->conversations()
            ->with(['customer', 'latestMessage'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('customer', fn ($c) => $c->where('name', 'like', $term));
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        $activeChat = null;
        $messages = collect();

        if ($request->filled('chat')) {
            $activeChat = $conversations->firstWhere('id', $request->integer('chat'));
        } elseif ($conversations->isNotEmpty()) {
            $activeChat = $conversations->first();
        }

        if ($activeChat) {
            abort_unless($activeChat->vendor_id === $vendor->id, 403);

            $activeChat->load('customer');
            $messages = $activeChat->messages()->orderBy('id')->get();

            $activeChat->messages()
                ->where('sender_type', ChatMessage::SENDER_CUSTOMER)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        if ($activeChat && ! $request->filled('chat')) {
            return redirect()->route('vendor.chat.index', array_filter([
                'chat' => $activeChat->id,
                'search' => $request->input('search'),
            ]));
        }

        return view('vendor.chat.index', compact('conversations', 'activeChat', 'messages'));
    }

    public function poll(Request $request, ChatLiveService $live): JsonResponse
    {
        $vendor = $this->vendor();

        $query = $vendor->conversations();

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->whereHas('customer', fn ($c) => $c->where('name', 'like', $term));
        }

        return $live->poll(
            $request,
            $query,
            ChatMessage::SENDER_VENDOR,
            fn (Conversation $conversation) => WebChatLivePresenter::vendorThread($conversation),
            fn (Conversation $chat) => abort_unless($chat->vendor_id === $vendor->id, 403),
        );
    }

    public function show(Conversation $chat): RedirectResponse
    {
        abort_unless($chat->vendor_id === $this->vendor()->id, 403);

        return redirect()->route('vendor.chat.index', ['chat' => $chat->id]);
    }

    public function sendMessage(Request $request, Conversation $chat): RedirectResponse|JsonResponse
    {
        abort_unless($chat->vendor_id === $this->vendor()->id, 403);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ChatAttachmentSupport::validationRules(),
        ]);

        abort_if(blank($data['body'] ?? null) && ! $request->hasFile('attachment'), 422);

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

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => WebChatLivePresenter::message($message, ChatMessage::SENDER_VENDOR),
            ]);
        }

        return redirect()
            ->route('vendor.chat.index', ['chat' => $chat->id])
            ->with('success', 'Message sent.');
    }
}
