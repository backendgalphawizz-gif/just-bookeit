<div
    class="vp-filters-date-group"
    x-data="{
        from: @js(request('from', '')),
        to: @js(request('to', '')),
        syncFrom(event) {
            this.from = event.target.value;
            if (this.to && this.from && this.to < this.from) {
                this.to = this.from;
                if (this.$refs.toInput) this.$refs.toInput.value = this.from;
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
    <div class="vp-filters-field vp-filters-field--date">
        <label class="vp-label" for="filter-from">From</label>
        <input type="date" id="filter-from" name="from" value="{{ request('from') }}" class="vp-input" x-ref="fromInput" :max="to || null" @change="syncFrom">
        @error('from')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="vp-filters-field vp-filters-field--date">
        <label class="vp-label" for="filter-to">To</label>
        <input type="date" id="filter-to" name="to" value="{{ request('to') }}" class="vp-input" x-ref="toInput" :min="from || null" @change="syncTo">
        @error('to')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
</div>
