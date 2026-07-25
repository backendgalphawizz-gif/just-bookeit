<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Services\Checkout\VendorBookingItemService;
use App\Support\FashionDesignerLifecycleSupport;
use Illuminate\Console\Command;

class AutoCompleteFashionDesignerItems extends Command
{
    protected $signature = 'bookings:auto-complete-designer {--dry-run : List items without updating}';

    protected $description = 'Auto-complete fashion designer items still Delivered after the 48-hour rework window';

    public function handle(VendorBookingItemService $items): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $completed = 0;

        OrderItem::query()
            ->with(['order.orderItems', 'order.checkoutOrder', 'order.category'])
            ->where('status', 'delivered')
            ->orderBy('id')
            ->chunkById(100, function ($chunk) use ($items, $dryRun, &$completed) {
                foreach ($chunk as $item) {
                    if (! FashionDesignerLifecycleSupport::shouldAutoComplete($item, $item->order)) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("Would complete item #{$item->id} on order {$item->order?->order_number}");
                        $completed++;

                        continue;
                    }

                    $order = $item->order;
                    if (! $order) {
                        continue;
                    }

                    try {
                        $items->updateItemStatus($order, $item, 'completed');
                        $completed++;
                        $this->info("Completed item #{$item->id} ({$order->order_number})");
                    } catch (\Throwable $exception) {
                        $this->error("Item #{$item->id}: ".$exception->getMessage());
                    }
                }
            });

        $this->info(($dryRun ? 'Would complete' : 'Completed')." {$completed} fashion designer item(s).");

        return self::SUCCESS;
    }
}
