@php
    use App\Support\AdminValidationRules;

    $minFilterDate = AdminValidationRules::MYSQL_MIN_TIMESTAMP_DATE;
    $maxFilterDate = AdminValidationRules::listDateMax();
@endphp
<div
    class="contents"
    x-data="{
        minDate: @js($minFilterDate),
        maxDate: @js($maxFilterDate),
        from: @js(request('from', '')),
        to: @js(request('to', '')),
        maxForFrom() {
            if (this.to && this.to < this.maxDate) {
                return this.to;
            }

            return this.maxDate;
        },
        minForTo() {
            if (this.from && this.from > this.minDate) {
                return this.from;
            }

            return this.minDate;
        },
        syncFrom(event) {
            this.from = event.target.value;

            if (this.to && this.from && this.to < this.from && this.$refs.toInput) {
                this.to = this.from;
                this.$refs.toInput.value = this.from;
            }
        },
        syncTo(event) {
            let value = event.target.value;

            if (this.from && value && value < this.from) {
                value = this.from;
            }

            if (value !== event.target.value) {
                event.target.value = value;
            }

            this.to = value;
        }
    }"
>
    <div class="jb-filters-field jb-filters-field--date">
        <label class="jb-label" for="filter-from">From</label>
        <input
            type="date"
            id="filter-from"
            name="from"
            value="{{ request('from') }}"
            class="jb-input"
            min="{{ $minFilterDate }}"
            max="{{ $maxFilterDate }}"
            x-ref="fromInput"
            :max="maxForFrom()"
            @change="syncFrom"
        >
        @error('from')
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
    <div class="jb-filters-field jb-filters-field--date">
        <label class="jb-label" for="filter-to">To</label>
        <input
            type="date"
            id="filter-to"
            name="to"
            value="{{ request('to') }}"
            class="jb-input"
            min="{{ $minFilterDate }}"
            max="{{ $maxFilterDate }}"
            x-ref="toInput"
            :min="minForTo()"
            @change="syncTo"
        >
        @error('to')
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
