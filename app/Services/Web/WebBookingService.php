<?php

namespace App\Services\Web;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Services\Booking\BookingPricingService;
use App\Support\CodeGenerator;
use App\Support\OrderDispatchSupport;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WebBookingService
{
    /** @return array{order: Order, pricing: array<string, mixed>} */
    public function createFromRequest(Customer $customer, PortfolioItem $item, array $data, ?Request $request = null): array
    {
        abort_unless($item->isCatalogAvailable(), 404);

        $rentalDays = BookingPricingService::rentalDays(
            $data['rental_start_date'] ?? null,
            $data['rental_end_date'] ?? null,
        );

        $pricing = BookingPricingService::forPortfolioItem($item, [
            'shipment_required' => (bool) ($data['shipment_required'] ?? true),
            'rental_days' => $rentalDays,
        ]);

        $notes = trim((string) ($data['customer_notes'] ?? ''));

        $order = Order::query()->create([
            'order_number' => CodeGenerator::orderNumber(),
            'customer_id' => $customer->id,
            'vendor_id' => $item->vendor_id,
            'category_id' => $item->category_id,
            'portfolio_item_id' => $item->id,
            'subcategory_id' => $item->subcategory_id,
            'order_type' => 'rental',
            'item_title' => $item->title,
            'item_description' => $item->description,
            'item_image_path' => $item->image_url,
            'size' => $data['size'] ?? null,
            'quantity' => 1,
            'rental_start_date' => $data['rental_start_date'] ?? null,
            'rental_end_date' => $data['rental_end_date'] ?? null,
            'delivery_address' => $data['delivery_address'],
            'billing_address' => $data['billing_address'] ?? $data['delivery_address'],
            'city' => $data['city'] ?? $customer->city,
            'pincode' => $data['pincode'] ?? null,
            'amount' => $pricing['subtotal'],
            'delivery_fee' => $pricing['shipping_fee'],
            'tax_amount' => $pricing['tax_amount'],
            'customer_notes' => $notes !== '' ? $notes : null,
            'measure_height_cm' => $data['measure_height_cm'] ?? null,
            'measure_chest_cm' => $data['measure_chest_cm'] ?? null,
            'measure_waist_cm' => $data['measure_waist_cm'] ?? null,
            'payment_status' => 'pending',
            'status' => 'new',
        ]);

        OrderDispatchSupport::preparePickupAddress($order);
        if (filled($order->pickup_address)) {
            $order->saveQuietly();
        }

        if ($request?->hasFile('reference_images')) {
            $paths = [];
            foreach ($request->file('reference_images') as $file) {
                $paths[] = StoresUploadedFiles::store($file, 'orders/reference-images');
            }
            $order->update(['reference_image_paths' => $paths]);
        }

        if ($customer->city === null && ! empty($data['city'])) {
            $customer->update(['city' => $data['city']]);
        }

        $count = Order::query()->where('customer_id', $customer->id)->count();
        $customer->update(['total_orders' => $count]);

        $order->load(['vendor', 'category', 'subcategory', 'customer']);

        return [
            'order' => $order,
            'pricing' => BookingPricingService::fromOrder($order),
        ];
    }

    public function assertCanBook(Customer $customer): void
    {
        if ($customer->is_guest) {
            throw ValidationException::withMessages([
                'booking' => 'Create a full account to place a booking. Guest browsing is view-only.',
            ]);
        }
    }
}
