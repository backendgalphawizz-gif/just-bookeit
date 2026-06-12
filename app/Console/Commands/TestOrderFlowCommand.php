<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V1\BookingController as CustomerBookingController;
use App\Http\Controllers\Api\V1\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Api\V2\BookingController as VendorBookingController;
use App\Http\Controllers\Api\V3\DeliveryController as DriverDeliveryController;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\CodeGenerator;
use App\Support\Api\CustomerApiPresenter;
use App\Support\Api\DriverApiPresenter;
use App\Support\Api\VendorApiPresenter;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestOrderFlowCommand extends Command
{
    protected $signature = 'test:order-flow {--fresh : Reset in-progress test order if exists}';

    protected $description = 'Run end-to-end customer → vendor → driver order flow and report results';

    /** @var list<string> */
    protected array $log = [];

    /** @var list<string> */
    protected array $errors = [];

    public function handle(): int
    {
        $this->info('=== Just Book IT — Full Order Flow Test ===');
        $this->newLine();

        try {
            PlatformSetting::set('enable_cod', true, 'payment', 'boolean');

            $customer = $this->resolveCustomer();
            $product = $this->resolveProduct();
            $vendor = $product->vendor ?? $this->resolveVendor();
            $driver = $this->resolveDriver();

            $this->line("Customer: {$customer->name} (#{$customer->id})");
            $this->line("Vendor: {$vendor->brand_name} (#{$vendor->id})");
            $this->line("Driver: {$driver->name} (#{$driver->id})");
            $this->line("Product: {$product->title} (#{$product->id})");
            $this->newLine();

            $order = $this->stepCreateBooking($customer, $product);
            $this->stepPay($customer, $order);
            $order->refresh();

            $this->stepVendorAccept($vendor, $order);
            $order->refresh();

            $this->stepVendorStatus($vendor, $order, 'in_progress');
            $order->refresh();

            $this->stepVendorStatus($vendor, $order, 'in_transit');
            $order->refresh();

            $this->assert($order->delivery_otp !== null, 'Delivery OTP generated when vendor dispatches');
            $this->assert(filled($order->pickup_address), 'Pickup address set on dispatch');

            $this->stepCustomerSeesOtp($customer, $order);

            $this->stepDriverAccept($driver, $order);
            $order->refresh();

            $this->stepDriverPickup($driver, $order);
            $this->stepDriverOutForDelivery($driver, $order);

            $otp = (string) $order->fresh()->delivery_otp;
            $this->stepDriverDeliver($driver, $order, $otp);
            $order->refresh();

            $this->assert($order->status === 'delivered', 'Order marked delivered');
            $this->assert($order->driver_id === $driver->id, 'Driver assigned on completed order');
            $this->assert((float) $driver->fresh()->wallet_balance > 0, 'Driver wallet credited');

            $this->stepCustomerReview($customer, $order);

            $this->newLine();
            $this->info('--- Step log ---');
            foreach ($this->log as $line) {
                $this->line("  ✓ {$line}");
            }

            $this->newLine();
            $this->info('All flow checks passed.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Flow failed: '.$e->getMessage());
            foreach ($this->errors as $error) {
                $this->line("  ✗ {$error}");
            }

            return self::FAILURE;
        }
    }

    protected function resolveCustomer(): Customer
    {
        $customer = Customer::query()
            ->where('is_guest', false)
            ->whereNotNull('mobile')
            ->orderBy('id')
            ->first();

        if (! $customer) {
            throw new \RuntimeException('No registered customer found. Run seeders or register via v1 API.');
        }

        return $customer;
    }

    protected function resolveVendor(): Vendor
    {
        $vendor = Vendor::query()
            ->where('status', 'active')
            ->where('is_listing_active', true)
            ->orderBy('id')
            ->first();

        if (! $vendor) {
            throw new \RuntimeException('No active vendor with listing enabled. Approve a vendor in admin.');
        }

        return $vendor;
    }

    protected function resolveDriver(): Driver
    {
        $driver = Driver::query()->where('status', 'active')->orderBy('id')->first();

        if (! $driver) {
            $driver = Driver::query()->where('status', 'pending')->orderBy('id')->first();

            if ($driver) {
                $driver->update(['status' => 'active', 'approved_at' => now(), 'is_verified' => true]);
                $this->warn("Activated pending driver #{$driver->id} for testing.");
            }
        }

        if (! $driver) {
            $driver = Driver::query()->firstOrCreate(
                ['mobile' => '9898989898'],
                [
                    'driver_code' => CodeGenerator::driverCode(),
                    'name' => 'Flow Test Driver',
                    'email' => 'flow-driver@test.local',
                    'city' => 'Mumbai',
                    'vehicle_no' => 'MH01TEST01',
                    'status' => 'active',
                    'is_verified' => true,
                    'approved_at' => now(),
                    'registered_at' => now(),
                ]
            );

            if ($driver->status !== 'active') {
                $driver->update(['status' => 'active', 'approved_at' => now(), 'is_verified' => true]);
            }

            $this->warn("Using test driver #{$driver->id} for E2E flow.");
        }

        return $driver->fresh();
    }

    protected function resolveProduct(): PortfolioItem
    {
        $product = PortfolioItem::query()
            ->with('vendor')
            ->where('status', 'approved')
            ->whereHas('vendor', fn ($q) => $q->where('status', 'active')->where('is_listing_active', true))
            ->orderBy('id')
            ->first();

        if (! $product) {
            throw new \RuntimeException('No approved catalog product found for an active vendor.');
        }

        return $product;
    }

    protected function stepCreateBooking(Customer $customer, PortfolioItem $product): Order
    {
        $request = Request::create('/api/v1/bookings', 'POST', [
            'portfolio_item_id' => $product->id,
            'delivery_address' => '4517 Washington Ave, Manchester, Test City',
            'billing_address' => '4517 Washington Ave, Manchester, Test City',
            'city' => 'Mumbai',
            'pincode' => '400001',
            'size' => 'M',
            'shipment_required' => true,
            'customer_notes' => 'E2E flow test booking',
        ]);
        $request->setUserResolver(fn () => $customer);

        $response = app(CustomerBookingController::class)->store($request);
        $payload = $response->getData(true);

        $this->assert($response->getStatusCode() === 201, 'Booking created (HTTP 201)');
        $this->assert(($payload['success'] ?? false) === true, 'Booking success flag');

        $orderId = $payload['data']['booking']['id'] ?? null;
        $this->assert($orderId !== null, 'Booking ID returned');

        $order = Order::query()->findOrFail($orderId);
        $this->log[] = "Customer created booking {$order->order_number} (status: {$order->status})";

        return $order;
    }

    protected function stepPay(Customer $customer, Order $order): void
    {
        $request = Request::create("/api/v1/payment/bookings/{$order->id}/pay", 'POST', [
            'payment_method' => 'cod',
        ]);
        $request->setUserResolver(fn () => $customer);

        $response = app(CustomerPaymentController::class)->pay($request, $order);
        $payload = $response->getData(true);

        $this->assert($response->getStatusCode() === 200, 'Payment succeeded');
        $this->assert(($payload['data']['payment']['status'] ?? '') === 'success', 'Payment status success');

        $order->refresh();
        $this->assert($order->payment_status === 'success', 'Order payment_status is success');
        $this->assert($order->status === 'pending_acceptance', 'Order moves to pending_acceptance after payment');
        $this->log[] = "Customer paid booking (status: {$order->status})";
    }

    protected function stepVendorAccept(Vendor $vendor, Order $order): void
    {
        $this->assert($order->vendor_id === $vendor->id, 'Booking belongs to test vendor');

        $request = Request::create("/api/v2/bookings/{$order->id}/accept", 'POST');
        $request->setUserResolver(fn () => $vendor);

        $response = app(VendorBookingController::class)->accept($request, $order);
        $payload = $response->getData(true);

        $this->assert($response->getStatusCode() === 200, 'Vendor accept succeeded');
        $this->assert(($payload['data']['booking']['status'] ?? '') === 'accepted', 'Vendor accepted booking');

        $this->log[] = 'Vendor accepted booking';
    }

    protected function stepVendorStatus(Vendor $vendor, Order $order, string $status): void
    {
        $request = Request::create("/api/v2/bookings/{$order->id}/status", 'POST', [
            'status' => $status,
        ]);
        $request->setUserResolver(fn () => $vendor);

        $response = app(VendorBookingController::class)->updateStatus($request, $order->fresh());
        $payload = $response->getData(true);

        $this->assert($response->getStatusCode() === 200, "Vendor status → {$status}");
        $this->assert(($payload['data']['booking']['status'] ?? '') === $status, "Booking status is {$status}");

        $this->log[] = "Vendor updated status to {$status}";
    }

    protected function stepCustomerSeesOtp(Customer $customer, Order $order): void
    {
        $detail = CustomerApiPresenter::bookingDetail($order->fresh(['vendor', 'category', 'review']));
        $this->assert(($detail['delivery_otp'] ?? null) !== null, 'Customer API returns delivery_otp in transit');
        $this->log[] = "Customer sees delivery OTP: {$detail['delivery_otp']}";
    }

    protected function stepDriverAccept(Driver $driver, Order $order): void
    {
        $request = Request::create("/api/v3/deliveries/{$order->id}/accept", 'POST');
        $request->setUserResolver(fn () => $driver);

        $response = app(DriverDeliveryController::class)->accept($request, $order->fresh());
        $payload = $response->getData(true);

        $this->assert($response->getStatusCode() === 200, 'Driver accept succeeded');
        $this->assert(($payload['data']['delivery']['driver_delivery_status'] ?? '') === 'accepted', 'Driver delivery accepted');

        $this->log[] = 'Driver accepted delivery';
    }

    protected function stepDriverPickup(Driver $driver, Order $order): void
    {
        $request = Request::create("/api/v3/deliveries/{$order->id}/pickup", 'POST');
        $request->setUserResolver(fn () => $driver);

        $response = app(DriverDeliveryController::class)->pickup($request, $order->fresh());
        $this->assert($response->getStatusCode() === 200, 'Driver pickup succeeded');
        $this->log[] = 'Driver marked pickup';
    }

    protected function stepDriverOutForDelivery(Driver $driver, Order $order): void
    {
        $request = Request::create("/api/v3/deliveries/{$order->id}/out-for-delivery", 'POST');
        $request->setUserResolver(fn () => $driver);

        $response = app(DriverDeliveryController::class)->outForDelivery($request, $order->fresh());
        $this->assert($response->getStatusCode() === 200, 'Driver out-for-delivery succeeded');
        $this->log[] = 'Driver out for delivery';
    }

    protected function stepDriverDeliver(Driver $driver, Order $order, string $otp): void
    {
        $request = Request::create("/api/v3/deliveries/{$order->id}/deliver", 'POST', [
            'delivery_otp' => $otp,
        ]);
        $request->setUserResolver(fn () => $driver);

        $response = app(DriverDeliveryController::class)->deliver($request, $order->fresh());
        $payload = $response->getData(true);

        $this->assert($response->getStatusCode() === 200, 'Driver deliver succeeded');
        $this->assert(($payload['data']['delivery']['status'] ?? '') === 'delivered', 'Delivery completed');

        $this->log[] = 'Driver completed delivery with OTP';
    }

    protected function stepCustomerReview(Customer $customer, Order $order): void
    {
        $request = Request::create("/api/v1/bookings/{$order->id}/review", 'POST', [
            'rating' => 5,
            'comment' => 'Great service — E2E test',
        ]);
        $request->setUserResolver(fn () => $customer);

        $response = app(CustomerBookingController::class)->review($request, $order->fresh());
        $this->assert($response->getStatusCode() === 200, 'Customer review submitted');
        $this->log[] = 'Customer submitted review';
    }

    protected function assert(bool $condition, string $message): void
    {
        if (! $condition) {
            $this->errors[] = $message;
            throw new \RuntimeException($message);
        }
    }
}
