@php
    use App\Support\AdminValidationRules;

    $minFilterDate = AdminValidationRules::listFilterMinDate();
    $maxFilterDate = AdminValidationRules::listDateMax();
@endphp
<div
    class="contents"
    x-data="jbAdminFilterDateRange(@js($minFilterDate), @js($maxFilterDate), @js(request('from', '')), @js(request('to', '')))"
>
    <div class="jb-filters-field jb-filters-field--date">
        <label class="jb-label" for="filter-from">From</label>
        @include('admin.partials.filter-date-control', [
            'id' => 'filter-from',
            'name' => 'from',
            'isoValue' => request('from'),
            'inputRef' => 'fromInput',
            'minBind' => 'minDate',
            'maxBind' => 'maxForFrom()',
            'changeHandler' => 'syncFrom',
        ])
        @error('from')
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
    <div class="jb-filters-field jb-filters-field--date">
        <label class="jb-label" for="filter-to">To</label>
        @include('admin.partials.filter-date-control', [
            'id' => 'filter-to',
            'name' => 'to',
            'isoValue' => request('to'),
            'inputRef' => 'toInput',
            'minBind' => 'minForTo()',
            'maxBind' => 'maxDate',
            'changeHandler' => 'syncTo',
        ])
        @error('to')
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
