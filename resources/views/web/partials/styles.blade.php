<style>
/* ─── Design tokens ───────────────────────────────────────────────── */
:root {
    --c-primary:    #E95433;
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
    background: rgb(255 255 255 / 0.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgb(229 217 206);
    box-shadow: 0 1px 0 rgb(0 0 0 / 0.03);
}

.jbw-header-inner {
    display: grid;
    grid-template-columns: minmax(0, auto) 1fr minmax(0, auto);
    align-items: center;
    gap: 1.25rem;
    min-height: 4rem;
    height: 4.500rem;
    padding-block: 0.375rem;
}

.jbw-logo-link {
    flex-shrink: 0;
    text-decoration: none;
    color: inherit;
    display: inline-flex;
    align-items: center;
    justify-self: start;
    line-height: 0;
    max-width: 12.5rem;
}

.jbw-logo {
    display: inline-flex;
    align-items: center;
    gap: 0.625rem;
    min-width: 0;
}

.jbw-logo-media {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    overflow: hidden;
    line-height: 0;
}

.jbw-logo-media--header {
    height: 2.875rem;
    max-width: 11.5rem;
}

.jbw-logo--image .jbw-logo-media--header {
    background: var(--c-navy);
    border-radius: 10px;
    padding: 0.3125rem 0.75rem;
    box-shadow: 0 1px 3px rgb(26 47 56 / 0.12);
}

.jbw-logo-media--footer {
    height: 3.25rem;
    max-width: 13rem;
}

.jbw-logo-media--auth {
    height: 4.75rem;
    max-width: 15rem;
}

.jbw-logo-media--mark {
    height: 2.5rem;
    max-width: 10rem;
}

.jbw-logo-image {
    display: block;
    height: 100%;
    width: auto;
    max-width: 100%;
    object-fit: contain;
    object-position: left center;
}

.jbw-logo--image {
    gap: 0;
}

.jbw-logo--fallback {
    gap: 0.625rem;
}

.jbw-logo-mark {
    width: 2.25rem;
    height: 2.25rem;
    flex-shrink: 0;
    display: block;
}

.jbw-logo-text {
    font-weight: 800;
    font-size: 1.0625rem;
    letter-spacing: -0.03em;
    line-height: 1;
    white-space: nowrap;
}

.jbw-logo-text-accent {
    font-weight: 800;
}

.jbw-logo--header .jbw-logo-mark {
    width: 2.125rem;
    height: 2.125rem;
}

.jbw-logo--footer .jbw-logo-mark {
    width: 2.375rem;
    height: 2.375rem;
}

.jbw-logo--auth {
    flex-direction: column;
    gap: 0.875rem;
}

.jbw-logo--auth .jbw-logo-mark {
    width: 3.75rem;
    height: 3.75rem;
}

.jbw-logo--auth .jbw-logo-text {
    font-size: clamp(1.125rem, 3vw, 1.375rem);
}

.jbw-auth-brand-logo-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
}

/* legacy aliases */
.jbw-logo-svg,
.jbw-logo-wordmark,
.jbw-logo-img,
.jbw-footer-logo-img {
    display: none;
}

.jbw-nav {
    display: none;
    align-items: center;
    justify-content: center;
    gap: 0.125rem;
    flex: 1;
}

.jbw-nav-link {
    text-decoration: none;
    color: var(--c-muted);
    font-size: 0.975rem;
    font-weight: 600;
    padding: 0.3rem 0.875rem;
    border-radius: var(--r-btn);
    transition: color var(--trans), background var(--trans);
}
/* .jbw-nav-link:hover { color: var(--c-text); background: var(--c-bg); } */
/* .jbw-nav-link:hover {
    color: var(--c-primary);
    background: transparent;
    box-shadow: none;
    border-bottom: 2px solid var(--c-primary);
    padding-bottom: 7px;
    border-radius: 0 !important;
} */
    .jbw-nav-link:hover,
.jbw-nav-link:hover,
.jbw-nav-link.is-active {
    color: var(--c-primary);
    background: transparent;
    box-shadow: none;
    text-decoration: underline;
    text-decoration-color: var(--c-primary);
    text-underline-offset: 8px;
    text-decoration-thickness: 2px;
}

/* .jbw-nav-link.is-active {
    color: #fff;
    background: var(--c-primary);
    box-shadow: 0 4px 14px rgb(242 81 35 / 0.28);
} */

/* .jbw-nav-link.is-active {
    color: var(--c-primary);
    background: transparent;
    box-shadow: none;
    border-bottom: 2px solid var(--c-primary);
    border-radius: 0;
} */

.jbw-header-tools {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

.jbw-header-search {
    display: none;
    align-items: center;
    border: 1px solid var(--c-border);
    border-radius: var(--r-btn);
    background: var(--c-surface);
    overflow: hidden;
}
.jbw-header-search-input {
    border: 0;
    background: transparent;
    padding: 0.5rem 0.75rem;
    font: inherit;
    font-size: 0.8125rem;
    width: 9rem;
    color: var(--c-text);
    outline: none;
}
.headerinputradius {
border-radius: 8px !important;
}

.bordercolor {
    border: 1px solid rgb(242 81 35 / 0.35) !important;
}
.jbw-header-search .jbw-icon-btn {
    border: 0;
    border-radius: 0;
    width: 2.25rem;
    height: 2.25rem;
}

@media (min-width: 900px) {
    .jbw-header-search { display: flex; }
}

.jbw-location-picker { position: relative; }

.jbw-location-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    border: 1px solid var(--c-border);
    background: var(--c-bg);
    border-radius: var(--r-btn);
    padding: 0.375rem;
    font-size: 0.8rem;
    font-family: var(--font-sans);
    color: var(--c-muted);
    cursor: pointer;
    transition: border-color var(--trans), background var(--trans);
}
.jbw-location-btn:hover {
    border-color: rgb(242 81 35 / 0.35);
    background: #fff;
}
.jbw-location-btn-label {
    display: none;
    max-width: 9rem;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.jbw-location-panel {
    position: absolute;
    right: 0;
    top: calc(100% + 0.625rem);
    width: min(22rem, calc(100vw - 2rem));
    max-height: min(28rem, calc(100vh - 6rem));
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    box-shadow: var(--c-shadow-lg);
    z-index: 60;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.jbw-location-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem 0.5rem;
    border-bottom: 1px solid var(--c-border);
}
.jbw-location-panel-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 800;
}
.jbw-location-panel-close {
    border: 0;
    background: none;
    font-size: 1.25rem;
    line-height: 1;
    color: var(--c-muted);
    cursor: pointer;
    padding: 0.125rem 0.375rem;
}
.jbw-location-search-wrap { padding: 0.75rem 1rem; }
.jbw-location-search { font-size: 0.8125rem; padding: 0.5rem 0.75rem; }
.jbw-location-section { padding: 0 1rem 0.75rem; }
.jbw-location-section--scroll {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 1rem;
}
.jbw-location-section-title {
    margin: 0 0 0.5rem;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--c-muted);
}
.jbw-location-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}
.jbw-location-chip {
    border: 1px solid var(--c-border);
    background: var(--c-bg);
    border-radius: 999px;
    padding: 0.35rem 0.75rem;
    font: inherit;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    color: var(--c-text);
    transition: background var(--trans), border-color var(--trans);
}
.jbw-location-chip:hover,
.jbw-location-chip.is-active {
    border-color: var(--c-primary);
    background: rgb(242 81 35 / 0.08);
    color: var(--c-primary);
}
.jbw-location-options { display: grid; gap: 0.25rem; }
.jbw-location-option-form { margin: 0; }
.jbw-location-option {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    background: none;
    border-radius: 10px;
    padding: 0.625rem 0.75rem;
    cursor: pointer;
    font: inherit;
    transition: background var(--trans);
}
.jbw-location-option:hover,
.jbw-location-option.is-active {
    background: rgb(242 81 35 / 0.08);
}
.jbw-location-option-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--c-text);
}
.jbw-location-option-meta {
    display: block;
    margin-top: 0.125rem;
    font-size: 0.75rem;
    color: var(--c-muted);
}

