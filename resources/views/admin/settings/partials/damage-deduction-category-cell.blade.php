@php
    $fieldPrefix = $fieldPrefix ?? 'damage_deduction_rules';
    $rowIndex = $rowIndex ?? 0;
    $selectedCategoryId = (string) ($rule['category_id'] ?? '');
    $otherValue = \App\Support\DamageDeductionCategoryResolver::OTHER;
@endphp
<div class="space-y-2" data-damage-category-field>
    <select
        name="{{ $fieldPrefix }}[{{ $rowIndex }}][category_id]"
        class="jb-select jb-damage-rules-table__select"
        data-damage-category
        required
    >
        <option value="">Select category</option>
        @foreach ($mainCategories as $mainCategory)
            <option value="{{ $mainCategory->id }}" @selected($selectedCategoryId === (string) $mainCategory->id)>
                {{ $mainCategory->name }}
            </option>
        @endforeach
        <option value="{{ $otherValue }}" @selected($selectedCategoryId === $otherValue)>Other (add new)</option>
    </select>
    <input
        type="text"
        name="{{ $fieldPrefix }}[{{ $rowIndex }}][category_name]"
        value="{{ $rule['category_name'] ?? '' }}"
        class="jb-input jb-damage-rules-custom-input"
        data-damage-category-custom
        placeholder="New category name"
        maxlength="255"
        hidden
    >
</div>
