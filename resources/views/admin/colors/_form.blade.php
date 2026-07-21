@php
    $color = $color ?? null;
    $hexValue = strtoupper((string) old('hex_code', $color?->hex_code ?? '#EC4899'));
    if ($hexValue !== '' && ! str_starts_with($hexValue, '#')) {
        $hexValue = '#'.$hexValue;
    }
    if (! preg_match('/^#[0-9A-F]{6}$/', $hexValue)) {
        $hexValue = '#EC4899';
    }
@endphp

@include('admin.partials.form-input', [
    'label' => 'Color name',
    'name' => 'name',
    'restrict' => 'text',
    'value' => old('name', $color?->name),
    'required' => true,
    'placeholder' => 'e.g. Rose Gold',
])

<div data-jb-color-picker>
    <label for="hex_code" class="jb-label">Hex code</label>
    <div class="jb-color-picker-row">
        <input
            type="color"
            id="hex_code_picker"
            value="{{ $hexValue }}"
            class="jb-color-picker-swatch"
            aria-label="Pick color"
            data-jb-color-picker-input
        >
        <input
            type="text"
            id="hex_code"
            name="hex_code"
            value="{{ $hexValue }}"
            class="jb-input jb-color-picker-hex @error('hex_code') border-rose-500 @enderror"
            placeholder="#EC4899"
            maxlength="7"
            autocomplete="off"
            data-jb-color-hex-input
        >
    </div>
    <p class="mt-1 text-xs text-slate-500">Pick a color to auto-fill the exact hex code for swatches.</p>
    @error('hex_code')
        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>

@include('admin.partials.form-input', [
    'label' => 'Sort order',
    'name' => 'sort_order',
    'type' => 'number',
    'min' => '0',
    'max' => '9999',
    'value' => old('sort_order', $color?->sort_order ?? 0),
])

<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $color?->is_active ?? true))>
    <label class="text-sm font-medium text-slate-700">Active</label>
</div>

@once
    @push('styles')
        <style>
            .jb-color-picker-row {
                display: flex;
                align-items: center;
                gap: .65rem;
            }
            .jb-color-picker-swatch {
                width: 2.75rem;
                height: 2.75rem;
                padding: 0;
                border: 1px solid #d0d5dd;
                border-radius: 10px;
                background: #fff;
                cursor: pointer;
                flex-shrink: 0;
            }
            .jb-color-picker-swatch::-webkit-color-swatch-wrapper {
                padding: 3px;
            }
            .jb-color-picker-swatch::-webkit-color-swatch {
                border: none;
                border-radius: 7px;
            }
            .jb-color-picker-swatch::-moz-color-swatch {
                border: none;
                border-radius: 7px;
            }
            .jb-color-picker-hex {
                flex: 1;
                min-width: 0;
                text-transform: uppercase;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            (function () {
                const root = document.querySelector('[data-jb-color-picker]');
                if (!root) return;

                const picker = root.querySelector('[data-jb-color-picker-input]');
                const hexInput = root.querySelector('[data-jb-color-hex-input]');
                if (!picker || !hexInput) return;

                const normalizeHex = (value) => {
                    let hex = String(value || '').trim().toUpperCase();
                    if (hex !== '' && !hex.startsWith('#')) hex = '#' + hex;
                    if (!/^#[0-9A-F]{6}$/.test(hex)) return null;
                    return hex;
                };

                picker.addEventListener('input', () => {
                    hexInput.value = String(picker.value || '').toUpperCase();
                });

                hexInput.addEventListener('input', () => {
                    const normalized = normalizeHex(hexInput.value);
                    if (normalized) {
                        picker.value = normalized;
                        hexInput.value = normalized;
                    }
                });

                hexInput.addEventListener('blur', () => {
                    const normalized = normalizeHex(hexInput.value);
                    if (normalized) {
                        picker.value = normalized;
                        hexInput.value = normalized;
                        return;
                    }
                    if (hexInput.value.trim() === '') {
                        picker.value = '#EC4899';
                        return;
                    }
                    hexInput.value = String(picker.value || '#EC4899').toUpperCase();
                });
            })();
        </script>
    @endpush
@endonce
