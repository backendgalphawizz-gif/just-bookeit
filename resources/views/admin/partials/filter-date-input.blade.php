@php
    use App\Support\AdminValidationRules;

    $name = $name ?? 'date';
    $id = $id ?? 'filter-'.$name;
    $label = $label ?? 'Date';
    $value = $value ?? request($name);
    $minFilterDate = AdminValidationRules::MYSQL_MIN_TIMESTAMP_DATE;
    $maxFilterDate = AdminValidationRules::listDateMax();
@endphp
<div
    class="jb-filters-field jb-filters-field--date"
    x-data="jbAdminFilterSingleDate(@js($minFilterDate), @js($maxFilterDate))"
>
    <label class="jb-label" for="{{ $id }}">{{ $label }}</label>
    @include('admin.partials.filter-date-control', [
        'id' => $id,
        'name' => $name,
        'isoValue' => $value,
        'inputRef' => 'dateInput',
    ])
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