/* ─── Notifications ───────────────────────────────────────────────── */
.jbw-notification-picker { position: relative; }
.marginnotificationicon {
    margin-top: 5px;
}
.jbw-notification-btn { position: relative; }
.jbw-notification-badge {
    position: absolute;
    top: -0.2rem;
    right: -0.2rem;
    min-width: 1.1rem;
    height: 1.1rem;
    padding: 0 0.25rem;
    border-radius: 999px;
    background: var(--c-primary);
    color: #fff;
    font-size: 0.625rem;
    font-weight: 800;
    display: grid;
    place-items: center;
    line-height: 1;
    border: 2px solid var(--c-surface);
}
.jbw-notification-panel {
    position: absolute;
    right: 0;
    top: calc(100% + 0.625rem);
    width: min(22rem, calc(100vw - 2rem));
    max-height: min(26rem, calc(100vh - 6rem));
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    box-shadow: var(--c-shadow-lg);
    z-index: 60;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.jbw-notification-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--c-border);
}
.jbw-notification-panel-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 800;
}
.jbw-notification-mark-all {
    border: 0;
    background: none;
    font: inherit;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--c-primary);
    cursor: pointer;
    padding: 0;
}
.jbw-notification-list {
    flex: 1;
    overflow-y: auto;
}
.jbw-notification-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--c-border);
}
.jbw-notification-item.is-unread {
    background: rgb(242 81 35 / 0.04);
}
.jbw-notification-item-title {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--c-text);
}
.jbw-notification-item-message {
    margin: 0.25rem 0 0;
    font-size: 0.75rem;
    color: var(--c-muted);
    line-height: 1.45;
}
.jbw-notification-item-time {
    margin: 0.35rem 0 0;
    font-size: 0.6875rem;
    color: var(--c-muted);
}
.jbw-notification-dot {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 999px;
    border: 0;
    background: var(--c-primary);
    cursor: pointer;
    flex-shrink: 0;
    margin-top: 0.35rem;
}
.jbw-notification-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--c-muted);
    font-size: 0.875rem;
}
.jbw-notification-panel-foot {
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--c-border);
    text-align: center;
}
.jbw-notification-view-all {
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--c-primary);
    text-decoration: none;
}
.jbw-notification-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--c-border);
}
.jbw-notification-row.is-unread {
    background: rgb(242 81 35 / 0.04);
}
.jbw-notification-row-title {
    margin: 0;
    font-weight: 700;
    font-size: 0.9375rem;
}
.jbw-notification-row-message {
    margin: 0.35rem 0 0;
    color: var(--c-muted);
    line-height: 1.55;
    font-size: 0.875rem;
}
.jbw-notification-row-time {
    margin: 0.5rem 0 0;
    font-size: 0.75rem;
    color: var(--c-muted);
}
@media (max-width: 899px) {
    .jbw-notification-panel {
        position: fixed;
        right: 1rem;
        left: 1rem;
        width: auto;
        top: 4.25rem;
    }
}

