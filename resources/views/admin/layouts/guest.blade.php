<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('admin.partials.document-head', ['branding' => $loginBranding ?? []])
    @include('admin.partials.admin-theme-vars')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.built-assets')
    <style>
        .jb-login-page {
            background-color: #f8fafc;
            background-image:
                radial-gradient(ellipse 80% 55% at 12% 18%, color-mix(in srgb, var(--jb-primary, #be123c) 20%, transparent), transparent 58%),
                radial-gradient(ellipse 70% 50% at 92% 82%, color-mix(in srgb, var(--jb-sidebar-bg, #0f172a) 16%, transparent), transparent 55%),
                linear-gradient(
                    145deg,
                    color-mix(in srgb, var(--jb-primary, #be123c) 9%, #f8fafc) 0%,
                    color-mix(in srgb, var(--jb-sidebar-bg, #0f172a) 5%, #f1f5f9) 48%,
                    color-mix(in srgb, var(--jb-primary, #be123c) 6%, #fff7ed) 100%
                );
        }
    </style>
</head>
<body class="jb-login-page">
    <div class="jb-login-wrap">
        @yield('content')
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</body>
</html>
