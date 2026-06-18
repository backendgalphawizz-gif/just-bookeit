<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverWalletTransaction;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\Vendor;
use App\Services\Driver\DriverWalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Seeds COD cash-collected delivery demo rows for the driver app dashboard.
 *
 * Run on server: php artisan db:seed --class=DriverCashCollectedDemoSeeder
 *
 * Login driver app with mobile: 9898989898 (OTP flow)
 */
class DriverCashCollectedDemoSeeder extends Seeder
{
    public const DEMO_DRIVER_CODE = 'DRV-COD';

    public const DEMO_DRIVER_MOBILE = '9898989898';

    public const DEMO_CUSTOMER_CODE = 'CUS-COD';

    public const ORDER_COD_TODAY = 'JB-COD-TODAY-001';

    public const ORDER_COD_YESTERDAY = 'JB-COD-YDAY-001';

    public const ORDER_COD_LAST_WEEK = 'JB-COD-WEEK-001';

    public const ORDER_COD_ACTIVE = 'JB-COD-ACTIVE-001';

    public function run(): void
    {
        PlatformSetting::set('enable_cod', true, 'payment', 'boolean');

        $category = Category::query()->where('slug', 'rented-dress')->first()
            ?? Category::query()->where('type', 'service')->first();

        if (! $category) {
            $this->command?->warn('No service category found. Run PlatformDataSeeder first.');

            return;
        }

        $driver = Driver::query()->updateOrCreate(
            ['mobile' => self::DEMO_DRIVER_MOBILE],
            [
                'driver_code' => self::DEMO_DRIVER_CODE,
                'name' => 'COD Demo Driver',
                'email' => 'cod-demo-driver@justbookit.test',
                'city' => 'Mumbai',
                'vehicle_no' => 'MH01COD001',
                'status' => 'active',
                'is_verified' => true,
                'approved_at' => now()->subMonths(1),
                'registered_at' => now()->subMonths(2),
            ]
        );

        $vendor = Vendor::query()->where('vendor_code', 'VEN-WALLET')->first()
            ?? Vendor::query()->where('status', 'active')->first();

        if (! $vendor) {
            $this->command?->warn('No active vendor found. Run PlatformDataSeeder or VendorWalletDemoSeeder first.');

            return;
        }

        $customer = Customer::query()->updateOrCreate(
            ['customer_code' => self::DEMO_CUSTOMER_CODE],
            [
                'name' => 'COD Demo Customer',
                'mobile' => '9191919193',
                'email' => 'cod-demo-customer@justbookit.test',
                'city' => 'Mumbai',
                'status' => 'active',
                'is_verified' => true,
                'profile_image_path' => 'https://picsum.photos/seed/jb-cod-customer/400/400',
                'registered_at' => now()->subMonths(2),
            ]
        );

        $walletService = app(DriverWalletService::class);

        $todayCollected = now()->startOfDay()->addHours(14);
        $this->seedCollectedOrder(
            walletService: $walletService,
            driver: $driver,
            vendor: $vendor,
            customer: $customer,
            category: $category,
            orderNumber: self::ORDER_COD_TODAY,
            itemTitle: 'Demo Lehenga — COD collected today',
            amount: 8500,
            deliveryFee: 199,
            collectedAt: $todayCollected,
            createdAt: now()->subDays(2),
        );

        $yesterdayCollected = now()->subDay()->startOfDay()->addHours(16);
        $this->seedCollectedOrder(
            walletService: $walletService,
            driver: $driver,
            vendor: $vendor,
            customer: $customer,
            category: $category,
            orderNumber: self::ORDER_COD_YESTERDAY,
            itemTitle: 'Demo Saree — COD collected yesterday',
            amount: 6200,
            deliveryFee: 149,
            collectedAt: $yesterdayCollected,
            createdAt: now()->subDays(4),
        );

        $weekCollected = now()->subDays(6)->startOfDay()->addHours(11);
        $this->seedCollectedOrder(
            walletService: $walletService,
            driver: $driver,
            vendor: $vendor,
            customer: $customer,
            category: $category,
            orderNumber: self::ORDER_COD_LAST_WEEK,
            itemTitle: 'Demo Gown — COD collected last week',
            amount: 11500,
            deliveryFee: 249,
            collectedAt: $weekCollected,
            createdAt: now()->subDays(8),
        );

        $this->upsertDemoOrder(self::ORDER_COD_ACTIVE, [
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'driver_id' => $driver->id,
            'category_id' => $category->id,
            'order_type' => 'rental',
            'item_title' => 'Demo Sharara — COD out for delivery (not collected yet)',
            'item_description' => 'Active COD delivery assigned to the demo driver. Cash not collected until delivery is completed.',
            'item_image_path' => 'https://picsum.photos/seed/jb-cod-active/600/800',
            'size' => 'M',
            'color' => 'Rose Gold',
            'quantity' => 1,
            'amount' => 9800,
            'delivery_fee' => 199,
            'tax_amount' => 490,
            'delivery_address' => '18 Demo Lane, Bandra, Mumbai',
            'billing_address' => '18 Demo Lane, Bandra, Mumbai',
            'city' => 'Mumbai',
            'pincode' => '400050',
            'rental_start_date' => now()->toDateString(),
            'rental_end_date' => now()->addDays(3)->toDateString(),
            'return_due_date' => now()->addDays(4)->toDateString(),
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'in_progress',
            'driver_delivery_status' => Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
            'driver_assigned_at' => now()->subHours(5),
            'driver_pickup_at' => now()->subHours(3),
            'delivery_otp' => '123456',
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        $driver->refresh();

        $collectedTotal = Order::query()
            ->where('driver_id', $driver->id)
            ->where('payment_method', 'cod')
            ->whereNotNull('cod_collected_at')
            ->get()
            ->sum(fn (Order $order) => $order->grandTotal());

        $this->command?->info('Driver COD cash-collected demo seeded successfully.');
        $this->command?->line('Driver: '.self::DEMO_DRIVER_CODE.' · mobile '.self::DEMO_DRIVER_MOBILE);
        $this->command?->line('Wallet balance: ₹'.number_format((float) $driver->wallet_balance, 2));
        $this->command?->line('Total COD collected (all demo orders): ₹'.number_format($collectedTotal, 2));
        $this->command?->newLine();
        $this->command?->line('Cash collected orders (GET /v3/home → cash_collected_orders):');
        $this->command?->line('  • '.self::ORDER_COD_TODAY.' — collected today');
        $this->command?->line('  • '.self::ORDER_COD_YESTERDAY.' — collected yesterday');
        $this->command?->line('  • '.self::ORDER_COD_LAST_WEEK.' — collected 6 days ago');
        $this->command?->line('Active COD delivery: '.self::ORDER_COD_ACTIVE.' (not in cash collected list yet)');
    }

    private function seedCollectedOrder(
        DriverWalletService $walletService,
        Driver $driver,
        Vendor $vendor,
        Customer $customer,
        Category $category,
        string $orderNumber,
        string $itemTitle,
        float $amount,
        float $deliveryFee,
        Carbon $collectedAt,
        Carbon $createdAt,
    ): void {
        $order = $this->upsertDemoOrder($orderNumber, [
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'driver_id' => $driver->id,
            'category_id' => $category->id,
            'order_type' => 'rental',
            'item_title' => $itemTitle,
            'item_description' => 'COD order delivered by the demo driver. Cash collected on delivery.',
            'item_image_path' => 'https://picsum.photos/seed/'.strtolower(str_replace([' ', '—'], '-', $orderNumber)).'/600/800',
            'size' => 'M',
            'color' => 'Maroon',
            'quantity' => 1,
            'amount' => $amount,
            'delivery_fee' => $deliveryFee,
            'tax_amount' => round($amount * 0.05, 2),
            'delivery_address' => '12 Demo Street, Andheri, Mumbai',
            'billing_address' => '12 Demo Street, Andheri, Mumbai',
            'city' => 'Mumbai',
            'pincode' => '400053',
            'rental_start_date' => $createdAt->copy()->addDay()->toDateString(),
            'rental_end_date' => $createdAt->copy()->addDays(5)->toDateString(),
            'return_due_date' => $createdAt->copy()->addDays(6)->toDateString(),
            'payment_method' => 'cod',
            'payment_status' => 'success',
            'status' => 'delivered',
            'driver_delivery_status' => null,
            'driver_assigned_at' => $collectedAt->copy()->subHours(6),
            'driver_pickup_at' => $collectedAt->copy()->subHours(4),
            'driver_delivered_at' => $collectedAt,
            'cod_collected_at' => $collectedAt,
            'delivery_otp' => '123456',
            'created_at' => $createdAt,
            'updated_at' => $collectedAt,
        ]);

        $this->ensureDeliveryCredit($walletService, $order, $driver);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertDemoOrder(string $orderNumber, array $attributes): Order
    {
        $order = Order::query()->firstOrNew(['order_number' => $orderNumber]);
        $order->fill($attributes);
        $order->save();

        return $order->fresh();
    }

    private function ensureDeliveryCredit(DriverWalletService $walletService, Order $order, Driver $driver): void
    {
        if (! in_array($order->status, ['delivered', 're_delivered'], true)) {
            return;
        }

        if (DriverWalletTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', DriverWalletTransaction::TYPE_DELIVERY_CREDIT)
            ->exists()) {
            return;
        }

        try {
            $walletService->creditDeliveryEarning($order->fresh(), $driver);
        } catch (InvalidArgumentException $exception) {
            $this->command?->warn("Could not credit delivery earning for {$order->order_number}: {$exception->getMessage()}");
        }
    }
}
