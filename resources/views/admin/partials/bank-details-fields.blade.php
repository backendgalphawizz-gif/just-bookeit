@php
    $prefix = $prefix ?? '';
    $values = $values ?? [];
    $value = fn (string $field) => old($prefix.$field, $values[$field] ?? '');
@endphp

<p class="jb-form-section-title sm:col-span-2">Bank details</p>
@include('admin.partials.form-input', [
    'label' => 'Account holder name',
    'name' => $prefix.'account_name',
    'value' => $value('account_name'),
    'restrict' => 'person-name',
    'hint' => 'Letters, spaces, dots, and hyphens only',
])
@include('admin.partials.form-input', [
    'label' => 'Account number',
    'name' => $prefix.'account_number',
    'value' => $value('account_number'),
    'restrict' => 'account-number',
    'hint' => 'Digits only, max 20',
])
@include('admin.partials.form-input', [
    'label' => 'IFSC code',
    'name' => $prefix.'ifsc_code',
    'value' => $value('ifsc_code'),
    'restrict' => 'ifsc',
    'hint' => '11-character IFSC (e.g. SBIN0001234)',
])
@include('admin.partials.form-input', [
    'label' => 'Bank name',
    'name' => $prefix.'bank_name',
    'value' => $value('bank_name'),
    'restrict' => 'title',
    'hint' => 'Letters, numbers, and common punctuation only',
])
<div class="jb-bank-account-type-field">
    <label for="{{ $prefix }}account_type" class="jb-label">Account type</label>
    <select id="{{ $prefix }}account_type" name="{{ $prefix }}account_type" class="jb-select" data-jb-bank-account-type>
        <option value="">Select type</option>
        @foreach (['savings' => 'Savings', 'current' => 'Current'] as $typeValue => $typeLabel)
            <option value="{{ $typeValue }}" @selected($value('account_type') === $typeValue)>{{ $typeLabel }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Select account type after entering vehicle number.</p>
    @error($prefix.'account_type')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>
