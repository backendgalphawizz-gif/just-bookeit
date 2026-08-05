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
        /* Match light admin shell: soft primary tint, white card, theme primary CTA */
        .jb-login-page {
            background-color: #f1f5f9;
            background-image: linear-gradient(
                180deg,
                color-mix(in srgb, var(--jb-primary, #be123c) 7%, #f8fafc) 0%,
                #f1f5f9 100%
            );
        }

        .jb-login-card {
            border: 1px solid color-mix(in srgb, var(--jb-primary, #be123c) 16%, #e2e8f0);
            background: color-mix(in srgb, var(--jb-primary, #be123c) 5%, #ffffff);
            box-shadow: 0 10px 28px -14px rgba(15, 23, 42, 0.2);
        }

        .jb-login-brand {
            background: color-mix(in srgb, var(--jb-primary, #be123c) 9%, #f8fafc);
            border-color: color-mix(in srgb, var(--jb-primary, #be123c) 12%, #e2e8f0);
        }

        .jb-login-brand-title {
            color: #0f172a;
        }

        .jb-login-brand-tagline {
            color: #64748b;
        }

        .jb-login-form-panel {
            background: color-mix(in srgb, var(--jb-primary, #be123c) 4%, #ffffff);
        }

        .jb-login-input:focus {
            border-color: var(--jb-primary, #be123c) !important;
            outline: none;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--jb-primary, #be123c) 18%, transparent);
        }

        .jb-login-remember input {
            accent-color: var(--jb-primary, #be123c);
        }

        .jb-login-submit {
            background: var(--jb-primary, #be123c) !important;
            box-shadow: none !important;
        }

        .jb-login-submit:hover {
            background: var(--jb-primary-hover, #9f1239) !important;
        }

        .jb-login-submit:focus {
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--jb-primary, #be123c) 22%, transparent) !important;
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
