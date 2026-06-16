<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OrderDemoSeeder extends Seeder
{
    public const SHOWCASE_ORDER_ID = 56;

    /** @var array<int, string> */
    private const RENTAL_ITEMS = [
        'Designer Lehenga — Royal Maroon',
        'Silk Bridal Saree — Gold Zari',
        'Indo-Western Gown — Emerald',
        'Party Wear Anarkali — Navy Blue',
        'Festive Sharara Set — Rose Gold',
        'Embroidered Cocktail Dress — Ivory',
    ];

    /** @var array<int, string> */
    private const SALE_ITEMS = [
        'Custom Tailored Blazer — Charcoal',
        'Handloom Kurta Set — Ochre',
        'Designer Dupatta — Crimson',
    ];

    public function run(): void
    {
        Order::query()->with('customer')->each(function (Order $order): void {
            $needsEnrichment = ! $order->item_title
                || ($order->isRental() && ! $order->rental_start_date);

            if (! $needsEnrichment) {
                return;
            }

            $order->update(self::demoAttributes($order, $order->id === self::SHOWCASE_ORDER_ID));
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function extrasForNewOrder(
        string $status,
        string $orderType,
        Carbon $createdAt,
        float $amount,
        ?string $city = null,
        int $seed = 0,
    ): array {
        $isRental = $orderType === 'rental';
        $itemTitle = $isRental
            ? fake()->randomElement(self::RENTAL_ITEMS)
            : fake()->randomElement(self::SALE_ITEMS);

        $city = $city ?? fake()->randomElement(['Mumbai', 'Delhi', 'Bengaluru', 'Hyderabad', 'Pune', 'Chennai']);
        $address = fake()->buildingNumber().' '.fake()->streetName().', '.fake()->streetAddress();

        $attrs = [
            'order_type' => $orderType,
            'item_title' => $itemTitle,
            'item_description' => fake()->sentence(12),
            'item_image_path' => 'https://picsum.photos/seed/jb-new-'.$seed.'/600/800',
            'size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            'color' => fake()->randomElement(['Maroon', 'Emerald', 'Navy Blue', 'Rose Gold', 'Ivory']),
            'quantity' => 1,
            'delivery_address' => $address,
            'billing_address' => $address,
            'city' => $city,
            'pincode' => fake()->numerify('######'),
            'security_deposit' => $isRental ? round($amount * 0.15, 2) : null,
            'delivery_fee' => fake()->randomElement([0, 99, 199, 299]),
            'tax_amount' => round($amount * 0.05, 2),
            'measure_height_cm' => rand(158, 178),
            'measure_chest_cm' => rand(84, 102),
            'measure_waist_cm' => rand(66, 88),
            'customer_notes' => fake()->optional(0.35)->sentence(12),
            'reference_image_paths' => [
                'https://picsum.photos/seed/jb-ref-'.$seed.'-1/400/500',
                'https://picsum.photos/seed/jb-ref-'.$seed.'-2/400/500',
            ],
        ];

        if ($isRental) {
            $dates = self::rentalDates($status, $createdAt, false);
            $attrs = array_merge($attrs, $dates);
            $attrs['event_date'] = Carbon::parse($dates['rental_start_date'])->addDays(2)->toDateString();
        } else {
            $attrs['event_date'] = $createdAt->copy()->addDays(rand(7, 30))->toDateString();
        }

        if (in_array($status, ['delivered'], true)) {
            $attrs['payment_status'] = 'success';
            $attrs['paid_at'] = $createdAt->copy()->addHours(2);
        }

        return $attrs;
    }

    /**
     * @return array<string, mixed>
     */
    public static function demoAttributes(Order $order, bool $showcase = false): array
    {
        $isRental = $order->isRental();
        $city = $order->city ?? $order->customer?->city ?? fake()->randomElement(['Mumbai', 'Delhi', 'Bengaluru', 'Hyderabad', 'Pune', 'Chennai']);
        $address = $order->delivery_address ?? fake()->buildingNumber().' '.fake()->streetName().', '.fake()->streetAddress();

        $attrs = [
            'item_title' => $isRental
                ? fake()->randomElement(self::RENTAL_ITEMS)
                : fake()->randomElement(self::SALE_ITEMS),
            'item_description' => fake()->sentence(12),
            'item_image_path' => 'https://picsum.photos/seed/jb-order-'.$order->id.'/600/800',
            'size' => $order->size ?? fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            'color' => $order->color ?? fake()->randomElement(['Maroon', 'Emerald', 'Navy Blue', 'Rose Gold', 'Ivory']),
            'quantity' => $order->quantity ?: 1,
            'delivery_address' => $address,
            'billing_address' => $order->billing_address ?? $address,
            'city' => $city,
            'pincode' => $order->pincode ?? fake()->numerify('######'),
            'security_deposit' => $isRental ? round((float) $order->amount * 0.15, 2) : null,
            'delivery_fee' => $order->delivery_fee ?? fake()->randomElement([0, 99, 199, 299]),
            'tax_amount' => $order->tax_amount ?? round((float) $order->amount * 0.05, 2),
            'measure_height_cm' => $order->measure_height_cm ?? rand(158, 178),
            'measure_chest_cm' => $order->measure_chest_cm ?? rand(84, 102),
            'measure_waist_cm' => $order->measure_waist_cm ?? rand(66, 88),
            'reference_image_paths' => $order->reference_image_paths ?? [
                'https://picsum.photos/seed/jb-ref-'.$order->id.'-1/400/500',
                'https://picsum.photos/seed/jb-ref-'.$order->id.'-2/400/500',
            ],
        ];

        if ($isRental) {
            $dates = self::rentalDates($order->status, $order->created_at, $showcase);
            $attrs = array_merge($attrs, $dates);
            $attrs['event_date'] = Carbon::parse($dates['rental_start_date'])->addDays(2)->toDateString();
        } elseif (! $order->event_date) {
            $attrs['event_date'] = $order->created_at->copy()->addDays(rand(7, 30))->toDateString();
        }

        if ($showcase) {
            $attrs['item_title'] = 'Designer Lehenga — Royal Maroon Collection';
            $attrs['size'] = 'M';
            $attrs['color'] = 'Maroon & Gold';
            $attrs['status'] = 'in_progress';
            $attrs['payment_status'] = 'success';
            $attrs['paid_at'] = now()->subDays(3);
            $attrs['delivery_fee'] = 199;
            $attrs['measure_height_cm'] = 168;
            $attrs['measure_chest_cm'] = 92;
            $attrs['measure_waist_cm'] = 74;
            $attrs['customer_notes'] = 'Please allow 2-inch blouse margin. Dupatta draping preferred on the left shoulder.';
            $attrs['security_deposit'] = round((float) $order->amount * 0.2, 2);
        } elseif (in_array($order->status, ['delivered'], true) && $order->payment_status === 'pending') {
            $attrs['payment_status'] = 'success';
            $attrs['paid_at'] = $order->created_at->copy()->addHours(2);
        }

        if (! $order->customer_notes && ! $showcase) {
            $attrs['customer_notes'] = fake()->optional(0.35)->sentence(12);
        }

        return $attrs;
    }

    /**
     * @return array{rental_start_date: string, rental_end_date: string, return_due_date: string}
     */
    private static function rentalDates(string $status, Carbon $createdAt, bool $showcase): array
    {
        if ($showcase) {
            $start = now()->subDays(2)->startOfDay();
            $end = now()->addDays(5)->startOfDay();

            return [
                'rental_start_date' => $start->toDateString(),
                'rental_end_date' => $end->toDateString(),
                'return_due_date' => $end->copy()->addDay()->toDateString(),
            ];
        }

        if (in_array($status, ['delivered', 'refunded', 'cancelled'], true)) {
            $start = $createdAt->copy()->addDay()->startOfDay();
            $end = $start->copy()->addDays(rand(4, 8));
        } elseif (in_array($status, ['in_progress', 'accepted'], true)) {
            $start = now()->subDays(rand(1, 3))->startOfDay();
            $end = now()->addDays(rand(4, 7))->startOfDay();
        } else {
            $start = $createdAt->copy()->addDay()->startOfDay();
            if ($start->lt(now()->startOfDay())) {
                $start = now()->copy()->addDays(rand(3, 10))->startOfDay();
            }
            $end = $start->copy()->addDays(rand(5, 10));
        }

        return [
            'rental_start_date' => $start->toDateString(),
            'rental_end_date' => $end->toDateString(),
            'return_due_date' => $end->copy()->addDay()->toDateString(),
        ];
    }
}
