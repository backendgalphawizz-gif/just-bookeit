@php
    use App\Support\AdminValidationRules;

    $inputType = $type ?? 'text';
    $isNumber = $inputType === 'number';
    $restrict = $restrict ?? AdminValidationRules::defaultRestrict($name, $inputType);
    $useNonNegative = $nonNegative ?? ($restrict === 'decimal' || $restrict === 'integer' || $isNumber);
    $minValue = $min ?? ($useNonNegative ? '0' : null);
    $maxValue = $max ?? null;
    $maxChars = $maxChars ?? null;

    if ($restrict === 'decimal' || $restrict === 'integer') {
        $inputType = 'text';
    }

    if ($restrict === 'email') {
        $inputType = 'text';
    }

    $inputMode = match ($restrict) {
        'phone', 'integer', 'account-number' => 'numeric',
        'decimal' => 'decimal',
        'email' => 'email',
        'url' => 'url',
        default => null,
    };

    $emailPattern = $restrict === 'email' ? AdminValidationRules::htmlEmailPattern() : null;
    $emailTitle = $restrict === 'email' ? AdminValidationRules::emailValidationMessage() : null;

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
        <label for="{{ $name }}" class="jb-label">{{ $label }}@if (!empty($required))<span class="text-rose-600"> *</span>@endif</label>
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
            @if ($maxChars) maxlength="{{ $maxChars }}" data-jb-max-chars="{{ $maxChars }}" @endif
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
            @if ($restrict === 'email') maxlength="255" pattern="{{ $emailPattern }}" title="{{ $emailTitle }}" @endif
            @if ($restrict === 'phone') maxlength="10" @endif
            @if ($restrict === 'currency') maxlength="10" @endif
            @if ($restrict === 'gst') maxlength="15" data-jb-max-chars="15" @endif
            @if ($restrict === 'vehicle-no') maxlength="20" data-jb-max-chars="20" @endif
            @if ($restrict === 'account-number') maxlength="20" data-jb-max-chars="20" @endif
            @if ($restrict === 'ifsc') maxlength="11" data-jb-max-chars="11" @endif
            @if ($maxChars && ! in_array($restrict, ['gst', 'vehicle-no', 'account-number', 'ifsc'], true)) maxlength="{{ $maxChars }}" data-jb-max-chars="{{ $maxChars }}" @endif
        >
    @endif
    @if ($maxChars && ($type ?? '') !== 'select')
        <p class="mt-1 text-xs text-slate-500" data-jb-char-count-for="{{ $name }}">{{ strlen((string) ($value ?? '')) }}/{{ $maxChars }}</p>
    @endif
    @if (!empty($hint))
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @elseif ($restrict === 'email')
        <p class="mt-1 text-xs text-slate-500">{{ AdminValidationRules::emailFieldHint() }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
