@php
    use App\Support\AdminValidationRules;

    $minFilterDate = AdminValidationRules::listFilterMinDate();
    $maxFilterDate = AdminValidationRules::listDateMax();
@endphp
<div
    class="vp-filters-date-group"
    x-data="jbFilterDateRange(@js($minFilterDate), @js($maxFilterDate), @js(request('from', '')), @js(request('to', '')))"
>
    <div class="vp-filters-field vp-filters-field--date">
        <label class="vp-label" for="filter-from">From</label>
        @include('vendor.partials.filter-date-control', [
            'id' => 'filter-from',
            'name' => 'from',
            'isoValue' => request('from'),
            'inputRef' => 'fromInput',
            'minBind' => 'minDate',
            'maxBind' => 'maxForFrom()',
            'changeHandler' => 'syncFrom',
        ])
        @error('from')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="vp-filters-field vp-filters-field--date">
        <label class="vp-label" for="filter-to">To</label>
        @include('vendor.partials.filter-date-control', [
            'id' => 'filter-to',
            'name' => 'to',
            'isoValue' => request('to'),
            'inputRef' => 'toInput',
            'minBind' => 'minForTo()',
            'maxBind' => 'maxDate',
            'changeHandler' => 'syncTo',
        ])
        @error('to')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
</div>
