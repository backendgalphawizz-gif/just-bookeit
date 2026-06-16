@php
    $rowIndex = $rowIndex ?? 0;
    $rule = $rule ?? ['category_id' => '', 'subcategory_id' => '', 'max_percent' => ''];
    $fieldPrefix = 'damage_deduction_rules';
    $selectedSubcategoryId = (string) ($rule['subcategory_id'] ?? '');
    $scopeLabel = $selectedSubcategoryId !== ''
        ? ($subcategories->firstWhere('id', (int) $selectedSubcategoryId)?->name ?? ($rule['subcategory_name'] ?? 'Sub-category'))
        : 'All sub-categories';
    $scopeClass = $selectedSubcategoryId !== '' ? 'jb-damage-rules-scope--sub' : 'jb-damage-rules-scope--all';
    $displayRowNumber = is_numeric($rowIndex) ? ((int) $rowIndex + 1) : '';
@endphp
<tr data-damage-settings-row>
    <td class="jb-col-sn text-slate-500" data-damage-row-number>{{ $displayRowNumber }}</td>
    <td>
        @include('admin.settings.partials.damage-deduction-category-cell', [
            'fieldPrefix' => $fieldPrefix,
            'rowIndex' => $rowIndex,
            'rule' => $rule,
        ])
    </td>
    <td>
        @include('admin.settings.partials.damage-deduction-subcategory-cell', [
            'fieldPrefix' => $fieldPrefix,
            'rowIndex' => $rowIndex,
            'rule' => $rule,
        ])
    </td>
    <td>
        <div class="space-y-1">
            <span class="jb-damage-rules-scope {{ $scopeClass }}" data-damage-scope>{{ $scopeLabel }}</span>
            <p class="jb-damage-rules-scope-hint text-xs leading-snug text-slate-500" data-damage-scope-hint hidden></p>
        </div>
    </td>
    <td class="text-center">
        <input
            type="number"
            name="damage_deduction_rules[{{ $rowIndex }}][max_percent]"
            value="{{ $rule['max_percent'] ?? '' }}"
            class="jb-input jb-damage-rules-table__percent"
            min="0"
            max="100"
            step="0.01"
            placeholder="0"
            required
        >
    </td>
    <td class="jb-table-actions-col">
        <div class="jb-actions">
            <button type="button" class="jb-btn jb-btn-ghost jb-btn-sm text-rose-600" data-damage-settings-remove>
                Remove
            </button>
        </div>
    </td>
</tr>
