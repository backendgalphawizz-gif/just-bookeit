<?php

namespace App\Http\Controllers\Web;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Vendor;
use App\Services\ChatLiveService;
use App\Support\ChatAttachmentSupport;
use App\Support\StoresUploadedFiles;
use App\Support\WebChatLivePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends WebController
{
    public function index(Request $request): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $conversations = $customer->conversations()
            ->with(['vendor', 'latestMessage'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->whereHas('vendor', fn ($vendor) => $vendor->where('brand_name', 'like', $term));
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

        if ($activeChat) {
            abort_unless($activeChat->customer_id === $customer->id, 403);

            $activeChat->load('vendor');
            $messages = $activeChat->messages()->orderBy('id')->get();

            $activeChat->messages()
                ->where('sender_type', ChatMessage::SENDER_VENDOR)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        if ($activeChat && ! $request->filled('chat')) {
            return redirect()->route('web.chat.index', array_filter([
                'chat' => $activeChat->id,
                'search' => $request->input('search'),
            ]));
        }

        return view('web.chat.index', compact('conversations', 'activeChat', 'messages'));
    }

    public function poll(Request $request, ChatLiveService $live): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        $query = $customer->conversations();

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->whereHas('vendor', fn ($vendor) => $vendor->where('brand_name', 'like', $term));
        }

        return $live->poll(
            $request,
            $query,
            ChatMessage::SENDER_CUSTOMER,
            fn (Conversation $conversation) => WebChatLivePresenter::customerThread($conversation),
            fn (Conversation $chat) => abort_unless($chat->customer_id === $customer->id, 403),
        );
    }

    public function start(Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->status === 'active', 404);

        $customer = Auth::guard('customer')->user();

        $conversation = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $customer->id,
                'vendor_id' => $vendor->id,
            ],
            ['last_message_at' => now()]
        );

        return redirect()->route('web.chat.index', ['chat' => $conversation->id]);
    }

    public function sendMessage(Request $request, Conversation $chat): RedirectResponse|JsonResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($chat->customer_id === $customer->id, 403);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ChatAttachmentSupport::validationRules(),
        ]);

        abort_if(blank($data['body'] ?? null) && ! $request->hasFile('attachment'), 422);

        $attachment = $request->file('attachment');

        $message = $chat->messages()->create([
            'sender_type' => ChatMessage::SENDER_CUSTOMER,
            'sender_id' => $customer->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $attachment
                ? StoresUploadedFiles::store($attachment, 'chat/attachments')
                : null,
            'attachment_name' => $attachment?->getClientOriginalName(),
        ]);

        $chat->forceFill([
            'last_message_at' => $message->created_at ?? now(),
        ])->save();

        $chat->load(['vendor', 'latestMessage']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => WebChatLivePresenter::message($message, ChatMessage::SENDER_CUSTOMER),
                'thread' => WebChatLivePresenter::customerThread($chat),
            ]);
        }

        return redirect()
            ->route('web.chat.index', ['chat' => $chat->id])
            ->with('success', 'Message sent.');
    }

    public function updateMessage(Request $request, Conversation $chat, ChatMessage $message): JsonResponse|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($chat->customer_id === $customer->id, 403);
        abort_unless($message->conversation_id === $chat->id, 404);
        abort_unless(
            $message->sender_type === ChatMessage::SENDER_CUSTOMER && (int) $message->sender_id === (int) $customer->id,
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

        $chat->load(['vendor', 'latestMessage']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => WebChatLivePresenter::message($message->fresh(), ChatMessage::SENDER_CUSTOMER),
                'thread' => WebChatLivePresenter::customerThread($chat),
            ]);
        }

        return redirect()
            ->route('web.chat.index', ['chat' => $chat->id])
            ->with('success', 'Message updated.');
    }

    public function destroyMessage(Request $request, Conversation $chat, ChatMessage $message): JsonResponse|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($chat->customer_id === $customer->id, 403);
        abort_unless($message->conversation_id === $chat->id, 404);
        abort_unless(
            $message->sender_type === ChatMessage::SENDER_CUSTOMER && (int) $message->sender_id === (int) $customer->id,
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
        $chat->load(['vendor', 'latestMessage']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'deleted' => true,
                'message_id' => $messageId,
                'thread' => WebChatLivePresenter::customerThread($chat),
            ]);
        }

        return redirect()
            ->route('web.chat.index', ['chat' => $chat->id])
            ->with('success', 'Message deleted.');
    }
}
