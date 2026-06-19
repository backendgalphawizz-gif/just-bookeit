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

        $message = $chat->messages()->create([
            'sender_type' => ChatMessage::SENDER_CUSTOMER,
            'sender_id' => $customer->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment')
                ? StoresUploadedFiles::store($request->file('attachment'), 'chat/attachments')
                : null,
        ]);

        $chat->update(['last_message_at' => $message->created_at]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => WebChatLivePresenter::message($message, ChatMessage::SENDER_CUSTOMER),
            ]);
        }

        return redirect()
            ->route('web.chat.index', ['chat' => $chat->id])
            ->with('success', 'Message sent.');
    }
}
