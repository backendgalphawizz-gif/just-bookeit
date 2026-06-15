@php
    $rowIndex = $rowIndex ?? 0;
    $rule = $rule ?? ['category_id' => '', 'subcategory_id' => '', 'service_category_id' => '', 'max_percent' => ''];
    $fieldPrefix = 'service_damage_deduction_rules';
    $selectedCategoryId = (string) ($rule['category_id'] ?? '');
    $selectedSubcategoryId = (string) ($rule['subcategory_id'] ?? '');
    $selectedServiceCategoryId = (string) ($rule['service_category_id'] ?? '');
    $otherValue = \App\Support\DamageDeductionCategoryResolver::OTHER;
    $scopeLabel = $selectedSubcategoryId !== ''
        ? ($subcategories->firstWhere('id', (int) $selectedSubcategoryId)?->name ?? ($rule['subcategory_name'] ?? 'Sub-category'))
        : ($selectedCategoryId !== '' ? 'All sub-categories' : 'All categories');
    $scopeClass = $selectedSubcategoryId !== ''
        ? 'jb-damage-rules-scope--sub'
        : ($selectedCategoryId !== '' ? 'jb-damage-rules-scope--all' : 'jb-damage-rules-scope--global');
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
    <td>
        <div class="space-y-2" data-damage-service-category-field>
            <select
                name="service_damage_deduction_rules[{{ $rowIndex }}][service_category_id]"
                class="jb-select jb-damage-rules-table__select"
                data-damage-service-category
                required
            >
                <option value="">Select service category</option>
                @foreach ($serviceCategories as $serviceCategory)
                    <option value="{{ $serviceCategory->id }}" @selected($selectedServiceCategoryId === (string) $serviceCategory->id)>
                        {{ $serviceCategory->name }}
                    </option>
                @endforeach
                <option value="{{ $otherValue }}" @selected($selectedServiceCategoryId === $otherValue)>Other (add new)</option>
            </select>
            <input
                type="text"
                name="service_damage_deduction_rules[{{ $rowIndex }}][service_category_name]"
                value="{{ $rule['service_category_name'] ?? '' }}"
                class="jb-input jb-damage-rules-custom-input"
                data-damage-service-category-custom
                placeholder="New service category name"
                maxlength="255"
                hidden
            >
        </div>
    </td>
    <td class="text-center">
        <input
            type="number"
            name="service_damage_deduction_rules[{{ $rowIndex }}][max_percent]"
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
