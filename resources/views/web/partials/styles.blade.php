<style>
/* ─── Design tokens ───────────────────────────────────────────────── */
:root {
    --c-primary:    #f25123;
    --c-primary-dk: #d0431b;
    --c-navy:       #1a2f38;
    --c-navy-lt:    #243b47;
    --c-bg:         #f8f7f5;
    --c-surface:    #ffffff;
    --c-text:       #1a1a2e;
    --c-muted:      #717585;
    --c-border:     #e8e6e1;
    --c-shadow-sm:  0 1px 3px rgb(0 0 0 / 0.06), 0 1px 2px rgb(0 0 0 / 0.04);
    --c-shadow-md:  0 4px 24px rgb(0 0 0 / 0.07);
    --c-shadow-lg:  0 16px 48px rgb(0 0 0 / 0.12);
    --r-card:       16px;
    --r-btn:        999px;
    --font-sans:    'Plus Jakarta Sans', system-ui, sans-serif;
    --font-serif:   'Playfair Display', Georgia, serif;
    --trans:        0.18s ease;

    /* backward-compat aliases so existing inline styles keep working */
    --jbw-primary:    var(--c-primary);
    --jbw-primary-dark: var(--c-primary-dk);
    --jbw-primary-soft: #fff4f0;
    --jbw-navy:       var(--c-navy);
    --jbw-bg:         var(--c-bg);
    --jbw-card:       var(--c-surface);
    --jbw-text:       var(--c-text);
    --jbw-muted:      var(--c-muted);
    --jbw-border:     var(--c-border);
    --jbw-radius:     var(--r-card);
    --jbw-shadow:     var(--c-shadow-md);
    --jbw-font:       var(--font-sans);
    --jbw-serif:      var(--font-serif);
    --jbw-page-bg:    var(--c-bg);
}

/* ─── Reset ───────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
[x-cloak] { display: none !important; }
.hidden { display: none !important; }

body {
    margin: 0;
    font-family: var(--font-sans);
    font-size: 0.9375rem;
    line-height: 1.6;
    color: var(--c-text);
    background: var(--c-bg);
    -webkit-font-smoothing: antialiased;
}

img { display: block; max-width: 100%; }
a { color: inherit; }

/* ─── Layout ──────────────────────────────────────────────────────── */
.jbw-container {
    width: min(1200px, 100% - 2.5rem);
    margin-inline: auto;
}
.jbw-main { padding-bottom: 0; }
.jbw-main--profile { padding-top: 1rem; }
.jbw-flash-wrap { padding-top: 1rem; }

/* ─── Header ──────────────────────────────────────────────────────── */
.jbw-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--c-surface);
    border-bottom: 1px solid var(--c-border);
    box-shadow: var(--c-shadow-sm);
}

.jbw-header-inner {
    display: flex;
    align-items: center;
    gap: 2rem;
    height: 4.5rem;
}

.jbw-logo {
    flex-shrink: 0;
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 0.625rem;
}

.jbw-logo-svg {
    height: 2.125rem;
    width: auto;
    display: block;
    flex-shrink: 0;
}

.jbw-logo-wordmark {
    font-weight: 800;
    font-size: 1rem;
    color: var(--c-navy);
    letter-spacing: -0.02em;
    white-space: nowrap;
}

/* fallback mark */
.jbw-logo-mark {
    width: 2.5rem; height: 2.5rem;
    border-radius: 8px;
    background: var(--c-primary);
    color: #fff;
    font-weight: 800;
    font-size: 0.875rem;
    display: grid;
    place-items: center;
}

/* legacy — kept so nothing breaks */
.jbw-logo-img {
    height: 2.25rem;
    width: auto;
    border-radius: 6px;
    display: block;
}

.jbw-nav {
    display: none;
    align-items: center;
    gap: 0.25rem;
    flex: 1;
}

.jbw-nav-link {
    text-decoration: none;
    color: var(--c-muted);
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    transition: color var(--trans), background var(--trans);
}
.jbw-nav-link:hover { color: var(--c-text); background: var(--c-bg); }
.jbw-nav-link.is-active { color: var(--c-primary); background: #fef3ee; }

.jbw-header-tools {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

.jbw-location-btn {
    display: none;
    align-items: center;
    gap: 0.375rem;
    border: 1px solid var(--c-border);
    background: var(--c-bg);
    border-radius: var(--r-btn);
    padding: 0.375rem 0.875rem;
    font-size: 0.8rem;
    font-family: var(--font-sans);
    color: var(--c-muted);
    max-width: 11rem;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    cursor: pointer;
}

.jbw-icon-btn,
.jbw-avatar-btn {
    width: 2.25rem; height: 2.25rem;
    border-radius: var(--r-btn);
    border: 1px solid var(--c-border);
    background: var(--c-surface);
    display: grid;
    place-items: center;
    color: var(--c-text);
    cursor: pointer;
    text-decoration: none;
    transition: background var(--trans), border-color var(--trans);
}
.jbw-icon-btn:hover { background: var(--c-bg); }
.jbw-avatar-btn { padding: 0; overflow: hidden; }
.jbw-avatar-img { width: 100%; height: 100%; object-fit: cover; }
.jbw-avatar-fallback {
    width: 100%; height: 100%;
    display: grid; place-items: center;
    background: #fce7df; color: var(--c-primary);
    font-weight: 700; font-size: 0.875rem;
}

.jbw-profile-menu { position: relative; }
.jbw-profile-dropdown {
    position: absolute;
    right: 0; top: calc(100% + 0.625rem);
    min-width: 13rem;
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    box-shadow: var(--c-shadow-lg);
    padding: 0.5rem;
    z-index: 50;
}
.jbw-profile-dropdown-name {
    font-size: 0.875rem;
    font-weight: 700;
    padding: 0.5rem 0.75rem 0.625rem;
    margin: 0;
    border-bottom: 1px solid var(--c-border);
    margin-bottom: 0.375rem;
}
.jbw-profile-dropdown a,
.jbw-profile-dropdown button {
    display: block; width: 100%;
    text-align: left; padding: 0.5rem 0.75rem;
    border: 0; background: none; font: inherit;
    font-size: 0.875rem;
    color: var(--c-text); text-decoration: none;
    border-radius: 8px; cursor: pointer;
    transition: background var(--trans);
}
.jbw-profile-dropdown a:hover,
.jbw-profile-dropdown button:hover { background: var(--c-bg); }

.jbw-mobile-toggle {
    display: grid; place-items: center;
    width: 2.25rem; height: 2.25rem;
    border: 0; background: none; cursor: pointer; color: var(--c-text);
}

@media (min-width: 900px) {
    .jbw-nav { display: flex; }
    .jbw-location-btn { display: inline-flex; }
    .jbw-mobile-toggle { display: none; }
}

/* ─── Full-width section bands ────────────────────────────────────── */
.jbw-section-band {
    padding: 5rem 0;
}

.jbw-section-band--warm {
    background: linear-gradient(135deg, #fdf6f0 0%, #fef9f5 40%, #f5f0ea 100%);
    border-top: 1px solid #ede8e0;
    border-bottom: 1px solid #ede8e0;
}

.jbw-section-band--dark {
    background: #111;
    position: relative;
    overflow: hidden;
}
.jbw-section-band--dark::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url('https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=1600&q=60&fit=crop');
    background-size: cover; background-position: center;
    opacity: 0.18;
}
.jbw-section-band--dark .jbw-section-head,
.jbw-section-band--dark .jbw-grid-3 { position: relative; z-index: 1; }

.jbw-section-band--navy {
    background: var(--c-navy);
    position: relative;
    overflow: hidden;
}
.jbw-section-band--navy::before {
    content: '';
    position: absolute;
    top: -60%; right: -20%;
    width: 60vw; height: 60vw;
    border-radius: 50%;
    background: radial-gradient(circle, rgb(242 81 35 / 0.12) 0%, transparent 70%);
    pointer-events: none;
}
.jbw-section-band--navy .jbw-section-head { position: relative; z-index: 1; }
.jbw-section-band--navy .jbw-designers { position: relative; z-index: 1; }

.jbw-section-band--cta {
    background: var(--c-navy);
    position: relative;
    overflow: hidden;
    padding: 6rem 0;
}
.jbw-section-band--cta::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600&q=50&fit=crop');
    background-size: cover; background-position: center top;
    opacity: 0.12;
}
.jbw-section-band--cta::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgb(26 47 56 / 0.96) 0%, rgb(26 47 56 / 0.82) 50%, rgb(26 47 56 / 0.96) 100%);
}

