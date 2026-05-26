@php $refund = $refund ?? null; @endphp
@if ($refund === null)
    <x-admin.form-select label="Order" name="order_id" :required="true" class="sm:col-span-2">
        @foreach ($orders as $o)
            <option value="{{ $o->id }}" @selected(old('order_id') == $o->id)>{{ $o->order_number }} — {{ $o->customer->name }}</option>
        @endforeach
    </x-admin.form-select>
    <x-admin.form-select label="Customer" name="customer_id" :required="true">
        @foreach ($customers as $c)
            <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }}</option>
        @endforeach
    </x-admin.form-select>
@endif
@include('admin.partials.form-input', ['label' => 'Amount (₹)', 'name' => 'amount', 'type' => 'number', 'step' => '0.01', 'value' => old('amount', $refund?->amount), 'required' => true])
<x-admin.form-select label="Status" name="status" :required="true">
    @foreach (['requested','under_review','approved','rejected','processed'] as $s)
        <option value="{{ $s }}" @selected(old('status', $refund?->status ?? 'requested') === $s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
    @endforeach
</x-admin.form-select>
@include('admin.partials.form-input', ['label' => 'Reason', 'name' => 'reason', 'type' => 'textarea', 'value' => old('reason', $refund?->reason), 'full' => true])
