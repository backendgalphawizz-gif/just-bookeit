@php
    $order = $order ?? null;
    $drivers = $drivers ?? collect();
    $isRentalOrder = old('order_type', $order?->order_type ?? 'rental') !== 'sale';
@endphp

<p class="jb-form-section-title sm:col-span-2">Order type & service</p>
<x-admin.form-select label="Order type" name="order_type" :required="true">
    <option value="rental" @selected(old('order_type', $order?->order_type ?? 'rental') === 'rental')>Rental (outfit on rent)</option>
    <option value="sale" @selected(old('order_type', $order?->order_type) === 'sale')>Sale (purchase)</option>
</x-admin.form-select>
<x-admin.form-select label="Customer" name="customer_id" :required="true">
    @foreach ($customers as $c)
        <option value="{{ $c->id }}" @selected(old('customer_id', $order?->customer_id) == $c->id)>{{ $c->name }} ({{ $c->customer_code }})</option>
    @endforeach
</x-admin.form-select>
<x-admin.form-select label="Vendor / boutique" name="vendor_id">
    <option value="">Unassigned</option>
    @foreach ($vendors as $v)
        <option value="{{ $v->id }}" @selected(old('vendor_id', $order?->vendor_id) == $v->id)>{{ $v->brand_name }}</option>
    @endforeach
</x-admin.form-select>
<x-admin.form-select label="Driver (delivery)" name="driver_id">
    <option value="">Unassigned</option>
    @foreach ($drivers as $d)
        <option value="{{ $d->id }}" @selected(old('driver_id', $order?->driver_id) == $d->id)>{{ $d->name }} ({{ $d->driver_code }})</option>
    @endforeach
</x-admin.form-select>
<x-admin.form-select label="Category" name="category_id" :required="true">
    @foreach ($categories as $cat)
        <option value="{{ $cat->id }}" @selected(old('category_id', $order?->category_id) == $cat->id)>{{ $cat->name }}</option>
    @endforeach
</x-admin.form-select>

<p class="jb-form-section-title sm:col-span-2">Outfit / item details</p>
@include('admin.partials.form-input', ['label' => 'Item name', 'name' => 'item_title', 'value' => old('item_title', $order?->item_title), 'placeholder' => 'e.g. Bridal lehenga — red'])
<div class="sm:col-span-2">
    <label for="item_description" class="jb-label">Description</label>
    <textarea id="item_description" name="item_description" rows="3" class="jb-input">{{ old('item_description', $order?->item_description) }}</textarea>
</div>
@include('admin.partials.form-input', ['label' => 'Size', 'name' => 'size', 'value' => old('size', $order?->size), 'placeholder' => 'M, L, 38, Free size'])
@include('admin.partials.form-input', ['label' => 'Color', 'name' => 'color', 'value' => old('color', $order?->color)])
@include('admin.partials.form-input', ['label' => 'Quantity', 'name' => 'quantity', 'type' => 'number', 'min' => '1', 'value' => old('quantity', $order?->quantity ?? 1)])

<p class="jb-form-section-title sm:col-span-2" data-order-dates-title>{{ $isRentalOrder ? 'Dates (rental / event)' : 'Dates (event)' }}</p>
@include('admin.partials.form-input', ['label' => 'Event date', 'name' => 'event_date', 'type' => 'date', 'value' => old('event_date', $order?->event_date?->format('Y-m-d'))])
<div class="jb-rental-only-fields contents" @unless ($isRentalOrder) hidden @endunless>
    @include('admin.partials.form-input', ['label' => 'Rental start', 'name' => 'rental_start_date', 'type' => 'date', 'value' => old('rental_start_date', $order?->rental_start_date?->format('Y-m-d'))])
    @include('admin.partials.form-input', ['label' => 'Rental end', 'name' => 'rental_end_date', 'type' => 'date', 'value' => old('rental_end_date', $order?->rental_end_date?->format('Y-m-d'))])
    @include('admin.partials.form-input', ['label' => 'Return due date', 'name' => 'return_due_date', 'type' => 'date', 'value' => old('return_due_date', $order?->return_due_date?->format('Y-m-d'))])
</div>
<script>
    (function () {
        const orderType = document.getElementById('order_type');
        const rentalFields = document.querySelector('.jb-rental-only-fields');
        const datesTitle = document.querySelector('[data-order-dates-title]');
        if (!orderType || !rentalFields) return;

        const syncRentalFields = () => {
            const isRental = orderType.value !== 'sale';
            rentalFields.hidden = !isRental;
            if (datesTitle) {
                datesTitle.textContent = isRental ? 'Dates (rental / event)' : 'Dates (event)';
            }
        };

        orderType.addEventListener('change', syncRentalFields);
        syncRentalFields();
    })();
</script>

<p class="jb-form-section-title sm:col-span-2">Delivery & address</p>
<div class="sm:col-span-2">
    <label for="delivery_address" class="jb-label">Delivery address</label>
    <textarea id="delivery_address" name="delivery_address" rows="2" class="jb-input">{{ old('delivery_address', $order?->delivery_address) }}</textarea>
