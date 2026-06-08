<?php

namespace App\Support\ListExports;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\VendorWalletTransaction;
use App\Services\Export\ListExportService;
use App\Support\AppliesListDateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorListExporter
{
    use AppliesListDateFilter;

    public const MODULES = [
        'bookings',
        'products',
        'payments',
        'wallet',
        'chat',
    ];

    public function __construct(
        protected ListExportService $exporter,
    ) {}

    public function export(Request $request, string $module): StreamedResponse|Response
    {
        abort_unless(in_array($module, self::MODULES, true), 404);

        $vendor = Auth::guard('vendor')->user();
        abort_unless($vendor, 403);

        $definition = $this->definition($module, $vendor->id);

        if ($definition['validate_dates'] ?? true) {
            $this->validateListDateRange($request);
        }

        return $this->exporter->respond(
            $request,
            $definition['query']($request),
            $definition['headers'],
            $definition['map'],
            $definition['basename'],
            $definition['title'],
        );
    }

    protected function definition(string $module, int $vendorId): array
    {
        return match ($module) {
            'bookings' => [
                'title' => 'Bookings Export',
                'basename' => 'vendor-bookings',
                'headers' => ['Order', 'Customer', 'Item', 'Amount', 'Payment', 'Status', 'Date'],
                'query' => fn (Request $request) => $this->applyDateRange(
                    Order::query()
                        ->where('vendor_id', $vendorId)
                        ->with(['customer', 'category'])
                        ->when($request->filled('search'), function (Builder $q) use ($request) {
                            $term = '%'.$request->string('search').'%';
                            $q->where(function (Builder $q) use ($term) {
                                $q->where('order_number', 'like', $term)
                                    ->orWhere('item_title', 'like', $term)
                                    ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term));
                            });
                        })
                        ->when($request->filled('status'), function (Builder $q) use ($request) {
                            $status = $request->string('status')->toString();
                            if ($status === 'new') {
                                $q->whereIn('status', ['new', 'pending_acceptance']);
                            } else {
                                $q->where('status', $status);
                            }
                        }),
                    $request
                )->orderByDesc('created_at'),
                'map' => fn (Order $order) => [
                    $order->order_number,
                    $order->customer?->name ?? '—',
                    $order->itemDisplayName(),
                    $order->grandTotal(),
                    $order->payment_status,
                    $order->statusLabel(),
                    $order->created_at?->format('Y-m-d H:i') ?? '—',
                ],
            ],
            'products' => [
                'title' => 'Products Export',
                'basename' => 'vendor-products',
                'headers' => ['Title', 'Category', 'Status', 'Created'],
                'query' => function (Request $request) use ($vendorId) {
                    $type = $request->string('type', 'fashion-designer')->toString();
                    $category = Category::query()->where('slug', $type)->first();

                    return $this->applyDateRange(
                        PortfolioItem::query()
                            ->where('vendor_id', $vendorId)
                            ->when($category, fn (Builder $q) => $q->where('category_id', $category->id))
                            ->when($request->filled('search'), fn (Builder $q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
                            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
                            ->with('category'),
                        $request
                    )->orderByDesc('id');
                },
                'map' => fn (PortfolioItem $item) => [
                    $item->title,
                    $item->category?->name ?? '—',
                    $item->status,
                    $item->created_at?->format('Y-m-d') ?? '—',
                ],
            ],
            'payments' => [
                'title' => 'Payments Export',
                'basename' => 'vendor-payments',
                'headers' => ['Order', 'Customer', 'Item', 'Amount', 'Hold Status', 'Date'],
                'query' => fn (Request $request) => $this->applyDateRange(
                    Order::query()
                        ->where('vendor_id', $vendorId)
                        ->where('payment_status', 'success')
                        ->with(['customer', 'category'])
                        ->when($request->filled('search'), function (Builder $q) use ($request) {
                            $term = '%'.$request->string('search').'%';
                            $q->where(function (Builder $q) use ($term) {
                                $q->where('order_number', 'like', $term)
                                    ->orWhere('item_title', 'like', $term)
                                    ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term));
                            });
                        }),
                    $request
                )->orderByDesc('created_at'),
                'map' => fn (Order $order) => [
                    $order->order_number,
                    $order->customer?->name ?? '—',
                    $order->itemDisplayName(),
                    $order->grandTotal(),
                    $order->wallet_hold_status,
                    $order->created_at?->format('Y-m-d H:i') ?? '—',
                ],
            ],
            'wallet' => [
                'title' => 'Wallet Activity Export',
                'basename' => 'vendor-wallet-activity',
                'headers' => ['Date', 'Type', 'Wallet', 'Order', 'Direction', 'Amount', 'Balance After'],
                'validate_dates' => false,
                'query' => fn (Request $request) => VendorWalletTransaction::query()
                    ->where('vendor_id', $vendorId)
                    ->with('order')
                    ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->date('from')))
                    ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->date('to')))
                    ->orderByDesc('id'),
                'map' => fn (VendorWalletTransaction $entry) => [
                    $entry->created_at?->format('Y-m-d H:i') ?? '—',
                    $entry->typeLabel(),
                    $entry->walletLabel(),
                    $entry->order?->order_number ?? '—',
                    ucfirst($entry->direction),
                    $entry->amount,
                    $entry->balance_after,
                ],
            ],
            'chat' => [
                'title' => 'Messages Export',
                'basename' => 'vendor-messages',
                'headers' => ['Customer', 'Last Message', 'Unread', 'Last Activity'],
                'validate_dates' => false,
                'query' => fn (Request $request) => Conversation::query()
                    ->where('vendor_id', $vendorId)
                    ->with(['customer', 'latestMessage'])
                    ->withCount([
                        'messages as unread_count' => fn (Builder $q) => $q
                            ->where('sender_type', 'customer')
                            ->whereNull('read_at'),
                    ])
                    ->when($request->filled('search'), function (Builder $q) use ($request) {
                        $term = '%'.$request->string('search').'%';
                        $q->whereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term));
                    })
                    ->orderByDesc('last_message_at'),
                'map' => fn (Conversation $chat) => [
                    $chat->customer?->name ?? '—',
                    $chat->latestMessage?->body ?? '—',
                    $chat->unread_count,
                    $chat->last_message_at?->format('Y-m-d H:i') ?? '—',
                ],
            ],
            default => abort(404),
        };
    }
}
