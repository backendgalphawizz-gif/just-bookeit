@php
    /** @var array<string, mixed> $measurements */
    $measurements = $measurements ?? [];
    $type = $measurements['measurement_type'] ?? null;
    $sections = \App\Support\WebMeasurementForm::sectionsForType($type);
    $labelToField = \App\Support\WebMeasurementForm::labelToField();

    $formatValue = static function (mixed $value): string {
        if ($value === null || $value === '') {
            return '—';
        }
        $text = is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') : (string) $value;

        return $text.(is_numeric($value) ? ' cm' : '');
    };

    $core = [
        ['label' => 'Height', 'value' => $formatValue($measurements['height_cm'] ?? null)],
        ['label' => 'Chest', 'value' => $formatValue($measurements['chest_cm'] ?? ($measurements['chest'] ?? null))],
        ['label' => 'Waist', 'value' => $formatValue($measurements['waist_cm'] ?? ($measurements['waist'] ?? null))],
    ];

    $filledSections = [];
    foreach ($sections as $sectionLabel => $labels) {
        $rows = [];
        foreach ($labels as $label) {
            $field = $labelToField[$label] ?? null;
            if (! $field || in_array($field, ['chest', 'waist'], true)) {
                continue;
            }
            $value = $measurements[$field] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $rows[] = ['label' => $label, 'value' => $formatValue($value)];
        }
        if ($rows !== []) {
            $filledSections[$sectionLabel] = $rows;
        }
    }

    $hasAny = collect($core)->contains(fn ($row) => $row['value'] !== '—')
        || filled($type)
        || $filledSections !== [];
@endphp

@if ($hasAny)
    <div class="jb-booking-card jb-measure-card">
        <div class="jb-measure-card-head">
            <h3 class="jb-booking-card-title mb-0">{{ $title ?? 'Customer measurements' }}</h3>
            @if ($type)
                <span class="jb-measure-type">{{ ucfirst($type) }}</span>
            @endif
        </div>

        <div class="jb-measure-grid">
            @foreach ($core as $row)
                <div class="jb-measure-cell">
                    <span class="jb-measure-cell-label">{{ $row['label'] }}</span>
                    <span class="jb-measure-cell-value">{{ $row['value'] }}</span>
                </div>
            @endforeach
        </div>

        @foreach ($filledSections as $sectionLabel => $rows)
            <p class="jb-measure-section">{{ $sectionLabel }}</p>
            <div class="jb-measure-grid">
                @foreach ($rows as $row)
                    <div class="jb-measure-cell">
                        <span class="jb-measure-cell-label">{{ $row['label'] }}</span>
                        <span class="jb-measure-cell-value">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
