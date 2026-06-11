@extends('vendor.layouts.app')

@section('title', ($item->exists ? 'Edit' : 'Add').' '.$typeLabel)

@section('content')
<a href="{{ route('vendor.products.index', ['type' => $type]) }}" class="vp-back-link">← Back to {{ $typeLabel }}</a>

<div class="vp-page-head">
    <h1 class="vp-page-title">{{ $item->exists ? 'Edit' : 'Add' }} {{ $typeLabel }}</h1>
    <p class="vp-page-sub">Same fields as the vendor app — admin will approve before customers can book.</p>
</div>

<div class="vp-card">
    <div class="vp-card-pad">
        @if ($errors->any())
            <div class="vp-alert vp-alert--error" style="margin-bottom:1rem;">
                <p style="margin:0 0 .35rem;font-weight:700;">Please fix the following:</p>
                <ul style="margin:0;padding-left:1.1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="vendor-product-form" method="POST" action="{{ $item->exists ? route('vendor.products.update', $item) : route('vendor.products.store') }}" enctype="multipart/form-data" data-vp-product-form>
            @csrf
            @if ($item->exists) @method('PUT') @endif
            @include('vendor.products._form')

            <div class="vp-form-actions">
                <button type="submit" class="vp-btn vp-btn--primary">{{ $item->exists ? 'Save changes' : 'Submit for approval' }}</button>
                <a href="{{ route('vendor.products.index', ['type' => $type]) }}" class="vp-btn vp-btn--outline">Cancel</a>
            </div>
        </form>

        @if ($item->exists && $item->relationLoaded('images'))
            @foreach ($item->images as $image)
                <form id="vendor-delete-gallery-{{ $image->id }}" method="POST" action="{{ route('vendor.products.images.destroy', [$item, $image]) }}" hidden
                      data-vp-confirm="This gallery image will be permanently removed."
                      data-vp-confirm-title="Remove image?"
                      data-vp-confirm-label="Remove"
                      data-vp-confirm-variant="error">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-vp-product-form]');
    if (!root) return;

    const appendTemplateRow = (template, list, index, bindRow) => {
        if (!template?.content?.firstElementChild) return;
        const row = template.content.firstElementChild.cloneNode(true);
        row.querySelectorAll('[name]').forEach((input) => {
            if (input.name) {
                input.name = input.name.replace(/__INDEX__/g, String(index));
            }
        });
        list.appendChild(row);
        bindRow(row);
    };

    const initRepeatable = (config) => {
        const section = root.querySelector(config.rootSelector);
        if (!section) return;

        const list = section.querySelector(config.listSelector);
        const template = section.querySelector(config.templateSelector);
        const addBtn = section.querySelector(config.addSelector);
        const emptyNote = section.querySelector(config.emptySelector);
        const namePattern = config.namePattern;

        const reindex = () => {
            list.querySelectorAll(config.rowSelector).forEach((row, index) => {
                row.querySelectorAll('input, select, textarea').forEach((input) => {
                    if (input.name) {
                        input.name = input.name.replace(namePattern, `${config.namePrefix}[${index}]`);
                    }
                });
            });
        };

        const bindRemove = (row) => {
            const btn = row.querySelector(config.removeSelector);
            if (!btn) return;
            btn.addEventListener('click', () => {
                if (config.minRows && list.querySelectorAll(config.rowSelector).length <= config.minRows) {
                    row.querySelectorAll('input[type="text"], input[type="number"]').forEach((input) => {
                        input.value = '';
                    });
                    const file = row.querySelector('input[type="file"]');
                    if (file) file.value = '';
                    return;
                }
                row.remove();
                reindex();
                if (emptyNote && !list.querySelector(config.rowSelector)) {
                    emptyNote.hidden = false;
                }
            });
        };

        list.querySelectorAll(config.rowSelector).forEach(bindRemove);

        addBtn?.addEventListener('click', () => {
            if (emptyNote) emptyNote.hidden = true;
            const index = list.querySelectorAll(config.rowSelector).length;
            appendTemplateRow(template, list, index, bindRemove);
        });
    };

    initRepeatable({
        rootSelector: '[data-vp-variants]',
        listSelector: '[data-vp-variants-list]',
        templateSelector: '[data-vp-variants-template]',
        addSelector: '[data-vp-variants-add]',
        emptySelector: null,
        rowSelector: '[data-vp-variants-row]',
        removeSelector: '[data-vp-variants-remove]',
        namePattern: /variants\[\d+]/,
        namePrefix: 'variants',
        minRows: 1,
    });

    initRepeatable({
        rootSelector: '[data-vp-damage]',
        listSelector: '[data-vp-damage-list]',
        templateSelector: '[data-vp-damage-template]',
        addSelector: '[data-vp-damage-add]',
        emptySelector: '[data-vp-damage-empty]',
        rowSelector: '[data-vp-damage-row]',
        removeSelector: '[data-vp-damage-remove]',
        namePattern: /damage_deductions\[\d+]/,
        namePrefix: 'damage_deductions',
        minRows: 0,
    });
})();
</script>
@endpush
