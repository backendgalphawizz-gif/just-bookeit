@props([
    'label',
    'name',
    'required' => false,
    'full' => false,
])

<div {{ $attributes->class([$full ? 'sm:col-span-2' : '']) }}>
    <label for="{{ $name }}" class="jb-label">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}" class="jb-select" {{ $required ? 'required' : '' }}>
        {{ $slot }}
    </select>
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