/* ─── Stats strip ──────────────────────────────────────────────────── */
.jbw-stats-strip {
    background: var(--c-surface);
    border-bottom: 1px solid var(--c-border);
    padding: 1.5rem 0;
}

.jbw-stats-grid {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0;
}

.jbw-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.625rem 2.5rem;
    text-align: center;
}

.jbw-stat-div {
    width: 1px;
    height: 2.5rem;
    background: var(--c-border);
}

.jbw-stat-num {
    font-family: var(--font-serif);
    font-size: 1.625rem;
    font-weight: 600;
    color: var(--c-primary);
    line-height: 1;
}

.jbw-stat-lbl {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--c-muted);
    margin-top: 0.25rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

@media (max-width: 600px) {
    .jbw-stats-grid { gap: 0.5rem; }
    .jbw-stat { padding: 0.5rem 1rem; }
    .jbw-stat-div { display: none; }
    .jbw-stat-num { font-size: 1.25rem; }
}

/* ─── Footer ──────────────────────────────────────────────────────── */
.jbw-footer {
    background: var(--c-navy);
    color: #8da4ae;
    padding: 4rem 0 0;
    margin-top: 4rem;
}

.jbw-footer-grid {
    display: grid;
    gap: 2.5rem;
    padding-bottom: 3rem;
}

.jbw-footer-logo-link { display: inline-block; text-decoration: none; }
.jbw-footer-logo-img {
    height: 2.75rem; width: auto;
    border-radius: 8px;
    display: block;
}
.jbw-footer-logo { font-family: var(--font-serif); font-size: 1.75rem; font-weight: 700; color: #fff; text-decoration: none; }

.jbw-footer-about {
    margin-top: 0.875rem;
    font-size: 0.875rem;
    line-height: 1.7;
    max-width: 19rem;
    color: #8da4ae;
}

.jbw-footer-heading {
    color: #fff;
    font-weight: 700;
    font-size: 0.875rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin: 0 0 1rem;
}

.jbw-footer-links { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.625rem; }
.jbw-footer-links a {
    color: #8da4ae;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color var(--trans);
}
.jbw-footer-links a:hover { color: #fff; }

.jbw-social { display: flex; gap: 0.625rem; margin-top: 0.25rem; }
.jbw-social a {
    width: 2.125rem; height: 2.125rem;
    border-radius: var(--r-btn);
    border: 1px solid #2d4a56;
    display: grid; place-items: center;
    color: #8da4ae; text-decoration: none;
    font-size: 0.6875rem; font-weight: 700;
    transition: border-color var(--trans), color var(--trans);
}
.jbw-social a:hover { border-color: var(--c-primary); color: var(--c-primary); }

.jbw-footer-bottom {
    border-top: 1px solid #1e3340;
    padding: 1.25rem 0 1.75rem;
    font-size: 0.8125rem;
    color: #5a7582;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

@media (min-width: 768px) {
    .jbw-footer-grid { grid-template-columns: 1.6fr 1fr 1fr 0.9fr; }
}

/* ─── Buttons ─────────────────────────────────────────────────────── */
.jbw-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border-radius: var(--r-btn);
    padding: 0.75rem 1.5rem;
    font-weight: 700;
    font-size: 0.875rem;
    font-family: var(--font-sans);
    text-decoration: none;
    border: 1.5px solid transparent;
    cursor: pointer;
    letter-spacing: 0.01em;
    transition: background var(--trans), box-shadow var(--trans), transform 0.1s;
    white-space: nowrap;
}
.jbw-btn:active { transform: scale(0.98); }

.jbw-btn--primary {
    background: var(--c-primary);
    color: #fff;
    box-shadow: 0 4px 16px rgb(242 81 35 / 0.3);
}
.jbw-btn--primary:hover { background: var(--c-primary-dk); box-shadow: 0 6px 20px rgb(242 81 35 / 0.4); }

.jbw-btn--navy {
    background: var(--c-navy);
    color: #fff;
    box-shadow: 0 4px 16px rgb(26 47 56 / 0.3);
}
.jbw-btn--navy:hover { background: var(--c-navy-lt); }

.jbw-btn--dark {
    background: #111;
    color: #fff;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-size: 0.8rem;
    padding: 0.875rem 2rem;
}
.jbw-btn--dark:hover { background: #333; }

.jbw-btn--outline {
    background: transparent;
    border-color: var(--c-border);
    color: var(--c-text);
}
.jbw-btn--outline:hover { background: var(--c-bg); }

.jbw-btn--ghost {
    background: transparent;
    border-color: transparent;
    color: var(--c-muted);
}
.jbw-btn--ghost:hover { color: var(--c-text); background: var(--c-bg); }

.jbw-btn--danger { background: #fff; border-color: #fecaca; color: #dc2626; }
.jbw-btn--block { width: 100%; }
.jbw-btn--sm { padding: 0.4375rem 1rem; font-size: 0.8125rem; }
.jbw-btn--lg { padding: 1rem 2rem; font-size: 1rem; }

/* ─── Forms ───────────────────────────────────────────────────────── */
.jbw-label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--c-muted);
    margin-bottom: 0.4rem;
}

.jbw-input,
.jbw-select,
.jbw-textarea {
    width: 100%;
    border: 1.5px solid var(--c-border);
    border-radius: 10px;
    padding: 0.8125rem 1rem;
    font: inherit;
    font-size: 0.9375rem;
    background: var(--c-surface);
    color: var(--c-text);
    transition: border-color var(--trans), box-shadow var(--trans);
    appearance: none;
}
.jbw-input:focus,
.jbw-select:focus,
.jbw-textarea:focus {
    outline: none;
    border-color: var(--c-primary);
    box-shadow: 0 0 0 3px rgb(242 81 35 / 0.1);
}
.jbw-input.is-invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgb(239 68 68 / 0.08);
}

.jbw-field + .jbw-field { margin-top: 1rem; }
.jbw-form-stack { display: grid; gap: 1rem; }
.jbw-form-stack--tight { margin-top: 0.25rem; }
.jbw-textarea { resize: vertical; min-height: 5rem; }

.jbw-field-error,
.jbw-field-hint {
    margin: 0.375rem 0 0;
    font-size: 0.8125rem;
    color: #dc2626;
}

/* ─── Alerts ──────────────────────────────────────────────────────── */
.jbw-alert {
    border-radius: 12px;
    padding: 0.9rem 1.125rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}
.jbw-alert--success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.jbw-alert--error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.jbw-alert--info    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.jbw-alert-list { margin: 0; padding-left: 1.1rem; }

/* ─── Cards ───────────────────────────────────────────────────────── */
.jbw-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--r-card);
    box-shadow: var(--c-shadow-sm);
    padding: 1.5rem;
}

