<?php

namespace App\Http\Controllers\Vendor;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\ChatLiveService;
use App\Services\ChatPresenceService;
use App\Support\ChatAttachmentSupport;
use App\Support\StoresUploadedFiles;
use App\Support\WebChatLivePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends VendorController
{
    public function index(Request $request, ChatPresenceService $presence): View|RedirectResponse
    {
        $vendor = $this->vendor();

        $conversations = $vendor->conversations()
            ->with(['customer', 'latestMessage'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('customer', fn ($c) => $c->where('name', 'like', $term));
            })
            ->orderByRaw('last_message_at is null')
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

        // Online only while a conversation thread is open.
        if ($activeChat) {
            $presence->touch(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);
        } else {
            $presence->leave(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);
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

    public function poll(Request $request, ChatLiveService $live, ChatPresenceService $presence): JsonResponse
    {
        $vendor = $this->vendor();

        if ($request->filled('chat_id') || $request->filled('chat')) {
            $presence->touch(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);
        }

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

    public function presence(Request $request, ChatPresenceService $presence): JsonResponse
    {
        $vendor = $this->vendor();

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:online,offline'],
        ]);

        if (($data['status'] ?? 'online') === 'offline') {
            $presence->leave(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);
            $online = false;
        } else {
            $presence->touch(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);
            $online = true;
        }

        return response()->json([
            'is_online' => $online,
            'online_status' => $online ? 'online' : 'offline',
        ]);
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
        $attachment = $request->file('attachment');

        $message = $chat->messages()->create([
            'sender_type' => ChatMessage::SENDER_VENDOR,
            'sender_id' => $vendor->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $attachment
                ? StoresUploadedFiles::store($attachment, 'chat/attachments')
                : null,
            'attachment_name' => $attachment?->getClientOriginalName(),
        ]);

        $chat->forceFill([
            'last_message_at' => $message->created_at ?? now(),
        ])->save();

        $chat->load(['customer', 'latestMessage']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => WebChatLivePresenter::message($message, ChatMessage::SENDER_VENDOR),
                'thread' => WebChatLivePresenter::vendorThread($chat),
            ]);
        }

        return redirect()
            ->route('vendor.chat.index', ['chat' => $chat->id])
            ->with('success', 'Message sent.');
    }

    public function updateMessage(Request $request, Conversation $chat, ChatMessage $message): JsonResponse|RedirectResponse
    {
        $vendor = $this->vendor();
        abort_unless($chat->vendor_id === $vendor->id, 403);
        abort_unless($message->conversation_id === $chat->id, 404);
        abort_unless(
            $message->sender_type === ChatMessage::SENDER_VENDOR && (int) $message->sender_id === (int) $vendor->id,
            403
        );
        abort_unless(filled($message->body) || filled($message->attachment_path), 422);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->forceFill([
            'body' => trim($data['body']),
            'edited_at' => now(),
        ])->save();

        $chat->load(['customer', 'latestMessage']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => WebChatLivePresenter::message($message->fresh(), ChatMessage::SENDER_VENDOR),
                'thread' => WebChatLivePresenter::vendorThread($chat),
            ]);
        }

        return redirect()
            ->route('vendor.chat.index', ['chat' => $chat->id])
            ->with('success', 'Message updated.');
    }

    public function destroyMessage(Request $request, Conversation $chat, ChatMessage $message): JsonResponse|RedirectResponse
    {
        $vendor = $this->vendor();
        abort_unless($chat->vendor_id === $vendor->id, 403);
        abort_unless($message->conversation_id === $chat->id, 404);
        abort_unless(
            $message->sender_type === ChatMessage::SENDER_VENDOR && (int) $message->sender_id === (int) $vendor->id,
            403
        );

        if ($message->attachment_path) {
            StoresUploadedFiles::delete($message->attachment_path);
        }

        $messageId = $message->id;
        $message->delete();

        $latest = $chat->messages()->latest('id')->first();
        $chat->forceFill([
            'last_message_at' => $latest?->created_at,
        ])->save();
        $chat->load(['customer', 'latestMessage']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'deleted' => true,
                'message_id' => $messageId,
                'thread' => WebChatLivePresenter::vendorThread($chat),
            ]);
        }

        return redirect()
            ->route('vendor.chat.index', ['chat' => $chat->id])
            ->with('success', 'Message deleted.');
    }
}
