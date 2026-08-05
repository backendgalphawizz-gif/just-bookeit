@php
    use App\Support\AdminValidationRules;

    $inputType = $type ?? 'text';
    $isNumber = $inputType === 'number';
    $isPassword = $inputType === 'password';
    $restrict = $restrict ?? AdminValidationRules::defaultRestrict($name, $inputType);
    $useNonNegative = $nonNegative ?? ($restrict === 'decimal' || $restrict === 'integer' || $isNumber);
    $minValue = $min ?? ($useNonNegative ? '0' : null);
    $maxValue = $max ?? null;
    $maxChars = $maxChars ?? null;
    $maxWords = $maxWords ?? null;

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

    $autocomplete = match (true) {
        $isPassword => 'new-password',
        $restrict === 'person-name' => 'name',
        $restrict === 'email' => 'email',
        $restrict === 'phone' => 'tel',
        $restrict === 'city' => 'address-level2',
        $restrict === 'url' => 'url',
        default => 'off',
    };
@endphp
<div class="{{ $class ?? '' }} @if(($type ?? '') === 'textarea' || ($full ?? false)) sm:col-span-2 @endif">
    @if (!empty($label))
        <label for="{{ $name }}" class="jb-label" >{{ $label }}@if (!empty($required))<span class="text-rose-600"> *</span>@endif</label>
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
            @if ($maxWords) data-jb-max-words="{{ $maxWords }}" @endif
            autocomplete="off"
        >{{ $value ?? '' }}</textarea>
    @elseif ($isPassword)
        <div class="jb-password-wrap" style="margin-bottom: 10px;" x-data="{ showPassword: false }">
            <input
                :type="showPassword ? 'text' : 'password'"
                id="{{ $name }}"
                name="{{ $name }}"
                value="{{ $value ?? '' }}"
                class="jb-input jb-input--password"
                {{ !empty($required) ? 'required' : '' }}
                autocomplete="{{ $autocomplete }}"
                @if (!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
                @if ($maxChars) maxlength="{{ $maxChars }}" data-jb-max-chars="{{ $maxChars }}" @endif
            >
            <button
                type="button"
                class="jb-password-toggle"
                @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                tabindex="-1"
            >
                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
            </button>
        </div>
    @else
        <input style="margin-bottom: 10px;"
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
            @if ($restrict === 'phone') maxlength="10" pattern="[6-9][0-9]{9}" title="10-digit mobile number starting with 6–9" @endif
            @if ($restrict === 'currency') maxlength="10" @endif
            @if ($restrict === 'gst') maxlength="15" data-jb-max-chars="15" @endif
            @if ($restrict === 'vehicle-no') maxlength="20" data-jb-max-chars="20" @endif
            @if ($restrict === 'account-number') maxlength="20" data-jb-max-chars="20" @endif
            @if ($restrict === 'ifsc') maxlength="11" data-jb-max-chars="11" @endif
            @if ($maxChars && ! in_array($restrict, ['gst', 'vehicle-no', 'account-number', 'ifsc'], true)) maxlength="{{ $maxChars }}" data-jb-max-chars="{{ $maxChars }}" @endif
            @if ($maxWords) data-jb-max-words="{{ $maxWords }}" @endif
        >
    @endif
    @if ($maxWords && ($type ?? '') !== 'select')
        @php
            $wordCount = \App\Support\WordLimit::count((string) ($value ?? ''));
        @endphp
        <p class="mt-1 text-xs text-slate-500" data-jb-word-count-for="{{ $name }}">{{ $wordCount }}/{{ $maxWords }} words</p>
    @elseif ($maxChars && ($type ?? '') !== 'select')
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
