@php
    $defaultDamageRules = [
        ['product_type' => 'Jewellery', 'max_percent' => '100'],
        ['product_type' => 'Cloth', 'max_percent' => '50'],
        ['product_type' => 'Other', 'max_percent' => '30'],
    ];
    $damageRules = old('damage_deduction_rules', $damageDeductionRules ?? $defaultDamageRules);
    if (! is_array($damageRules) || $damageRules === []) {
        $damageRules = $defaultDamageRules;
    }
@endphp

<div class="sm:col-span-2" data-damage-rules>
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <label class="jb-label">Max damage deduction by product type (%)</label>
            <p class="mt-1 text-sm text-slate-500">Set different limits for jewellery, cloth, and any other product types you add.</p>
        </div>
        <button type="button" class="jb-btn jb-btn-secondary jb-btn-sm" data-damage-rules-add>
            + Add product type
        </button>
    </div>

    <div class="space-y-3" data-damage-rules-list>
        @foreach ($damageRules as $index => $rule)
            <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_10rem_auto]" data-damage-rules-row>
                <div>
                    <label class="jb-label" for="damage_product_type_{{ $index }}">Product type</label>
                    <input
                        id="damage_product_type_{{ $index }}"
                        type="text"
                        name="damage_deduction_rules[{{ $index }}][product_type]"
                        value="{{ $rule['product_type'] ?? '' }}"
                        class="jb-input"
                        placeholder="e.g. Jewellery, Cloth, Accessories"
                        required
                    >
                </div>
                <div>
                    <label class="jb-label" for="damage_max_percent_{{ $index }}">Max deduction (%)</label>
                    <input
                        id="damage_max_percent_{{ $index }}"
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
                <div class="flex items-end">
                    <button type="button" class="jb-btn jb-btn-ghost jb-btn-sm text-rose-600" data-damage-rules-remove title="Remove row">
                        Remove
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @error('damage_deduction_rules')
        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
    @error('damage_deduction_rules.*')
        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror

    <template data-damage-rules-template>
        <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_10rem_auto]" data-damage-rules-row>
            <div>
                <label class="jb-label">Product type</label>
                <input type="text" name="damage_deduction_rules[__INDEX__][product_type]" class="jb-input" placeholder="e.g. Jewellery, Cloth, Accessories" required>
            </div>
            <div>
                <label class="jb-label">Max deduction (%)</label>
                <input type="number" name="damage_deduction_rules[__INDEX__][max_percent]" class="jb-input" min="0" max="100" step="0.01" required>
            </div>
            <div class="flex items-end">
                <button type="button" class="jb-btn jb-btn-ghost jb-btn-sm text-rose-600" data-damage-rules-remove title="Remove row">
                    Remove
                </button>
            </div>
        </div>
    </template>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-damage-rules]');
        if (!root) return;

        const list = root.querySelector('[data-damage-rules-list]');
        const template = root.querySelector('[data-damage-rules-template]');
        const addBtn = root.querySelector('[data-damage-rules-add]');

        const reindexRows = () => {
            list.querySelectorAll('[data-damage-rules-row]').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    input.name = input.name.replace(/damage_deduction_rules\[\d+]/, `damage_deduction_rules[${index}]`);
                });
            });
        };

        const bindRemove = (row) => {
            const btn = row.querySelector('[data-damage-rules-remove]');
            btn?.addEventListener('click', () => {
                if (list.querySelectorAll('[data-damage-rules-row]').length <= 1) {
                    return;
                }
                row.remove();
                reindexRows();
            });
        };

        list.querySelectorAll('[data-damage-rules-row]').forEach(bindRemove);

        addBtn?.addEventListener('click', () => {
            const index = list.querySelectorAll('[data-damage-rules-row]').length;
            const html = template.innerHTML.replaceAll('__INDEX__', String(index));
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;
            list.appendChild(row);
            bindRemove(row);
            row.querySelector('input')?.focus();
        });
    })();
</script>
