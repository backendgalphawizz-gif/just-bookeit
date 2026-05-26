@php
    use App\Support\AdminValidationRules;

    $inputType = $type ?? 'text';
    $isNumber = $inputType === 'number';
    $restrict = $restrict ?? AdminValidationRules::defaultRestrict($name, $inputType);
    $useNonNegative = $nonNegative ?? ($restrict === 'decimal' || $restrict === 'integer' || $isNumber);
    $minValue = $min ?? ($useNonNegative ? '0' : null);
    $maxValue = $max ?? null;

    if ($restrict === 'decimal' || $restrict === 'integer') {
        $inputType = 'text';
    }

    $inputMode = match ($restrict) {
        'phone', 'integer' => 'numeric',
        'decimal' => 'decimal',
        'email' => 'email',
        'url' => 'url',
        default => null,
    };

    $autocomplete = match ($restrict) {
        'person-name' => 'name',
        'email' => 'email',
        'phone' => 'tel',
        'city' => 'address-level2',
        'url' => 'url',
        default => 'off',
    };
@endphp
<div class="{{ $class ?? '' }} @if(($type ?? '') === 'textarea' || ($full ?? false)) sm:col-span-2 @endif">
    @if (!empty($label))
        <label for="{{ $name }}" class="jb-label">{{ $label }}</label>
    @endif
    @if ($inputType === 'select')
        <select id="{{ $name }}" name="{{ $name }}" class="jb-select" {{ !empty($required) ? 'required' : '' }}>
            {{ $slot }}
        </select>
    @elseif (($type ?? '') === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows ?? 3 }}"
            class="jb-textarea"
            {{ !empty($required) ? 'required' : '' }}
            @if ($restrict) data-jb-restrict="{{ $restrict }}" @endif
            autocomplete="off"
        >{{ $value ?? '' }}</textarea>
    @else
        <input
            type="{{ $inputType }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $value ?? '' }}"
            class="jb-input"
            {{ !empty($required) ? 'required' : '' }}
            @if ($restrict) data-jb-restrict="{{ $restrict }}" @endif
            @if ($inputMode) inputmode="{{ $inputMode }}" @endif
            autocomplete="{{ $autocomplete }}"
            @if ($minValue !== null) min="{{ $minValue }}" @endif
            @if ($maxValue !== null) max="{{ $maxValue }}" @endif
            @if (!empty($step)) step="{{ $step }}" @endif
            @if (!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
            @if ($restrict === 'phone') maxlength="15" @endif
            @if ($restrict === 'currency') maxlength="10" @endif
        >
    @endif
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
