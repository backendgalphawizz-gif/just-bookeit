<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\Order;
use App\Http\Requests\Admin\DisputeStoreRequest;
use App\Http\Requests\Admin\DisputeUpdateRequest;
use App\Support\AppliesListDateFilter;
use App\Support\ChatAttachmentSupport;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'disputes';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $categoryId = $request->filled('category') ? $request->integer('category') : null;
        $raisedBy = $request->filled('raised_by') && in_array($request->string('raised_by')->toString(), ['customer', 'vendor'], true)
            ? $request->string('raised_by')->toString()
            : null;

        $disputes = $this->applyDateRange(Dispute::query(), $request)
            ->with(['order.customer', 'order.vendor', 'category'])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($raisedBy, fn ($q) => $q->where('raised_by', $raisedBy))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('order', fn ($order) => $order->where('order_number', 'like', $term));
            })
            ->when(
                $request->get('status') === '_open_' || $request->boolean('open_only'),
                fn ($q) => $q->whereIn('status', Dispute::OPEN_STATUSES)
            )
            ->when(
                $request->filled('status') && $request->get('status') !== '_open_',
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->newestFirst()
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()
            ->where('type', 'service')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.disputes.index', compact('disputes', 'categories', 'categoryId', 'raisedBy'));
    }

    public function create(): View
    {
        return view('admin.disputes.create', [
            'orders' => Order::query()->with(['customer', 'category'])->orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function store(DisputeStoreRequest $request): RedirectResponse
    {
        $order = Order::query()->findOrFail($request->integer('order_id'));

        Dispute::createForOrder($order, collect($request->validated())->except('order_id')->all());

        return redirect()
            ->route('admin.disputes.index', array_filter(['category' => $order->category_id]))
            ->with('success', 'Dispute created successfully.');
    }

    public function show(Dispute $dispute): View
    {
        $dispute->load(['order.customer', 'order.vendor', 'order.category', 'category', 'messages']);

        $dispute->messages()
            ->where('sender_type', DisputeMessage::SENDER_CUSTOMER)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('admin.disputes.show', compact('dispute'));
    }

    public function edit(Dispute $dispute): View
    {
        return view('admin.disputes.edit', compact('dispute'));
    }

    public function update(DisputeUpdateRequest $request, Dispute $dispute): RedirectResponse
    {
        $dispute->update($request->validated());

        return redirect()->route('admin.disputes.show', $dispute)->with('success', 'Dispute updated successfully.');
    }

    public function destroy(Dispute $dispute): RedirectResponse
    {
        $dispute->delete();

        return redirect()->route('admin.disputes.index')->with('success', 'Dispute deleted successfully.');
    }

    public function sendMessage(Request $request, Dispute $dispute): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        if (! $dispute->isChatOpen()) {
            return back()->with('error', 'This dispute chat is closed. No new messages can be sent.');
        }

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ChatAttachmentSupport::validationRules(),
        ]);

        if (blank($data['body']) && ! $request->hasFile('attachment')) {
            return back()->withErrors(['body' => 'Enter a message or attach a file.'])->withInput();
        }

        $admin = auth('admin')->user();

        $dispute->messages()->create([
            'sender_type' => DisputeMessage::SENDER_ADMIN,
            'sender_id' => $admin->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment')
                ? StoresUploadedFiles::store($request->file('attachment'), 'disputes/chat')
                : null,
        ]);

        if ($dispute->status === 'raised') {
            $dispute->update(['status' => 'under_review']);
        }

        return back()->with('success', 'Message sent to customer.');
    }

    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        if (! in_array($dispute->status, Dispute::OPEN_STATUSES, true)) {
            return back()->with('error', 'This dispute is already resolved or closed and cannot be updated.');
        }

        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $dispute->update([
            'status' => 'resolved',
            'resolution_note' => $data['resolution_note'] ?? $dispute->resolution_note,
        ]);

        return back()->with('success', 'Dispute resolved. Customer chat is now closed.');
    }

    public function close(Dispute $dispute): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $dispute->update(['status' => 'closed']);

        return back()->with('success', 'Dispute closed. Chat is no longer available.');
    }
}