/* ─── Section typography ──────────────────────────────────────────── */
.jbw-section { padding: 5rem 0 0; }
.jbw-section--tight { padding-top: 3rem; }

.jbw-section-head { text-align: center; margin-bottom: 2.5rem; }

.jbw-eyebrow {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--c-primary);
    margin-bottom: 0.75rem;
}

.jbw-section-title {
    font-family: var(--font-serif);
    font-size: clamp(1.75rem, 3.5vw, 2.5rem);
    font-weight: 600;
    line-height: 1.15;
    margin: 0 0 0.5rem;
    color: var(--c-text);
}

.jbw-section-sub {
    color: var(--c-muted);
    margin: 0 auto;
    font-size: 1rem;
    line-height: 1.7;
    max-width: 36rem;
}

.jbw-page-head { margin-bottom: 1.5rem; padding-top: 2rem; }
.jbw-page-title { font-family: var(--font-serif); font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 600; margin: 0; }
.jbw-page-subtitle { color: var(--c-muted); margin: 0.375rem 0 0; font-size: 0.9375rem; }
.jbw-back-link { text-decoration: none; color: var(--c-text); font-weight: 700; }

/* ─── Hero ────────────────────────────────────────────────────────── */
.jbw-hero {
    position: relative;
    overflow: hidden;
    min-height: min(92vh, 720px);
    background: #111;
}

.jbw-hero-slide {
    position: absolute; inset: 0;
    background-size: cover;
    background-position: center;
    transform: scale(1.04);
    transition: transform 8s ease;
}
.jbw-hero:hover .jbw-hero-slide { transform: scale(1); }

.jbw-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        105deg,
        rgb(0 0 0 / 0.72) 0%,
        rgb(0 0 0 / 0.42) 50%,
        rgb(0 0 0 / 0.1) 100%
    );
}

.jbw-hero-content-wrap {
    position: relative;
    z-index: 2;
    min-height: min(92vh, 720px);
    display: flex;
    align-items: center;
}

.jbw-hero-content {
    max-width: 38rem;
    padding: 4rem 0;
    color: #fff;
}

.jbw-hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--c-primary);
    margin-bottom: 1.25rem;
}
.jbw-hero-kicker::before {
    content: '';
    display: block;
    width: 2rem; height: 2px;
    background: var(--c-primary);
}

.jbw-hero-title {
    font-family: var(--font-serif);
    font-size: clamp(2.5rem, 6vw, 4.5rem);
    font-weight: 600;
    line-height: 1.05;
    margin: 0 0 1.25rem;
    color: #fff;
    letter-spacing: -0.02em;
}

.jbw-hero-text {
    color: rgb(255 255 255 / 0.75);
    font-size: 1.0625rem;
    line-height: 1.75;
    margin-bottom: 2rem;
    max-width: 30rem;
}

.jbw-hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }

.jbw-hero-scroll {
    position: absolute;
    bottom: 2rem; left: 50%;
    transform: translateX(-50%);
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: rgb(255 255 255 / 0.5);
    font-size: 0.6875rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.jbw-hero-scroll-line {
    width: 1px; height: 3rem;
    background: linear-gradient(to bottom, rgb(255 255 255 / 0.5), transparent);
}

/* ─── How it works strip ─────────────────────────────────────────── */
.jbw-steps {
    display: grid;
    gap: 2rem;
    margin-bottom: 1rem;
}

.jbw-step {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
}

.jbw-step-num {
    flex-shrink: 0;
    width: 3rem; height: 3rem;
    border-radius: var(--r-btn);
    background: #fef3ee;
    color: var(--c-primary);
    font-weight: 800;
    font-size: 1.125rem;
    display: grid;
    place-items: center;
}

.jbw-step-title { font-weight: 700; margin: 0 0 0.25rem; font-size: 1rem; }
.jbw-step-text { font-size: 0.875rem; color: var(--c-muted); margin: 0; line-height: 1.6; }

@media (min-width: 768px) { .jbw-steps { grid-template-columns: repeat(3, 1fr); } }

/* ─── Grid tiles (services / categories) ─────────────────────────── */
.jbw-grid-3 { display: grid; gap: 1.25rem; margin-bottom: 1rem; }
@media (min-width: 640px) { .jbw-grid-3 { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 960px) { .jbw-grid-3 { grid-template-columns: repeat(3, 1fr); } }

.jbw-tile {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    min-height: 22rem;
    display: block;
    text-decoration: none;
    color: #fff;
    box-shadow: var(--c-shadow-md);
    transition: transform 0.28s ease, box-shadow 0.28s ease;
}
.jbw-tile:hover { transform: translateY(-5px); box-shadow: var(--c-shadow-lg); }

.jbw-tile img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}
.jbw-tile:hover img { transform: scale(1.06); }

.jbw-tile-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        180deg,
        transparent 30%,
        rgb(0 0 0 / 0.3) 60%,
        rgb(0 0 0 / 0.85) 100%
    );
}

.jbw-tile-body {
    position: absolute;
    left: 0; right: 0; bottom: 0;
    padding: 1.5rem;
    z-index: 1;
}

.jbw-tile-label {
    display: block;
    font-weight: 700;
    font-size: 1.125rem;
    letter-spacing: -0.01em;
}

.jbw-tile-meta {
    font-size: 0.8125rem;
    color: rgb(255 255 255 / 0.7);
    margin-top: 0.25rem;
}

/* ─── Featured designers row ─────────────────────────────────────── */
.jbw-designers {
    display: flex;
    gap: 1.25rem;
    overflow-x: auto;
    padding-bottom: 0.75rem;
    margin-bottom: 0.5rem;
    scrollbar-width: none;
}
.jbw-designers::-webkit-scrollbar { display: none; }

