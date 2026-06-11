@php
    $damageRows = old('damage_deductions');
    if (! is_array($damageRows)) {
        $damageRows = $portfolio->relationLoaded('damageDeductions') && $portfolio->damageDeductions->isNotEmpty()
            ? $portfolio->damageDeductions->map(fn ($r) => [
                'damage_type' => $r->damage_type,
                'percent' => $r->percent,
            ])->all()
            : [];
    }
@endphp

<div class="sm:col-span-2" data-product-damage>
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <label class="jb-label">Damage deduction rules</label>
            <p class="mt-1 text-sm text-slate-500">Optional. Percent charged per damage type if the rented item is returned damaged.</p>
        </div>
        <button type="button" class="jb-btn jb-btn-secondary jb-btn-sm" data-product-damage-add>+ Add rule</button>
    </div>

    <div class="space-y-3" data-product-damage-list>
        @forelse ($damageRows as $index => $rule)
            <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_10rem_auto]" data-product-damage-row>
                <div>
                    <label class="jb-label">Damage type</label>
                    <input type="text" name="damage_deductions[{{ $index }}][damage_type]" value="{{ $rule['damage_type'] ?? '' }}" class="jb-input" placeholder="e.g. Tear, Stain">
                </div>
                <div>
                    <label class="jb-label">Deduction (%)</label>
                    <input type="number" name="damage_deductions[{{ $index }}][percent]" value="{{ $rule['percent'] ?? '' }}" class="jb-input" min="0" max="100" step="0.01" placeholder="0">
                </div>
                <div class="flex items-end">
                    <button type="button" class="jb-btn jb-btn-ghost jb-btn-sm text-rose-600" data-product-damage-remove>Remove</button>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500" data-product-damage-empty>No damage rules yet.</p>
        @endforelse
    </div>

    @error('damage_deductions')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @error('damage_deductions.*')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

    <template data-product-damage-template>
        <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_10rem_auto]" data-product-damage-row>
            <div>
                <label class="jb-label">Damage type</label>
                <input type="text" name="damage_deductions[__INDEX__][damage_type]" class="jb-input" placeholder="e.g. Tear, Stain">
            </div>
            <div>
                <label class="jb-label">Deduction (%)</label>
                <input type="number" name="damage_deductions[__INDEX__][percent]" class="jb-input" min="0" max="100" step="0.01" placeholder="0">
            </div>
            <div class="flex items-end">
                <button type="button" class="jb-btn jb-btn-ghost jb-btn-sm text-rose-600" data-product-damage-remove>Remove</button>
            </div>
        </div>
    </template>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-product-damage]');
        if (!root) return;

        const list = root.querySelector('[data-product-damage-list]');
        const template = root.querySelector('[data-product-damage-template]');
        const addBtn = root.querySelector('[data-product-damage-add]');
        const emptyNote = root.querySelector('[data-product-damage-empty]');

        const reindexRows = () => {
            list.querySelectorAll('[data-product-damage-row]').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    input.name = input.name.replace(/damage_deductions\[\d+]/, `damage_deductions[${index}]`);
                });
            });
        };

        const bindRemove = (row) => {
            row.querySelector('[data-product-damage-remove]')?.addEventListener('click', () => {
                row.remove();
                reindexRows();
                if (!list.querySelector('[data-product-damage-row]') && emptyNote) {
                    emptyNote.hidden = false;
                }
            });
        };

        list.querySelectorAll('[data-product-damage-row]').forEach(bindRemove);

        addBtn?.addEventListener('click', () => {
            if (emptyNote) emptyNote.hidden = true;
            const index = list.querySelectorAll('[data-product-damage-row]').length;
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
