@php
    $order = $order ?? null;
    $drivers = $drivers ?? collect();
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

<p class="jb-form-section-title sm:col-span-2">Dates (rental / event)</p>
@include('admin.partials.form-input', ['label' => 'Event date', 'name' => 'event_date', 'type' => 'date', 'value' => old('event_date', $order?->event_date?->format('Y-m-d'))])
@include('admin.partials.form-input', ['label' => 'Rental start', 'name' => 'rental_start_date', 'type' => 'date', 'value' => old('rental_start_date', $order?->rental_start_date?->format('Y-m-d'))])
@include('admin.partials.form-input', ['label' => 'Rental end', 'name' => 'rental_end_date', 'type' => 'date', 'value' => old('rental_end_date', $order?->rental_end_date?->format('Y-m-d'))])
@include('admin.partials.form-input', ['label' => 'Return due date', 'name' => 'return_due_date', 'type' => 'date', 'value' => old('return_due_date', $order?->return_due_date?->format('Y-m-d'))])

<p class="jb-form-section-title sm:col-span-2">Delivery & address</p>
<div class="sm:col-span-2">
    <label for="delivery_address" class="jb-label">Delivery address</label>
    <textarea id="delivery_address" name="delivery_address" rows="2" class="jb-input">{{ old('delivery_address', $order?->delivery_address) }}</textarea>
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

<p class="jb-form-section-title sm:col-span-2">Notes</p>
<div class="sm:col-span-2">
    <label for="customer_notes" class="jb-label">Customer notes</label>
    <textarea id="customer_notes" name="customer_notes" rows="2" class="jb-input">{{ old('customer_notes', $order?->customer_notes) }}</textarea>
</div>
<div class="sm:col-span-2">
    <label for="admin_notes" class="jb-label">Admin notes (internal)</label>
    <textarea id="admin_notes" name="admin_notes" rows="2" class="jb-input">{{ old('admin_notes', $order?->admin_notes) }}</textarea>
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
