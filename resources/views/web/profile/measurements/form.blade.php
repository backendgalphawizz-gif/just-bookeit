@extends('web.layouts.app')

@section('title', ($editing ?? false) ? 'Edit measurement profile' : 'Add measurement profile')

@section('content')
<div class="jbw-container jbw-measure-page">
    @php
        $redirectTo = $redirectTo ?? null;
        $editing = $editing ?? false;
        $defaultName = $defaultName ?? 'Profile 1';
    @endphp
    <div class="jbw-measure-topbar">
        <a href="{{ $redirectTo ?: route('web.profile.measurements') }}" class="jbw-measure-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            {{ $redirectTo ? 'Back' : 'My Measurements' }}
        </a>
        @unless ($editing)
            <a href="{{ $redirectTo ?: route('web.catalog.index') }}" class="jbw-measure-skip">Skip for now</a>
        @endunless
    </div>

    <div class="jbw-measure-card">
        <form
            method="POST"
            action="{{ $editing ? route('web.profile.measurements.update', $profile) : route('web.profile.measurements.store') }}"
        >
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            @if ($redirectTo)
                <input type="hidden" name="redirect" value="{{ $redirectTo }}">
            @endif

            <div class="jbw-measure-section">
                <h2 class="jbw-measure-section-title">Profile</h2>
                <div class="jbw-measure-form-grid">
                    <div class="jbw-field">
                        <label class="jbw-label" for="name">Profile name</label>
                        <input type="text" id="name" name="name" class="jbw-input" value="{{ old('name', $profile?->name ?? $defaultName) }}" placeholder="e.g. My size, Husband, Kids">
                    </div>
                    <div class="jbw-field">
                        <label class="jbw-label" for="measurement_type">Type</label>
                        <select id="measurement_type" name="measurement_type" class="jbw-select">
                            @foreach (['women' => 'Women', 'men' => 'Men', 'kid' => 'Kids'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('measurement_type', $profile?->measurement_type ?? 'women') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @php
                $fieldMap = \App\Support\WebMeasurementForm::labelToField();
                $sectionsByType = \App\Support\WebMeasurementForm::sectionsByType();
                $currentType = old('measurement_type', $profile?->measurement_type ?? 'women');

                $fieldTypes = [];
                $sectionTypes = [];
                foreach ($sectionsByType as $type => $typeSections) {
                    foreach ($typeSections as $secTitle => $secFields) {
                        $sectionTypes[$secTitle] = array_values(array_unique(array_merge($sectionTypes[$secTitle] ?? [], [$type])));
                        foreach ($secFields as $secField) {
                            $fieldTypes[$secField] = array_values(array_unique(array_merge($fieldTypes[$secField] ?? [], [$type])));
                        }
                    }
                }
            @endphp

            @foreach ($sections as $title => $fields)
                @php
                    $secApplies = $sectionTypes[$title] ?? ['women'];
                    $secVisible = in_array($currentType, $secApplies, true);
                @endphp
                <div class="jbw-measure-section" data-measure-section data-types="{{ implode(',', $secApplies) }}" @unless ($secVisible) style="display:none" @endunless>
                    <h2 class="jbw-measure-section-title">{{ $title }}</h2>
                    <div class="jbw-measure-form-grid">
                        @foreach ($fields as $field)
                            @php
                                $key = $fieldMap[$field];
                                $fieldApplies = $fieldTypes[$field] ?? ['women'];
                                $fieldVisible = in_array($currentType, $fieldApplies, true);
                            @endphp
                            <div class="jbw-field" data-measure-field data-types="{{ implode(',', $fieldApplies) }}" @unless ($fieldVisible) style="display:none" @endunless>
                                <label class="jbw-label" for="{{ $key }}">{{ $field }}</label>
                                <input
                                    type="text"
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    class="jbw-input jbw-input--measure"
                                    value="{{ old($key, $values[$key] ?? '') }}"
                                    placeholder="—"
                                    @unless ($fieldVisible) disabled @endunless
                                >
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="jbw-measure-actions">
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--cta">
                    {{ $editing ? 'SAVE PROFILE' : 'SAVE & CONTINUE' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var typeSelect = document.getElementById('measurement_type');
    if (!typeSelect) return;

    function appliesTo(el, type) {
        var types = (el.getAttribute('data-types') || '').split(',').map(function (t) { return t.trim(); });
        return types.indexOf(type) !== -1;
    }

    function applyType(type) {
        document.querySelectorAll('[data-measure-field]').forEach(function (field) {
            var visible = appliesTo(field, type);
            field.style.display = visible ? '' : 'none';
            field.querySelectorAll('input, select, textarea').forEach(function (input) {
                input.disabled = !visible;
            });
        });

        document.querySelectorAll('[data-measure-section]').forEach(function (section) {
            section.style.display = appliesTo(section, type) ? '' : 'none';
        });
    }

    typeSelect.addEventListener('change', function () {
        applyType(typeSelect.value);
    });

    applyType(typeSelect.value);
})();
</script>
@endpush
