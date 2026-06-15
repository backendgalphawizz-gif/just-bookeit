@php
    $rows = old('damage_deduction_rules');

    if (is_array($rows) && isset($rows[0]['service_category_id'])) {
        $names = collect($damageDeductionRules ?? [])->keyBy('service_category_id');
        $rows = collect($rows)->map(fn (array $rule) => [
            'service_category_id' => $rule['service_category_id'],
            'service_category_name' => $names->get($rule['service_category_id'])['service_category_name'] ?? '',
            'max_percent' => $rule['max_percent'] ?? '',
        ])->all();
    } else {
        $rows = $damageDeductionRules ?? [];
    }
@endphp

<div class="sm:col-span-2" data-damage-rules>
    <div class="mb-3">
        <label class="jb-label">Max damage deduction by service category (%)</label>
        <p class="mt-1 text-sm text-slate-500">
            Set the maximum total damage deduction vendors can apply per product for each service category
            (Fashion Designer, Rented Dress, Rented Jewellery).
        </p>
    </div>

    @if ($rows === [])
        <p class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-500">
            Add service categories first under Categories → Service categories.
        </p>
    @else
        <div class="space-y-3" data-damage-rules-list>
            @foreach ($rows as $index => $rule)
                <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_10rem]" data-damage-rules-row>
                    <div>
                        <label class="jb-label" for="damage_service_category_{{ $rule['service_category_id'] }}">Service category</label>
                        <input
                            id="damage_service_category_{{ $rule['service_category_id'] }}"
                            type="text"
                            value="{{ $rule['service_category_name'] ?? '' }}"
                            class="jb-input bg-slate-50"
                            readonly
                        >
                        <input type="hidden" name="damage_deduction_rules[{{ $index }}][service_category_id]" value="{{ $rule['service_category_id'] }}">
                    </div>
                    <div>
                        <label class="jb-label" for="damage_max_percent_{{ $rule['service_category_id'] }}">Max deduction (%)</label>
                        <input
                            id="damage_max_percent_{{ $rule['service_category_id'] }}"
                            type="number"
                            name="damage_deduction_rules[{{ $index }}][max_percent]"
                            value="{{ $rule['max_percent'] ?? '' }}"
                            class="jb-input"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                        >
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @error('damage_deduction_rules')
        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
    @error('damage_deduction_rules.*')
        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
