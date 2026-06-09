@php $dispute = $dispute ?? null; @endphp
@if (!isset($dispute) || $dispute === null)
    <x-admin.form-select label="Order" name="order_id" :required="true" class="sm:col-span-2">
        @foreach ($orders as $o)
            <option value="{{ $o->id }}" @selected(old('order_id') == $o->id)>
                {{ $o->order_number }} — {{ $o->customer->name }}@if ($o->category) ({{ $o->category->name }})@endif
            </option>
        @endforeach
    </x-admin.form-select>
@endif
<x-admin.form-select label="Raised by" name="raised_by" :required="true">
    <option value="customer" @selected(old('raised_by', $dispute?->raised_by) === 'customer')>Customer</option>
    <option value="vendor" @selected(old('raised_by', $dispute?->raised_by) === 'vendor')>Vendor</option>
</x-admin.form-select>
<x-admin.form-select label="Status" name="status" :required="true">
    @foreach (['raised','under_review','resolved','closed'] as $s)
        <option value="{{ $s }}" @selected(old('status', $dispute?->status ?? 'raised') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
    @endforeach
</x-admin.form-select>
@include('admin.partials.form-input', ['label' => 'Subject', 'name' => 'subject', 'value' => old('subject', $dispute?->subject), 'required' => true, 'full' => true])
