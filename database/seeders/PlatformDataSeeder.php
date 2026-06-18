<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Refund;
use App\Models\Vendor;
use App\Models\VendorPayout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformDataSeeder extends Seeder
{
    public function run(): void
    {
        $mainCategories = [
            ['name' => 'Women', 'slug' => 'women'],
            ['name' => 'Men', 'slug' => 'men'],
            ['name' => 'Kids', 'slug' => 'kids'],
        ];

        foreach ($mainCategories as $index => $data) {
            Category::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'type' => 'main',
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        $serviceCategories = [
            ['name' => 'Fashion Designer', 'slug' => 'fashion-designer'],
            ['name' => 'Rented Dress', 'slug' => 'rented-dress'],
            ['name' => 'Rented Jewellery', 'slug' => 'rented-jewellery'],
        ];

        foreach ($serviceCategories as $index => $data) {
            Category::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'type' => 'service',
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        $subcategoryDefinitions = [
            'women' => ['Sarees', 'Lehengas', 'Gowns', 'Kurtis'],
            'men' => ['Suits', 'Kurtas', 'Sherwanis'],
            'kids' => ['Ethnic Wear', 'Party Wear'],
        ];

        foreach ($subcategoryDefinitions as $mainSlug => $names) {
            $main = Category::query()->where('type', 'main')->where('slug', $mainSlug)->first();

            if (! $main) {
                continue;
            }

            foreach ($names as $index => $name) {
                Category::query()->updateOrCreate(
                    ['slug' => $mainSlug.'-'.Str::slug($name)],
                    [
                        'name' => $name,
                        'type' => 'sub',
                        'parent_id' => $main->id,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }

        $serviceIds = Category::query()->where('type', 'service')->pluck('id');
        $subcategoryIds = Category::query()->where('type', 'sub')->pluck('id');
        $cities = ['Mumbai', 'Delhi', 'Bengaluru', 'Hyderabad', 'Pune', 'Chennai'];

        $customers = collect();
        for ($i = 1; $i <= 28; $i++) {
            $customers->push(Customer::query()->updateOrCreate(
                ['customer_code' => 'CUS'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)],
                [
                    'name' => fake()->name(),
                    'mobile' => '9'.fake()->numerify('#########'),
                    'email' => "customer{$i}@example.com",
                    'city' => fake()->randomElement($cities),
                    'status' => fake()->randomElement(['active', 'active', 'active', 'suspended']),
                    'is_verified' => fake()->boolean(70),
                    'total_orders' => 0,
                    'registered_at' => now()->subDays(rand(1, 180)),
                ]
            ));
        }

        $vendorStatuses = array_merge(
            array_fill(0, 5, 'pending'),
            array_fill(0, 10, 'active'),
            ['suspended', 'rejected']
        );

        $vendors = collect();
        foreach ($vendorStatuses as $index => $status) {
            $num = $index + 1;
            $vendors->push(Vendor::query()->updateOrCreate(
                ['vendor_code' => 'VEN'.str_pad((string) $num, 5, '0', STR_PAD_LEFT)],
                [
                    'brand_name' => fake()->company().' Studio',
                    'owner_name' => fake()->name(),
                    'mobile' => '9'.fake()->numerify('#########'),
                    'email' => "vendor{$num}@example.com",
                    'city' => fake()->randomElement($cities),
                    'categories' => ['Fashion Designer', 'Rented Dress'],
                    'rating' => $status === 'active' ? fake()->randomFloat(2, 3.5, 5) : 0,
                    'orders_completed' => $status === 'active' ? rand(5, 120) : 0,
                    'earnings' => $status === 'active' ? rand(50000, 800000) : 0,
                    'status' => $status,
                    'approved_at' => $status === 'active' ? now()->subDays(rand(10, 90)) : null,
                    'created_at' => now()->subDays(rand(1, 120)),
                ]
            ));
        }

        $activeVendors = $vendors->where('status', 'active');
        $orderStatuses = [
            'new', 'new', 'pending_acceptance', 'accepted', 'in_progress',
            'in_progress', 'delivered', 'delivered', 'delivered', 'cancelled', 'refunded',
        ];

        $orderCount = 0;
        for ($m = 5; $m >= 0; $m--) {
            $monthStart = now()->subMonths($m)->startOfMonth();
            $ordersThisMonth = rand(6, 14);

            for ($j = 0; $j < $ordersThisMonth; $j++) {
                $orderCount++;
                $customer = $customers->random();
                $status = fake()->randomElement($orderStatuses);
                $orderType = fake()->randomElement(['rental', 'rental', 'rental', 'sale']);
                $createdAt = $monthStart->copy()->addDays(rand(0, 27));
                if ($createdAt->isFuture()) {
                    $createdAt = now()->subHours(rand(2, 72));
                }
                $amount = rand(1500, 45000);
                $paymentStatus = in_array($status, ['cancelled', 'refunded'])
                    ? ($status === 'refunded' ? 'refunded' : 'failed')
                    : (in_array($status, ['delivered']) ? 'success' : fake()->randomElement(['pending', 'success']));

                Order::query()->create(array_merge([
                    'order_number' => 'JB'.now()->format('ym').str_pad((string) $orderCount, 5, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'vendor_id' => $activeVendors->isNotEmpty() ? $activeVendors->random()->id : null,
                    'category_id' => $serviceIds->random(),
                    'amount' => $amount,
                    'payment_status' => $paymentStatus,
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ], OrderDemoSeeder::extrasForNewOrder(
                    $status,
                    $orderType,
                    $createdAt,
                    (float) $amount,
                    $customer->city,
                    $orderCount,
                )));
            }
        }

        Customer::query()->each(function (Customer $customer) {
            $customer->update([
                'total_orders' => $customer->orders()->count(),
            ]);
        });

        $ordersForRefunds = Order::query()->whereIn('status', ['delivered', 'cancelled', 'refunded'])->inRandomOrder()->limit(12)->get();
        foreach ($ordersForRefunds as $index => $order) {
            Refund::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'customer_id' => $order->customer_id,
                    'amount' => $order->amount * 0.8,
                    'reason' => fake()->randomElement(['Wrong size', 'Late delivery', 'Quality issue', 'Order cancelled']),
                    'status' => fake()->randomElement(['requested', 'under_review', 'approved', 'processed', 'rejected']),
                    'created_at' => $order->created_at->copy()->addDays(rand(1, 10)),
                ]
            );
        }

        $ordersForDisputes = Order::query()->inRandomOrder()->limit(7)->get();
        foreach ($ordersForDisputes as $index => $order) {
            $status = fake()->randomElement(['raised', 'under_review', 'resolved', 'closed']);
            $dispute = Dispute::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'category_id' => $order->category_id,
                    'raised_by' => fake()->randomElement(['customer', 'vendor']),
                    'subject' => fake()->randomElement(['Delivery delay', 'Measurement mismatch', 'Payment dispute', 'Service quality']),
                    'status' => $status,
                    'resolution_note' => in_array($status, ['resolved', 'closed'], true)
                        ? 'Issue addressed after discussion with the customer.'
                        : null,
                    'created_at' => $order->created_at->copy()->addDays(rand(2, 15)),
                ]
            );

            if ($dispute->messages()->count() === 0) {
                $dispute->messages()->create([
                    'sender_type' => \App\Models\DisputeMessage::SENDER_CUSTOMER,
                    'sender_id' => $order->customer_id,
                    'body' => 'I need help with this order. '.$dispute->subject.'.',
                    'created_at' => $dispute->created_at,
                ]);

                if (in_array($status, ['under_review', 'resolved', 'closed'], true)) {
                    $dispute->messages()->create([
                        'sender_type' => \App\Models\DisputeMessage::SENDER_ADMIN,
                        'sender_id' => 1,
                        'body' => 'Thanks for reaching out. Our team is reviewing your dispute and will assist you shortly.',
                        'created_at' => $dispute->created_at->copy()->addHours(2),
                    ]);
                }
            }
        }

        $payoutStatuses = array_merge(
            array_fill(0, 6, 'pending'),
            ['scheduled', 'scheduled', 'processing', 'paid', 'paid', 'paid', 'failed', 'cancelled']
        );
        foreach ($activeVendors as $index => $vendor) {
            $gross = rand(8000, 120000);
            $commission = round($gross * 0.12, 2);
            $status = $payoutStatuses[$index % count($payoutStatuses)];
            VendorPayout::query()->updateOrCreate(
                ['payout_code' => 'PAY'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'vendor_id' => $vendor->id,
                    'gross_amount' => $gross,
                    'commission_amount' => $commission,
                    'net_amount' => $gross - $commission,
                    'status' => $status,
                    'reference' => $status === 'paid' ? 'UTR'.fake()->numerify('##########') : null,
                    'paid_at' => $status === 'paid' ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now()->subDays(rand(1, 60)),
                ]
            );
        }

        $portfolioStatuses = array_merge(
            array_fill(0, 8, 'pending'),
            array_fill(0, 12, 'approved'),
            ['rejected', 'rejected']
        );
        $portfolioTitles = [
            'Bridal lehenga look', 'Indo-western ensemble', 'Designer saree drape',
            'Party wear collection', 'Kids festive wear', 'Custom menswear fitting',
        ];
        foreach ($activeVendors->take(10) as $index => $vendor) {
            $status = $portfolioStatuses[$index % count($portfolioStatuses)];
            PortfolioItem::query()->updateOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'title' => $portfolioTitles[$index % count($portfolioTitles)],
                ],
                [
                    'category_id' => $serviceIds->random(),
                    'subcategory_id' => $subcategoryIds->isNotEmpty() ? $subcategoryIds->random() : null,
                    'audience' => fake()->randomElement(['women', 'men', 'kids']),
                    'description' => fake()->sentence(12),
                    'image_url' => 'https://picsum.photos/seed/jb-'.$vendor->id.'-'.$index.'/800/600',
                    'status' => $status,
                    'rejection_reason' => $status === 'rejected' ? 'Image quality does not meet guidelines.' : null,
                    'reviewed_at' => $status !== 'pending' ? now()->subDays(rand(1, 20)) : null,
                    'created_at' => now()->subDays(rand(1, 45)),
                ]
            );
        }

        Banner::query()->updateOrCreate(
            ['title' => 'Festive Collection 2026', 'audience' => 'customer'],
            [
                'subtitle' => 'Book top designers near you',
                'redirect_url' => '/',
                'is_active' => true,
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(30),
            ]
        );

        Banner::query()->updateOrCreate(
            ['title' => 'Grow your studio bookings', 'audience' => 'vendor'],
            [
                'subtitle' => 'Complete your portfolio to get more customer orders',
                'redirect_url' => '/vendor/products',
                'is_active' => true,
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addDays(60),
            ]
        );

        Banner::query()->updateOrCreate(
            ['title' => 'Peak season bonus deliveries', 'audience' => 'driver'],
            [
                'subtitle' => 'Earn more on weekend outfit drop-offs and returns',
                'redirect_url' => null,
                'is_active' => true,
                'starts_at' => now()->subDays(1),
                'ends_at' => now()->addDays(45),
            ]
        );
    }
}
