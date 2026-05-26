@php
    $startName = $startName ?? 'starts_at';
    $endName = $endName ?? 'ends_at';
    $startLabel = $startLabel ?? 'Start date';
    $endLabel = $endLabel ?? 'End date';
    $startValue = old($startName, $startValue ?? '');
    $endValue = old($endName, $endValue ?? '');
@endphp
<div
    class="{{ $class ?? 'sm:col-span-2 grid gap-4 sm:grid-cols-2' }}"
    x-data="{
        from: @js($startValue),
        to: @js($endValue),
        syncFrom(event) {
            this.from = event.target.value;
            if (this.to && this.from && this.to < this.from) {
                this.to = this.from;
                if (this.$refs.endInput) {
                    this.$refs.endInput.value = this.from;
                }
            }
        },
        syncTo(event) {
            this.to = event.target.value;
            if (this.from && this.to && this.to < this.from) {
                this.to = this.from;
                event.target.value = this.from;
            }
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
            :max="to || null"
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
            x-ref="endInput"
            :min="from || null"
            @change="syncTo"
        >
        @error($endName)
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
