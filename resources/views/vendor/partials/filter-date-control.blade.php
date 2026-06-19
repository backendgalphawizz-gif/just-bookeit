@php
    $isoValue = $isoValue ?? '';
    $inputRef = $inputRef ?? 'dateInput';
    $minBind = $minBind ?? 'minDate';
    $maxBind = $maxBind ?? 'maxDate';
    $changeHandler = $changeHandler ?? null;
@endphp
<input
    type="date"
    id="{{ $id }}"
    name="{{ $name }}"
    value="{{ $isoValue }}"
    lang="en-US"
    class="vp-input vp-date-native"
    x-ref="{{ $inputRef }}"
    :min="{{ $minBind }}"
    :max="{{ $maxBind }}"
    @if ($changeHandler) @change="{{ $changeHandler }}" @endif
>
