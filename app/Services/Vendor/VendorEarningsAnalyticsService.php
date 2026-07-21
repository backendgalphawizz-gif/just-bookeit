<?php

namespace App\Services\Vendor;

use App\Models\Order;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VendorEarningsAnalyticsService
{
    /** @var array<string, string> */
    public const SERVICE_TYPES = [
        'fashion-designer' => 'Fashion Designer',
        'rented-dress' => 'Rented Dress',
        'rented-jewellery' => 'Rented Jewelry',
    ];

    /** @return array<string, mixed> */
    public function analytics(Vendor $vendor, int $year): array
    {
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        $orders = Order::query()
            ->where('vendor_id', $vendor->id)
            ->paymentConfirmed()
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->with(['category', 'orderItems'])
            ->orderBy('created_at')
            ->get();

        $monthlyBuckets = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyBuckets[$month] = $this->emptyServiceBucket();
            $monthlyBuckets[$month]['orders'] = 0;
        }

        $yearBucket = $this->emptyServiceBucket();
        $yearOrders = 0;
        $orderIdsByType = $this->emptyServiceOrderIds();

        foreach ($orders as $order) {
            $allocations = $this->allocateOrderEarnings($order);
            if ($allocations === []) {
                continue;
            }

            $month = (int) $order->created_at->month;
            $yearOrders++;
            $monthlyBuckets[$month]['orders']++;

            foreach ($allocations as $slug => $amount) {
                if (! array_key_exists($slug, self::SERVICE_TYPES)) {
                    continue;
                }

                $rounded = round($amount, 2);
                $yearBucket[$slug] = round($yearBucket[$slug] + $rounded, 2);
                $monthlyBuckets[$month][$slug] = round($monthlyBuckets[$month][$slug] + $rounded, 2);
                $orderIdsByType[$slug][$order->id] = true;
            }
        }

        $yearTotal = round(array_sum($yearBucket), 2);

        $byServiceType = collect(self::SERVICE_TYPES)
            ->map(function (string $label, string $slug) use ($yearBucket, $orderIdsByType, $yearTotal) {
                $amount = round((float) $yearBucket[$slug], 2);

                return [
                    'service_type' => $slug,
                    'label' => $label,
                    'amount' => $amount,
                    'amount_label' => '₹'.number_format($amount, 0),
                    'orders_count' => count($orderIdsByType[$slug]),
                    'percent' => $yearTotal > 0 ? round(($amount / $yearTotal) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        $monthly = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthDate = Carbon::create($year, $month, 1);
            $bucket = $monthlyBuckets[$month];
            $monthTotal = round(
                (float) $bucket['fashion-designer']
                + (float) $bucket['rented-dress']
                + (float) $bucket['rented-jewellery'],
                2
            );

            $monthly[] = [
                'month' => $month,
                'month_label' => $monthDate->format('M Y'),
                'amount' => $monthTotal,
                'amount_label' => '₹'.number_format($monthTotal, 0),
                'orders_count' => (int) $bucket['orders'],
                'by_service_type' => [
                    'fashion-designer' => round((float) $bucket['fashion-designer'], 2),
                    'rented-dress' => round((float) $bucket['rented-dress'], 2),
                    'rented-jewellery' => round((float) $bucket['rented-jewellery'], 2),
                ],
            ];
        }

        return [
            'year' => $year,
            'currency' => 'INR',
            'available_years' => $this->availableYears($vendor),
            'summary' => [
                'year_total' => $yearTotal,
                'year_total_label' => '₹'.number_format($yearTotal, 0),
                'orders_count' => $yearOrders,
                'last_updated_at' => now()->format('M d, Y g:i A'),
                'last_updated_at_iso' => now()->toIso8601String(),
            ],
            'by_service_type' => $byServiceType,
            'monthly' => $monthly,
        ];
    }

    /** @return list<int> */
    public function availableYears(Vendor $vendor): array
    {
        $years = Order::query()
            ->where('vendor_id', $vendor->id)
            ->paymentConfirmed()
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->filter(fn (int $year) => $year > 0)
            ->values()
            ->all();

        $current = (int) now()->year;
        if (! in_array($current, $years, true)) {
            array_unshift($years, $current);
        }

        return array_values(array_unique($years));
    }

    /**
     * Split an order's earning amount across service types.
     *
     * @return array<string, float> slug => amount
     */
    protected function allocateOrderEarnings(Order $order): array
    {
        $earning = $this->orderEarningAmount($order);
        if ($earning <= 0) {
            return [];
        }

        /** @var Collection<int, \App\Models\OrderItem> $items */
        $items = $order->relationLoaded('orderItems') ? $order->orderItems : $order->orderItems()->get();

        if ($items->isNotEmpty()) {
            $grouped = [];
            foreach ($items as $item) {
                $slug = $this->normalizeServiceType($item->serviceType() ?? $item->categorySlug());
                if ($slug === null) {
                    $slug = $this->normalizeServiceType($order->category?->slug);
                }
                if ($slug === null) {
                    continue;
                }

                $line = max(0.0, (float) $item->line_amount);
                $grouped[$slug] = ($grouped[$slug] ?? 0.0) + $line;
            }

            $linesTotal = array_sum($grouped);
            if ($linesTotal <= 0) {
                $slug = $this->normalizeServiceType($order->category?->slug);
                return $slug ? [$slug => $earning] : [];
            }

            $allocated = [];
            foreach ($grouped as $slug => $lineTotal) {
                $allocated[$slug] = round($earning * ($lineTotal / $linesTotal), 2);
            }

            // Fix rounding drift on the largest slice.
            $diff = round($earning - array_sum($allocated), 2);
            if (abs($diff) >= 0.01) {
                $maxSlug = array_key_first($allocated);
                foreach ($allocated as $slug => $amount) {
                    if ($amount >= ($allocated[$maxSlug] ?? 0)) {
                        $maxSlug = $slug;
                    }
                }
                $allocated[$maxSlug] = round(($allocated[$maxSlug] ?? 0) + $diff, 2);
            }

            return $allocated;
        }

        $slug = $this->normalizeServiceType($order->category?->slug);

        return $slug ? [$slug => $earning] : [];
    }

    protected function orderEarningAmount(Order $order): float
    {
        if ($order->vendor_net_amount !== null) {
            return max(0.0, (float) $order->vendor_net_amount);
        }

        return max(0.0, (float) $order->amount);
    }

    protected function normalizeServiceType(?string $slug): ?string
    {
        $slug = strtolower(trim((string) $slug));
        if ($slug === '') {
            return null;
        }

        $aliases = [
            'fashion-designer' => 'fashion-designer',
            'fashion_designer' => 'fashion-designer',
            'designing' => 'fashion-designer',
            'designer' => 'fashion-designer',
            'rented-dress' => 'rented-dress',
            'rental-dress' => 'rented-dress',
            'rented_dress' => 'rented-dress',
            'rented-jewellery' => 'rented-jewellery',
            'rented-jewelry' => 'rented-jewellery',
            'rental-jewellery' => 'rented-jewellery',
            'rental-jewelry' => 'rented-jewellery',
            'rented_jewellery' => 'rented-jewellery',
        ];

        return $aliases[$slug] ?? (array_key_exists($slug, self::SERVICE_TYPES) ? $slug : null);
    }

    /** @return array<string, float> */
    protected function emptyServiceBucket(): array
    {
        return [
            'fashion-designer' => 0.0,
            'rented-dress' => 0.0,
            'rented-jewellery' => 0.0,
        ];
    }

    /** @return array<string, array<int, bool>> */
    protected function emptyServiceOrderIds(): array
    {
        return [
            'fashion-designer' => [],
            'rented-dress' => [],
            'rented-jewellery' => [],
        ];
    }
}