.jbw-designer {
    flex: 0 0 auto;
    text-align: center;
    text-decoration: none;
    color: inherit;
    width: 6rem;
    transition: transform var(--trans);
}
.jbw-designer:hover { transform: translateY(-3px); }

.jbw-designer-avatar {
    width: 5rem; height: 5rem;
    border-radius: var(--r-btn);
    object-fit: cover;
    margin: 0 auto 0.625rem;
    border: 3px solid var(--c-surface);
    box-shadow: var(--c-shadow-md);
    display: block;
}

.jbw-designer-fallback {
    display: grid; place-items: center;
    background: #fce7df;
    color: var(--c-primary);
    font-weight: 800; font-size: 1.25rem;
}

.jbw-designer-name {
    font-size: 0.8rem;
    font-weight: 700;
    line-height: 1.3;
    color: var(--c-text);
}

/* ─── CTA / Promo band ────────────────────────────────────────────── */
.jbw-cta-band {
    background: var(--c-navy);
    border-radius: 24px;
    padding: 4rem 2.5rem;
    text-align: center;
    margin: 4rem 0 0;
    position: relative;
    overflow: hidden;
}
.jbw-cta-band::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgb(242 81 35 / 0.2) 0%, transparent 65%);
    pointer-events: none;
}
.jbw-cta-band .jbw-section-title { color: #fff; }
.jbw-cta-band .jbw-section-sub { color: rgb(255 255 255 / 0.65); }
.jbw-cta-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem; }

/* ─── Catalog ─────────────────────────────────────────────────────── */
.jbw-catalog-layout { display: grid; gap: 2rem; }

.jbw-filters {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--r-card);
    padding: 1.25rem;
    height: fit-content;
    position: sticky;
    top: 6rem;
}

.jbw-filter-title {
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--c-muted);
    margin: 0 0 1rem;
}

.jbw-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1.25rem;
}

