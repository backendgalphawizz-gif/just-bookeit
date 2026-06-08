@props([
    'module',
    'params' => [],
])

@php
    $query = request()->only($params);
@endphp

<div class="vp-export-dropdown" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" class="vp-btn vp-btn--outline vp-btn--sm" @click="open = !open" aria-haspopup="true" :aria-expanded="open">
        Export
        <span aria-hidden="true">▾</span>
    </button>
    <div class="vp-export-menu" x-show="open" x-cloak x-transition>
        <a href="{{ route('vendor.list-export', array_merge(['module' => $module, 'format' => 'csv'], $query)) }}" class="vp-export-menu-item">CSV</a>
        <a href="{{ route('vendor.list-export', array_merge(['module' => $module, 'format' => 'pdf'], $query)) }}" class="vp-export-menu-item">PDF</a>
    </div>
</div>
