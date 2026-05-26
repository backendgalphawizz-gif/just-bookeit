@php($t = $adminTheme ?? [])
<style>
    :root {
        --jb-primary: {{ $t['primary'] ?? '#be123c' }};
        --jb-primary-hover: {{ $t['primary_hover'] ?? '#9f1239' }};
        --jb-sidebar-bg: {{ $t['sidebar_bg'] ?? '#0f172a' }};
        --jb-sidebar-hover: {{ $t['sidebar_hover'] ?? '#1e293b' }};
        --jb-sidebar-text: {{ $t['sidebar_text'] ?? '#e2e8f0' }};
        --jb-topbar-bg: {{ $t['topbar_bg'] ?? '#ffffff' }};
    }
</style>
