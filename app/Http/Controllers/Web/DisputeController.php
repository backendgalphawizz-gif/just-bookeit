<?php

namespace App\Http\Controllers\Web;

use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\Order;
use App\Support\ChatAttachmentSupport;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DisputeController extends WebController
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        if ($order->dispute) {
            return redirect()
                ->route('web.bookings.dispute.show', $order)
                ->with('info', 'A dispute is already open for this booking.');
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        $dispute = Dispute::createForOrder($order, [
            'raised_by' => 'customer',
            'subject' => $data['subject'],
            'status' => 'raised',
        ]);

        if (filled($data['body'] ?? null)) {
            $dispute->messages()->create([
                'sender_type' => DisputeMessage::SENDER_CUSTOMER,
                'sender_id' => $customer->id,
                'body' => $data['body'],
            ]);
        }

        return redirect()
            ->route('web.bookings.dispute.show', $order)
            ->with('success', 'Dispute raised. Our team will review it under '.$order->category?->name.'.');
    }

    public function show(Order $order): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $order->load(['vendor', 'category', 'dispute.messages']);

        if (! $order->dispute) {
            return redirect()
                ->route('web.bookings.show', $order)
                ->with('error', 'No dispute found for this booking.');
        }

        $order->dispute->messages()
            ->where('sender_type', DisputeMessage::SENDER_ADMIN)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('web.bookings.dispute', [
            'order' => $order,
            'dispute' => $order->dispute,
        ]);
    }

    public function sendMessage(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $dispute = $order->dispute;
        if (! $dispute) {
            return redirect()->route('web.bookings.show', $order)->with('error', 'No dispute found for this booking.');
        }

        if (! $dispute->isChatOpen()) {
            return back()->with('error', 'This dispute chat is closed.');
        }

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ChatAttachmentSupport::validationRules(),
        ]);

        if (blank($data['body']) && ! $request->hasFile('attachment')) {
            return back()->withErrors(['body' => 'Enter a message or attach a file.'])->withInput();
        }

        $dispute->messages()->create([
            'sender_type' => DisputeMessage::SENDER_CUSTOMER,
            'sender_id' => $customer->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment')
                ? StoresUploadedFiles::store($request->file('attachment'), 'disputes/chat')
                : null,
        ]);

        return back()->with('success', 'Message sent.');
    }
}