</div>
<div class="sm:col-span-2">
    <label for="billing_address" class="jb-label">Billing address</label>
    <textarea id="billing_address" name="billing_address" rows="2" class="jb-input">{{ old('billing_address', $order?->billing_address) }}</textarea>
</div>
<div class="sm:col-span-2">
    <label for="pickup_address" class="jb-label">Pickup / return address</label>
    <textarea id="pickup_address" name="pickup_address" rows="2" class="jb-input">{{ old('pickup_address', $order?->pickup_address) }}</textarea>
</div>
@include('admin.partials.form-input', ['label' => 'City', 'name' => 'city', 'value' => old('city', $order?->city)])
@include('admin.partials.form-input', ['label' => 'Pincode', 'name' => 'pincode', 'value' => old('pincode', $order?->pincode)])

<p class="jb-form-section-title sm:col-span-2">Pricing</p>
@include('admin.partials.form-input', ['label' => 'Outfit amount (₹)', 'name' => 'amount', 'type' => 'number', 'step' => '0.01', 'value' => old('amount', $order?->amount), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Security deposit (₹)', 'name' => 'security_deposit', 'type' => 'number', 'step' => '0.01', 'value' => old('security_deposit', $order?->security_deposit)])
@include('admin.partials.form-input', ['label' => 'Delivery fee (₹)', 'name' => 'delivery_fee', 'type' => 'number', 'step' => '0.01', 'value' => old('delivery_fee', $order?->delivery_fee)])
@include('admin.partials.form-input', ['label' => 'Tax / GST (₹)', 'name' => 'tax_amount', 'type' => 'number', 'step' => '0.01', 'value' => old('tax_amount', $order?->tax_amount)])

<p class="jb-form-section-title sm:col-span-2">Customer measurements</p>
@include('admin.partials.form-input', ['label' => 'Height (cm)', 'name' => 'measure_height_cm', 'type' => 'number', 'min' => '50', 'max' => '250', 'value' => old('measure_height_cm', $order?->measure_height_cm)])
@include('admin.partials.form-input', ['label' => 'Chest (cm)', 'name' => 'measure_chest_cm', 'type' => 'number', 'min' => '50', 'max' => '200', 'value' => old('measure_chest_cm', $order?->measure_chest_cm)])
@include('admin.partials.form-input', ['label' => 'Waist (cm)', 'name' => 'measure_waist_cm', 'type' => 'number', 'min' => '40', 'max' => '200', 'value' => old('measure_waist_cm', $order?->measure_waist_cm)])

<p class="jb-form-section-title sm:col-span-2">Damage & return</p>
@include('admin.partials.form-input', ['label' => 'Damage note', 'name' => 'damage_note', 'value' => old('damage_note', $order?->damage_note), 'placeholder' => 'e.g. Missing parts'])
@include('admin.partials.form-input', ['label' => 'Damage deduction (%)', 'name' => 'damage_deduct_percent', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'max' => '100', 'value' => old('damage_deduct_percent', $order?->damage_deduct_percent)])

<p class="jb-form-section-title sm:col-span-2">Images</p>
@include('admin.partials.profile-photo-upload', [
    'name' => 'item_image',
    'label' => 'Outfit / product photo',
    'currentUrl' => $order?->itemImageUrl(),
    'initials' => '👗',
])
<div class="sm:col-span-2">
    <label for="reference_images" class="jb-label">Reference images</label>
    <p class="text-xs text-slate-500 mb-2">Upload styling or accessory reference photos (multiple allowed)</p>
    <input type="file" id="reference_images" name="reference_images[]" accept="image/png,image/jpeg,image/jpg,image/webp" multiple class="jb-input">
    @if ($order && count($order->referenceImageUrls()) > 0)
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($order->referenceImageUrls() as $url)
                <img src="{{ $url }}" alt="" class="h-16 w-16 rounded-lg object-cover ring-1 ring-slate-200 panel-lightbox-trigger">
            @endforeach
        </div>
    @endif
    @error('reference_images')
        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
    @error('reference_images.*')
        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>

<p class="jb-form-section-title sm:col-span-2">Notes</p>
<div class="sm:col-span-2">
    <label for="customer_notes" class="jb-label">Customer notes</label>
    <textarea id="customer_notes" name="customer_notes" rows="2" class="jb-input">{{ old('customer_notes', $order?->customer_notes) }}</textarea>
</div>
<div class="sm:col-span-2">
    <label for="admin_notes" class="jb-label">Admin notes (internal)</label>
    <textarea id="admin_notes" name="admin_notes" rows="2" class="jb-input jb-textarea-break">{{ old('admin_notes', $order?->admin_notes) }}</textarea>
</div>

<p class="jb-form-section-title sm:col-span-2">Status</p>
<x-admin.form-select label="Payment status" name="payment_status" :required="true">
    @foreach (['pending','success','failed','refunded'] as $s)
        <option value="{{ $s }}" @selected(old('payment_status', $order?->payment_status ?? 'pending') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
</x-admin.form-select>
<x-admin.form-select label="Order status" name="status" :required="true">
    @foreach (['new','pending_acceptance','accepted','in_progress','in_transit','delivered','cancelled','refunded'] as $s)
        <option value="{{ $s }}" @selected(old('status', $order?->status ?? 'new') === $s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
    @endforeach
</x-admin.form-select>
