@php
    $prefix = $prefix ?? '';
    $values = $values ?? [];
    $value = fn (string $field) => old($prefix.$field, $values[$field] ?? '');
@endphp

<p class="jb-form-section-title sm:col-span-2">Address</p>
@include('admin.partials.form-input', ['label' => 'Address', 'name' => $prefix.'address', 'value' => $value('address'), 'full' => true])
@include('admin.partials.form-input', ['label' => 'Country', 'name' => $prefix.'country', 'value' => $value('country')])
@include('admin.partials.form-input', ['label' => 'State', 'name' => $prefix.'state', 'value' => $value('state')])
@include('admin.partials.form-input', ['label' => 'City', 'name' => $prefix.'city', 'value' => $value('city')])
@include('admin.partials.form-input', ['label' => 'Pincode', 'name' => $prefix.'pincode', 'value' => $value('pincode')])
