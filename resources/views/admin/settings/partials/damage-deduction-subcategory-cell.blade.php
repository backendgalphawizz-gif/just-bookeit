@php
    $fieldPrefix = $fieldPrefix ?? 'damage_deduction_rules';
    $rowIndex = $rowIndex ?? 0;
    $selectedCategoryId = (string) ($rule['category_id'] ?? '');
    $selectedSubcategoryId = (string) ($rule['subcategory_id'] ?? '');
    $otherValue = \App\Support\DamageDeductionCategoryResolver::OTHER;
@endphp
<div class="space-y-2" data-damage-subcategory-field>
    <select
        name="{{ $fieldPrefix }}[{{ $rowIndex }}][subcategory_id]"
        class="jb-select jb-damage-rules-table__select"
        data-damage-subcategory
        @disabled($selectedCategoryId === '')
    >
        <option value="">All sub-categories</option>
        @foreach ($subcategories as $subcategory)
            <option
                value="{{ $subcategory->id }}"
                data-parent-id="{{ $subcategory->parent_id }}"
                @selected($selectedSubcategoryId === (string) $subcategory->id)
            >
                {{ $subcategory->name }}
            </option>
        @endforeach
        <option value="{{ $otherValue }}" @selected($selectedSubcategoryId === $otherValue)>Other (add new)</option>
    </select>
    <input
        type="text"
        name="{{ $fieldPrefix }}[{{ $rowIndex }}][subcategory_name]"
        value="{{ $rule['subcategory_name'] ?? '' }}"
        class="jb-input jb-damage-rules-custom-input"
        data-damage-subcategory-custom
        placeholder="New sub-category name"
        maxlength="255"
        hidden
    >
</div>