@media (max-width: 899px) {
    .jbw-location-panel {
        position: fixed;
        right: 1rem;
        left: 1rem;
        width: auto;
        top: 4.25rem;
    }
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

@media (max-width: 899px) {
    .jbw-header-inner {
        gap: 0.75rem;
        min-height: 3.75rem;
        height: 3.75rem;
    }

    .jbw-logo-link {
        max-width: 9.5rem;
    }

    .jbw-logo-media--header {
        height: 2.5rem;
        max-width: 9rem;
    }

    .jbw-logo--header .jbw-logo-mark {
        width: 2rem;
        height: 2rem;
    }

    .jbw-logo--fallback .jbw-logo-text {
        font-size: 0.9375rem;
    }
}

@media (min-width: 900px) {
    .jbw-nav { display: flex; }
    .jbw-location-btn { padding: 0.58rem 0.875rem; max-width: 12rem; }
    .jbw-location-btn-label { display: inline; }
    .jbw-mobile-toggle { display: none; }
}

/* ─── Full-width section bands ────────────────────────────────────── */
.jbw-section-band {
    padding: 5rem 0;
}

.jbw-section-band--warm {
    background: linear-gradient(135deg, #fdf6f0 0%, #fef9f5 40%, #f5f0ea 100%);
    /* border-top: 1px solid #ede8e0;
    border-bottom: 1px solid #ede8e0; */
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
    padding: 3rem 0 !important;
    border-radius: 10px;

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
    margin-top: 15px;
    background: var(--c-surface);
    border-bottom: 1px solid var(--c-border);
    padding: 1.35rem 0;
    box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.8);
    border-radius: 8px;
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
    margin-top: 0rem;
}

.jbw-footer-grid {
    display: grid;
    gap: 2.5rem;
        padding-bottom: 1rem;
    /* padding-bottom: 3rem; */
}

.backgroundborder {
    border: none !important;
    background: none !important;
}
.jbw-footer-logo-link { display: inline-block; text-decoration: none; margin-bottom: 0.25rem; }

.jbw-footer-about {
    margin-top: 1rem;
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
    padding: 0.25rem 0 1.75rem;
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

.fontsize {
    font-size: 20px !important;
}

/* ─── Buttons ─────────────────────────────────────────────────────── */
.jbw-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
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

.jbw-measure-form-grids {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 768px) {
    .jbw-measure-form-grids {
        grid-template-columns: 1fr !important;
    }
}
.jbw-input.is-invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgb(239 68 68 / 0.08);
}

.jbw-field + .jbw-field { margin-top: 0rem; }
.jbw-form-stack { display: grid; gap: 1rem; }
.jbw-form-stack--tight { margin-top: 0.25rem; }
.jbw-textarea { resize: vertical; min-height: 5rem; }

.jbw-field-error,
.jbw-field-hint {
    margin: 0.375rem 0 0;
    font-size: 0.8125rem;
    color: #dc2626;
}

/* ─── Toast pop-ups ───────────────────────────────────────────────── */
.jbw-toast-stack {
    position: fixed;
    top: 5.25rem;
    right: clamp(0.75rem, 3vw, 1.5rem);
    z-index: 10050;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    width: min(22rem, calc(100vw - 1.5rem));
    pointer-events: none;
}
.jbw-toast {
    pointer-events: auto;
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.85rem 0.9rem 0.85rem 1rem;
    border-radius: 14px;
    border: 1px solid transparent;
    box-shadow: 0 14px 40px rgb(15 23 42 / 0.16);
    animation: jbw-toast-in 0.38s cubic-bezier(0.22, 1, 0.36, 1);
    backdrop-filter: blur(8px);
}
.jbw-toast.is-leaving {
    animation: jbw-toast-out 0.32s ease forwards;
}
@keyframes jbw-toast-in {
    from { opacity: 0; transform: translateX(1.25rem) scale(0.96); }
    to { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes jbw-toast-out {
    from { opacity: 1; transform: translateX(0) scale(1); }
    to { opacity: 0; transform: translateX(1rem) scale(0.96); }
}
.jbw-toast-icon {
    flex-shrink: 0;
    width: 1.35rem;
    height: 1.35rem;
    border-radius: 999px;
    display: grid;
    place-items: center;
    font-size: 0.75rem;
    font-weight: 800;
    line-height: 1;
    margin-top: 0.05rem;
}
.jbw-toast-text {
    flex: 1;
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.45;
}
.jbw-toast-list {
    margin: 0;
    padding-left: 1rem;
}
.jbw-toast-close {
    flex-shrink: 0;
    border: none;
    background: transparent;
    color: inherit;
    opacity: 0.55;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    padding: 0;
    margin: -0.1rem -0.1rem 0 0;
}
.jbw-toast-close:hover { opacity: 1; }
.jbw-toast--success {
    background: rgb(236 253 245 / 0.97);
    color: #047857;
    border-color: #a7f3d0;
}
.jbw-toast--success .jbw-toast-icon {
    background: #d1fae5;
    color: #047857;
}
.jbw-toast--error {
    background: rgb(254 242 242 / 0.97);
    color: #b91c1c;
    border-color: #fecaca;
}
.jbw-toast--error .jbw-toast-icon {
    background: #fee2e2;
    color: #b91c1c;
}
.jbw-toast--info {
    background: rgb(239 246 255 / 0.97);
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.jbw-toast--info .jbw-toast-icon {
    background: #dbeafe;
    color: #1d4ed8;
}
@media (max-width: 640px) {
    .jbw-toast-stack {
        top: auto;
        bottom: 1rem;
        right: 0.75rem;
        left: 0.75rem;
        width: auto;
    }
    @keyframes jbw-toast-in {
        from { opacity: 0; transform: translateY(1rem) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes jbw-toast-out {
        from { opacity: 1; transform: translateY(0) scale(1); }
        to { opacity: 0; transform: translateY(0.75rem) scale(0.96); }
    }
}

/* ─── Alerts (legacy inline) ─────────────────────────────────────── */
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
    padding: 1.0rem;
}


.jbw-faq-question {
    position: relative;
    cursor: pointer;
    list-style: none;
    padding-right: 32px;
    font-weight: 600;
}

.jbw-faq-question::-webkit-details-marker {
    display: none;
}

.jbw-faq-question::after {
    content: "";
    position: absolute;
    right: 10px;
    top: 50%;
    width: 10px;
    height: 10px;
    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: translateY(-70%) rotate(45deg);
    transition: transform 0.25s ease;
}

.jbw-faq-item[open] .jbw-faq-question::after {
    transform: translateY(-30%) rotate(-135deg);
}
.jbw-faq-list { display: grid; gap: 0.75rem; }
.jbw-faq-item { padding: 0; overflow: hidden; }
.jbw-faq-question {
    cursor: pointer;
    font-weight: 700;
    padding: 1.25rem 1.5rem;
    list-style: none;
}
.jbw-faq-question::-webkit-details-marker { display: none; }
.jbw-faq-answer {
    padding: 0 1.5rem 1.25rem;
    color: var(--c-muted);
    line-height: 1.65;
    border-top: 1px solid var(--c-border);
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

.paddingtop {
    padding-top: 0rem !important;
    margin-bottom: 0.5rem !important;
}
/* .jbw-page-head { margin-bottom: 1.5rem; padding-top: 1rem; } */
.jbw-page-head { margin-bottom: 1.5rem; padding-top: 0rem; }
.jbw-page-title { font-family: var(--font-serif); font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 600; margin: 0; }
.jbw-page-subtitle { color: var(--c-muted); margin: 0.375rem 0 0; font-size: 0.9375rem; }
.jbw-back-link { text-decoration: none; color: var(--c-text); font-weight: 700; }

/* ─── Hero ────────────────────────────────────────────────────────── */
.jbw-hero {
    position: relative;
    overflow: hidden;
    height: clamp(420px, 58vh, 560px);
    min-height: 420px;
    background: #111;
}

.jbw-hero-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 0.8s ease;
    z-index: 0;
}

.jbw-hero-slide.is-active {
    opacity: 1;
    z-index: 1;
}

.jbw-hero-slide-img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.jbw-hero-slides {
    position: absolute;
    inset: 0;
}

.jbw-hero-content-stack {
    position: relative;
    z-index: 2;
    height: 100%;
}

.jbw-hero-content-panel {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.45s ease;
}

.jbw-hero-content-panel.is-active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.jbw-hero-nav {
    position: absolute;
    inset: 0;
    z-index: 3;
    pointer-events: none;
}

.jbw-hero-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: auto;
    width: 2.75rem;
    height: 2.75rem;
    border: none;
    border-radius: 9999px;
    background: rgb(255 255 255 / 0.88);
    color: #111;
    font-size: 1.125rem;
    cursor: pointer;
    box-shadow: 0 8px 24px rgb(0 0 0 / 0.18);
}

.jbw-hero-arrow--prev { left: 1rem; }
.jbw-hero-arrow--next { right: 1rem; }

.jbw-hero-dots {
    position: absolute;
    left: 50%;
    bottom: 1.25rem;
    transform: translateX(-50%);
    z-index: 3;
    display: flex;
    gap: 0.5rem;
}

.jbw-hero-dot {
    width: 0.625rem;
    height: 0.625rem;
    border: none;
    border-radius: 9999px;
    background: rgb(255 255 255 / 0.45);
    cursor: pointer;
    padding: 0;
}

.jbw-hero-dot.is-active {
    background: #fff;
    transform: scale(1.15);
}

.alignmentheading {
    text-align: start !important;
    margin-bottom: 20px;
}
.jbw-hero:hover .jbw-hero-slide { transform: scale(1); }

.jbw-hero-overlay {
    position: absolute; inset: 0;
    /* background: linear-gradient(
        115deg,
        rgb(0 0 0 / 0.78) 0%,
        rgb(0 0 0 / 0.52) 42%,
        rgb(0 0 0 / 0.18) 100%
    ); */
}

.jbw-hero-content-wrap {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    align-items: center;
}

.jbw-hero-content {
    padding: clamp(1.25rem, 3vw, 2.5rem);
    color: #fff;
    background: rgba(0, 0, 0, 0.45);
    display: inline-block;
    border-radius: 5px;
    max-width: min(100%, 36rem);
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
    font-size: clamp(2.5rem, 6vw, 3.5rem);
    font-weight: 600;
    line-height: 1.05;
    margin: 0 0 1.25rem;
    color: #fff;
    letter-spacing: -0.02em;
}

.jbw-hero-text {
    color: rgb(255 255 255 / 0.75);
    font-size: 1.0125rem;
    line-height: 1.75;
    margin-bottom: 2rem;
    max-width: 30rem;
}

.jbw-hero-actions { display: flex; gap: 0.875rem; flex-wrap: wrap; align-items: center; }

.jbw-btn--hero-secondary {
    background: rgb(255 255 255 / 0.12);
    color: #fff;
    border: 1.5px solid rgb(255 255 255 / 0.35);
    backdrop-filter: blur(6px);
}
.jbw-btn--hero-secondary:hover {
    background: rgb(255 255 255 / 0.2);
    border-color: rgb(255 255 255 / 0.55);
}

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

.textalign {
    margin-top: 10px;
    text-align: center !important;
    text-decoration: none !important;
    border-bottom: none !important;
}


@media (min-width: 768px) { .jbw-steps { grid-template-columns: repeat(3, 1fr); } }

/* ─── Grid tiles (services / categories) ─────────────────────────── */
.jbw-grid-3 { display: grid; gap: 1.25rem; margin-bottom: 1rem; }
@media (min-width: 640px) { .jbw-grid-3 { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 960px) { .jbw-grid-3 { grid-template-columns: repeat(3, 1fr); } }

.jbw-tile {
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    min-height: 23rem;
    display: block;
    text-decoration: none;
    color: #fff;
    box-shadow: var(--c-shadow-md);
    /* transition: transform 0.28s ease, box-shadow 0.28s ease; */
}

/* .jbw-tile:hover { transform: translateY(-5px); box-shadow: var(--c-shadow-lg); } */

.jbw-tile img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}
.jbw-tile:hover img { transform: scale(1.06); }

.textlimit {
    display: block;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
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


/* price rnge */
.jbw-price-range{
    position: relative;
    height: 4px;
    background: #ddd;
    border-radius: 10px;
    margin: 20px 0 10px;
}





.jbw-price-thumb-left{
    left: 20%;
}

.jbw-price-thumb-right{
    left: 80%;
}

.jbw-price-labels{
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #666;
}
.borderradius {
    border-radius: 30px;
    height: 45px;
    margin-bottom: 15px;
}

.namespace {
    margin-top: 0px;
    margin-bottom: 0px;
}
/* categoryslider */
.category-wrapper {
    position: relative;
}

.category-nav {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-bottom: 20px;
}

.category-slider-wrapper {
    position: relative;
    overflow: hidden;
}

.category-slider {
    display: flex;
    gap: 1.25rem;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 0.25rem;
}

.category-slider::-webkit-scrollbar {
    display: none;
}

.category-card {
    flex: 0 0 clamp(220px, 28vw, 300px);
    min-width: clamp(220px, 28vw, 300px);
    text-decoration: none;
    color: inherit;
}

.jbw-tile--category {
    aspect-ratio: 4 / 5;
    min-height: 0;
    width: 100%;
}

.jbw-tile--category img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.category-card .jbw-step-title {
    margin-top: 0.75rem;
}

.category-arrow {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 50%;
    background: rgb(242, 81, 35);
    color: #fff;
    cursor: pointer;
    font-size: 18px;
}
.service-slider-wrapper {
    position: relative;
}

.service-nav {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-bottom: 20px;
}

/* .service-slider {
    display: flex;
    gap: 20px;
    overflow: hidden;
    scroll-behavior: smooth;
} */

    .service-slider {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.service-slider::-webkit-scrollbar {
    display: none;
}
.service-card {
    min-width: 306px;
    flex-shrink: 0;
    text-decoration: none;
}

.service-arrow {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 50%;
    background: rgb(242, 81, 35);
    color: #fff;
    font-size: 18px;
    cursor: pointer;
}
/* our service modal */

/* Modal Panel Wrapper Mask Layer */
.jbw-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
}

/* Modal Content Card matching image_64c9e4.png configuration */
.jbw-modal-content {
    background-color: #ffffff;
    border-radius: 20px;
    width: 90%;
    max-width: 820px;
    padding: 3.0rem 2.0rem 2.0rem 2.0rem;
    position: relative;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Top Right Close Button Icon Wrapper */
.jbw-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: none;
    border: 1px solid #cbd5e1;
    font-size: 1.5rem;
    line-height: 1;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.jbw-modal-close:hover {
    background-color: #f1f5f9;
    color: #000;
}

/* Three Columns Layout Engine Grid */
.jbw-modal-options-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0rem;
    text-align: center;
}

.jbw-modal-option {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    group: hover;
}

/* Perfect Circular Image Frames */
.jbw-modal-circle-thumb {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.jbw-modal-circle-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Interactive Hover Feedback Scaling Animations */
.jbw-modal-option:hover .jbw-modal-circle-thumb {
    transform: scale(1.04);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Captions Styles */
.jbw-modal-option h3 {
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #1e293b;
    letter-spacing: 0.05em;
    margin: 0;
}

/* Responsive Scaling Overrides for Smaller Mobile viewports */
@media (max-width: 640px) {
    /* Main Content Card scaling adjustments */
    .jbw-modal-content {
        padding: 2.5rem 1.5rem 2rem 1.5rem;
        width: 85%;
        max-height: 90vh;
        overflow-y: auto; /* Fallback for exceptionally small screens */
    }

    /* Change grid from horizontal columns into a stacked vertical list */
    .jbw-modal-options-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    /* Keep items centered in a vertical row layout or flex align */
    .jbw-modal-option {
        flex-direction: row; /* Switch layout inline for scannability, or keep 'column' to match desktop */
        justify-content: flex-start;
        align-items: center;
        gap: 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 0.75rem;
        width: 100%;
    }

    /* Remove border highlight from the final list option */
    .jbw-modal-option:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    /* Resize circle thumbnails smoothly for mobile finger taps */
    .jbw-modal-circle-thumb {
        width: 70px;
        height: 70px;
        margin-bottom: 0; /* Clear bottom margin since text sits next to it now */
    }

    /* Typography size enhancements for mobile scanning */
    .jbw-modal-option h3 {
        font-size: 1.1rem;
        letter-spacing: 0.03em;
        text-align: left;
    }
}

/* ─── Featured designers row ─────────────────────────────────────── */


.designer-slider {
    overflow: hidden;
    width: 100%;
}

.designer-track {
    display: flex;
    gap: 24px;
    width: max-content;
    animation: scrollDesigners 25s linear infinite;
}

.designer-slider:hover .designer-track {
    animation-play-state: paused;
}

@keyframes scrollDesigners {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-50%);
    }
}
.designer-carousel {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
}

.designer-slider {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    flex: 1;
}

.designer-slider::-webkit-scrollbar {
    display: none;
}



.designers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.designer-nav {
    display: flex;
    gap: 10px;
}

.designer-arrow {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 50%;
    /* background: rgb(242, 81, 35); */
    color: rgb(242, 81, 35);
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.designer-arrow:hover {
    opacity: 0.9;
}
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
    width: 8rem;
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
.jbw-product-detail { display: grid; gap: 2.5rem; margin-bottom: 1rem; }
.jbw-gallery-main { border-radius: 20px; overflow: hidden; background: #f0ede8; }
.jbw-gallery-main img { width: 100%; display: block; aspect-ratio: 1/; object-fit: cover; }
.jbw-product-detail-title { font-family: var(--font-serif); font-size: clamp(1.75rem, 3vw, 2.5rem); font-weight: 600; margin: 0 0 0.625rem; }

.jbw-gallery-wrap{
    display:flex;
    gap:1rem;
    align-items:flex-start;
}

.jbw-gallery-thumbs{
    display:flex;
    flex-direction:column;
    gap:0.5rem;

    max-height:450px;
    overflow-y:auto;
    padding-right:4px;
}
@media (max-width: 767px) {
    .jbw-gallery-thumbs {
        max-height: 200px; /* adjust as needed */
    }
    .jbw-breadcrumb {
         padding-top: 0rem !important;
        }
}

.jbw-gallery-thumbs::-webkit-scrollbar{
    width:6px;
}

.jbw-gallery-thumbs::-webkit-scrollbar-thumb{
    background:#ccc;
    border-radius:10px;
}

.jbw-gallery-thumbs button{
    border:none;
    background:none;
    padding:0;
    cursor:pointer;
}

.jbw-gallery-thumbs img{
    width:70px;
    height:70px;
    object-fit:cover;
    border-radius:8px;
    border:1px solid #ddd;
}

.jbw-gallery-main{
    flex:1;
}

.jbw-gallery-main img{
    width:100%;
    /* max-width:500px; */
        max-width:100%;
    aspect-ratio:1/1;
    object-fit:cover;
    border-radius:12px;
}
.starcolor {
    color: gold;
    font-size: 14px;
}
.buttonheight {
    height: 47px;
}
.textalignment {
    width: 100%;
    text-align: center !important;
    display: block;
}

.textalignment i {
    margin-right: 5px;
}
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
.jbw-booking-tabs { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--c-border); }
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

.viewdetails {
    /* background: #dbeafe; */
    color: #000000;
    height: fit-content;
    display: inline-flex;
    padding: 0.15rem 1.75rem;
    border-radius: 999px;
    font-size: 0.7875rem !important;
    font-weight: 700;
    text-decoration: none;

    letter-spacing: 0.04em;
    border: 1px solid var(--c-border);
}
.jbw-status {
    height: fit-content;
    display: inline-flex;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.6875rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.jbw-status--new       { background: #dbeafe; color: #1d4ed8; }
.jbw-status--in_progress{ background: #ffedd5; color: #c2410c; }
.jbw-status--delivered { background: #dcfce7; color: #15803d; }
.jbw-status--cancelled { background: #fee2e2; color: #b91c1c; }
.jbw-status--default   { background: #f1f5f9; color: #475569; }

/* booking detail */
.jbw-booking-layout { display: grid; gap: 1.5rem; }
.jbw-booking-main,
.jbw-booking-sidebar { display: grid; gap: 1.25rem; height: fit-content; }
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
.jbw-booking-track-step--current .jbw-booking-track-marker {
    background: #fff;
    border-color: var(--c-primary);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--c-primary) 22%, transparent);
}
.jbw-booking-track-step--current .jbw-booking-track-label { color: var(--c-text); }
.jbw-booking-track-step--upcoming .jbw-booking-track-marker { background: var(--c-bg); }
.jbw-booking-track-step--upcoming .jbw-booking-track-label { color: var(--c-muted); font-weight: 600; }
.jbw-booking-track-step--cancelled .jbw-booking-track-marker { background: #fee2e2; border-color: #fecaca; }
.jbw-booking-track-label { font-weight: 700; font-size: 0.875rem; margin: 0; }
.jbw-booking-track-time { font-size: 0.75rem; color: var(--c-muted); margin: 0.125rem 0 0; }
.jbw-booking-track-content { min-width: 0; padding-top: 0.1rem; }

.jbw-payment-lines { display: grid; gap: 0.5rem; font-size: 0.875rem; }
.jbw-payment-lines div { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; }
.jbw-payment-line--deduct span:last-child { color: #dc2626; font-weight: 700; }
.jbw-payment-total {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--c-border);
}
.jbw-payment-total strong { font-size: 1.375rem; color: var(--c-primary); }

/* ─── Booking detail page ─────────────────────────────────────────── */
.jbw-booking-detail-page {
    padding-top: 0.25rem;
    padding-bottom: 3.5rem;
}
.jbw-booking-detail-header {
    margin-bottom: 1.75rem;
}
.jbw-booking-detail-eyebrow {
    margin: 0 0 0.35rem;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--c-muted);
}
.jbw-booking-detail-title {
    margin: 0;
    font-family: var(--font-serif);
    font-size: clamp(1.625rem, 3.5vw, 2.125rem);
    font-weight: 600;
    line-height: 1.15;
    letter-spacing: -0.02em;
}
.jbw-booking-detail-meta {
    margin: 0.5rem 0 0;
    font-size: 0.9375rem;
    color: var(--c-muted);
}
.jbw-booking-detail-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
}
.jbw-booking-detail-type {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    background: #f1f5f9;
    color: #475569;
}
.jbw-booking-pay-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.jbw-booking-pay-badge--paid { background: #dcfce7; color: #15803d; }
.jbw-booking-pay-badge--pending { background: #ffedd5; color: #c2410c; }
.jbw-booking-pay-badge--failed { background: #fee2e2; color: #b91c1c; }

.jbw-booking-detail-layout {
    align-items: start;
}
.jbw-booking-detail-product-row {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
}
.jbw-booking-detail-product-media {
    flex-shrink: 0;
}
.jbw-booking-detail-product-img {
    width: 7.5rem;
    height: 7.5rem;
    border-radius: 14px;
    object-fit: cover;
    background: #f0ede8;
    border: 1px solid var(--c-border);
}
.jbw-booking-detail-product-body {
    flex: 1;
    min-width: 0;
}
.jbw-booking-detail-attrs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem 0.75rem;
    margin: 0.5rem 0 0.75rem;
}
.jbw-booking-detail-attr {
    font-size: 0.8125rem;
    color: var(--c-muted);
    font-weight: 600;
}
.jbw-booking-detail-split {
    align-items: stretch;
}
.jbw-booking-designer-row {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}
.jbw-booking-designer-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 999px;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--c-border);
}
.jbw-booking-designer-avatar--fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #fce7df, #fecaca);
    color: var(--c-primary);
    font-weight: 800;
    font-size: 1rem;
}
.jbw-booking-designer-info {
    min-width: 0;
}
.jbw-booking-designer-name {
    display: block;
    font-weight: 800;
    font-size: 0.9375rem;
    color: var(--c-text);
    text-decoration: none;
    line-height: 1.3;
}
.jbw-booking-designer-name:hover {
    color: var(--c-primary);
}
.jbw-booking-detail-rental-range {
    margin: 0;
    font-weight: 800;
    font-size: 1.0625rem;
    line-height: 1.35;
    color: var(--c-text);
}
.jbw-booking-detail-rental-range span {
    margin: 0 0.35rem;
    color: var(--c-muted);
    font-weight: 600;
}
.jbw-booking-detail-empty {
    margin: 0;
    font-size: 0.875rem;
    color: var(--c-muted);
}
.jbw-booking-detail-address {
    display: flex;
    gap: 0.875rem;
    align-items: flex-start;
}
.jbw-booking-detail-address-icon {
    display: grid;
    place-items: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 10px;
    background: #fef3ee;
    color: var(--c-primary);
    flex-shrink: 0;
}
.jbw-booking-detail-address-name {
    margin: 0;
    font-weight: 800;
    font-size: 0.9375rem;
}
.jbw-booking-detail-address-lines {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    line-height: 1.55;
    color: var(--c-muted);
}
.jbw-booking-detail-notes {
    margin: 0;
    font-size: 0.9375rem;
    line-height: 1.65;
    color: var(--c-muted);
}
.jbw-booking-ref-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(5rem, 1fr));
    gap: 0.625rem;
}
.jbw-booking-ref-item {
    display: block;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--c-border);
    background: #f0ede8;
    transition: transform var(--trans), box-shadow var(--trans);
}
.jbw-booking-ref-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--c-shadow-md);
}
.jbw-booking-ref-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.jbw-booking-sidebar-sticky {
    display: grid;
    gap: 1.25rem;
}
@media (min-width: 1024px) {
    .jbw-booking-sidebar-sticky {
        position: sticky;
        top: 5.5rem;
    }
}
.jbw-booking-payment-card--pending {
    border-color: color-mix(in srgb, var(--c-primary) 35%, var(--c-border));
    background: linear-gradient(180deg, #fffaf7 0%, var(--c-surface) 100%);
}
.jbw-booking-detail-pay-btn {
    margin-top: 1rem;
    border-radius: 12px;
}
.jbw-booking-detail-paid-note {
    margin: 0.875rem 0 0;
    font-size: 0.8125rem;
    color: var(--c-muted);
}
.jbw-booking-detail-help-meta {
    margin: 0 0 1rem;
    font-size: 0.8125rem;
    color: var(--c-muted);
}
.jbw-booking-detail-dispute-subject {
    margin: 0 0 1rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--c-muted);
}
.jbw-booking-detail-form {
    display: grid;
    gap: 1rem;
}
.jbw-booking-detail-cancel-card {
    border-color: #fecaca;
    background: linear-gradient(180deg, #fffbfb 0%, var(--c-surface) 100%);
}
.jbw-booking-detail-cancel-hint {
    margin: -0.35rem 0 1rem;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: var(--c-muted);
}
.jbw-label-optional {
    font-weight: 600;
    color: var(--c-muted);
    text-transform: none;
    letter-spacing: 0;
}

@media (max-width: 639px) {
    .jbw-booking-detail-product-row {
        flex-direction: column;
    }
    .jbw-booking-detail-product-img {
        width: 100%;
        height: auto;
        aspect-ratio: 16 / 10;
        max-height: 14rem;
    }
    .jbw-booking-detail-badges {
        gap: 0.375rem;
    }
}

/* ─── Measurements ────────────────────────────────────────────────── */
.jbw-measure-page { padding-top: 1.0rem; padding-bottom: 1rem; }
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
    border-radius: 10px; padding: 0.8rem 1rem; background: var(--c-surface);
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
.jbw-input--measure { text-align: start; color: var(--c-muted); }
.jbw-measure-actions { display: flex; justify-content: flex-end; margin-top: 2.5rem; padding-top: 1.25rem; border-top: 1px solid var(--c-border); }
.jbw-measure-form-grid { display: grid; gap: 1rem 1.25rem; }
@media (min-width: 768px) { .jbw-measure-form-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 767px) { .jbw-measure-form-grid { grid-template-columns: repeat(1, 1fr); } }

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
.jbw-auth-main { min-height: 100vh; background: #fefefe; position: relative; }

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
@media (max-width: 768px) {
    .jbw-auth-page-brand {
        min-height: 12vh;
        padding: 1.5rem 1rem;
    }
     .auth-logo {
        height: 100px !important;
    }
    .jbw-auth-page-form {
        padding-top: 0rem !important;
    }
    .jbw-auth-page-form {
        padding: 0rem 1.25rem 3rem !important;
    }
}
.textmanage {
    font-weight: 800;
    color: #e95433 !important;
    text-decoration: none;

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
    padding: 2rem 1.25rem 3rem; background: #fefefe;
}
.auth-logo {
    width: 360px;
    height: auto;
    display: block;
    object-fit: contain;
}
.jbw-auth-card {
    width: min(540px, 100%);
    background: var(--c-surface);
    border-radius: 24px;
    box-shadow: 0 2px 4px rgb(0 0 0 / 0.04), 0 20px 60px rgb(0 0 0 / 0.1);
    padding: 3.5rem 2.25rem 3.25rem;
    border: 1px solid var(--c-border);
}
.jbw-auth-card--centered .jbw-auth-title,
.jbw-auth-card--centered .jbw-auth-sub { text-align: center; }

.jbw-auth-title {
    font-family: var(--font-serif);
    font-size: clamp(1.5rem, 4vw, 1.875rem);
    font-weight: 600; line-height: 1.2;
    margin: 0 0 0.375rem; color: var(--c-text);
    text-align: center;
}
.jbw-auth-sub {
    color: #9ca3af; margin: 0 0 0.95rem;
    font-size: 0.9375rem; line-height: 1.5;
    text-align: center;
}


.jbw-input--auth {
    padding: 0.9375rem 1.0625rem;
    border-radius: 10px;
    font-size: 0.9375rem;
    border-color: #d8d5cf;
}

.jbw-phone-field {
    display: flex;
    align-items: stretch;
    border: 1.5px solid #d8d5cf;
    border-radius: 10px;
    background: var(--c-surface);
    overflow: hidden;
    transition: border-color var(--trans), box-shadow var(--trans);
}

.jbw-phone-field:focus-within {
    border-color: var(--c-primary);
    box-shadow: 0 0 0 3px rgb(242 81 35 / 0.1);
}

.jbw-phone-field.is-invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgb(239 68 68 / 0.08);
}

.jbw-phone-prefix {
    display: inline-flex;
    align-items: center;
    padding: 0.9375rem 0.875rem;
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--c-text);
    background: var(--c-bg);
    border-right: 1.5px solid #d8d5cf;
    user-select: none;
    white-space: nowrap;
}

.jbw-phone-input {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    padding: 0.9375rem 1.0625rem;
    font: inherit;
    font-size: 0.9375rem;
    color: var(--c-text);
}

.jbw-phone-input:focus {
    outline: none;
}

.jbw-phone-input::placeholder {
    color: #b8b5af;
}

.jbw-btn--cta {
    border-radius: 10px;
    padding: 0.8375rem 1.5rem;
    font-size: 0.8rem;
    font-weight: 600;
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
.jbw-btn--social:disabled { opacity: 0.95; cursor: not-allowed; }

.jbw-social-icon {
    width: 1.125rem; height: 1.125rem;
    border-radius: 999px; display: inline-grid;
    place-items: center; font-size: 0.6875rem;
    font-weight: 800; flex-shrink: 0;
}
.jbw-social-icon--google { background: #fff; border: 1px solid #e0e0e0; color: #4285f4; }
.jbw-social-icon--apple { background: #111; color: #fff; font-size: 0.875rem; }

.jbw-auth-footer {
    text-align: center; margin-top: 0.5rem;
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
    width: clamp(3.0rem, 15vw, 4.05rem);
    height: clamp(2.8rem, 15vw, 2.85rem);
    flex: 0 0 clamp(3.5rem, 15vw, 4.25rem);
    text-align: center; font-size: 1.3rem; font-weight: 600;
    border: 1.5px solid #d8d5cf; border-radius: 5px;
    background: var(--c-bg); color: var(--c-primary);
    font-family: var(--font-sans); padding: 0;
    transition: border-color var(--trans), box-shadow var(--trans), background var(--trans);
}
.jbw-otp-box:focus { outline: none; border-color: var(--c-primary); background: #fff; box-shadow: 0 0 0 4px rgb(242 81 35 / 0.1); }
.jbw-otp-box.is-filled { border-color: #f4a574; background: #fff; }
.jbw-form-stack--otp { gap: 0; }


.text-danger {
    color: red;
    font-size: 10px;
}
@media (min-width: 960px) {
    .jbw-auth-page { flex-direction: row; align-items: stretch; }
    .jbw-auth-page-brand { flex: 1; min-height: 100vh; padding: 3rem 3rem; }
    .jbw-auth-page-brand-inner { max-width: 32rem; gap: 3rem; }
    .jbw-logo--auth .jbw-logo-media--auth { height: 5rem; max-width: 16rem; }
    .jbw-auth-page-form { flex: 1; min-height: 100vh; padding: 3rem 3rem; }
}
@media (max-width: 959px) {
    .jbw-auth-brand-tagline { display: none; }
}

/* ─── Breadcrumb ─────────────────────────────────────────────────── */
.jbw-breadcrumb { margin-bottom: 1.5rem; padding-top: 0.5rem; }
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

.jbw-subcategory-strip {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
    scrollbar-width: thin;
}
.jbw-subcategory-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.375rem;
    min-width: 5.5rem;
    padding: 0.5rem;
    border-radius: 12px;
    border: 1px solid var(--c-border);
    background: var(--c-surface);
    text-decoration: none;
    color: inherit;
    transition: border-color var(--trans), box-shadow var(--trans);
}
.jbw-subcategory-chip:hover,
.jbw-subcategory-chip.is-active {
    border-color: var(--c-primary);
    box-shadow: 0 0 0 1px rgb(242 81 35 / 0.15);
}
.jbw-subcategory-chip-img {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 999px;
    object-fit: cover;
}
.jbw-subcategory-chip-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-align: center;
    line-height: 1.2;
}
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

.bannercss {
    padding: 15px;

}

.lookbutton{
border-radius: 0px;
padding:  7px 17px 7px 17px;
font-size: 12px;
background: #AE2A0B;
}
.borderbanner {
    border-radius: 10px;
}
/* contactform */
/* Contact Form */
.contact-form-card{
    padding-top: 0px !important;
    background:#fff;
    padding:50px;
    margin-top:0px;
}

.contact-form-card h2{
    font-size:48px;
    font-weight:700;
    color:#222;
    margin-bottom:40px;
    font-family: Georgia, serif;
}

.contact-form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:30px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group.full-width{
    grid-column:1 / -1;
}

.form-group label{
    font-size:11px;
    letter-spacing:2px;
    text-transform:uppercase;
    color:#999;
    margin-bottom:12px;
    font-weight:600;
}

.contact-input,
.contact-textarea,
.contact-select{
    border:none;
    border-bottom:1px solid #d8a0a0;
    background:transparent;
    padding:12px 0;
    font-size:15px;
    color:#222;
    outline:none;
    width:100%;
}

.contact-input::placeholder,
.contact-textarea::placeholder{
    color:#bdbdbd;
}

.contact-textarea{
    resize:none;
    min-height:120px;
}

.contact-input:focus,
.contact-textarea:focus,
.contact-select:focus{
    border-bottom:1px solid #000;
}

.contact-submit{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    background:#111;
    color:#fff;
    border:none;
    padding:16px 40px;
    font-size:12px;
    letter-spacing:2px;
    text-transform:uppercase;
    cursor:pointer;
    transition:.3s;
}

.contact-submit:hover{
    background:#000;
    color:#fff;
}

.contact-submit-wrap{
    margin-top:30px;
}

@media (max-width:768px){
    .contact-form-card{
        padding:25px;
    }

    .contact-form-grid{
        grid-template-columns:1fr;
        gap:20px;
    }

    .contact-form-card h2{
        font-size:34px;
    }
}
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
    aspect-ratio: 1/1;
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


/* review section  */
.reviews-section {
    padding: 10px 20px;

}

.reviews-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    border-bottom: 1px solid #f1d7d0;
}



.rating-summary {
    color: #f5a623;
    font-size: 18px;
    margin-top: 0px;
    margin-bottom: 10px;
}

.rating-summary span {
    color: #444;
    font-size: 15px;
    margin-left: 8px;
}

.view-all {
    text-decoration: none;
    font-size: 13px;
    letter-spacing: 1px;
    color: #ff5b2e;
    font-weight: 600;
}

.reviews-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.review-card {
    border: 1px solid #f1d7d0;
    border-radius: 16px;
    padding: 24px;
    background: #fff;
    transition: all .3s ease;
}

.review-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,.08);
}

.review-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.review-user {
    display: flex;
    gap: 15px;
    align-items: center;
}

.review-user img {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    object-fit: cover;
}

.review-user h4 {
    margin: 0;
    font-size: 20px;
    color: #222;
}

.stars {
    color: #ff6b35;
    font-size: 15px;
    letter-spacing: 2px;
}

.review-time {
    color: #888;
    font-size: 14px;
}

.review-card p {
    color: #666;
    line-height: 1.8;
    margin: 0;
    font-size: 15px;
}

@media(max-width:768px) {
    .reviews-grid {
        grid-template-columns: 1fr;
    }

    .reviews-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .reviews-header h2 {
        font-size: 28px;
    }
}
/* ══════════════════════════════════════════════════════════════════
   GLOBAL RESPONSIVE FIXES
   ══════════════════════════════════════════════════════════════════ */

/* Container gets tighter padding on small screens */
@media (max-width: 480px) {
    .jbw-container { width: min(100%, 100%); }
}
/* @media (max-width: 480px) {
    .jbw-container { width: min(100%, 100% - 1.5rem); }
} */

/* Hero - smaller on mobile */
@media (max-width: 640px) {
    .jbw-hero {
        height: clamp(360px, 52vh, 480px);
        min-height: 360px;
    }
    .jbw-hero-content {
        max-width: 85%;
    }

    .jbw-hero-content { padding: 1.25rem 1rem; }
    .jbw-hero-title { font-size: clamp(1.875rem, 9vw, 2.5rem); margin-bottom: 0.875rem; }
    .jbw-hero-text { font-size: 0.9375rem; margin-bottom: 1.25rem; }
    .jbw-hero-actions { gap: 0.75rem; }
    .jbw-hero-actions .jbw-btn--lg { padding: 0.875rem 1.25rem; font-size: 0.9375rem; }
    .jbw-hero-scroll { display: none; }
    .jbw-hero-arrow { width: 2.25rem; height: 2.25rem; font-size: 1rem; }
    .category-card {
        flex-basis: clamp(306px, 62vw, 240px);
        min-width: clamp(180px, 62vw, 240px);
    }
}

/* Section bands — less padding on mobile */
@media (max-width: 768px) {
    .jbw-section-band { padding: 0.180rem 0; }
    .jbw-section-band--cta { padding: 3.5rem 0; }
    .jbw-section-head { margin-bottom: 0.75rem; }
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
    /* .jbw-footer { padding-top: 2.5rem; margin-top: 2.5rem; padding-left: 1rem; } */
        .jbw-footer { padding-top: 2.5rem; margin-top: 0.5rem; padding-left: 1rem; }
    .jbw-footer-grid { gap: 1rem; }
    .jbw-footer-about { max-width: 100%; }
    .jbw-footer-bottom { flex-direction: column; text-align: center; gap: 0.375rem; }
}

/* Page heads on mobile */
@media (max-width: 640px) {
    /* .jbw-page-head { padding-top: 1.5rem; margin-bottom: 1.25rem; } */
        .jbw-page-head { padding-top: 0rem; margin-bottom: 0rem; }
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
    .jbw-section-band { padding: 1.0rem 0; }
}

/* Product grid on wide screens */
@media (min-width: 1100px) {
    .jbw-product-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
}

.brand-rating-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.brand-rating-row .jbw-product-brand {
    margin: 0;
    flex: 1;
}

.rating-wrap {
    display: flex;
    align-items: center;
    gap: 4px;
    line-height: 1;
}

.rating-wrap svg {
    display: block;
    flex-shrink: 0;
}

.rating-wrap span {
    display: block;
    line-height: 1;
    font-size: 11px;
    font-weight: 600;
}
/* ─── Chat ─────────────────────────────────────────────────────────── */
.jbw-chat-message-wrapper{
    display: flex;
    flex-direction: column;
    max-width: 78%;
    min-width: 0;
}
.jbw-chat-message-wrapper--mine {
    align-self: flex-end;
}
.jbw-chat-message-wrapper--theirs {
    align-self: flex-start;
}

.jbw-chat-bubble--mine + .jbw-chat-time{
    text-align: right;
}

.jbw-chat-bubble--theirs + .jbw-chat-time{
    text-align: left;
}

.jbw-chat-time{
    margin-top: 4px;
    font-size: 12px;
}
.jbw-chat-compose{
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px 12px;
    background:#f8f8f8;
    border:1px solid #ddd;
    border-radius:30px;
}

.jbw-chat-attach{
    width:32px;
    height:32px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    color:#666;
    flex-shrink:0;
}

.jbw-chat-input{
    flex:1;
    border:none;
    background:transparent;
    resize:none;
    outline:none;
    min-height:24px;
    padding:0;
    font-size:14px;
}

.jbw-chat-send{
    width:36px;
    height:36px;
    border:none;
    border-radius:50%;
    background:#b52d00;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    flex-shrink:0;
}
.jbw-chat-layout {
    display: grid;
    grid-template-columns: minmax(220px, 260px) minmax(0, 1fr);
    gap: 0.75rem;
    width: 100%;
    max-width: 1080px;
    height: calc(100vh - 9rem);
    max-height: calc(100vh - 9rem);
    min-height: 20rem;
    margin-bottom: 0;
}
.jbw-chat-sidebar,
.jbw-chat-main {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--r-card);
    box-shadow: var(--c-shadow-sm);
    overflow: hidden;
    min-height: 0;
}
.jbw-chat-sidebar {
    display: flex;
    flex-direction: column;
    min-height: 0;
    min-width: 0;
    max-width: 260px;
}
.jbw-chat-sidebar-title {
    margin: 0;
    padding: 1rem 1.25rem 0.5rem;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--c-muted);
}
.jbw-chat-search { padding: 0 1rem 0.75rem; }
.jbw-chat-search .jbw-input { font-size: 0.8125rem; padding: 0.5rem 0.75rem; }
.jbw-chat-threads {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior: contain;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.jbw-chat-threads::-webkit-scrollbar { display: none; }
.jbw-chat-thread {
    display: flex;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    text-decoration: none;
    color: inherit;
    border-left: 3px solid transparent;
    transition: background var(--trans);
}
.jbw-chat-thread:hover { background: var(--c-bg); }
.jbw-chat-thread.is-active {
    background: rgb(242 81 35 / 0.06);
    border-left-color: var(--c-primary);
}
.jbw-chat-thread-avatar {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 999px;
    object-fit: cover;
    flex-shrink: 0;
}
.jbw-chat-thread-avatar--fallback {
    display: grid;
    place-items: center;
    background: #fce7df;
    color: var(--c-primary);
    font-weight: 700;
}
.jbw-chat-thread-body { min-width: 0; flex: 1; }
.jbw-chat-thread-top {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.8125rem;
}
.jbw-chat-thread-top span { color: var(--c-muted); font-size: 0.6875rem; white-space: nowrap; }
.jbw-chat-thread-body p {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: var(--c-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.jbw-chat-empty-sidebar,
.jbw-chat-main-empty {
    padding: 2rem 1.5rem;
    text-align: center;
    color: var(--c-muted);
}
.jbw-chat-main { display: flex; flex-direction: column; min-height: 0; height: 100%; }
.jbw-chat-main-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--c-border);
    flex-shrink: 0;
}
.jbw-chat-main-vendor { display: flex; align-items: center; gap: 0.75rem; }
.jbw-chat-messages {
    flex: 1 1 auto;
    min-height: 0;
    min-width: 0;
    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 0;
    background: #f8fafc;
    border-bottom: 1px solid #e8e6e1;
}
.jbw-chat-messages::-webkit-scrollbar { display: none; }
.jbw-chat-messages-track {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 0.75rem;
    min-height: 100%;
    padding: 1.25rem;
    box-sizing: border-box;
}
.jbw-plus-icon{
    width:20px;
    height:20px;
    border:1px solid #bdbdbd;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
}

.jbw-plus-icon span{
    line-height:1;
    font-size:16px;
    transform: translateY(-1px);
}
.jbw-chat-attach{
    display: flex;
    align-items: center;
    justify-content: center;
}
.jbw-chat-bubble {
    max-width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    line-height: 1.55;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.jbw-chat-bubble p { margin: 0; white-space: pre-wrap; word-break: break-word; }
.jbw-chat-bubble--theirs {
    background: #fff;
    border: 1px solid var(--c-border);
    border-bottom-left-radius: 0.25rem;
}
.jbw-chat-bubble--mine {
    background: #0f4c5c;
    color: #fff;
    border-bottom-right-radius: 0.25rem;
}
.jbw-chat-time {
    display: block;
    margin-top: 0.35rem;
    font-size: 0.6875rem;
    opacity: 0.75;
}
.jbw-chat-attachment {
    display: block;
    margin-top: 0.5rem;
    max-width: 12rem;
    border-radius: 0.5rem;
}
.jbw-chat-attachment video {
    display: block;
    width: 100%;
    max-width: 14rem;
    border-radius: 0.5rem;
    background: #000;
}
.jbw-chat-compose {
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    margin: 0.75rem 1rem 1rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--c-border);
    border-radius: 999px;
    background: #f8f8f8;
    flex-shrink: 0;
}
.jbw-chat-attach {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    color: var(--c-muted);
    cursor: pointer;
}

.jbw-chat-empty-thread {
    margin: 0 auto;
    color: var(--c-muted);
    font-size: 0.875rem;
}
@media (max-width: 899px) {
    .jbw-chat-layout {
        grid-template-columns: 1fr;
    }
    .jbw-chat-main { min-height: 0; }
}

/* Chat page: fit viewport, no page scrollbar */
.jbw-body--chat {
    overflow: hidden;
    height: 100vh;
    display: flex;
    flex-direction: column;
}
.jbw-body--chat .jbw-main {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    padding-bottom: 0;
    display: flex;
    flex-direction: column;
}
.jbw-body--chat .jbw-footer {
    display: none;
}
.jbw-page--chat {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    width: min(100%, 1080px);
    max-width: 1080px;
    margin-inline: auto;
    padding-inline: clamp(1rem, 4vw, 2rem);
    box-sizing: border-box;
}
.jbw-body--chat .jbw-chat-layout {
    display: grid;
    flex: 1 1 auto;
    min-height: 0;
    width: 100%;
    max-width: 1080px;
    height: auto;
    max-height: none;
    margin-bottom: 0;
}

/* Smooth scroll and selection */
html { scroll-behavior: smooth; }
::selection { background: rgb(242 81 35 / 0.15); }
</style>

