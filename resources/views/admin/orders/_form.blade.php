@php $order = $order ?? null; @endphp
<x-admin.form-select label="Customer" name="customer_id" :required="true">
    @foreach ($customers as $c)
        <option value="{{ $c->id }}" @selected(old('customer_id', $order?->customer_id) == $c->id)>{{ $c->name }} ({{ $c->customer_code }})</option>
    @endforeach
</x-admin.form-select>
<x-admin.form-select label="Vendor" name="vendor_id">
    <option value="">Unassigned</option>
    @foreach ($vendors as $v)
        <option value="{{ $v->id }}" @selected(old('vendor_id', $order?->vendor_id) == $v->id)>{{ $v->brand_name }}</option>
    @endforeach
</x-admin.form-select>
<x-admin.form-select label="Category" name="category_id" :required="true">
    @foreach ($categories as $cat)
        <option value="{{ $cat->id }}" @selected(old('category_id', $order?->category_id) == $cat->id)>{{ $cat->name }}</option>
    @endforeach
</x-admin.form-select>
@include('admin.partials.form-input', ['label' => 'Amount (₹)', 'name' => 'amount', 'type' => 'number', 'step' => '0.01', 'value' => old('amount', $order?->amount), 'required' => true])
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
