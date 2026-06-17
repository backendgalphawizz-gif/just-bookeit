<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorWalletTransaction;
use App\Services\Vendor\VendorWalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds predictable wallet + dispute demo rows for live/staging review.
 *
 * Run on server: php artisan db:seed --class=VendorWalletDemoSeeder
 */
class VendorWalletDemoSeeder extends Seeder
{
    public const DEMO_VENDOR_CODE = 'VEN-WALLET';

    public const DEMO_CUSTOMER_CODE = 'CUS-WALLET';

    public const ORDER_HELD = 'JB-WALLET-HELD-001';

    public const ORDER_RELEASED = 'JB-WALLET-RELEASED-001';

    public const ORDER_DISP_CUSTOMER = 'JB-DISP-CUST-001';

    public const ORDER_DISP_VENDOR = 'JB-DISP-VEND-001';

    public function run(): void
    {
        $category = Category::query()->where('slug', 'rented-dress')->first()
            ?? Category::query()->where('type', 'service')->first();

        if (! $category) {
            $this->command?->warn('No service category found. Run PlatformDataSeeder first.');

            return;
        }

        $vendor = Vendor::query()->updateOrCreate(
            ['vendor_code' => self::DEMO_VENDOR_CODE],
            [
                'brand_name' => 'Wallet Demo Boutique',
                'owner_name' => 'Demo Vendor Owner',
                'mobile' => '9191919191',
                'email' => 'wallet-demo-vendor@justbookit.test',
                'city' => 'Mumbai',
                'categories' => ['Rented Dress'],
                'rating' => 4.8,
                'orders_completed' => 42,
                'earnings' => 250000,
                'status' => 'active',
                'approved_at' => now()->subMonths(2),
            ]
        );

        $customer = Customer::query()->updateOrCreate(
            ['customer_code' => self::DEMO_CUSTOMER_CODE],
            [
                'name' => 'Wallet Demo Customer',
                'mobile' => '9191919192',
                'email' => 'wallet-demo-customer@justbookit.test',
                'city' => 'Mumbai',
                'status' => 'active',
                'is_verified' => true,
                'registered_at' => now()->subMonths(2),
            ]
        );

        $walletService = app(VendorWalletService::class);
        $adminId = Admin::query()->orderBy('id')->value('id') ?? 1;

        $heldPaidAt = now()->startOfDay()->addHours(12);

        $heldOrder = $this->upsertDemoOrder(self::ORDER_HELD, [
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'order_type' => 'rental',
            'item_title' => 'Demo Lehenga — 15-day digital wallet hold',
            'item_description' => 'Delivered today. Vendor payout sits in the digital wallet until the 15-day hold ends.',
            'size' => 'M',
            'color' => 'Maroon',
            'quantity' => 1,
            'amount' => 15000,
            'delivery_fee' => 199,
            'tax_amount' => 750,
            'delivery_address' => '12 Demo Street, Bandra, Mumbai',
            'billing_address' => '12 Demo Street, Bandra, Mumbai',
            'city' => 'Mumbai',
            'pincode' => '400050',
            'rental_start_date' => now()->subDay()->toDateString(),
            'rental_end_date' => now()->addDays(4)->toDateString(),
            'return_due_date' => now()->addDays(5)->toDateString(),
            'payment_status' => 'success',
            'payment_method' => 'online',
            'status' => 'delivered',
            'paid_at' => $heldPaidAt,
            'driver_delivered_at' => $heldPaidAt->copy()->subHour(),
            'created_at' => now()->subDays(2),
            'updated_at' => now(),
        ]);

        $this->ensureWalletCredit($walletService, $heldOrder);

        $customerDisputeOnHeld = $this->seedDispute(
            order: $heldOrder->fresh(),
            raisedBy: 'customer',
            subject: 'DEMO — Customer raised: quality issue on held payout',
            status: 'under_review',
            openerType: DisputeMessage::SENDER_CUSTOMER,
            openerId: $customer->id,
            openerBody: 'The lehenga lining was torn on delivery. I raised this dispute while the vendor payout is still in the digital wallet.',
            adminId: $adminId,
            adminBody: 'Thanks for reporting this. We are reviewing the issue while the vendor payout remains on hold.',
        );

        $releasedPaidAt = now()->subDays(20)->startOfDay()->addHours(14);

        $releasedOrder = $this->upsertDemoOrder(self::ORDER_RELEASED, [
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'order_type' => 'sale',
            'item_title' => 'Demo Kurta Set — released to actual wallet',
            'item_description' => 'Delivered 20 days ago. Hold period ended and funds moved to the actual wallet.',
            'size' => 'L',
            'color' => 'Ivory',
            'quantity' => 1,
            'amount' => 12000,
            'delivery_fee' => 99,
            'tax_amount' => 600,
            'delivery_address' => '45 Sample Road, Andheri, Mumbai',
            'billing_address' => '45 Sample Road, Andheri, Mumbai',
            'city' => 'Mumbai',
            'pincode' => '400053',
            'event_date' => now()->subDays(18)->toDateString(),
            'payment_status' => 'success',
            'payment_method' => 'online',
            'status' => 'delivered',
            'paid_at' => $releasedPaidAt,
            'driver_delivered_at' => $releasedPaidAt->copy()->subHours(2),
            'created_at' => now()->subDays(22),
            'updated_at' => $releasedPaidAt->copy()->addDay(),
        ]);

        $this->ensureWalletCredit($walletService, $releasedOrder);
        $walletService->releaseExpiredHolds();

        $vendorDisputeOnReleased = $this->seedDispute(
            order: $releasedOrder->fresh(),
            raisedBy: 'vendor',
            subject: 'DEMO — Vendor raised: late return after wallet release',
            status: 'resolved',
            openerType: 'vendor',
            openerId: $vendor->id,
            openerBody: 'Customer returned the outfit two days late. Dispute raised by vendor after payout moved to actual wallet.',
            adminId: $adminId,
            adminBody: 'We reviewed the vendor dispute and recorded the resolution.',
            resolutionNote: 'Late return fee waived. Vendor payout was already released to the actual wallet.',
        );

        $customerDisputeOrder = $this->upsertDemoOrder(self::ORDER_DISP_CUSTOMER, [
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'order_type' => 'rental',
            'item_title' => 'Demo Saree — customer dispute (open)',
            'item_description' => 'Standalone dispute demo raised by the customer against the vendor.',
            'size' => 'S',
            'color' => 'Gold',
            'quantity' => 1,
            'amount' => 8500,
            'delivery_fee' => 149,
            'tax_amount' => 425,
            'delivery_address' => '8 Demo Lane, Juhu, Mumbai',
            'billing_address' => '8 Demo Lane, Juhu, Mumbai',
            'city' => 'Mumbai',
            'pincode' => '400049',
            'rental_start_date' => now()->subDays(5)->toDateString(),
            'rental_end_date' => now()->addDay()->toDateString(),
            'return_due_date' => now()->addDays(2)->toDateString(),
            'payment_status' => 'success',
            'payment_method' => 'online',
            'status' => 'delivered',
            'paid_at' => now()->subDays(4)->startOfDay()->addHours(10),
            'driver_delivered_at' => now()->subDays(4)->startOfDay()->addHours(9),
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(3),
        ]);

        $this->ensureWalletCredit($walletService, $customerDisputeOrder);

        $customerDisputeOpen = $this->seedDispute(
            order: $customerDisputeOrder->fresh(),
            raisedBy: 'customer',
            subject: 'DEMO — Customer raised: wrong size delivered',
            status: 'raised',
            openerType: DisputeMessage::SENDER_CUSTOMER,
            openerId: $customer->id,
            openerBody: 'I ordered size S but received size M. Please help resolve this issue with the vendor.',
            adminId: $adminId,
        );

        $vendorDisputeOrder = $this->upsertDemoOrder(self::ORDER_DISP_VENDOR, [
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'order_type' => 'sale',
            'item_title' => 'Demo Sherwani — vendor dispute (open)',
            'item_description' => 'Standalone dispute demo raised by the vendor against the customer.',
            'size' => 'XL',
            'color' => 'Navy',
            'quantity' => 1,
            'amount' => 22000,
            'delivery_fee' => 199,
            'tax_amount' => 1100,
            'delivery_address' => '22 Demo Avenue, Powai, Mumbai',
            'billing_address' => '22 Demo Avenue, Powai, Mumbai',
            'city' => 'Mumbai',
            'pincode' => '400076',
            'event_date' => now()->addDays(10)->toDateString(),
            'payment_status' => 'success',
            'payment_method' => 'online',
            'status' => 'in_progress',
            'paid_at' => now()->subDays(2)->startOfDay()->addHours(15),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDay(),
        ]);

        $this->ensureWalletCredit($walletService, $vendorDisputeOrder);

        $vendorDisputeOpen = $this->seedDispute(
            order: $vendorDisputeOrder->fresh(),
            raisedBy: 'vendor',
            subject: 'DEMO — Vendor raised: customer cancellation request',
            status: 'under_review',
            openerType: 'vendor',
            openerId: $vendor->id,
            openerBody: 'Customer asked to cancel after tailoring started. Vendor raised this dispute for admin review.',
            adminId: $adminId,
            adminBody: 'We are reviewing the vendor dispute and coordinating with both parties.',
        );

        $vendor->refresh();

        $this->command?->info('Wallet + dispute demo seeded successfully.');
        $this->command?->line('Vendor: '.self::DEMO_VENDOR_CODE.' ('.$vendor->brand_name.')');
        $this->command?->line('Customer: '.self::DEMO_CUSTOMER_CODE.' ('.$customer->name.')');
        $this->command?->line('Digital wallet: ₹'.number_format((float) $vendor->digital_wallet_balance, 2));
        $this->command?->line('Actual wallet: ₹'.number_format((float) $vendor->wallet_balance, 2));
        $this->command?->newLine();
        $this->command?->line('Wallet demos:');
        $this->command?->line('  • '.self::ORDER_HELD.' → digital hold (release '.$heldOrder->fresh()->wallet_release_at?->format('M d, Y').')');
        $this->command?->line('  • '.self::ORDER_RELEASED.' → released to actual wallet');
        $this->command?->newLine();
        $this->command?->line('Dispute demos (admin → Disputes, filter Customer / Vendor tabs):');
        $this->command?->line('  • #'.$customerDisputeOnHeld->id.' customer raised · under_review · '.self::ORDER_HELD);
        $this->command?->line('  • #'.$vendorDisputeOnReleased->id.' vendor raised · resolved · '.self::ORDER_RELEASED);
        $this->command?->line('  • #'.$customerDisputeOpen->id.' customer raised · raised · '.self::ORDER_DISP_CUSTOMER);
        $this->command?->line('  • #'.$vendorDisputeOpen->id.' vendor raised · under_review · '.self::ORDER_DISP_VENDOR);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertDemoOrder(string $orderNumber, array $attributes): Order
    {
        $order = Order::query()->firstOrNew(['order_number' => $orderNumber]);
        $isNew = ! $order->exists;

        $order->fill($attributes);

        if ($isNew) {
            $order->wallet_hold_status = 'none';
            $order->vendor_wallet_held_amount = 0;
        }

        $order->save();

        return $order->fresh();
    }

    private function ensureWalletCredit(VendorWalletService $walletService, Order $order): void
    {
        $order->refresh();

        if ($order->wallet_hold_status !== 'none') {
            return;
        }

        if (VendorWalletTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', VendorWalletTransaction::TYPE_PAYMENT_CREDIT)
            ->exists()) {
            return;
        }

        if ($order->payment_status !== 'success' || ! $order->vendor_id) {
            return;
        }

        $walletService->creditFromPayment($order->fresh());
    }

    private function seedDispute(
        Order $order,
        string $raisedBy,
        string $subject,
        string $status,
        string $openerType,
        int $openerId,
        string $openerBody,
        int $adminId,
        ?string $adminBody = null,
        ?string $resolutionNote = null,
    ): Dispute {
        $createdAt = $order->paid_at instanceof Carbon
            ? $order->paid_at->copy()->addDay()
            : now()->subDay();

        $dispute = Dispute::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'category_id' => $order->category_id,
                'raised_by' => $raisedBy,
                'subject' => $subject,
                'status' => $status,
                'resolution_note' => $resolutionNote,
                'created_at' => $createdAt,
            ]
        );

        if (! $dispute->messages()->where('sender_type', $openerType)->where('sender_id', $openerId)->exists()) {
            $dispute->messages()->create([
                'sender_type' => $openerType,
                'sender_id' => $openerId,
                'body' => $openerBody,
                'created_at' => $dispute->created_at,
            ]);
        }

        if ($adminBody && ! $dispute->messages()->where('sender_type', DisputeMessage::SENDER_ADMIN)->exists()) {
            $dispute->messages()->create([
                'sender_type' => DisputeMessage::SENDER_ADMIN,
                'sender_id' => $adminId,
                'body' => $adminBody,
                'created_at' => $dispute->created_at->copy()->addHours(3),
            ]);
        }

        return $dispute->fresh();
    }
}
