@php
    use App\Support\AdminValidationRules;

    $startName = $startName ?? 'starts_at';
    $endName = $endName ?? 'ends_at';
    $startLabel = $startLabel ?? 'Start date';
    $endLabel = $endLabel ?? 'End date';
    $startValue = old($startName, $startValue ?? '');
    $endValue = old($endName, $endValue ?? '');
    $minFilterDate = AdminValidationRules::MYSQL_MIN_TIMESTAMP_DATE;
    $maxFilterDate = $allowFuture ?? false ? null : AdminValidationRules::listDateMax();
@endphp
<div
    class="{{ $class ?? 'sm:col-span-2 grid gap-4 sm:grid-cols-2' }}"
    x-data="{
        minDate: @js($minFilterDate),
        maxDate: @js($maxFilterDate),
        from: @js($startValue),
        to: @js($endValue),
        maxForFrom() {
            if (! this.maxDate) {
                return this.to || null;
            }

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

            if (this.to && this.from && this.to < this.from && this.$refs.endInput) {
                this.to = this.from;
                this.$refs.endInput.value = this.from;
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
    <div>
        <label for="{{ $startName }}" class="jb-label">{{ $startLabel }}</label>
        <input
            type="date"
            id="{{ $startName }}"
            name="{{ $startName }}"
            value="{{ $startValue }}"
            class="jb-input"
            min="{{ $minFilterDate }}"
            @if ($maxFilterDate) max="{{ $maxFilterDate }}" @endif
            :max="maxForFrom()"
            @change="syncFrom"
        >
        @error($startName)
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="{{ $endName }}" class="jb-label">{{ $endLabel }}</label>
        <input
            type="date"
            id="{{ $endName }}"
            name="{{ $endName }}"
            value="{{ $endValue }}"
            class="jb-input"
            min="{{ $minFilterDate }}"
            @if ($maxFilterDate) max="{{ $maxFilterDate }}" @endif
            x-ref="endInput"
            :min="minForTo()"
            @change="syncTo"
        >
        @error($endName)
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
