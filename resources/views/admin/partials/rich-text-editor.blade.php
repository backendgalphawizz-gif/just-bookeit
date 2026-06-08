@props([
    'name',
    'label',
    'value' => '',
    'required' => false,
])

@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
        <style>
            .jb-rich-text-field {
                position: relative;
                width: 100%;
            }
            .jb-rich-text-field .jb-quill-mount {
                display: block;
                width: 100%;
            }
            .jb-rich-text-field .ql-toolbar.ql-snow {
                border-radius: 0.75rem 0.75rem 0 0;
                border-color: rgb(226 232 240);
                background: rgb(248 250 252);
                font-family: inherit;
                box-sizing: border-box;
                padding: 6px 8px;
            }
            .jb-rich-text-field .ql-container.ql-snow {
                border-radius: 0 0 0.75rem 0.75rem;
                border-color: rgb(226 232 240);
                background: #fff;
                font-family: inherit;
                font-size: 0.875rem;
                box-sizing: border-box;
            }
            .jb-rich-text-field .ql-editor {
                min-height: 12rem;
                line-height: 1.65;
            }
            .jb-rich-text-field .ql-editor.ql-blank::before {
                color: rgb(148 163 184);
                font-style: normal;
            }
            /* Prevent Tailwind / missing Quill CSS from blowing up toolbar icons */
            .jb-rich-text-field .ql-snow .ql-toolbar button,
            .jb-rich-text-field .ql-snow .ql-toolbar .ql-picker-label {
                width: 28px;
                height: 28px;
                padding: 2px 4px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .jb-rich-text-field .ql-snow .ql-toolbar button svg,
            .jb-rich-text-field .ql-snow .ql-toolbar .ql-picker-label svg {
                width: 18px !important;
                height: 18px !important;
                float: none;
                display: block;
            }
            .jb-rich-text-field .ql-snow .ql-picker {
                height: 28px;
            }
            .jb-rich-text-field .ql-snow .ql-picker-label {
                width: auto;
                min-width: 72px;
                padding-right: 20px;
            }
            .jb-rich-text-field .ql-snow .ql-stroke {
                stroke: #444;
            }
            .jb-rich-text-field .ql-snow .ql-fill {
                fill: #444;
            }
            .jb-rich-text-field .ql-snow .ql-picker-options {
                z-index: 20;
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
        <script src="{{ asset('js/rich-text-editor.js') }}" defer></script>
    @endpush
@endonce

<div class="jb-rich-text-field sm:col-span-2" data-jb-quill-field>
    <label for="{{ $name }}" class="jb-label">
        {{ $label }}
        @if ($required)<span class="text-rose-600"> *</span>@endif
    </label>
    <p class="mb-2 text-xs text-slate-500">Paste from Word or Google Docs — headings, lists, and bold text are preserved.</p>
    <div id="jb-quill-{{ $name }}" class="jb-quill-mount"></div>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        class="hidden"
        data-jb-quill-input
        tabindex="-1"
        aria-hidden="true"
        {{ $required ? 'required' : '' }}
    >{{ $value }}</textarea>
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