.jbw-product-card {
    background: var(--c-surface);
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    border: 1px solid var(--c-border);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.jbw-product-card:hover { transform: translateY(-4px); box-shadow: var(--c-shadow-lg); }

.jbw-product-card img {
    width: 100%;
    aspect-ratio: 3/4;
    object-fit: cover;
    background: #f0ede8;
    display: block;
}

.jbw-product-card-body { padding: 1rem; }
.jbw-product-brand {
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    color: var(--c-muted);
    text-transform: uppercase;
    margin: 0;
}
.jbw-product-title { font-weight: 700; margin: 0.25rem 0; font-size: 0.9375rem; line-height: 1.4; }
.jbw-product-price { color: var(--c-primary); font-weight: 800; margin: 0; font-size: 0.9375rem; }

@media (min-width: 900px) {
    .jbw-catalog-layout { grid-template-columns: 260px 1fr; }
}

/* ─── Product detail ─────────────────────────────────────────────── */
.jbw-product-detail { display: grid; gap: 2.5rem; margin-bottom: 3rem; }
.jbw-gallery-main { border-radius: 20px; overflow: hidden; background: #f0ede8; }
.jbw-gallery-main img { width: 100%; display: block; aspect-ratio: 3/4; object-fit: cover; }
.jbw-product-detail-title { font-family: var(--font-serif); font-size: clamp(1.75rem, 3vw, 2.5rem); font-weight: 600; margin: 0 0 0.625rem; }

.jbw-vendor-chip {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.875rem 1rem;
    border: 1px solid var(--c-border);
    border-radius: 14px;
    margin: 1.25rem 0;
    text-decoration: none;
    color: inherit;
    transition: background var(--trans);
}
.jbw-vendor-chip:hover { background: var(--c-bg); }
.jbw-vendor-chip img,
.jbw-vendor-chip-avatar {
    width: 3rem; height: 3rem;
    border-radius: var(--r-btn);
    object-fit: cover;
}
.jbw-detail-actions { display: flex; flex-wrap: wrap; gap: 0.875rem; margin-top: 1.5rem; }

@media (min-width: 900px) {
    .jbw-product-detail { grid-template-columns: 1fr 1fr; align-items: start; }
}

/* ─── Profile shell ──────────────────────────────────────────────── */
.jbw-profile-shell { display: grid; gap: 1.5rem; }

.jbw-profile-sidebar {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--r-card);
    padding: 1.25rem;
    height: fit-content;
}

.jbw-profile-card { text-align: center; padding-bottom: 1.25rem; border-bottom: 1px solid var(--c-border); margin-bottom: 1rem; }
.jbw-profile-card-photo {
    width: 5.5rem; height: 5.5rem;
    border-radius: var(--r-btn);
    object-fit: cover;
    margin: 0 auto 0.875rem;
    display: block;
    border: 3px solid var(--c-surface);
    box-shadow: var(--c-shadow-md);
}
.jbw-profile-card-photo--fallback {
    display: grid; place-items: center;
    background: #fce7df; color: var(--c-primary);
    font-weight: 800; font-size: 1.5rem;
}
.jbw-profile-card-name { font-weight: 700; margin: 0; font-size: 1rem; }
.jbw-profile-card-meta { font-size: 0.8125rem; color: var(--c-muted); margin: 0.2rem 0 0; }

.jbw-profile-nav { display: grid; gap: 0.25rem; }
.jbw-profile-nav-link,
.jbw-profile-logout button {
    display: flex; align-items: center; gap: 0.625rem;
    padding: 0.625rem 0.875rem; border-radius: 10px;
    text-decoration: none; color: var(--c-text);
    font-size: 0.875rem; font-weight: 600;
    border: 0; background: none; width: 100%;
    cursor: pointer; font-family: var(--font-sans);
    transition: background var(--trans), color var(--trans);
}
.jbw-profile-nav-link:hover { background: var(--c-bg); }
.jbw-profile-nav-link.is-active { background: #fef3ee; color: var(--c-primary); }
.jbw-profile-logout button { color: #dc2626; margin-top: 0.5rem; }
.jbw-profile-logout button:hover { background: #fef2f2; }
.jbw-profile-content .jbw-card + .jbw-card { margin-top: 1.25rem; }

@media (min-width: 900px) {
    .jbw-profile-shell { grid-template-columns: 270px 1fr; align-items: start; }
}

/* ─── Profile edit ────────────────────────────────────────────────── */
.jbw-profile-edit-photo {
    width: 5.5rem; height: 5.5rem;
    border-radius: var(--r-btn);
    object-fit: cover;
    display: block; margin-bottom: 1.25rem;
    border: 3px solid var(--c-surface);
    box-shadow: var(--c-shadow-md);
}
.jbw-profile-edit-photo--fallback {
    display: grid; place-items: center;
    background: #fce7df; color: var(--c-primary);
    font-weight: 800; font-size: 1.5rem;
}

/* ─── Bookings ────────────────────────────────────────────────────── */
.jbw-booking-tabs { display: flex; gap: 0.25rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--c-border); }
.jbw-booking-tab {
    padding: 0.875rem 0.25rem;
    margin-right: 1.25rem;
    text-decoration: none;
    color: var(--c-muted);
    font-weight: 700;
    font-size: 0.875rem;
    border-bottom: 2px solid transparent;
    transition: color var(--trans), border-color var(--trans);
}
.jbw-booking-tab.is-active { color: var(--c-primary); border-bottom-color: var(--c-primary); }

.jbw-booking-row {
    display: grid;
    grid-template-columns: 5.5rem 1fr auto;
    gap: 1.25rem;
    align-items: center;
    padding: 1.25rem 0;
    border-bottom: 1px solid var(--c-border);
}
.jbw-booking-row img {
    width: 5.5rem; height: 5.5rem;
    border-radius: 12px;
    object-fit: cover;
    background: #f0ede8;
}

.jbw-status {
    display: inline-flex;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.6875rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.jbw-status--new       { background: #dbeafe; color: #1d4ed8; }
.jbw-status--in_transit{ background: #ffedd5; color: #c2410c; }
.jbw-status--delivered { background: #dcfce7; color: #15803d; }
.jbw-status--cancelled { background: #fee2e2; color: #b91c1c; }
.jbw-status--default   { background: #f1f5f9; color: #475569; }

/* booking detail */
.jbw-booking-layout { display: grid; gap: 1.5rem; }
.jbw-booking-main,
.jbw-booking-sidebar { display: grid; gap: 1.25rem; }
.jbw-booking-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--r-card);
    padding: 1.375rem;
}
.jbw-booking-card-title {
    font-size: 0.6875rem; font-weight: 800;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--c-muted); margin: 0 0 1rem;
}
.jbw-booking-split { display: grid; gap: 1rem; }
@media (min-width: 640px) { .jbw-booking-split { grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) { .jbw-booking-layout { grid-template-columns: 1fr 22rem; } }

.jbw-booking-product-row { display: flex; gap: 1rem; }
.jbw-booking-product-img { width: 5.5rem; height: 5.5rem; border-radius: 12px; object-fit: cover; }
.jbw-booking-product-name { font-weight: 800; margin: 0; font-size: 1rem; }
.jbw-booking-product-meta { font-size: 0.8125rem; color: var(--c-muted); margin: 0.25rem 0 0; }
.jbw-booking-product-price { font-weight: 800; color: var(--c-primary); margin-top: 0.5rem; font-size: 1.125rem; }

.jbw-booking-track { list-style: none; margin: 0; padding: 0; }
.jbw-booking-track-step { display: flex; gap: 0.875rem; padding-bottom: 1.25rem; position: relative; }
.jbw-booking-track-step:not(:last-child)::before {
    content: ''; position: absolute; left: 0.6rem; top: 1.4rem; bottom: 0;
    width: 2px; background: var(--c-border);
}
.jbw-booking-track-step--done:not(:last-child)::before { background: var(--c-primary); }
.jbw-booking-track-marker {
    width: 1.25rem; height: 1.25rem; border-radius: 999px;
    border: 2px solid var(--c-border); flex-shrink: 0; background: #fff;
}
.jbw-booking-track-step--done .jbw-booking-track-marker { background: var(--c-primary); border-color: var(--c-primary); }
.jbw-booking-track-label { font-weight: 700; font-size: 0.875rem; margin: 0; }
.jbw-booking-track-time { font-size: 0.75rem; color: var(--c-muted); margin: 0.125rem 0 0; }

.jbw-payment-lines { display: grid; gap: 0.5rem; font-size: 0.875rem; }
.jbw-payment-lines div { display: flex; justify-content: space-between; }
.jbw-payment-total {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--c-border);
}
.jbw-payment-total strong { font-size: 1.375rem; color: var(--c-primary); }

/* ─── Measurements ────────────────────────────────────────────────── */
.jbw-measure-page { padding-top: 2rem; padding-bottom: 4rem; }
.jbw-measure-topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.jbw-measure-back {
    display: inline-flex; align-items: center; gap: 0.5rem;
    text-decoration: none; color: var(--c-text);
    font-weight: 700; font-size: 1.125rem;
}
.jbw-measure-skip {
    font-size: 0.8125rem; font-weight: 700; letter-spacing: 0.04em;
    text-transform: uppercase; color: var(--c-muted);
    text-decoration: none; border: 1.5px solid var(--c-border);
    border-radius: 10px; padding: 0.5rem 1rem; background: var(--c-surface);
    transition: background var(--trans);
}
.jbw-measure-skip:hover { background: var(--c-bg); }

.jbw-measure-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 20px;
    box-shadow: var(--c-shadow-md);
    padding: 2rem 1.75rem 2.5rem;
}
.jbw-measure-section + .jbw-measure-section { margin-top: 2rem; }
.jbw-measure-section-title {
    color: var(--c-primary);
    font-weight: 700; font-size: 0.875rem;
    letter-spacing: 0.04em; text-transform: uppercase;
    margin: 0 0 1.25rem; padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--c-border);
}
.jbw-input--measure { text-align: center; color: var(--c-muted); }
.jbw-measure-actions { display: flex; justify-content: flex-end; margin-top: 2.5rem; padding-top: 1.25rem; border-top: 1px solid var(--c-border); }
.jbw-measure-form-grid { display: grid; gap: 1rem 1.25rem; }
@media (min-width: 768px) { .jbw-measure-form-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 767px) { .jbw-measure-form-grid { grid-template-columns: repeat(2, 1fr); } }

.jbw-measures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.625rem; }
.jbw-measure { background: var(--c-bg); border-radius: 10px; padding: 0.75rem; text-align: center; }
.jbw-measure-label { display: block; font-size: 0.625rem; font-weight: 800; color: var(--c-muted); text-transform: uppercase; }
.jbw-measure-value { display: block; font-weight: 800; margin-top: 0.25rem; }

/* ─── Addresses ──────────────────────────────────────────────────── */
.jbw-address-grid { display: grid; gap: 1rem; }
@media (min-width: 640px) { .jbw-address-grid { grid-template-columns: 1fr 1fr; } }
.jbw-address-card { position: relative; }
.jbw-address-tag {
    display: inline-block; font-size: 0.6875rem; font-weight: 800;
    letter-spacing: 0.08em; color: var(--c-primary); margin-bottom: 0.5rem;
    text-transform: uppercase;
}
.jbw-add-card {
    border: 2px dashed var(--c-border); background: var(--c-bg);
    display: grid; place-items: center; min-height: 10rem;
    text-decoration: none; color: var(--c-muted); border-radius: var(--r-card);
    transition: border-color var(--trans);
}
.jbw-add-card:hover { border-color: var(--c-primary); }
.jbw-add-card strong { display: block; color: var(--c-text); margin-top: 0.5rem; }

/* ─── Vendor page ────────────────────────────────────────────────── */
.jbw-vendor-hero {
    position: relative; border-radius: 20px; overflow: hidden;
    min-height: 14rem; background: #ddd; margin-bottom: 4.5rem;
}
.jbw-vendor-hero img { width: 100%; height: 14rem; object-fit: cover; display: block; }
.jbw-vendor-head {
    display: flex; gap: 1.25rem; align-items: flex-end;
    margin-top: -3.5rem; position: relative; z-index: 1; padding: 0 1.5rem 1.25rem;
}
.jbw-vendor-head-avatar {
    width: 7rem; height: 7rem; border-radius: var(--r-btn);
    border: 4px solid var(--c-surface); object-fit: cover;
    box-shadow: var(--c-shadow-md);
}

/* ─── Auth pages ─────────────────────────────────────────────────── */
.jbw-body--guest { background: #f0eeea; }
.jbw-auth-main { min-height: 100vh; background: #f0eeea; position: relative; }

.jbw-dev-otp-badge {
    position: fixed; bottom: 1.25rem; left: 1.25rem; z-index: 70;
    background: var(--c-navy); color: #e2edf1;
    font-size: 0.8125rem; font-weight: 600;
    padding: 0.5625rem 1rem; border-radius: 999px;
    box-shadow: var(--c-shadow-lg); letter-spacing: 0.02em;
}

.jbw-auth-flash {
    position: fixed; top: 1rem; left: 50%;
    transform: translateX(-50%);
    width: min(420px, calc(100% - 2rem)); z-index: 60;
}

.jbw-auth-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── Left brand panel ── */
.jbw-auth-page-brand {
    position: relative;
    display: flex; align-items: center; justify-content: center;
    padding: 3rem 2rem;
    overflow: hidden;
    min-height: 38vh;
}

.jbw-auth-brand-bg {
    position: absolute; inset: 0;
    background-image: url('https://images.unsplash.com/photo-1540479859555-17af45c78602?w=1200&q=80&fit=crop');
    background-size: cover; background-position: center;
}

.jbw-auth-brand-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        135deg,
        rgb(26 47 56 / 0.9) 0%,
        rgb(26 47 56 / 0.75) 50%,
        rgb(0 0 0 / 0.88) 100%
    );
}

.jbw-auth-page-brand-inner {
    position: relative; z-index: 1;
    text-align: center;
    width: 100%; max-width: 28rem;
    display: flex; flex-direction: column; align-items: center; gap: 2rem;
}

.jbw-auth-brand-logo-link {
    display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
    text-decoration: none;
}

.jbw-auth-brand-svg {
    width: min(8rem, 40vw); height: auto; display: block;
    filter: drop-shadow(0 4px 16px rgb(0 0 0 / 0.3));
}

.jbw-auth-brand-name {
    font-weight: 800;
    font-size: clamp(1.125rem, 3vw, 1.5rem);
    color: #fff;
    letter-spacing: -0.02em;
}

.jbw-auth-brand-tagline { text-align: center; }

.jbw-auth-brand-quote {
    font-family: var(--font-serif);
    font-size: clamp(1.125rem, 2.5vw, 1.625rem);
    font-weight: 500;
    color: #fff;
    line-height: 1.4;
    margin: 0 0 0.75rem;
    letter-spacing: -0.01em;
}

.jbw-auth-brand-sub {
    font-size: 0.8125rem;
    color: rgb(255 255 255 / 0.55);
    margin: 0;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

/* legacy classes kept for backward compat */
.jbw-brand-lockup { display: flex; flex-direction: column; align-items: center; }
.jbw-brand-img { width: min(16rem, 80vw); height: auto; display: block; border-radius: 12px; }

/* ── Right form panel ── */
.jbw-auth-page-form {
    flex: 1; display: flex; align-items: center; justify-content: center;
    padding: 2rem 1.25rem 3rem; background: #f0eeea;
}

.jbw-auth-card {
    width: min(440px, 100%);
    background: var(--c-surface);
    border-radius: 24px;
    box-shadow: 0 2px 4px rgb(0 0 0 / 0.04), 0 20px 60px rgb(0 0 0 / 0.1);
    padding: 2.5rem 2.25rem 2.25rem;
    border: 1px solid var(--c-border);
}
.jbw-auth-card--centered .jbw-auth-title,
.jbw-auth-card--centered .jbw-auth-sub { text-align: center; }

.jbw-auth-title {
    font-family: var(--font-serif);
    font-size: clamp(1.5rem, 4vw, 1.875rem);
    font-weight: 600; line-height: 1.2;
    margin: 0 0 0.375rem; color: var(--c-text);
}
.jbw-auth-sub {
    color: #9ca3af; margin: 0 0 1.75rem;
    font-size: 0.9375rem; line-height: 1.5;
}

.jbw-input--auth {
    padding: 0.9375rem 1.0625rem;
    border-radius: 10px;
    font-size: 0.9375rem;
    border-color: #d8d5cf;
}

.jbw-btn--cta {
    border-radius: 10px;
    padding: 0.9375rem 1.5rem;
    font-size: 0.9rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    box-shadow: 0 8px 24px rgb(242 81 35 / 0.3);
}

.jbw-auth-divider {
    display: flex; align-items: center; gap: 1rem;
    margin: 1.5rem 0; color: #aaa; font-size: 0.8125rem;
}
.jbw-auth-divider::before,
.jbw-auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--c-border); }
.jbw-auth-divider span { white-space: nowrap; }

.jbw-btn--social {
    background: var(--c-surface);
    border: 1.5px solid var(--c-border);
    color: var(--c-text);
    border-radius: 999px;
    padding: 0.8125rem 1rem;
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    justify-content: center;
    gap: 0.625rem;
    transition: background var(--trans), border-color var(--trans);
}
.jbw-btn--social:hover:not(:disabled) { background: var(--c-bg); border-color: #bbb; }
.jbw-btn--social:disabled { opacity: 0.65; cursor: not-allowed; }

.jbw-social-icon {
    width: 1.125rem; height: 1.125rem;
    border-radius: 999px; display: inline-grid;
    place-items: center; font-size: 0.6875rem;
    font-weight: 800; flex-shrink: 0;
}
.jbw-social-icon--google { background: #fff; border: 1px solid #e0e0e0; color: #4285f4; }
.jbw-social-icon--apple { background: #111; color: #fff; font-size: 0.875rem; }

.jbw-auth-footer {
    text-align: center; margin-top: 1.5rem;
    font-size: 0.875rem; color: #9ca3af;
}
.jbw-auth-footer a { color: var(--c-primary); font-weight: 700; text-decoration: none; }
.jbw-auth-footer a:hover { text-decoration: underline; }

.jbw-auth-resend { text-align: center; margin-top: 1.25rem; font-size: 0.875rem; color: #9ca3af; }
.jbw-auth-resend a { color: #6b7280; text-decoration: none; margin-right: 0.375rem; }
.jbw-auth-timer { color: var(--c-primary); font-weight: 700; }

.jbw-otp-row {
    display: flex; justify-content: center; align-items: center;
    gap: clamp(0.75rem, 3vw, 1.125rem); margin: 0 0 1.625rem;
}
.jbw-otp-box {
    width: clamp(3.5rem, 15vw, 4.25rem);
    height: clamp(3.5rem, 15vw, 4.25rem);
    flex: 0 0 clamp(3.5rem, 15vw, 4.25rem);
    text-align: center; font-size: 1.5rem; font-weight: 700;
    border: 1.5px solid #d8d5cf; border-radius: 14px;
    background: var(--c-bg); color: var(--c-primary);
    font-family: var(--font-sans); padding: 0;
    transition: border-color var(--trans), box-shadow var(--trans), background var(--trans);
}
.jbw-otp-box:focus { outline: none; border-color: var(--c-primary); background: #fff; box-shadow: 0 0 0 4px rgb(242 81 35 / 0.1); }
.jbw-otp-box.is-filled { border-color: #f4a574; background: #fff; }
.jbw-form-stack--otp { gap: 0; }

@media (min-width: 960px) {
    .jbw-auth-page { flex-direction: row; align-items: stretch; }
    .jbw-auth-page-brand { flex: 1; min-height: 100vh; padding: 4rem 3rem; }
    .jbw-auth-page-brand-inner { max-width: 32rem; gap: 3rem; }
    .jbw-auth-brand-svg { width: 9rem; }
    .jbw-auth-page-form { flex: 1; min-height: 100vh; padding: 4rem 3rem; }
}
@media (max-width: 959px) {
    .jbw-auth-brand-tagline { display: none; }
}

/* ─── Breadcrumb ─────────────────────────────────────────────────── */
.jbw-breadcrumb { margin-bottom: 1.5rem; padding-top: 1.5rem; }
.jbw-breadcrumb-link {
    display: inline-flex; align-items: center; gap: 0.375rem;
    text-decoration: none; color: var(--c-muted);
    font-size: 0.875rem; font-weight: 600;
    transition: color var(--trans);
}
.jbw-breadcrumb-link:hover { color: var(--c-text); }

/* ─── Detail page ────────────────────────────────────────────────── */
.jbw-detail-price {
    font-size: 1.375rem; font-weight: 800;
    color: var(--c-primary); margin: 0.25rem 0 1rem;
}
.jbw-detail-desc {
    color: var(--c-muted); line-height: 1.75; margin: 0 0 1.25rem;
    font-size: 0.9375rem;
}

/* ─── Catalog empty state ────────────────────────────────────────── */
.jbw-catalog-empty {
    grid-column: 1 / -1;
    text-align: center; padding: 4rem 2rem;
    color: var(--c-muted);
    background: var(--c-surface); border-radius: var(--r-card);
    border: 1px solid var(--c-border);
}
.jbw-catalog-empty svg { margin: 0 auto 1rem; opacity: 0.4; }
.jbw-catalog-empty p { margin: 0 0 1.25rem; font-size: 0.9375rem; }

/* ─── Booking Overview ───────────────────────────────────────────── */
.jbw-overview-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 16px;
    padding: 1.375rem 1.5rem;
}
.jbw-overview-card--accent {
    background: var(--c-surface);
    border-color: var(--c-border);
}
.jbw-overview-card--sticky { position: sticky; top: 6rem; }

.jbw-overview-label {
    font-size: 0.6875rem; font-weight: 800;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--c-muted); margin: 0 0 1rem;
}

.jbw-overview-product {
    display: flex; gap: 1.125rem; align-items: flex-start;
}
.jbw-overview-img {
    width: 5.5rem; height: 5.5rem;
    border-radius: 12px; object-fit: cover;
    background: #f0ede8; flex-shrink: 0;
}
.jbw-overview-product-info { flex: 1; }
.jbw-overview-brand {
    font-size: 0.6875rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.08em;
    color: var(--c-muted); margin: 0;
}
.jbw-overview-title {
    font-weight: 700; font-size: 1.0625rem; margin: 0.25rem 0 0.25rem; line-height: 1.3;
}
.jbw-overview-cat { font-size: 0.8125rem; color: var(--c-muted); margin: 0 0 0.5rem; }
.jbw-overview-price {
    font-weight: 800; font-size: 1.0625rem; color: var(--c-primary); margin: 0;
}

.jbw-overview-dates-placeholder {
    display: flex; align-items: center; gap: 0.75rem;
    background: var(--c-bg); border-radius: 10px; padding: 0.875rem 1rem;
    border: 1.5px dashed var(--c-border); color: var(--c-muted);
}

.jbw-overview-add-address {
    display: flex; align-items: center; gap: 0.5rem;
    text-decoration: none; color: var(--c-primary);
    font-weight: 700; font-size: 0.875rem;
    background: #fef3ee; border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: background var(--trans);
}
.jbw-overview-add-address:hover { background: #fde5d7; }

/* ─── Pagination ─────────────────────────────────────────────────── */
.pagination { display: flex; flex-wrap: wrap; gap: 0.375rem; list-style: none; padding: 0; margin: 0; }
.pagination li a,
.pagination li span {
    display: inline-flex; min-width: 2.25rem; height: 2.25rem;
    align-items: center; justify-content: center; padding: 0 0.625rem;
    border-radius: 8px; border: 1px solid var(--c-border);
    text-decoration: none; color: var(--c-text); font-size: 0.875rem;
    transition: background var(--trans);
}
.pagination li a:hover { background: var(--c-bg); }
.pagination li.active span { background: var(--c-primary); border-color: var(--c-primary); color: #fff; }

/* ─── Designer profile hero ──────────────────────────────────────── */
.jbw-designer-detail-avatar {
    width: 5rem; height: 5rem;
    border-radius: var(--r-btn);
    object-fit: cover;
    border: 3px solid var(--c-surface);
    box-shadow: var(--c-shadow-md);
}

/* ══════════════════════════════════════════════════════════════════
   MOBILE NAV — full-width slide-down drawer
   ══════════════════════════════════════════════════════════════════ */
.jbw-mobile-nav {
    background: var(--c-surface);
    border-top: 1px solid var(--c-border);
    box-shadow: 0 8px 32px rgb(0 0 0 / 0.12);
    padding: 0.75rem 0 1.25rem;
}
.jbw-mobile-nav-links {
    display: grid;
    padding: 0 0.75rem;
}
.jbw-mobile-nav-user {
    border-top: 1px solid var(--c-border);
    margin-top: 0.5rem;
    padding: 0.75rem 0.75rem 0;
    display: grid;
    gap: 0.25rem;
}
.jbw-mnav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0.875rem;
    text-decoration: none;
    color: var(--c-text);
    font-size: 0.9375rem;
    font-weight: 600;
    border-radius: 10px;
    border: 0;
    background: none;
    cursor: pointer;
    font-family: var(--font-sans);
    transition: background var(--trans), color var(--trans);
}
.jbw-mnav-link:hover { background: var(--c-bg); }
.jbw-mnav-link.is-active { color: var(--c-primary); background: #fef3ee; }
.jbw-mnav-link--danger { color: #dc2626; }
.jbw-mnav-link--danger:hover { background: #fef2f2; }

/* Alpine transition helpers */
.jbw-mnav-enter { transition: opacity 0.18s ease, transform 0.18s ease; }
.jbw-mnav-enter-start { opacity: 0; transform: translateY(-6px); }
.jbw-mnav-enter-end { opacity: 1; transform: translateY(0); }

@media (min-width: 900px) {
    .jbw-mobile-nav { display: none !important; }
}

/* ══════════════════════════════════════════════════════════════════
   CATALOG — mobile filter toggle & layout fixes
   ══════════════════════════════════════════════════════════════════ */
.jbw-filter-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--c-surface);
    border: 1.5px solid var(--c-border);
    border-radius: 10px;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 700;
    font-family: var(--font-sans);
    color: var(--c-text);
    cursor: pointer;
    transition: border-color var(--trans), background var(--trans);
    margin-bottom: 1rem;
}
.jbw-filter-toggle:hover { border-color: var(--c-primary); }
.jbw-filter-badge {
    background: var(--c-primary);
    color: #fff;
    font-size: 0.625rem;
    font-weight: 800;
    border-radius: 999px;
    width: 1.125rem; height: 1.125rem;
    display: inline-grid;
    place-items: center;
}

/* on desktop, never show filter toggle */
@media (min-width: 900px) {
    .jbw-filter-toggle { display: none; }
    .jbw-filters { display: block !important; }
}

/* on mobile, filter sidebar is hidden by default */
@media (max-width: 899px) {
    .jbw-catalog-layout { display: block; }
    .jbw-filters {
        display: none;
        margin-bottom: 1.25rem;
    }
    .jbw-filters.is-open { display: block; }
    .jbw-product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.875rem;
    }
}

.jbw-catalog-results { min-width: 0; }
.jbw-catalog-count {
    font-size: 0.875rem;
    color: var(--c-muted);
    margin-bottom: 1rem;
}

/* Product card image wrapper */
.jbw-product-card-img {
    width: 100%;
    aspect-ratio: 3/4;
    overflow: hidden;
    background: #f0ede8;
}
.jbw-product-card-img img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}
.jbw-product-card:hover .jbw-product-card-img img { transform: scale(1.04); }

/* ══════════════════════════════════════════════════════════════════
   GLOBAL RESPONSIVE FIXES
   ══════════════════════════════════════════════════════════════════ */

/* Container gets tighter padding on small screens */
@media (max-width: 480px) {
    .jbw-container { width: min(100%, 100% - 1.5rem); }
}

/* Hero - smaller on mobile */
@media (max-width: 640px) {
    .jbw-hero { min-height: min(85vh, 560px); }
    .jbw-hero-content-wrap { min-height: min(85vh, 560px); }
    .jbw-hero-content { padding: 2rem 0; }
    .jbw-hero-title { font-size: clamp(1.875rem, 9vw, 2.5rem); margin-bottom: 0.875rem; }
    .jbw-hero-text { font-size: 0.9375rem; margin-bottom: 1.5rem; }
    .jbw-hero-actions { gap: 0.75rem; }
    .jbw-hero-actions .jbw-btn--lg { padding: 0.875rem 1.25rem; font-size: 0.9375rem; }
    .jbw-hero-scroll { display: none; }
}

/* Section bands — less padding on mobile */
@media (max-width: 768px) {
    .jbw-section-band { padding: 3rem 0; }
    .jbw-section-band--cta { padding: 3.5rem 0; }
    .jbw-section-head { margin-bottom: 1.75rem; }
    .jbw-section-title { font-size: clamp(1.375rem, 5vw, 1.875rem); }
    .jbw-steps { gap: 1.25rem; }
    .jbw-cta-actions { gap: 0.75rem; }
    .jbw-cta-actions .jbw-btn { width: 100%; justify-content: center; }
}

/* Tile grid — single column on very small screens */
@media (max-width: 479px) {
    .jbw-grid-3 { grid-template-columns: 1fr !important; }
    .jbw-tile { min-height: 18rem; }
}

/* Stats strip mobile */
@media (max-width: 480px) {
    .jbw-stats-strip { padding: 1rem 0; }
    .jbw-stat { padding: 0.5rem 0.875rem; }
}

/* Product detail - mobile layout fix */
@media (max-width: 599px) {
    .jbw-detail-actions { flex-direction: column; }
    .jbw-detail-actions .jbw-btn { width: 100%; justify-content: center; }
}

/* Booking overview - stacks sidebar below main on mobile */
@media (max-width: 1023px) {
    .jbw-booking-layout { grid-template-columns: 1fr; }
    .jbw-overview-card--sticky { position: static; }
}

/* Booking row - 2-col on small screens */
@media (max-width: 500px) {
    .jbw-booking-row {
        grid-template-columns: 4rem 1fr;
        grid-template-rows: auto auto;
    }
    .jbw-booking-row img { width: 4rem; height: 4rem; }
    .jbw-booking-row > div:last-child {
        grid-column: 1 / -1;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
}

/* Profile shell - mobile stacks */
@media (max-width: 899px) {
    .jbw-profile-shell { grid-template-columns: 1fr; }
    .jbw-profile-sidebar { position: static; }
}

/* Auth page - section sizes on mobile */
@media (max-width: 479px) {
    .jbw-auth-card { padding: 1.75rem 1.25rem 1.5rem; border-radius: 16px; }
    .jbw-auth-title { font-size: 1.375rem; }
    .jbw-otp-box {
        width: clamp(3rem, 18vw, 3.75rem);
        height: clamp(3rem, 18vw, 3.75rem);
        font-size: 1.25rem;
    }
}

/* Footer responsive */
@media (max-width: 479px) {
    .jbw-footer { padding-top: 2.5rem; margin-top: 2.5rem; }
    .jbw-footer-grid { gap: 2rem; }
    .jbw-footer-about { max-width: 100%; }
    .jbw-footer-bottom { flex-direction: column; text-align: center; gap: 0.375rem; }
}

/* Page heads on mobile */
@media (max-width: 640px) {
    .jbw-page-head { padding-top: 1.5rem; margin-bottom: 1.25rem; }
    .jbw-page-title { font-size: 1.5rem; }
    .jbw-page-subtitle { font-size: 0.875rem; }
    .jbw-card { padding: 1.125rem; }
    .jbw-overview-card { padding: 1.125rem; }
}

/* Vendor page hero mobile */
@media (max-width: 640px) {
    .jbw-vendor-hero img { height: 10rem; }
    .jbw-vendor-hero { margin-bottom: 3rem; }
    .jbw-vendor-head { padding: 0 0.875rem 1rem; gap: 0.875rem; }
    .jbw-vendor-head-avatar { width: 5rem; height: 5rem; }
}

/* Section band paddings on desktop */
@media (min-width: 1200px) {
    .jbw-section-band { padding: 5.5rem 0; }
}

/* Product grid on wide screens */
@media (min-width: 1100px) {
    .jbw-product-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
}

/* Smooth scroll and selection */
html { scroll-behavior: smooth; }
::selection { background: rgb(242 81 35 / 0.15); }
</style>