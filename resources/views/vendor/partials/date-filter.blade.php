@php
    $minFilterDate = \App\Support\AdminValidationRules::MYSQL_MIN_TIMESTAMP_DATE;
@endphp
<div
    class="vp-filters-date-group"
    x-data="jbDateRangeFilter({
        minDate: @js($minFilterDate),
        from: @js(request('from', '')),
        to: @js(request('to', '')),
    })"
>
    <div class="vp-filters-field vp-filters-field--date">
        <label class="vp-label" for="filter-from">From</label>
        <input
            type="text"
            id="filter-from"
            name="from"
            value="{{ request('from') }}"
            class="vp-input"
            placeholder="YYYY-MM-DD"
            inputmode="numeric"
            maxlength="10"
            autocomplete="off"
            spellcheck="false"
            x-ref="fromInput"
            @input="formatDateInput"
            @blur="normalizeFrom"
            @change="normalizeFrom"
        >
        @error('from')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="vp-filters-field vp-filters-field--date">
        <label class="vp-label" for="filter-to">To</label>
        <input
            type="text"
            id="filter-to"
            name="to"
            value="{{ request('to') }}"
            class="vp-input"
            placeholder="YYYY-MM-DD"
            inputmode="numeric"
            maxlength="10"
            autocomplete="off"
            spellcheck="false"
            x-ref="toInput"
            @input="formatDateInput"
            @blur="normalizeTo"
            @change="normalizeTo"
        >
        @error('to')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
</div>
