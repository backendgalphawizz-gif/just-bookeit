@php
    $damageRows = old('damage_deductions');
    if (! is_array($damageRows)) {
        $damageRows = $item->relationLoaded('damageDeductions') && $item->damageDeductions->isNotEmpty()
            ? $item->damageDeductions->map(fn ($r) => ['damage_type' => $r->damage_type, 'percent' => $r->percent])->all()
            : [];
    }
@endphp

<div class="vp-field vp-field--full vp-form-section" data-vp-damage>
    <div class="vp-form-section-head">
        <div>
            <label class="vp-label">Damage deduction rules</label>
            <p class="vp-field-hint">Optional — percent charged per damage type.</p>
        </div>
        <button type="button" class="vp-btn vp-btn--outline vp-btn--sm" data-vp-damage-add>+ Add rule</button>
    </div>

    <div style="display:flex;flex-direction:column;gap:.75rem;" data-vp-damage-list>
        @forelse ($damageRows as $index => $rule)
            <div class="vp-repeat-row" data-vp-damage-row>
                <div class="vp-repeat-row-grid vp-repeat-row-grid--damage">
                    <div>
                        <label class="vp-label">Damage type</label>
                        <input type="text" name="damage_deductions[{{ $index }}][damage_type]" value="{{ $rule['damage_type'] ?? '' }}" class="vp-input" placeholder="e.g. Tear, Stain">
                    </div>
                    <div>
                        <label class="vp-label">Deduction (%)</label>
                        <input type="number" name="damage_deductions[{{ $index }}][percent]" value="{{ $rule['percent'] ?? '' }}" class="vp-input" min="0" max="100" step="0.01" placeholder="0">
                    </div>
                    <div>
                        <button type="button" class="vp-btn vp-btn--ghost vp-btn--sm" style="color:#dc2626;" data-vp-damage-remove>Remove</button>
                    </div>
                </div>
            </div>
        @empty
            <p class="vp-field-hint" data-vp-damage-empty>No damage rules yet.</p>
        @endforelse
    </div>

    @error('damage_deductions')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('damage_deductions.*')<p class="vp-field-error">{{ $message }}</p>@enderror

    <template data-vp-damage-template>
        <div class="vp-repeat-row" data-vp-damage-row>
            <div class="vp-repeat-row-grid vp-repeat-row-grid--damage">
                <div><label class="vp-label">Damage type</label><input type="text" name="damage_deductions[__INDEX__][damage_type]" class="vp-input" placeholder="e.g. Tear, Stain"></div>
                <div><label class="vp-label">Deduction (%)</label><input type="number" name="damage_deductions[__INDEX__][percent]" class="vp-input" min="0" max="100" step="0.01" placeholder="0"></div>
                <div><button type="button" class="vp-btn vp-btn--ghost vp-btn--sm" style="color:#dc2626;" data-vp-damage-remove>Remove</button></div>
            </div>
        </div>
    </template>
</div>
