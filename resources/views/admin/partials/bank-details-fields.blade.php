@php
    $prefix = $prefix ?? '';
    $values = $values ?? [];
    $value = fn (string $field) => old($prefix.$field, $values[$field] ?? '');
@endphp

<p class="jb-form-section-title sm:col-span-2">Bank details</p>
@include('admin.partials.form-input', ['label' => 'Account holder name', 'name' => $prefix.'account_name', 'value' => $value('account_name')])
@include('admin.partials.form-input', ['label' => 'Account number', 'name' => $prefix.'account_number', 'value' => $value('account_number')])
@include('admin.partials.form-input', ['label' => 'IFSC code', 'name' => $prefix.'ifsc_code', 'value' => $value('ifsc_code')])
@include('admin.partials.form-input', ['label' => 'Bank name', 'name' => $prefix.'bank_name', 'value' => $value('bank_name')])
<div>
    <label for="{{ $prefix }}account_type" class="jb-label">Account type</label>
    <select id="{{ $prefix }}account_type" name="{{ $prefix }}account_type" class="jb-select">
        <option value="">Select type</option>
        @foreach (['savings' => 'Savings', 'current' => 'Current'] as $typeValue => $typeLabel)
            <option value="{{ $typeValue }}" @selected($value('account_type') === $typeValue)>{{ $typeLabel }}</option>
        @endforeach
    </select>
    @error($prefix.'account_type')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>
