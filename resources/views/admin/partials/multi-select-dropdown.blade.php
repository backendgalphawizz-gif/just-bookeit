@props([
    'name',
    'label',
    'options' => [],
    'selected' => [],
    'placeholder' => 'Select options',
    'hint' => null,
    'required' => false,
])

@php
    $fieldName = str_ends_with($name, '[]') ? $name : $name.'[]';
    $optionItems = collect($options)->map(fn ($option) => [
        'id' => (int) ($option['id'] ?? $option->id ?? 0),
        'label' => (string) ($option['label'] ?? $option['name'] ?? $option->name ?? ''),
    ])->values()->all();
    $selectedIds = collect($selected)->map(fn ($id) => (int) $id)->values()->all();
@endphp

<div
    class="jb-multi-select"
    x-data="{
        open: false,
        selected: @js($selectedIds),
        options: @js($optionItems),
        placeholder: @js($placeholder),
        toggle(id) {
            id = Number(id);
            this.selected = this.selected.includes(id)
                ? this.selected.filter((value) => value !== id)
                : [...this.selected, id];
        },
        summary() {
            if (this.selected.length === 0) {
                return this.placeholder;
            }

            const labels = this.options
                .filter((option) => this.selected.includes(Number(option.id)))
                .map((option) => option.label);

            return labels.join(', ');
        },
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <label class="jb-label">
        {{ $label }}
        @if ($required && ! str_contains($label, '*'))
            <span class="text-rose-600">*</span>
        @endif
    </label>

    <button
        type="button"
        class="jb-multi-select-trigger"
        :class="{ 'is-open': open }"
        @click="open = !open"
        :aria-expanded="open"
    >
        <span class="jb-multi-select-trigger-text" x-text="summary()"></span>
        <svg class="jb-multi-select-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
        </svg>
    </button>

    <div class="jb-multi-select-menu" x-show="open" x-cloak x-transition>
        @forelse ($optionItems as $option)
            <label class="jb-multi-select-option">
                <input
                    type="checkbox"
                    value="{{ $option['id'] }}"
                    :checked="selected.includes({{ $option['id'] }})"
                    @change="toggle({{ $option['id'] }})"
                >
                <span>{{ $option['label'] }}</span>
            </label>
        @empty
            <p class="jb-multi-select-empty">No options available.</p>
        @endforelse
    </div>

    <template x-for="id in selected" :key="'{{ $name }}-' + id">
        <input type="hidden" name="{{ $fieldName }}" :value="id">
    </template>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error(rtrim($name, '[]'))
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
    @error(rtrim($name, '[]').'.*')
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
