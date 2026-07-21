<style>
:root {
    --vp-orange: #f25123;
    --vp-orange-hover: #e04518;
    --vp-orange-soft: #fff4ef;
    --vp-orange-muted: #ffd8c8;
    --vp-teal: #0d4f4f;
    --vp-teal-light: #167272;
    --vp-bg: #f3f5f7;
    --vp-surface: #ffffff;
    --vp-border: #e6eaee;
    --vp-border-strong: #d5dce3;
    --vp-text: #152536;
    --vp-muted: #6b7c8f;
    --vp-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 8px 24px rgba(16, 24, 40, .06);
    --vp-radius: 14px;
    --vp-radius-lg: 18px;
    --vp-sidebar-w: 272px;
}
*, *::before, *::after { box-sizing: border-box; }
html { -webkit-font-smoothing: antialiased; height: 100%; }
body.vp-body {
    margin: 0; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--vp-bg);
    color: var(--vp-text); line-height: 1.5; height: 100%; overflow: hidden;
}
body.vp-body--guest {
    overflow: auto;
    height: auto;
    min-height: 100%;
}
a { color: inherit; }
img { max-width: 100%; display: block; }
[x-cloak] { display: none !important; }

/* Shell */
.vp-shell { display: flex; height: 100vh; min-height: 0; overflow: hidden; }
.vp-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); z-index: 35; backdrop-filter: blur(2px); }
.vp-sidebar {
    width: var(--vp-sidebar-w); background: var(--vp-surface); border-right: 1px solid var(--vp-border);
    display: flex; flex-direction: column; position: fixed; inset: 0 auto 0 0; z-index: 40;
    height: 100vh; overflow: hidden;
}
.vp-sidebar-brand {
    padding: .75rem .85rem; border-bottom: 1px solid var(--vp-border); background: var(--vp-surface);
}
.vp-brand-card {
    display: flex; align-items: center; justify-content: center;
    width: 100%; padding: .5rem .65rem;
    border-radius: 10px; border: 1px solid var(--vp-border); background: #fff;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    text-decoration: none; transition: border-color .15s, box-shadow .15s;
}
.vp-brand-card:hover {
    border-color: var(--vp-orange-muted);
    box-shadow: 0 2px 8px rgba(16, 24, 40, .06);
}
.vp-brand-img {
    display: block; width: 100%; height: auto; max-height: 2.35rem;
    object-fit: contain; object-position: center;
}
.vp-nav { flex: 1; overflow-y: auto; padding: 1rem .85rem; }
.vp-nav-link {
    display: flex; align-items: center; gap: .75rem; padding: .72rem .9rem; border-radius: 12px;
    color: var(--vp-muted); text-decoration: none; font-size: .9rem; font-weight: 600; margin-bottom: 4px;
    transition: background .15s, color .15s;
}
.vp-nav-link:hover { background: #f8fafc; color: var(--vp-text); }
.vp-nav-link--active { background: var(--vp-orange-soft); color: var(--vp-orange); }
.vp-nav-link--active .vp-icon { color: var(--vp-orange); }
.vp-nav-group-btn {
    width: 100%; display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    padding: .72rem .9rem; border: none; background: transparent; border-radius: 12px;
    color: var(--vp-muted); font: inherit; font-weight: 600; font-size: .9rem; cursor: pointer; text-align: left;
}
.vp-nav-group-btn:hover, .vp-nav-group-btn--open { background: #f8fafc; color: var(--vp-text); }
.vp-nav-sub { padding: .15rem 0 .35rem .35rem; margin-left: 2.1rem; border-left: 2px solid var(--vp-border); }
.vp-nav-sub .vp-nav-link { font-size: .84rem; font-weight: 500; padding: .55rem .75rem; }
.vp-icon { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
.vp-sidebar-foot { padding: 1rem 1.1rem 1.25rem; border-top: 1px solid var(--vp-border); }
.vp-sidebar-foot p { margin: .85rem 0 0; font-size: .72rem; color: var(--vp-muted); text-align: center; }

.vp-main {
    flex: 1; margin-left: var(--vp-sidebar-w); display: flex; flex-direction: column;
    min-width: 0; height: 100vh; overflow-y: auto; overflow-x: hidden;
}
.vp-topbar {
    background: var(--vp-surface); border-bottom: 1px solid var(--vp-border); padding: .9rem 1.75rem;
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    position: sticky; top: 0; z-index: 30; min-width: 0; max-width: 100%;
}
.vp-topbar-left { display: flex; align-items: center; gap: .85rem; min-width: 0; flex: 1 1 auto; }
.vp-menu-btn {
    display: none; width: 40px; height: 40px; border: 1px solid var(--vp-border); border-radius: 10px;
    background: #fff; cursor: pointer; align-items: center; justify-content: center;
}
.vp-topbar-user { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.vp-avatar {
    width: 44px; height: 44px; border-radius: 50%; background: var(--vp-orange-soft); color: var(--vp-orange);
    display: flex; align-items: center; justify-content: center; font-weight: 800; overflow: hidden;
    border: 2px solid #fff; box-shadow: 0 0 0 1px var(--vp-border);
}
.vp-avatar img { width: 100%; height: 100%; object-fit: cover; }
.vp-user-name { font-weight: 700; font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.vp-user-greet { font-size: .8rem; color: var(--vp-muted); }
.vp-icon-btn {
    width: 42px; height: 42px; border-radius: 12px; border: 1px solid var(--vp-border); background: #fff;
    display: inline-flex; align-items: center; justify-content: center; color: var(--vp-muted); cursor: pointer;
}

/* Notifications */
.vp-notification-picker { position: relative; }
.vp-notification-btn { position: relative; }
.vp-notification-badge {
    position: absolute;
    top: -0.15rem;
    right: -0.15rem;
    min-width: 1.1rem;
    height: 1.1rem;
    padding: 0 0.25rem;
    border-radius: 999px;
    background: var(--vp-orange);
    color: #fff;
    font-size: 0.625rem;
    font-weight: 800;
    display: grid;
    place-items: center;
    line-height: 1;
    border: 2px solid #fff;
    pointer-events: none;
}
.vp-notification-panel {
    position: absolute;
    right: 0;
    top: calc(100% + 0.625rem);
    width: min(22rem, calc(100vw - 2rem));
    max-height: min(26rem, calc(100vh - 6rem));
    background: #fff;
    border: 1px solid var(--vp-border);
    border-radius: 14px;
    box-shadow: var(--vp-shadow);
    z-index: 60;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.vp-notification-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--vp-border);
}
.vp-notification-panel-title { margin: 0; font-size: 0.875rem; font-weight: 800; }
.vp-notification-mark-all {
    border: 0;
    background: none;
    font: inherit;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--vp-orange);
    cursor: pointer;
    padding: 0;
}
.vp-notification-list { flex: 1; overflow-y: auto; }
.vp-notification-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--vp-border);
}
.vp-notification-item.is-unread { background: var(--vp-orange-soft); }
.vp-notification-item-title { margin: 0; font-size: 0.8125rem; font-weight: 700; }
.vp-notification-item-message { margin: 0.25rem 0 0; font-size: 0.75rem; color: var(--vp-muted); line-height: 1.45; }
.vp-notification-item-time { margin: 0.35rem 0 0; font-size: 0.6875rem; color: var(--vp-muted); }
.vp-notification-dot {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 999px;
    border: 0;
    background: var(--vp-orange);
    cursor: pointer;
    flex-shrink: 0;
    margin-top: 0.35rem;
}
.vp-notification-empty { padding: 2rem 1rem; text-align: center; color: var(--vp-muted); font-size: 0.875rem; }
.vp-notification-panel-foot { padding: 0.75rem 1rem; border-top: 1px solid var(--vp-border); text-align: center; }
.vp-notification-view-all { font-size: 0.8125rem; font-weight: 700; color: var(--vp-orange); text-decoration: none; }
.vp-notification-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--vp-border);
}
.vp-notification-row.is-unread { background: var(--vp-orange-soft); }
.vp-notification-row-title { margin: 0; font-size: 0.9rem; font-weight: 700; }
.vp-notification-row-message { margin: 0.35rem 0 0; font-size: 0.85rem; color: var(--vp-muted); line-height: 1.5; }
.vp-notification-row-time { margin: 0.35rem 0 0; font-size: 0.75rem; color: var(--vp-muted); }
.vp-content { padding: 1.5rem 1.75rem 2rem; flex: 1; min-width: 0; max-width: 100%; overflow-x: clip; }

/* Typography & layout */
.vp-page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.35rem; }
.vp-page-title { font-size: 1.65rem; font-weight: 800; margin: 0; letter-spacing: -.02em; }
.vp-page-sub { color: var(--vp-muted); margin: .35rem 0 0; font-size: .92rem; }
.vp-grid-2, .vp-stat-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
.vp-grid-4, .vp-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.35rem; }
/* Filters (admin-style) */
.vp-filters {
    margin-bottom: 1.35rem; border-radius: var(--vp-radius-lg); border: 1px solid var(--vp-border);
    background: var(--vp-surface); padding: 1.1rem 1.25rem; box-shadow: var(--vp-shadow);
    max-width: 100%;
    box-sizing: border-box;
}
.vp-filters-grid { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; min-width: 0; }
.vp-filters-field { flex: 0 1 auto; min-width: 8.5rem; max-width: 100%; }
.vp-filters-field--wide { min-width: 11rem; max-width: min(16rem, 100%); flex: 1 1 14rem; }
.vp-filters-field--date { min-width: 10.75rem; max-width: 100%; }
.vp-filters-date-group { display: contents; }
.vp-filters-actions, .vp-filters-page-actions { display: flex; flex-direction: column; flex-shrink: 0; max-width: 100%; }
.vp-filters-page-actions { margin-left: auto; }
.vp-filters-actions-btns, .vp-filters-page-actions-btns { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; max-width: 100%; }
.vp-filters-actions .vp-btn, .vp-filters-page-actions .vp-btn { min-height: 2.625rem; white-space: nowrap; }
.vp-label--spacer { visibility: hidden; margin-bottom: .4rem; display: block; user-select: none; }
.vp-filters-active {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    margin-top: .9rem;
    padding-top: .9rem;
    border-top: 1px solid var(--vp-border);
}
.vp-filters-active__label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--vp-muted);
    margin-right: .15rem;
}
.vp-filter-chip {
    display: inline-flex;
    align-items: center;
    padding: .28rem .65rem;
    border-radius: 9999px;
    background: #fff;
    border: 1px solid var(--vp-border);
    font-size: .78rem;
    font-weight: 600;
    color: var(--vp-text);
}
.vp-filter-chip--clear { text-decoration: none; color: var(--vp-orange); border-color: #fed7aa; background: #fff7ed; }
.vp-card-count-inline { font-size: .82rem; font-weight: 600; color: var(--vp-muted); }
.vp-table-meta { font-size: .78rem; color: var(--vp-muted); }
.vp-empty-state { text-align: center; padding: 2.75rem 1.5rem; }
.vp-empty-state__title { margin: 0 0 .35rem; font-size: 1rem; font-weight: 700; color: var(--vp-text); }
.vp-empty-state__text { margin: 0; font-size: .88rem; color: var(--vp-muted); }
.vp-empty-state__text a { color: var(--vp-orange); font-weight: 600; text-decoration: none; }

.vp-date-native {
    min-height: 2.625rem;
    color-scheme: light;
}
.vp-date-native::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: .65;
}
.vp-date-native::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}

.vp-card-count { padding: .9rem 1.15rem; border-bottom: 1px solid var(--vp-border); font-size: .875rem; font-weight: 600; color: var(--vp-muted); }
.vp-back-link { display: inline-flex; align-items: center; gap: .35rem; font-size: .875rem; font-weight: 600; color: var(--vp-muted); text-decoration: none; margin-bottom: 1rem; }
.vp-back-link:hover { color: var(--vp-orange); }
.vp-empty { text-align: center; color: var(--vp-muted); padding: 2.5rem 1.5rem; font-size: .9rem; }
.vp-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; }
.vp-detail-item .vp-stat-label { margin-bottom: .35rem; }
.vp-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
.vp-payout-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 0; border-bottom: 1px solid var(--vp-border); }
.vp-payout-row:last-child { border-bottom: none; }

/* Cards */
.vp-card { background: var(--vp-surface); border: 1px solid var(--vp-border); border-radius: var(--vp-radius-lg); box-shadow: var(--vp-shadow); overflow: hidden; max-width: 100%; }
.vp-card-pad { padding: 1.35rem; }
.vp-card-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.35rem; border-bottom: 1px solid var(--vp-border); }
.vp-card-head h3 { margin: 0; font-size: 1rem; font-weight: 700; }

/* Stats */
.vp-stat {
    background: var(--vp-surface); border: 1px solid var(--vp-border); border-radius: var(--vp-radius);
    padding: 1.15rem 1.2rem; box-shadow: var(--vp-shadow); display: flex; gap: .9rem; align-items: flex-start;
}
.vp-stat-icon {
    width: 42px; height: 42px; border-radius: 12px; background: var(--vp-orange-soft); color: var(--vp-orange);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.vp-stat-icon .vp-icon { width: 1.35rem; height: 1.35rem; }
.vp-stat-body { min-width: 0; }
.vp-stat-label { font-size: .68rem; font-weight: 700; letter-spacing: .05em; color: var(--vp-muted); text-transform: uppercase; }
.vp-stat-value { font-size: 1.65rem; font-weight: 800; margin-top: .2rem; line-height: 1.1; }
.vp-stat-sub { font-size: .78rem; color: var(--vp-muted); margin-top: .25rem; }

.vp-earnings {
    background: linear-gradient(135deg, var(--vp-teal) 0%, var(--vp-teal-light) 100%);
    color: #fff; border-radius: var(--vp-radius-lg); padding: 1.35rem 1.5rem; margin-bottom: 1.35rem;
    box-shadow: 0 12px 32px rgba(13, 79, 79, .22); display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
}
.vp-earnings h3 { margin: 0 0 .75rem; font-size: .72rem; letter-spacing: .08em; opacity: .85; font-weight: 700; text-transform: uppercase; }
.vp-earnings-grid { display: flex; gap: 2.5rem; flex-wrap: wrap; }
.vp-earnings-val { font-size: 1.85rem; font-weight: 800; line-height: 1.1; }
.vp-earnings-actions { display: flex; gap: .65rem; align-items: center; }

/* Figma dashboard: stat cards */
.vp-stat--figma {
    flex-direction: column;
    gap: .85rem;
    padding: 1.2rem 1.25rem 1.15rem;
    border-radius: 16px;
}
.vp-stat--figma .vp-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
}
.vp-stat--figma .vp-stat-icon .vp-icon { width: 1.15rem; height: 1.15rem; }
.vp-stat--figma .vp-stat-label { margin: 0; }
.vp-stat-metrics {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    width: 100%;
    margin-top: .15rem;
}
.vp-stat-metric {
    display: flex;
    flex-direction: column;
    gap: .2rem;
    min-width: 0;
}
.vp-stat-metric span {
    font-size: .72rem;
    font-weight: 600;
    color: var(--vp-muted);
}
.vp-stat-metric strong {
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1;
    color: var(--vp-text);
}

/* Figma dashboard: total earnings */
.vp-earnings.vp-earnings--figma {
    display: block;
    background: #0d2c2f;
    background-image: none;
    color: #fff;
    border-radius: 20px;
    padding: 1.65rem 1.85rem 1.75rem;
    margin-bottom: 1.35rem;
    box-shadow: 0 14px 36px rgba(8, 28, 30, .28);
}
.vp-earnings--figma .vp-earnings-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.35rem;
}
.vp-earnings--figma h3 {
    margin: 0;
    font-size: .8rem;
    letter-spacing: .1em;
    font-weight: 700;
    text-transform: uppercase;
    opacity: 1;
    color: #fff;
}
.vp-earnings-tools {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
}
.vp-earnings-month-form { margin: 0; }
.vp-earnings--figma .vp-earnings-month {
    appearance: none;
    -webkit-appearance: none;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23152536'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right .7rem center;
    background-size: 1rem;
    border: none;
    color: #152536;
    border-radius: 999px;
    padding: .55rem 2.15rem .55rem 1rem;
    font: inherit;
    font-size: .84rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
}
.vp-earnings--figma .vp-earnings-month option { color: var(--vp-text); }
.vp-earnings--figma .vp-btn--earnings-download {
    background: transparent;
    border: 1.5px solid rgba(255, 255, 255, .55);
    color: #fff;
    border-radius: 999px;
    padding: .55rem 1.1rem;
    font-size: .84rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    line-height: 1;
}
.vp-earnings--figma .vp-btn--earnings-download:hover {
    background: rgba(255, 255, 255, .1);
    color: #fff;
    border-color: #fff;
}
.vp-earnings--figma .vp-btn--earnings-download .vp-icon {
    width: 1.05rem;
    height: 1.05rem;
}
.vp-earnings--figma .vp-earnings-split {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 1.75rem;
    align-items: center;
}
.vp-earnings--figma .vp-earnings-col-label {
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .72);
    margin-bottom: .55rem;
}
.vp-earnings--figma .vp-earnings-val {
    font-size: 2.15rem;
    font-weight: 800;
    line-height: 1.05;
    color: #fff;
    letter-spacing: -.02em;
}
.vp-earnings--figma .vp-earnings-divider {
    width: 1px;
    align-self: stretch;
    background: rgba(255, 255, 255, .28);
    min-height: 3.5rem;
}
.vp-btn--edit-portfolio {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    white-space: nowrap;
}
.vp-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Figma dashboard: delivery schedule */
.vp-schedule-card {
    margin-bottom: 1.35rem;
    border-radius: 18px;
    overflow: hidden;
}
.vp-schedule-inner {
    display: block;
    padding: 1.35rem 1.5rem 1.25rem;
}
.vp-schedule-title {
    display: block;
    width: 100%;
    margin: 0 0 1.1rem;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1d2939;
    letter-spacing: -.01em;
}
.vp-week-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin: 0 0 1.35rem;
    width: 100%;
}
.vp-week-nav {
    width: auto;
    height: auto;
    min-width: 1.5rem;
    padding: .35rem .2rem;
    border: none;
    border-radius: 0;
    background: transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #98a2b3;
    text-decoration: none;
    flex: 0 0 auto;
    z-index: 1;
}
.vp-week-nav:hover {
    color: #1d2939;
    background: transparent;
    border-color: transparent;
}
.vp-week-nav .vp-icon {
    width: 1.15rem;
    height: 1.15rem;
}
.vp-week-days {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .85rem;
    flex: 0 0 auto;
    width: max-content;
    max-width: calc(100% - 4rem);
    margin: 0 auto;
}
.vp-week-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .32rem;
    flex: 0 0 auto;
    width: 3.2rem;
    min-width: 3.2rem;
    max-width: 3.2rem;
    padding: .7rem 0;
    border-radius: 14px;
    text-decoration: none;
    color: inherit;
    transition: background .15s, color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.vp-week-day-name {
    font-size: .68rem;
    font-weight: 600;
    letter-spacing: .04em;
    color: #98a2b3;
    text-transform: uppercase;
    line-height: 1;
}
.vp-week-day-num {
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1;
    color: #1d2939;
}
.vp-week-day.is-active {
    background: #f25123;
    box-shadow: 0 8px 18px rgba(242, 81, 35, .28);
}
.vp-week-day.is-active .vp-week-day-name,
.vp-week-day.is-active .vp-week-day-num {
    color: #fff;
}
.vp-week-day:not(.is-active):hover {
    background: #fff4ef;
}
.vp-week-day:not(.is-active):hover .vp-week-day-name,
.vp-week-day:not(.is-active):hover .vp-week-day-num {
    color: #f25123;
}

.vp-schedule-list {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    min-height: 12rem;
}
.vp-schedule-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.1rem;
    background: #f8fafb;
    border-radius: 14px;
    border: none;
}
.vp-schedule-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.vp-schedule-icon .vp-icon { width: 1.3rem; height: 1.3rem; }
.vp-schedule-icon--pickup { background: #fff1ea; color: #0d4f4f; }
.vp-schedule-icon--delivery { background: #e6f5f3; color: #0d4f4f; }
.vp-schedule-icon--delivered { background: #e9f8ef; color: #0d4f4f; }
.vp-schedule-main { flex: 1; min-width: 0; }
.vp-schedule-main strong {
    display: block;
    font-size: .95rem;
    font-weight: 700;
    color: #1d2939;
    line-height: 1.3;
}
.vp-schedule-meta {
    margin-top: .25rem;
    font-size: .8rem;
    color: #667085;
}
.vp-schedule-aside {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .4rem;
    flex-shrink: 0;
    margin-left: auto;
}
.vp-schedule-tag {
    display: inline-flex;
    align-items: center;
    padding: .4rem .8rem;
    border-radius: 999px;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .04em;
    white-space: nowrap;
}
.vp-schedule-tag--pickup {
    background: #ffe8df;
    color: #e04a16;
}
.vp-schedule-tag--delivery {
    background: #d8f3ef;
    color: #0f766e;
}
.vp-schedule-tag--delivered {
    background: #dcfce7;
    color: #15803d;
}
.vp-schedule-time {
    font-size: .82rem;
    font-weight: 500;
    color: #667085;
    white-space: nowrap;
}
.vp-empty--schedule {
    flex: 1;
    min-height: 12rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 2.5rem 1rem;
    color: #98a2b3;
    font-size: .9rem;
}

/* Figma bookings card */
.vp-page-head--bookings { margin-bottom: 1rem; }
.vp-bookings-card { margin-bottom: 1.35rem; overflow: visible; }
.vp-bookings-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.15rem 1.35rem .85rem;
}
.vp-bookings-card-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #1d2939;
}
.vp-bookings-tools {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-wrap: wrap;
    margin-left: auto;
}
.vp-bookings-search {
    position: relative;
    display: flex;
    align-items: center;
}
.vp-bookings-search .vp-icon {
    position: absolute;
    left: .75rem;
    width: 1rem;
    height: 1rem;
    color: var(--vp-muted);
    pointer-events: none;
}
.vp-bookings-search-input {
    width: 13.5rem;
    max-width: 100%;
    border: 1px solid var(--vp-border-strong);
    border-radius: 10px;
    padding: .55rem .85rem .55rem 2.15rem;
    font: inherit;
    font-size: .84rem;
    background: #fff;
    color: var(--vp-text);
}
.vp-bookings-search-input:focus {
    outline: none;
    border-color: var(--vp-orange);
    box-shadow: 0 0 0 3px rgba(242, 81, 35, .12);
}
.vp-bookings-date-details { position: relative; z-index: 60; }
.vp-bookings-date-btn {
    list-style: none;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem .85rem;
    border: 1px solid var(--vp-border-strong);
    border-radius: 10px;
    background: #fff;
    font: inherit;
    font-size: .84rem;
    font-weight: 600;
    color: var(--vp-text);
    cursor: pointer;
}
.vp-bookings-date-btn::-webkit-details-marker { display: none; }
.vp-bookings-date-dot {
    width: .45rem;
    height: .45rem;
    border-radius: 50%;
    background: var(--vp-orange);
}
.vp-bookings-date-panel {
    position: absolute;
    top: calc(100% + .4rem);
    left: 0;
    right: auto;
    z-index: 70;
    width: max-content;
    min-width: 20rem;
    max-width: min(22rem, calc(100vw - 2rem));
    padding: 1rem;
    border: 1px solid var(--vp-border);
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
    box-sizing: border-box;
}
.vp-bookings-card-top .vp-bookings-date-panel,
.vp-bookings-tools .vp-bookings-date-panel {
    left: auto;
    right: 0;
}
.vp-products-tools .vp-bookings-date-panel {
    left: 0;
    right: auto;
}
.vp-bookings-date-panel .vp-filters-date-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .65rem;
    min-width: 0;
}
.vp-bookings-date-panel .vp-filters-field,
.vp-bookings-date-panel .vp-filters-field--date {
    min-width: 0;
    max-width: none;
    width: auto;
    flex: 1 1 0;
}
.vp-bookings-date-panel .vp-input,
.vp-bookings-date-panel input {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}
.vp-bookings-date-actions {
    display: flex;
    gap: .5rem;
    margin-top: .85rem;
    flex-wrap: wrap;
}
.vp-bookings-status-select {
    appearance: none;
    -webkit-appearance: none;
    border: 1px solid var(--vp-border-strong);
    border-radius: 10px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7c8f'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") no-repeat right .65rem center / 1rem;
    padding: .55rem 2rem .55rem .85rem;
    font: inherit;
    font-size: .84rem;
    font-weight: 600;
    color: var(--vp-text);
    cursor: pointer;
    min-width: 7.5rem;
}
.vp-btn--export-all {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: #0d4f4f;
    border-color: #0d4f4f;
    color: #fff;
    border-radius: 10px;
    padding: .55rem .9rem;
    font-size: .84rem;
    font-weight: 700;
}
.vp-btn--export-all:hover {
    background: #167272;
    border-color: #167272;
    color: #fff;
}
.vp-bookings-tabs {
    display: flex;
    align-items: center;
    gap: 1.35rem;
    flex-wrap: wrap;
    padding: 0 1.35rem;
    border-bottom: 1px solid var(--vp-border);
}
.vp-bookings-tab {
    position: relative;
    padding: .55rem 0 .7rem;
    text-decoration: none;
    font-size: .9rem;
    font-weight: 600;
    color: var(--vp-muted);
}
.vp-bookings-tab.is-active {
    color: var(--vp-orange);
}
.vp-bookings-tab.is-active::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    height: 2.5px;
    border-radius: 2px 2px 0 0;
    background: var(--vp-orange);
}
.vp-table--bookings {
    min-width: 980px;
}
.vp-table--bookings thead th {
    font-size: .68rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #98a2b3;
    background: #f8fafc;
    padding: .9rem 1.15rem;
    border-bottom: 1px solid var(--vp-border);
}
.vp-table--bookings tbody td {
    padding: 1.05rem 1.15rem;
    vertical-align: middle;
    border-bottom: 1px solid #f0f2f5;
}
.vp-table--bookings tbody tr:last-child td { border-bottom: none; }
.vp-table-customer {
    display: flex;
    align-items: center;
    gap: .65rem;
    min-width: 0;
}
.vp-table-datetotal {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .15rem;
}
.vp-avatar--sm {
    width: 36px;
    height: 36px;
    font-size: .75rem;
    border-width: 1px;
    box-shadow: none;
}
.vp-avatar--sm img { width: 100%; height: 100%; object-fit: cover; }
.vp-status-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .38rem .75rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    white-space: nowrap;
}
.vp-status-pill::after {
    content: '';
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid currentColor;
    opacity: .55;
}
.vp-status-select--new { background: #e8effc; color: #1e429f; }
.vp-status-select--accepted { background: #ffe8df; color: #c2410c; }
.vp-status-select--transit { background: #fff4df; color: #b45309; }
.vp-status-select--done { background: #dcfce7; color: #15803d; }
.vp-status-select--returned { background: #eef2ff; color: #4338ca; }
.vp-status-select--cancelled { background: #fee2e2; color: #b91c1c; }
.vp-btn--icon {
    width: 36px;
    height: 36px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 1px solid var(--vp-border-strong);
    background: #fff;
    color: var(--vp-muted);
}
.vp-btn--icon:hover { color: var(--vp-text); border-color: var(--vp-border-strong); }
.vp-actions--bookings {
    flex-wrap: nowrap;
    gap: .45rem;
}
.vp-bookings-card-foot {
    padding: .9rem 1.35rem 1.15rem;
    border-top: 1px solid var(--vp-border);
    text-align: right;
}
.vp-link-more {
    color: var(--vp-orange);
    font-weight: 700;
    font-size: .88rem;
    text-decoration: none;
}
.vp-link-more:hover { text-decoration: underline; }

@media (max-width: 1100px) {
    .vp-earnings--figma .vp-earnings-split { gap: 1rem; }
}

@media (max-width: 900px) {
    .vp-week-strip {
        width: 100%;
        gap: .5rem;
    }
    .vp-week-days {
        gap: .35rem;
        max-width: calc(100% - 3rem);
    }
    .vp-week-day {
        width: 2.6rem;
        min-width: 2.6rem;
        max-width: 2.6rem;
        padding: .5rem 0;
    }
    .vp-week-day-name { font-size: .55rem; }
    .vp-week-day-num { font-size: .88rem; }
    .vp-bookings-card-top { align-items: stretch; }
    .vp-bookings-tools { width: 100%; margin-left: 0; }
    .vp-bookings-search { flex: 1; }
    .vp-bookings-search-input { width: 100%; }
}

@media (max-width: 768px) {
    .vp-earnings--figma .vp-earnings-split {
        grid-template-columns: 1fr;
        gap: .85rem;
    }
    .vp-earnings--figma .vp-earnings-divider {
        width: 100%;
        height: 1px;
        min-height: 0;
    }
    .vp-earnings--figma .vp-earnings-val {
        font-size: 1.75rem;
    }
    .vp-week-day { padding: .55rem .2rem; }
    .vp-week-day-name { font-size: .58rem; }
    .vp-week-day-num { font-size: .9rem; }
    .vp-schedule-row { flex-wrap: wrap; padding: .9rem; }
    .vp-schedule-aside {
        width: 100%;
        align-items: flex-start;
        padding-left: 3.5rem;
    }
    .vp-bookings-date-panel {
        left: 0;
        right: auto;
        max-width: calc(100vw - 2rem);
    }
    .vp-bookings-card-top .vp-bookings-date-panel,
    .vp-bookings-tools .vp-bookings-date-panel {
        left: 0;
        right: auto;
    }
}

@media (max-width: 480px) {
    .vp-week-strip { gap: .35rem; }
    .vp-week-nav { min-width: 1.25rem; padding: .2rem; }
    .vp-stat-metric strong { font-size: 1.35rem; }
}

.vp-wallet-grid {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.35rem;
}
.vp-wallet-card {
    border-radius: var(--vp-radius-lg); padding: 1.25rem 1.35rem; border: 1px solid var(--vp-border);
    background: #fff; box-shadow: 0 6px 20px rgba(15, 23, 42, .04);
}
.vp-wallet-card--digital { border-top: 4px solid #f59e0b; }
.vp-wallet-card--actual { border-top: 4px solid #15803d; }
.vp-wallet-card-label { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vp-muted); }
.vp-wallet-card-value { font-size: 2rem; font-weight: 800; margin-top: .35rem; line-height: 1.1; color: var(--vp-text); }
.vp-wallet-card-note { margin: .55rem 0 0; font-size: .8rem; color: var(--vp-muted); line-height: 1.45; }
.vp-amount--credit { color: #15803d; }
.vp-amount--debit { color: #dc2626; }

.vp-export-dropdown { position: relative; display: inline-flex; }
.vp-export-menu {
    position: absolute; top: calc(100% + .35rem); right: 0; z-index: 30; min-width: 7.5rem;
    background: #fff; border: 1px solid var(--vp-border-strong); border-radius: 10px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .12); overflow: hidden;
}
.vp-export-menu-item {
    display: block; padding: .55rem .85rem; font-size: .82rem; font-weight: 600;
    color: var(--vp-text); text-decoration: none;
}
.vp-export-menu-item:hover { background: var(--vp-bg-soft); }

.vp-topbar-right { display: flex; align-items: center; gap: .65rem; margin-left: auto; flex-shrink: 0; min-width: 0; max-width: 100%; }
.vp-topbar-wallets { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; justify-content: flex-end; }
.vp-topbar-wallet {
    display: flex; flex-direction: column; align-items: flex-end; gap: .1rem;
    padding: .4rem .7rem; border-radius: 10px; text-decoration: none; border: 1px solid var(--vp-border);
    background: #fff; min-width: 5.5rem;
}
.vp-topbar-wallet--digital { border-top: 3px solid #f59e0b; }
.vp-topbar-wallet--actual { border-top: 3px solid #15803d; }
.vp-topbar-wallet-label {
    font-size: .62rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vp-muted);
}
.vp-topbar-wallet-value { font-size: .92rem; font-weight: 800; color: var(--vp-text); line-height: 1.1; }

/* Buttons */
.vp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
    padding: .62rem 1.15rem; border-radius: 11px; font-weight: 700; font-size: .875rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background .15s, border-color .15s, transform .1s;
}
.vp-btn:active { transform: translateY(1px); }
.vp-btn--primary { background: var(--vp-orange); color: #fff; box-shadow: 0 4px 14px rgba(242, 81, 35, .28); }
.vp-btn--primary:hover { background: var(--vp-orange-hover); }
.vp-btn--dark { background: var(--vp-teal); color: #fff; }
.vp-btn--outline { background: #fff; border-color: var(--vp-border-strong); color: var(--vp-text); }
.vp-btn--ghost { background: transparent; border-color: var(--vp-border); color: var(--vp-muted); }
.vp-btn--danger { background: #fff; border-color: #fecaca; color: #dc2626; }
.vp-btn--sm { padding: .45rem .8rem; font-size: .78rem; border-radius: 9px; }
.vp-btn--block { width: 100%; }

/* Badges */
.vp-badge { display: inline-flex; align-items: center; padding: .28rem .62rem; border-radius: 999px; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; white-space: nowrap; }
.vp-badge--new { background: #ede9fe; color: #6d28d9; }
.vp-badge--accepted { background: #ffedd5; color: #c2410c; }
.vp-badge--transit { background: #fef3c7; color: #b45309; }
.vp-badge--done { background: #dcfce7; color: #15803d; }
.vp-badge--pending { background: #fef9c3; color: #a16207; }
.vp-badge--cancelled { background: #fee2e2; color: #b91c1c; }
.vp-badge--failed { background: #fee2e2; color: #b91c1c; }

/* Table */
.vp-table-wrap { overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch; }
.vp-table { width: 100%; border-collapse: collapse; font-size: .875rem; min-width: 640px; table-layout: auto; }
.vp-table th {
    text-align: left; padding: .9rem 1.15rem; font-size: .68rem; text-transform: uppercase;
    letter-spacing: .05em; color: var(--vp-muted); border-bottom: 1px solid var(--vp-border); background: #fafbfc; white-space: nowrap;
}
.vp-table td {
    padding: 1rem 1.15rem; border-bottom: 1px solid var(--vp-border); vertical-align: middle;
    overflow-wrap: anywhere; word-break: break-word; max-width: 18rem;
}
.vp-table td.vp-td-note,
.vp-table .vp-td-note {
    max-width: 14rem;
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    font-size: .82rem;
    color: var(--vp-muted);
    line-height: 1.45;
}
.vp-table tbody tr:hover td { background: #fbfcfd; }
.vp-table-product { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.vp-thumb { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; background: #f1f5f9; border: 1px solid var(--vp-border); flex-shrink: 0; }


/* Tabs */
.vp-tabs { display: flex; gap: 1.25rem; flex-wrap: wrap; border-bottom: 1px solid var(--vp-border); margin-bottom: 1.15rem; }
.vp-tab {
    padding: .65rem 0; font-size: .88rem; font-weight: 600; text-decoration: none; color: var(--vp-muted);
    border-bottom: 2px solid transparent; margin-bottom: -1px;
}
.vp-tab:hover { color: var(--vp-text); }
.vp-tab--active { color: var(--vp-orange); border-bottom-color: var(--vp-orange); }

/* Forms */
.vp-input, .vp-select, .vp-textarea {
    width: 100%; padding: .72rem .95rem; border: 1px solid var(--vp-border-strong); border-radius: 11px;
    font: inherit; background: #fff; color: var(--vp-text); transition: border-color .15s, box-shadow .15s;
}
.vp-input:focus, .vp-select:focus, .vp-textarea:focus { outline: none; border-color: var(--vp-orange); box-shadow: 0 0 0 3px rgba(242, 81, 35, .12); }
.vp-input::placeholder { color: #9aa8b6; }
.vp-label { display: block; font-size: .8rem; font-weight: 600; margin-bottom: .4rem; color: var(--vp-text); }
.vp-required { color: #dc2626; }
.vp-field { margin-bottom: 1.1rem; }
.vp-field-hint { font-size: .78rem; color: var(--vp-muted); margin-top: .35rem; }
.vp-field-error { font-size: .78rem; color: #dc2626; margin-top: .35rem; font-weight: 500; }
.vp-input--error, .vp-select--error, .vp-textarea--error { border-color: #fca5a5 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, .08) !important; }
.vp-portfolio-delete-form { margin: 0; }
.vp-file { font-size: .85rem; }

/* Modal alerts (matches admin panel) */
.vp-modal-alert {
    position: fixed; inset: 0; z-index: 300; display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.vp-modal-alert-backdrop {
    position: absolute; inset: 0; background: rgba(15, 23, 42, .45); backdrop-filter: blur(2px);
}
.vp-modal-alert-card {
    position: relative; z-index: 10; width: 100%; max-width: 28rem; border-radius: 1rem;
    background: #fff; padding: 2rem; text-align: center;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, .25);
}
.vp-modal-alert-card--animate {
    animation: vp-modal-card-in .22s ease-out;
}
@keyframes vp-modal-card-in {
    from { opacity: 0; transform: scale(.96) translateY(6px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.vp-modal-alert-icon-wrap {
    position: relative; margin: 0 auto 1.25rem; display: flex; width: 5rem; height: 5rem;
    align-items: center; justify-content: center;
}
.vp-modal-alert-icon-ring { position: absolute; inset: 0; border-radius: 50%; opacity: .3; }
.vp-modal-alert-icon-wrap--success .vp-modal-alert-icon-ring { background: #38bdf8; }
.vp-modal-alert-icon-wrap--error .vp-modal-alert-icon-ring { background: #fb7185; }
.vp-modal-alert-icon-wrap--warning .vp-modal-alert-icon-ring { background: #fbbf24; }
.vp-modal-alert-icon {
    position: relative; display: flex; width: 3.5rem; height: 3.5rem; align-items: center; justify-content: center;
    border-radius: 50%; color: #fff;
}
.vp-modal-alert-icon svg { width: 1.75rem; height: 1.75rem; }
.vp-modal-alert-icon-wrap--success .vp-modal-alert-icon { background: #0ea5e9; box-shadow: 0 10px 15px -3px rgba(14, 165, 233, .3); }
.vp-modal-alert-icon-wrap--error .vp-modal-alert-icon { background: #f43f5e; box-shadow: 0 10px 15px -3px rgba(244, 63, 94, .3); }
.vp-modal-alert-icon-wrap--warning .vp-modal-alert-icon { background: #f59e0b; box-shadow: 0 10px 15px -3px rgba(245, 158, 11, .3); }
.vp-modal-alert-title { font-size: 1.25rem; font-weight: 700; letter-spacing: -.02em; color: #0f172a; margin: 0; }
.vp-modal-alert-message { margin: .5rem auto 0; max-width: 20rem; font-size: .875rem; line-height: 1.6; color: #64748b; }
.vp-modal-alert-btn {
    margin-top: 1.5rem; width: 100%; border: none; border-radius: .75rem; padding: .75rem 1rem;
    font-size: .875rem; font-weight: 600; color: #fff; cursor: pointer; transition: background .15s;
    background: var(--vp-orange); box-shadow: 0 4px 14px rgba(242, 81, 35, .28);
}
.vp-modal-alert-btn:hover { background: var(--vp-orange-hover); }
.vp-modal-alert-actions {
    display: flex; gap: .75rem; margin-top: 1.5rem;
}
.vp-modal-alert-actions .vp-modal-alert-btn { margin-top: 0; flex: 1 1 0; }
.vp-modal-alert-btn--ghost {
    background: #f1f5f9; color: #334155; box-shadow: none;
}
.vp-modal-alert-btn--ghost:hover { background: #e2e8f0; }
.vp-modal-enter { transition: opacity .25s ease-out; }
.vp-modal-enter-start { opacity: 0; }
.vp-modal-enter-end { opacity: 1; }
.vp-modal-leave { transition: opacity .2s ease-in; }
.vp-modal-leave-start { opacity: 1; }
.vp-modal-leave-end { opacity: 0; }
.vp-modal-card-enter { transition: all .3s cubic-bezier(.16, 1, .3, 1); }
.vp-modal-card-enter-start { opacity: 0; transform: scale(.92) translateY(8px); }
.vp-modal-card-enter-end { opacity: 1; transform: scale(1) translateY(0); }
.vp-modal-card-leave { transition: all .2s ease-in; }
.vp-modal-card-leave-start { opacity: 1; transform: scale(1) translateY(0); }
.vp-modal-card-leave-end { opacity: 0; transform: scale(.95) translateY(4px); }

.vp-pending-banner { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: .85rem 1rem; border-radius: 12px; margin-bottom: 1.25rem; font-size: .875rem; }

/* Schedule */
.vp-schedule-item { display: flex; gap: .85rem; padding: .9rem 0; border-bottom: 1px solid var(--vp-border); }
.vp-schedule-item:last-child { border-bottom: none; }

/* Chat */


/* ajay chat */


/* ajay chat */
.vp-page-head--compact { margin-bottom: 0.75rem; }
.vp-page--chat .vp-page-head--compact {
    margin-bottom: 0.35rem;
    flex-shrink: 0;
}
.vp-page--chat .vp-page-title {
    font-size: 1.35rem;
    line-height: 1.2;
}
.vp-chat-layout {
    display: grid;
    grid-template-columns: minmax(260px, 320px) minmax(0, 1fr);
    gap: 0.75rem;
    width: 100%;
    height: calc(100vh - 9rem);
    max-height: calc(100vh - 9rem);
    min-height: 20rem;
    margin-bottom: 0;
}
.vp-chat-sidebar,
.vp-chat-main {
    background: #fff;
    border: 1px solid var(--vp-border);
    border-radius: 16px;
    box-shadow: 0 1px 3px rgb(15 23 42 / 0.04);
    overflow: hidden;
    min-height: 0;
}
.vp-chat-sidebar {
    display: flex;
    flex-direction: column;
    min-width: 0;
    height: 100%;
    align-self: stretch;
}
.vp-chat-sidebar-title {
    margin: 0;
    padding: 1rem 1.15rem .5rem;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--vp-muted);
}
.vp-chat-search { padding: 0 1rem .85rem; }
.vp-chat-search .vp-input { font-size: .84rem; padding: .55rem .75rem; }
.vp-chat-threads {
    flex: 1 1 auto;
    min-height: 0;
    max-height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior: contain;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.vp-chat-threads::-webkit-scrollbar { display: none; }
.vp-chat-thread {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.75rem 0.85rem;
    min-height: 0;
    text-decoration: none;
    color: inherit;
    border-left: 3px solid transparent;
    border-bottom: 1px solid var(--vp-border);
    transition: background .15s;
}
.vp-chat-thread:hover { background: #f8fafc; }
.vp-chat-thread.is-active {
    background: var(--vp-orange-soft);
    border-left-color: var(--vp-orange);
}
.vp-chat-avatar {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 999px;
    object-fit: cover;
    flex-shrink: 0;
}
.vp-chat-avatar--fallback {
    display: grid;
    place-items: center;
    background: var(--vp-orange-soft);
    color: var(--vp-orange);
    font-weight: 700;
    font-size: .95rem;
}
.vp-chat-thread-body { min-width: 0; flex: 1; }
.vp-chat-thread-top {
    display: flex;
    justify-content: space-between;
    gap: .5rem;
    font-size: .84rem;
}
.vp-chat-thread-top span {
    color: var(--vp-muted);
    font-size: .7rem;
    white-space: nowrap;
}
.vp-chat-thread-body p {
    margin: .25rem 0 0;
    font-size: .82rem;
    color: var(--vp-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.vp-chat-empty-sidebar,
.vp-chat-main-empty {
    padding: 2rem 1.25rem;
    text-align: center;
    color: var(--vp-muted);
    font-size: .875rem;
}
.vp-chat-main {
    display: flex;
    flex-direction: column;
    min-height: 0;
    height: 90%;
    align-self: stretch;
}
.vp-chat-main-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.15rem;
    border-bottom: 1px solid var(--vp-border);
    flex-shrink: 0;
}
.vp-chat-main-user {
    display: flex;
    align-items: center;
    gap: .75rem;
    min-width: 0;
}
.vp-chat-main-user strong { display: block; font-size: .95rem; }
.vp-chat-main-sub {
    display: block;
    font-size: .78rem;
    color: var(--vp-muted);
    font-weight: 500;
}
.vp-chat-back {
    display: none;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    margin-right: 0.15rem;
    border: 1px solid var(--vp-border);
    border-radius: 10px;
    background: #fff;
    text-decoration: none;
    color: var(--vp-text);
    flex-shrink: 0;
    padding: 0;
    cursor: pointer;
    font: inherit;
    transition: background .15s, border-color .15s;
}
.vp-chat-back:hover {
    background: #f8fafc;
    border-color: var(--vp-border-strong);
}
.vp-chat-back .vp-icon {
    width: 1.15rem;
    height: 1.15rem;
}
.vp-chat-messages {
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
}
.vp-chat-messages::-webkit-scrollbar { display: none; }
.vp-chat-messages-track {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: stretch;
    gap: .85rem;
    min-height: 100%;
    padding: 1.15rem;
    box-sizing: border-box;
}
.vp-chat-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    align-self: flex-start;
    max-width: 78%;
    min-width: 0;
}
.vp-chat-row--theirs {
    align-self: flex-start;
    align-items: flex-start;
}
.vp-chat-row--mine {
    align-self: flex-end;
    align-items: flex-end;
}
.vp-chat-bubble {
    max-width: 100%;
    padding: .75rem 1rem;
    border-radius: 1rem;
    font-size: .875rem;
    line-height: 1.5;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.vp-chat-bubble p { margin: 0; white-space: pre-wrap; word-break: break-word; }
.vp-chat-bubble--theirs {
    background: #fff;
    color: #0f172a;
    border: 1px solid var(--vp-border);
    border-bottom-left-radius: .25rem;
}
.vp-chat-bubble--mine {
    background: var(--vp-teal);
    color: #fff;
    border: 1px solid transparent;
    border-bottom-right-radius: .25rem;
}
.vp-chat-time {
    margin-top: .3rem;
    font-size: .68rem;
    color: var(--vp-muted);
}
.vp-chat-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .2rem;
    margin-top: .2rem;
}
.vp-chat-row--theirs .vp-chat-meta {
    align-items: flex-start;
}
.vp-chat-message-actions {
    display: flex;
    gap: .55rem;
}
.vp-chat-action {
    border: 0;
    background: none;
    padding: 0;
    font-size: .68rem;
    font-weight: 700;
    color: var(--vp-muted);
    cursor: pointer;
}
.vp-chat-action:hover { color: #0f172a; }
.vp-chat-action--danger:hover { color: #b42318; }
.vp-chat-edited {
    color: var(--vp-muted);
    font-weight: 500;
}
.vp-chat-edit-banner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    margin: 0 .15rem;
    padding: .65rem .75rem;
    border-left: 3px solid var(--vp-teal, #0f766e);
    border-radius: .65rem;
    background: #ecfeff;
}
.vp-chat-edit-banner[hidden] {
    display: none !important;
}
.vp-chat-edit-banner-copy {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: .15rem;
}
.vp-chat-edit-banner-copy strong {
    font-size: .75rem;
    font-weight: 800;
    color: var(--vp-teal, #0f766e);
}
.vp-chat-edit-banner-copy span {
    font-size: .8125rem;
    color: var(--vp-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.vp-chat-edit-banner-close {
    border: 0;
    background: transparent;
    color: var(--vp-muted);
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    padding: 0;
}
.vp-chat-row.is-editing .vp-chat-bubble {
    outline: 2px solid rgb(15 118 110 / 0.35);
    outline-offset: 2px;
}
.vp-chat-compose.is-editing-message .vp-chat-attach[hidden] {
    display: none !important;
}
.vp-chat-attachment {
    display: block;
    margin-top: .5rem;
    max-width: 12rem;
    border-radius: .5rem;
}
.vp-chat-attachment video {
    display: block;
    width: 100%;
    max-width: 14rem;
    border-radius: .5rem;
    background: #000;
}
.vp-chat-file,
.vp-chat-attachment--file,
.jbw-chat-attachment--file {
    display: flex !important;
    align-items: center;
    gap: .65rem;
    max-width: 16rem;
    width: max-content;
    margin-top: .5rem;
    padding: .65rem .75rem;
    border-radius: .75rem;
    text-decoration: none !important;
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.28);
    color: inherit;
}
.vp-chat-bubble--theirs .vp-chat-file,
.vp-chat-bubble--theirs .vp-chat-attachment--file,
.jbw-chat-bubble--theirs .jbw-chat-attachment--file {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #0f172a;
}
.vp-chat-file-icon {
    display: grid;
    place-items: center;
    width: 2rem;
    height: 2rem;
    border-radius: .5rem;
    background: rgba(255, 255, 255, 0.18);
    flex-shrink: 0;
}
.vp-chat-bubble--theirs .vp-chat-file-icon {
    background: #e2e8f0;
    color: #334155;
}
.vp-chat-file-meta {
    display: flex;
    flex-direction: column;
    gap: .1rem;
    min-width: 0;
}
.vp-chat-file-name {
    font-size: .8rem;
    font-weight: 600;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 11rem;
}
.vp-chat-file-action {
    font-size: .68rem;
    opacity: .85;
}
.vp-chat-compose {
    display: flex;
    align-items: flex-end;
    gap: .55rem;
    padding: .85rem 1rem;
    border-top: 1px solid var(--vp-border);
    background: #fff;
    flex-shrink: 0;
}
.vp-chat-attach {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    flex-shrink: 0;
    cursor: pointer;
    color: var(--vp-muted);
}
.vp-chat-attach-icon {
    width: 1.35rem;
    height: 1.35rem;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    display: grid;
    place-items: center;
    font-size: 1rem;
    line-height: 1;
}
.vp-chat-input {
    flex: 1;
    min-height: 2.5rem;
    max-height: 6rem;
    resize: none;
    border: 1px solid var(--vp-border);
    border-radius: 999px;
    padding: .65rem 1rem;
    font: inherit;
    font-size: .875rem;
    background: #f8fafc;
}
.vp-chat-input:focus {
    outline: none;
    border-color: var(--vp-orange);
    box-shadow: 0 0 0 3px rgb(242 81 35 / 0.1);
    background: #fff;
}
.vp-chat-send {
    width: 2.5rem;
    height: 2.5rem;
    border: none;
    border-radius: 999px;
    background: var(--vp-orange);
    color: #fff;
    display: grid;
    place-items: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s;
}
.vp-chat-send:hover { background: #e04820; }
.vp-chat-send svg { transform: rotate(45deg); }
.vp-chat-empty-thread {
    margin: 0 auto;
    color: var(--vp-muted);
    font-size: .875rem;
}

@media (max-width: 1023px) {
    .vp-page--chat-active .vp-page-head--compact {
        display: none;
    }

    .vp-chat-layout {
        display: grid !important;
        grid-template-columns: 1fr !important;
        grid-template-rows: minmax(0, 1fr) !important;
        position: relative;
        height: 100% !important;
        max-height: none !important;
        min-height: 0;
        gap: 0 !important;
        overflow: hidden;
    }

    .vp-chat-sidebar,
    .vp-chat-main {
        min-height: 0;
        height: 100%;
        align-self: stretch;
    }

    .vp-chat-main--mobile-hide {
        display: none !important;
    }

    .vp-chat-sidebar--mobile-hide {
        display: none !important;
    }

    .vp-chat-sidebar--mobile-hide.vp-chat-sidebar--mobile-open {
        display: flex !important;
        position: absolute;
        inset: 0;
        z-index: 25;
        width: 100%;
        max-width: none;
        height: 100%;
    }

    .vp-chat-back {
        display: inline-flex !important;
    }

    .vp-chat-sidebar-mobile-head {
        display: none;
        align-items: center;
        gap: 0.85rem;
        padding: 1rem 1.15rem 0.85rem;
        flex-shrink: 0;
        border-bottom: 1px solid var(--vp-border);
    }

    .vp-chat-sidebar--mobile-open .vp-chat-sidebar-mobile-head {
        display: flex;
    }

    .vp-chat-sidebar-title--desktop-only {
        display: none;
    }

    .vp-chat-sidebar-title--mobile {
        margin: 0;
        padding: 0;
        flex: 1;
        min-width: 0;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--vp-muted);
    }

    .vp-chat-sidebar--mobile-open .vp-chat-search {
        padding-top: 0.85rem;
    }

    .vp-chat-sidebar-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border: 1px solid var(--vp-border);
        border-radius: 10px;
        background: #fff;
        color: var(--vp-text);
        cursor: pointer;
        flex-shrink: 0;
        padding: 0;
        font: inherit;
    }

    .vp-chat-sidebar-close .vp-icon {
        width: 1.15rem;
        height: 1.15rem;
    }

    .vp-chat-messages-track {
        justify-content: flex-end;
    }

    .vp-chat-row {
        max-width: 85%;
    }
}

@media (min-width: 1024px) {
    .vp-chat-sidebar-mobile-head {
        display: none !important;
    }

    .vp-chat-sidebar-title--mobile {
        display: none;
    }
}

/* Vendor chat page: fit shell, no outer scroll */
.vp-body--chat {
    overflow: hidden;
}
.vp-body--chat .vp-main {
    overflow: hidden;
    height: 100vh;
    height: 100dvh;
    min-height: 0;
}
.vp-body--chat .vp-content {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0.65rem 1rem 0.75rem;
}
.vp-page--chat {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    width: 100%;
    max-width: none;
    margin-inline: 0;
}
.vp-body--chat .vp-chat-layout {
    display: grid;
    flex: 1 1 auto;
    min-height: 0;
    width: 100%;
    max-width: none;
    height: auto;
    max-height: none;
    margin-bottom: 0;
    align-content: stretch;
}

/* Settings */
.vp-settings-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.vp-settings-layout { display: grid; grid-template-columns: 260px 1fr; gap: 1.25rem; align-items: start; }
.vp-settings-nav-card {
    padding: .65rem; position: sticky; top: 5rem; align-self: start;
    max-height: calc(100vh - 6.5rem); overflow-y: auto;
}
.vp-settings-nav a {
    display: flex; align-items: center; gap: .7rem; padding: .72rem .85rem; border-radius: 10px;
    text-decoration: none; font-weight: 600; font-size: .88rem; color: var(--vp-muted); margin-bottom: 2px;
    border-left: 3px solid transparent;
}
.vp-settings-nav a:hover { background: #f8fafc; color: var(--vp-text); }
.vp-settings-nav a.active {
    background: var(--vp-orange-soft); color: var(--vp-orange); border-left-color: var(--vp-orange);
}
.vp-settings-nav a.active .vp-icon { color: var(--vp-orange); }
.vp-settings-panel { padding: 1.5rem 1.65rem; }
.vp-settings-panel-title { margin: 0 0 1.35rem; font-size: 1.05rem; font-weight: 700; }
.vp-settings-panel-sub { margin: -.85rem 0 1.35rem; font-size: .88rem; color: var(--vp-muted); line-height: 1.5; }
.vp-settings-panel-foot { display: flex; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--vp-border); }

.vp-toggle-wrap { display: flex; align-items: center; gap: .65rem; }
.vp-toggle-label { font-size: .88rem; font-weight: 600; color: var(--vp-text); }
.vp-toggle { position: relative; width: 48px; height: 26px; flex-shrink: 0; }
.vp-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.vp-toggle-track {
    position: absolute; inset: 0; background: #d5dce3; border-radius: 999px; cursor: pointer; transition: background .2s;
}
.vp-toggle-track::after {
    content: ''; position: absolute; width: 20px; height: 20px; left: 3px; top: 3px; background: #fff;
    border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.15);
}
.vp-toggle input:checked + .vp-toggle-track { background: var(--vp-orange); }
.vp-toggle input:checked + .vp-toggle-track::after { transform: translateX(22px); }
.vp-toggle input:disabled + .vp-toggle-track { opacity: .55; cursor: not-allowed; }

/* Products list (Figma) */
.vp-products-card {
    margin-bottom: 1.35rem;
    overflow: visible;
}
.vp-products-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.15rem 1.35rem;
    border-bottom: 1px solid var(--vp-border);
    overflow: visible;
    position: relative;
    z-index: 5;
}
.vp-products-tools {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-wrap: wrap;
    flex: 1 1 auto;
    min-width: 0;
    overflow: visible;
}
.vp-table--products { min-width: 920px; }
.vp-table--products thead th {
    font-size: .68rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #98a2b3;
    background: #f8fafc;
    padding: .9rem 1.15rem;
}
.vp-table--products tbody td {
    padding: 1.05rem 1.15rem;
    vertical-align: middle;
    border-bottom: 1px solid #f0f2f5;
}
.vp-table--products tbody tr:last-child td { border-bottom: none; }
.vp-rating {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-weight: 700;
    color: #1d2939;
}
.vp-rating-star {
    width: 1rem;
    height: 1rem;
    color: #f5b301;
}
.vp-listing-toggle {
    display: inline-flex;
    align-items: center;
    gap: .55rem;
    margin: 0;
}
.vp-listing-toggle.is-disabled { opacity: .7; }
.vp-listing-toggle-label {
    font-size: .84rem;
    font-weight: 700;
    color: #98a2b3;
}
.vp-listing-toggle-label.is-active { color: var(--vp-orange); }
.vp-actions--products {
    flex-wrap: nowrap;
    gap: .4rem;
}
.vp-btn--icon-view { color: #2563eb; border-color: #bfdbfe; }
.vp-btn--icon-view:hover { color: #1d4ed8; background: #eff6ff; }
.vp-btn--icon-edit { color: var(--vp-orange); border-color: #ffd8c8; }
.vp-btn--icon-edit:hover { color: var(--vp-orange-hover); background: var(--vp-orange-soft); }
.vp-btn--icon-delete { color: #dc2626; border-color: #fecaca; }
.vp-btn--icon-delete:hover { color: #b91c1c; background: #fef2f2; }
.vp-table-color-size {
    display: flex;
    flex-direction: column;
    gap: .4rem;
    min-width: 7rem;
}
.vp-table-swatches {
    display: flex;
    align-items: center;
    gap: .3rem;
}
.vp-table-swatch {
    width: 18px;
    height: 18px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, .1);
    flex-shrink: 0;
}
.vp-table-swatch--light { border-color: #d0d5dd; }
.vp-table-sizes {
    font-size: .84rem;
    font-weight: 600;
    color: #475467;
}

/* Product view (Figma) */
.vp-product-view {
    overflow: hidden;
    margin-bottom: 1.35rem;
}
.vp-product-view-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.15rem 1.35rem;
    border-bottom: 1px solid var(--vp-border);
}
.vp-product-view-back {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1d2939;
    text-decoration: none;
}
.vp-product-view-back:hover { color: var(--vp-orange); }
.vp-product-view-actions {
    display: flex;
    align-items: center;
    gap: .55rem;
}
.vp-btn--view-edit {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: #fff1ea;
    border: 1px solid #ffd8c8;
    color: var(--vp-orange);
    border-radius: 10px;
    padding: .55rem .95rem;
    font-size: .84rem;
    font-weight: 700;
    text-decoration: none;
}
.vp-btn--view-edit:hover {
    background: #ffe4d6;
    color: var(--vp-orange-hover);
}
.vp-btn--view-delete {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    border-radius: 10px;
    padding: .55rem .95rem;
    font-size: .84rem;
    font-weight: 700;
    cursor: pointer;
}
.vp-btn--view-delete:hover {
    background: #fee2e2;
    color: #b91c1c;
}
.vp-product-view-body {
    display: grid;
    grid-template-columns: minmax(240px, 340px) minmax(0, 1fr);
    gap: 2rem;
    padding: 1.5rem 1.5rem 1.75rem;
    align-items: start;
}
.vp-product-view-media { min-width: 0; }
.vp-product-view-hero {
    width: 100%;
    aspect-ratio: 3 / 4;
    object-fit: cover;
    border-radius: 18px;
    background: #f1f5f9;
    display: block;
}
.vp-product-view-hero--empty {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--vp-muted);
    font-weight: 600;
}
.vp-product-view-thumbs {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-top: .85rem;
}
.vp-product-view-thumb {
    width: 64px;
    height: 64px;
    padding: 0;
    border-radius: 10px;
    border: 2px solid transparent;
    background: #f8fafc;
    overflow: hidden;
    cursor: pointer;
    display: grid;
    place-items: center;
    position: relative;
}
.vp-product-view-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.vp-product-view-thumb.is-active {
    border-color: var(--vp-orange);
}
.vp-product-view-thumb--video {
    background: #101828;
    padding: 0;
}
.vp-product-view-thumb--video video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    pointer-events: none;
}
.vp-product-view-thumb-video-label {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: .2rem .25rem;
    font-size: .58rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #fff;
    text-align: center;
    background: linear-gradient(to top, rgba(16, 24, 40, .8), transparent);
    pointer-events: none;
}
.vp-product-view-title {
    margin: 0;
    font-size: 1.65rem;
    font-weight: 800;
    color: #1d2939;
    letter-spacing: -.02em;
}
.vp-product-view-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.vp-product-view-title-row--tag {
    align-items: center;
    margin-top: .75rem;
}
.vp-product-view-tags--inline {
    margin-top: .65rem;
}
.vp-avail-switch {
    display: inline-flex;
    align-items: stretch;
    border-radius: 999px;
    background: #f2f4f7;
    padding: .2rem;
    gap: .15rem;
    flex-shrink: 0;
}
.vp-avail-switch.is-disabled {
    opacity: .65;
    pointer-events: none;
}
.vp-avail-switch-btn {
    border: 0;
    background: transparent;
    color: #98a2b3;
    font-size: .78rem;
    font-weight: 700;
    padding: .45rem .9rem;
    border-radius: 999px;
    cursor: pointer;
    white-space: nowrap;
}
.vp-avail-switch-btn.is-on.is-available {
    background: #12b76a;
    color: #fff;
    box-shadow: 0 1px 2px rgba(18, 183, 106, .35);
}
.vp-avail-switch-btn.is-on.is-unavailable {
    background: #98a2b3;
    color: #fff;
}
.vp-product-view-meta--rental {
    gap: 2.5rem;
    margin-top: 1.25rem;
}
.vp-product-view-rating--gold {
    color: #f5b301;
}
.vp-product-view-rating--gold .vp-rating-star {
    color: #f5b301;
}
.vp-product-view--rental .vp-product-view-desc {
    margin-top: 1.35rem;
    padding-top: 1.15rem;
    border-top: 1px solid #eaecf0;
}
.vp-product-view--rental .vp-product-view-options {
    margin-top: 1.15rem;
}
.vp-product-view-tags {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    margin-top: .75rem;
}
.vp-product-view-tag {
    display: inline-flex;
    padding: .28rem .7rem;
    border-radius: 999px;
    background: #ffe8df;
    color: #c2410c;
    font-size: .75rem;
    font-weight: 700;
}
.vp-product-view-tag--muted {
    background: #f1f5f9;
    color: #64748b;
}
.vp-product-view-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.75rem;
    margin-top: 1.35rem;
}
.vp-product-view-meta-label {
    font-size: .78rem;
    font-weight: 600;
    color: #98a2b3;
    margin-bottom: .3rem;
}
.vp-product-view-meta-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1d2939;
}
.vp-product-view-meta-value--sm {
    font-size: .95rem;
    font-weight: 700;
}
.vp-product-view-meta-value--strike {
    color: #98a2b3;
    text-decoration: line-through;
    font-weight: 700;
}
.vp-product-view-rating {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
}
.vp-product-view-status-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-top: 1.15rem;
}
.vp-product-view-options {
    margin-top: 1.25rem;
}
.vp-product-view-options-label {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #98a2b3;
    margin-bottom: .65rem;
}
.vp-product-view-colors {
    display: flex;
    flex-wrap: wrap;
    gap: .55rem;
}
.vp-product-view-color-cards {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
}
.vp-product-view-color-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .4rem;
    width: 4.5rem;
    padding: 0;
    border: 2px solid transparent;
    border-radius: 12px;
    background: transparent;
    cursor: pointer;
    color: inherit;
}
.vp-product-view-color-card.is-active,
.vp-product-view-color-card:hover {
    border-color: var(--vp-orange);
}
.vp-product-view-color-card-img {
    width: 4.25rem;
    height: 4.25rem;
    object-fit: cover;
    border-radius: 10px;
    display: block;
    background: #f1f5f9;
}
.vp-product-view-color-card--swatch-only {
    padding-top: .35rem;
}
.vp-product-view-color-card--swatch-only .vp-product-view-swatch {
    width: 2.5rem;
    height: 2.5rem;
}
.vp-product-view-color-card-name {
    font-size: .72rem;
    font-weight: 600;
    color: #475467;
    text-align: center;
    line-height: 1.2;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.vp-product-view-swatch {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, .08);
    box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
    flex-shrink: 0;
}
.vp-product-view-swatch--light {
    border-color: #d0d5dd;
}
.vp-product-view-sizes {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}
.vp-product-view-size {
    min-width: 40px;
    height: 40px;
    padding: 0 .75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #f2f4f7;
    color: #344054;
    font-size: .88rem;
    font-weight: 700;
}
.vp-product-view-desc {
    margin-top: 1.5rem;
}
.vp-product-view-desc h2,
.vp-product-view-section h2 {
    margin: 0 0 .35rem;
    font-size: 1rem;
    font-weight: 700;
    color: #1d2939;
}
.vp-product-view-desc p {
    margin: 0;
    font-size: .92rem;
    line-height: 1.6;
    color: #667085;
}
.vp-product-view-sections {
    padding: 0 1.5rem 1.75rem;
    display: grid;
    gap: 1.15rem;
}
.vp-product-view-section {
    margin-top: 0;
    padding: 1.15rem 1.2rem;
    border: 1px solid var(--vp-border);
    border-radius: 14px;
    background: #fff;
}
.vp-product-view-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: .95rem;
}
.vp-product-view-section-head p {
    margin: 0;
    font-size: .82rem;
    color: #98a2b3;
}
.vp-product-view-empty {
    margin: 0;
    font-size: .88rem;
    color: #98a2b3;
}
.vp-product-view-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
}
.vp-product-view-gallery-btn {
    width: 88px;
    height: 88px;
    padding: 0;
    border-radius: 12px;
    border: 2px solid transparent;
    background: #f8fafc;
    overflow: hidden;
    cursor: pointer;
    display: grid;
    place-items: center;
    position: relative;
}
.vp-product-view-gallery-btn.is-active {
    border-color: var(--vp-orange);
}
.vp-product-view-gallery-btn:hover {
    border-color: #fdba8c;
}
.vp-product-view-gallery-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border: none;
    border-radius: 0;
    cursor: inherit;
    pointer-events: none;
}
.vp-product-view-gallery-video {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: .25rem;
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #fff;
    text-align: center;
    background: linear-gradient(to top, rgba(16, 24, 40, .8), transparent);
    pointer-events: none;
}
.vp-product-view-variant-list,
.vp-product-view-rule-list {
    display: grid;
    gap: .75rem;
}
.vp-product-view-variant-card,
.vp-product-view-rule-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: .85rem 1rem;
    border: 1px solid #edf1f5;
    border-radius: 12px;
    background: #fafbfc;
}
.vp-product-view-variant-img {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--vp-border);
    flex-shrink: 0;
    background: #f1f5f9;
}
.vp-product-view-variant-img--empty {
    display: block;
}
.vp-product-view-variant-fields,
.vp-product-view-rule-card {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem 1.75rem;
    flex: 1;
    min-width: 0;
}
.vp-product-view-rule-card {
    align-items: flex-start;
}

@media (max-width: 900px) {
    .vp-product-view-body {
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    .vp-product-view-hero {
        max-width: 320px;
        margin: 0 auto;
    }
    .vp-product-view-sections {
        padding: 0 1rem 1.25rem;
    }
}

@media (max-width: 900px) {
    .vp-products-toolbar { align-items: stretch; }
    .vp-products-tools { width: 100%; }
}

.vp-input-icon-wrap { position: relative; }
.vp-input-icon-wrap .vp-icon { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); width: 1.1rem; height: 1.1rem; color: var(--vp-muted); }
.vp-input-icon-wrap .vp-input { padding-left: 2.65rem; background: #f8fafc; border-color: #edf1f5; }
.vp-input-icon-wrap .vp-input-toggle {
    position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: var(--vp-muted); cursor: pointer; padding: .25rem;
}

.vp-profile-layout { display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; align-items: start; }
.vp-cover-block { position: relative; }
.vp-cover-label { font-size: .8rem; font-weight: 600; margin-bottom: .55rem; display: block; }
.vp-cover-frame {
    position: relative; height: 160px; border-radius: 14px; overflow: hidden; background: linear-gradient(135deg, #ffe8de, #fff4ef);
    border: 1px solid var(--vp-border);
}
.vp-cover-frame img { width: 100%; height: 100%; object-fit: cover; }
.vp-cover-placeholder { width: 100%; height: 100%; display: grid; place-items: center; color: var(--vp-muted); font-size: .82rem; }
.vp-cover-edit, .vp-profile-edit {
    position: absolute; width: 34px; height: 34px; border-radius: 50%; background: var(--vp-orange); color: #fff;
    border: 2px solid #fff; display: flex; align-items: center; justify-content: center; cursor: pointer;
    box-shadow: 0 4px 12px rgba(242,81,35,.35);
}
.vp-cover-edit .vp-icon, .vp-profile-edit .vp-icon, .vp-portfolio-edit .vp-icon { width: .95rem; height: .95rem; }
.vp-cover-edit { top: .65rem; right: .65rem; }
.vp-profile-edit { width: 30px; height: 30px; bottom: 2px; right: 2px; }
.vp-profile-avatar-wrap {
    position: absolute; left: 1rem; bottom: -36px; width: 88px; height: 88px; border-radius: 50%;
    border: 4px solid #fff; overflow: hidden; background: var(--vp-orange-soft); box-shadow: 0 4px 14px rgba(16,24,40,.1);
}
.vp-profile-avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
.vp-profile-avatar-fallback {
    width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.5rem; color: var(--vp-orange);
}
.vp-cover-block { margin-bottom: 2.75rem; }

.vp-editor-toolbar {
    display: flex; flex-wrap: wrap; gap: .25rem; padding: .55rem .65rem; border: 1px solid var(--vp-border);
    border-bottom: none; border-radius: 11px 11px 0 0; background: #fafbfc;
}
.vp-editor-btn {
    width: 32px; height: 32px; border: none; background: transparent; border-radius: 7px;
    color: var(--vp-muted); font-weight: 700; cursor: pointer; font-size: .82rem;
}
.vp-editor-btn:hover { background: #eef2f6; color: var(--vp-text); }
.vp-editor-area {
    width: 100%; min-height: 220px; padding: 1rem; border: 1px solid var(--vp-border); border-radius: 0 0 11px 11px;
    font: inherit; resize: vertical; background: #fff;
}
.vp-editor-area:focus { outline: none; border-color: var(--vp-orange); box-shadow: 0 0 0 3px rgba(242,81,35,.1); }

.vp-portfolio-tabs { display: flex; gap: 1.75rem; border-bottom: 1px solid var(--vp-border); margin: 0 0 1.35rem; }
.vp-portfolio-tab {
    padding: .35rem 0 .7rem; font-weight: 600; font-size: .95rem; text-decoration: none; color: #94a3b8;
    border-bottom: 2.5px solid transparent; margin-bottom: -1px; transition: color .15s, border-color .15s;
}
.vp-portfolio-tab:hover { color: var(--vp-text); }
.vp-portfolio-tab--active { color: var(--vp-orange); border-bottom-color: var(--vp-orange); }
.vp-portfolio-panel { display: flex; flex-direction: column; gap: 1.25rem; }
.vp-portfolio-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
.vp-portfolio-item { position: relative; border-radius: 14px; overflow: hidden; aspect-ratio: 1; background: #f1f5f9; }
.vp-portfolio-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.vp-portfolio-edit {
    position: absolute; bottom: .6rem; left: .6rem; width: 32px; height: 32px; border-radius: 50%;
    background: var(--vp-orange); color: #fff; border: 2px solid #fff; display: flex; align-items: center; justify-content: center;
    text-decoration: none; box-shadow: 0 2px 8px rgba(242,81,35,.35); cursor: pointer; padding: 0;
}
.vp-portfolio-edit .vp-icon { width: .95rem; height: .95rem; }
.vp-portfolio-empty {
    margin: 0; padding: 1.5rem 0 .25rem; text-align: center; color: var(--vp-muted); font-size: .9rem;
}
.vp-portfolio-upload { width: 100%; margin: 0; }
.vp-portfolio-upload-error { margin-top: .65rem; text-align: center; }
.vp-portfolio-add {
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .55rem;
    width: 100%; min-height: 132px; padding: 1.5rem 1.25rem; text-align: center;
    border: 1.5px dashed #d0d5dd; border-radius: 14px; background: #fafbfc;
    color: var(--vp-muted); cursor: pointer; transition: border-color .15s, background .15s, color .15s;
}
.vp-portfolio-add:hover {
    border-color: var(--vp-orange); background: var(--vp-orange-soft); color: var(--vp-text);
}
.vp-portfolio-add-icon {
    width: auto; height: auto; margin: 0; border: none; background: transparent; color: #98a2b3;
    display: flex; align-items: center; justify-content: center; border-radius: 0; padding: 0;
}
.vp-portfolio-add-icon .vp-icon { width: 1.65rem; height: 1.65rem; }
.vp-portfolio-add:hover .vp-portfolio-add-icon { background: transparent; color: var(--vp-orange); border: none; }
.vp-portfolio-add-title { font-weight: 600; font-size: .95rem; color: #667085; margin: 0; }
.vp-portfolio-add:hover .vp-portfolio-add-title { color: var(--vp-orange); }

.vp-service-chips { display: flex; flex-wrap: wrap; gap: .65rem; }
.vp-service-chip { position: relative; }
.vp-service-chip input { position: absolute; opacity: 0; pointer-events: none; }
.vp-service-chip span {
    display: inline-flex; padding: .62rem 1.1rem; border-radius: 11px; border: 1.5px solid var(--vp-border-strong);
    font-size: .875rem; font-weight: 600; color: var(--vp-muted); cursor: pointer; background: #fff; transition: all .15s;
}
.vp-service-chip input:checked + span { border-color: var(--vp-orange); color: var(--vp-orange); background: var(--vp-orange-soft); }

.vp-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.vp-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
.vp-form-grid .vp-field--full { grid-column: 1 / -1; }

/* Product add/edit — Figma layout */
.vp-product-form {
    overflow: hidden;
    margin-bottom: 1.35rem;
}
.vp-product-form-head {
    padding: 1.15rem 1.5rem;
    border-bottom: 1px solid var(--vp-border);
}
.vp-product-form-back {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1d2939;
    text-decoration: none;
}
.vp-product-form-back:hover { color: var(--vp-orange); }
.vp-product-form-body {
    padding: 1.5rem 1.5rem 1.75rem;
}
.vp-product-form-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.15rem 1.25rem;
}
.vp-product-form-grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.vp-product-form-grid--jewellery {
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1fr);
}
.vp-product-form-grid > .vp-field--full {
    grid-column: 1 / -1;
}
.vp-currency-input {
    display: flex;
    align-items: stretch;
    border: 1px solid var(--vp-border-strong, #d0d5dd);
    border-radius: 10px;
    background: #fff;
    overflow: hidden;
    transition: border-color .15s, box-shadow .15s;
}
.vp-currency-input:focus-within {
    border-color: var(--vp-orange);
    box-shadow: 0 0 0 3px rgba(242, 81, 35, .12);
}
.vp-currency-input--error {
    border-color: #fca5a5;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, .08);
}
.vp-currency-prefix {
    display: inline-flex;
    align-items: center;
    padding: 0 .9rem;
    background: #f8fafc;
    border-right: 1px solid var(--vp-border, #eaecf0);
    color: #667085;
    font-weight: 700;
    font-size: .95rem;
}
.vp-currency-input .vp-input {
    border: 0 !important;
    box-shadow: none !important;
    border-radius: 0;
    flex: 1;
    min-width: 0;
}
.vp-upload-stack {
    display: grid;
    gap: 1.15rem;
}
.vp-upload-block-label {
    font-size: .8rem;
    font-weight: 600;
    color: var(--vp-text);
    margin-bottom: .2rem;
}
.vp-dropzone {
    margin-top: .55rem;
    border: 1.5px dashed #d0d5dd;
    border-radius: 14px;
    background: #f9fafb;
    padding: 1.75rem 1.25rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
.vp-dropzone:hover,
.vp-dropzone.is-dragover {
    border-color: var(--vp-orange);
    background: #fff7f3;
}
.vp-dropzone-icon {
    width: 2.5rem;
    height: 2.5rem;
    margin: 0 auto .75rem;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #eaecf0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667085;
}
.vp-dropzone-icon svg {
    width: 1.25rem;
    height: 1.25rem;
}
.vp-dropzone-title {
    font-size: .9rem;
    font-weight: 600;
    color: #344054;
}
.vp-dropzone-hint {
    margin-top: .35rem;
    font-size: .78rem;
    color: #98a2b3;
}
.vp-dropzone-file {
    margin-top: .65rem;
    font-size: .78rem;
    font-weight: 600;
    color: var(--vp-orange);
}
.vp-product-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    padding-top: 1.35rem;
    margin-top: 1.35rem;
    border-top: 1px solid var(--vp-border);
}
.vp-product-form .vp-form-section {
    border-top: 1px solid var(--vp-border);
    padding-top: 1.25rem;
    margin-top: .35rem;
}
.vp-link-btn {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    border: 0;
    background: transparent;
    padding: 0;
    font-size: .88rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
}
.vp-link-btn--accent { color: var(--vp-orange); }
.vp-link-btn--accent:hover { color: var(--vp-orange-hover); }
.vp-link-btn--muted { color: #98a2b3; }
.vp-link-btn--muted:hover { color: #667085; }
.vp-damage-figma-list {
    display: grid;
    gap: .75rem;
    margin: .55rem 0 .65rem;
}
.vp-damage-figma-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
    gap: .75rem;
    align-items: center;
}
.vp-product-form-grid--3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem 1.15rem;
}
@media (max-width: 900px) {
    .vp-product-form-grid--3 { grid-template-columns: 1fr; }
}

.vp-dress-media {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .85rem;
}
.vp-dress-media.is-dragover .vp-dress-media-slot--upload {
    border-color: var(--vp-orange);
    background: var(--vp-orange-soft);
}
.vp-dress-media-slot {
    aspect-ratio: 1.15;
    border-radius: 12px;
    border: 1.5px dashed #d0d5dd;
    background: #f9fafb;
    overflow: hidden;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.vp-dress-media-slot--upload {
    flex-direction: column;
    gap: .35rem;
    padding: .85rem;
    text-align: center;
    cursor: pointer;
    color: #667085;
}
.vp-dress-media-slot--upload:hover {
    border-color: var(--vp-orange);
    background: #fff7f4;
}
.vp-dress-media-upload-icon { color: #98a2b3; display: grid; place-items: center; }
.vp-dress-media-upload-badge {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 8px;
    background: var(--vp-orange);
    color: #fff;
    display: grid;
    place-items: center;
    margin-bottom: .15rem;
}
.vp-dress-media-upload-title { font-size: .78rem; font-weight: 600; line-height: 1.3; color: #475467; }
.vp-dress-media-upload-hint { font-size: .68rem; color: #98a2b3; line-height: 1.3; }
.vp-dress-media-empty-icon {
    color: #d0d5dd;
    display: grid;
    place-items: center;
}
.vp-dress-media-slot.has-file .vp-dress-media-empty-icon { display: none; }
.vp-dress-media-slot [data-vp-existing-media] {
    position: absolute;
    inset: 0;
}
.vp-dress-media-slot [data-vp-existing-media] img,
.vp-dress-media-slot img[data-vp-new-preview],
.vp-dress-media-slot img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.vp-dress-media-video-preview {
    position: absolute;
    inset: 0;
    background: #101828;
}
.vp-dress-media-video-preview video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.vp-dress-media-video-preview .vp-dress-media-video-badge {
    pointer-events: none;
    background: linear-gradient(to top, rgba(16, 24, 40, .72), transparent 55%);
    color: #fff;
    align-items: end;
    padding-bottom: .55rem;
    font-size: .7rem;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.vp-dress-media-slot.has-file {
    border-style: solid;
    border-color: #e4e7ec;
    background: #fff;
}
.vp-dress-media-existing {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    margin-top: .85rem;
}
.vp-dress-media-existing--overflow {
    margin-top: .85rem;
}
.vp-dress-media-existing-item {
    width: 5.5rem;
    height: 5.5rem;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    background: #f2f4f7;
    border: 1px solid #e4e7ec;
}
.vp-dress-media-existing-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.vp-dress-media-remove {
    position: absolute;
    top: .35rem;
    right: .35rem;
    width: 1.35rem;
    height: 1.35rem;
    border: none;
    border-radius: 999px;
    background: rgba(15, 23, 42, .7);
    color: #fff;
    cursor: pointer;
    line-height: 1;
    z-index: 2;
}
.vp-dress-media-video-badge {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    font-size: .75rem;
    font-weight: 700;
    color: #475467;
    background: #eef2f6;
}
.vp-dress-media-existing-item .vp-dress-media-video-preview {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 5rem;
}

.vp-dress-variants-toggle {
    width: 100%;
    min-height: 2.75rem;
    border: 1.5px solid var(--vp-orange);
    border-radius: 10px;
    background: #fff;
    color: var(--vp-orange);
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}
.vp-dress-variants-toggle:hover { background: var(--vp-orange-soft); }
.vp-dress-variant-composer {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.vp-dress-variant-upload {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 9.5rem;
    border: 1.5px dashed #d0d5dd;
    border-radius: 12px;
    background: #f9fafb;
    cursor: pointer;
    color: #667085;
    text-align: center;
}
.vp-dress-variant-upload:hover {
    border-color: var(--vp-orange);
    background: #fff7f4;
}
.vp-dress-variant-upload-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .4rem;
    font-weight: 700;
    letter-spacing: .04em;
    font-size: .85rem;
}
.vp-dress-variant-upload-preview {
    width: 100%;
    height: 9.5rem;
    border-radius: 12px;
    overflow: hidden;
}
.vp-dress-variant-upload-preview[hidden],
.vp-dress-variant-upload-empty[hidden],
.vp-dress-variant-card-thumb-empty[hidden],
img[data-vp-variant-thumb][hidden] {
    display: none !important;
}
.vp-dress-variant-upload-preview img {
    width: 100%;
    height: 9.5rem;
    object-fit: cover;
    border-radius: 12px;
    display: block;
}
.vp-dress-variant-add-btn {
    width: 100%;
    min-height: 2.85rem;
    border-radius: 10px;
    font-weight: 700;
}
.vp-dress-variant-list {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    margin-top: 1rem;
}
.vp-dress-variant-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: .75rem 1rem;
    border: 1px solid #e4e7ec;
    border-radius: 12px;
    background: #fff;
}
.vp-dress-variant-card-thumb {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 10px;
    overflow: hidden;
    background: #f2f4f7;
    flex-shrink: 0;
    display: grid;
    place-items: center;
}
.vp-dress-variant-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.vp-dress-variant-card-thumb-empty {
    font-size: .65rem;
    color: #98a2b3;
    text-align: center;
    padding: .25rem;
}
.vp-dress-variant-card-meta {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: .15rem;
    font-size: .84rem;
    color: #475467;
}
.vp-dress-variant-card-meta strong { color: #101828; font-weight: 700; }
.vp-dress-variant-card-actions {
    display: flex;
    align-items: center;
    gap: .35rem;
    flex-shrink: 0;
}
.vp-dress-variant-icon-btn {
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 8px;
    background: #f2f4f7;
    color: #667085;
    display: grid;
    place-items: center;
    cursor: pointer;
}
.vp-dress-variant-icon-btn .vp-icon { width: 1rem; height: 1rem; }
.vp-dress-variant-icon-btn--danger {
    background: #fef3f2;
    color: #d92d20;
}
.vp-dress-variant-icon-btn:hover { filter: brightness(.97); }

@media (max-width: 900px) {
    .vp-dress-media { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
.vp-colors-panel {
    margin-top: .55rem;
    border: 1px solid var(--vp-border);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    background: #fafbfc;
}
.vp-colors-list {
    display: grid;
    gap: 1rem;
}
.vp-colors-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 1rem;
}
.vp-colors-upload {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .35rem;
    padding-bottom: .15rem;
}

/* Searchable multi-select */
.vp-ms {
    position: relative;
}
.vp-ms-trigger {
    width: 100%;
    min-height: 2.75rem;
    display: flex;
    align-items: center;
    gap: .45rem;
    flex-wrap: wrap;
    padding: .45rem 2.25rem .45rem .65rem;
    border: 1px solid var(--vp-border-strong, #d0d5dd);
    border-radius: 10px;
    background: #fff;
    text-align: left;
    cursor: pointer;
    position: relative;
}
.vp-ms-trigger.is-open,
.vp-ms-trigger:focus {
    outline: none;
    border-color: var(--vp-orange);
    box-shadow: 0 0 0 3px rgba(242, 81, 35, .12);
}
.vp-ms-placeholder {
    color: #98a2b3;
    font-size: .9rem;
    font-weight: 500;
}
.vp-ms-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
}
.vp-ms-chip {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    padding: .2rem .5rem;
    border-radius: 999px;
    background: #fff1ea;
    color: #c2410c;
    font-size: .78rem;
    font-weight: 700;
}
.vp-ms-chip-x {
    font-size: .95rem;
    line-height: 1;
    opacity: .75;
}
.vp-ms-chip:hover .vp-ms-chip-x { opacity: 1; }
.vp-ms-caret {
    width: 1rem;
    height: 1rem;
    color: #98a2b3;
    position: absolute;
    right: .75rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}
.vp-ms-panel {
    position: absolute;
    z-index: 40;
    left: 0;
    right: 0;
    top: calc(100% + .35rem);
    background: #fff;
    border: 1px solid var(--vp-border);
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
    overflow: hidden;
}
.vp-ms-search-wrap {
    position: relative;
    border-bottom: 1px solid #f0f2f5;
}
.vp-ms-search-icon {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1rem;
    height: 1rem;
    color: #98a2b3;
}
.vp-ms-search {
    width: 100%;
    border: 0;
    outline: none;
    padding: .75rem .85rem .75rem 2.35rem;
    font-size: .9rem;
    background: #fff;
}
.vp-ms-list {
    list-style: none;
    margin: 0;
    padding: .35rem;
    max-height: 220px;
    overflow-y: auto;
}
.vp-ms-option {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    border: 0;
    background: transparent;
    border-radius: 8px;
    padding: .6rem .7rem;
    font-size: .9rem;
    font-weight: 600;
    color: #344054;
    cursor: pointer;
    text-align: left;
}
.vp-ms-option:hover { background: #f8fafc; }
.vp-ms-option.is-selected {
    background: #fff7f3;
    color: var(--vp-orange);
}
.vp-ms-check {
    width: 1rem;
    height: 1rem;
    color: var(--vp-orange);
    flex-shrink: 0;
}
.vp-ms-empty {
    padding: .85rem .7rem;
    font-size: .85rem;
    color: #98a2b3;
    text-align: center;
}

.vp-form-section-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .75rem; margin-bottom: .85rem; }
.vp-form-section-head .vp-label { margin-bottom: 0; }
.vp-form-section { padding-top: 1.25rem; margin-top: .25rem; border-top: 1px solid var(--vp-border); }
.vp-form-section:first-child { padding-top: 0; margin-top: 0; border-top: none; }
.vp-form-actions { display: flex; flex-wrap: wrap; gap: .75rem; padding-top: 1.25rem; margin-top: 1.25rem; border-top: 1px solid var(--vp-border); }
.vp-repeat-row { border: 1px solid var(--vp-border); border-radius: 12px; padding: 1rem; background: #fafbfc; }
.vp-repeat-row-grid { display: grid; gap: .75rem; grid-template-columns: repeat(4, minmax(0, 1fr)) auto; align-items: end; }
.vp-repeat-row-grid--damage { grid-template-columns: minmax(0, 1fr) 8rem auto; }
.vp-gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(88px, 1fr)); gap: .75rem; margin-bottom: .85rem; }
.vp-gallery-item { position: relative; overflow: hidden; border-radius: 12px; border: 1px solid var(--vp-border); background: #fff; }
.vp-gallery-item img { aspect-ratio: 1; width: 100%; object-fit: cover; display: block; }
.vp-gallery-remove { position: absolute; top: .35rem; right: .35rem; min-width: 1.65rem; height: 1.65rem; padding: 0; border-radius: 8px; font-size: .85rem; line-height: 1; }
.vp-product-preview { width: 6rem; height: 6rem; border-radius: 12px; object-fit: cover; border: 1px solid var(--vp-border); margin-bottom: .75rem; }
.vp-section-title { font-size: .95rem; font-weight: 700; margin: 1.5rem 0 1rem; padding-top: .5rem; border-top: 1px solid var(--vp-border); }
.vp-section-title:first-child { margin-top: 0; padding-top: 0; border-top: none; }

.vp-account-type { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.vp-account-type label { position: relative; }
.vp-account-type input { position: absolute; opacity: 0; }
.vp-account-type span {
    display: block; text-align: center; padding: .85rem 1rem; border-radius: 11px; border: 1.5px solid var(--vp-border-strong);
    font-weight: 600; color: var(--vp-muted); cursor: pointer; background: #fff;
}
.vp-account-type input:checked + span { border-color: var(--vp-orange); color: var(--vp-orange); background: var(--vp-orange-soft); }

.vp-legal-content { font-size: .9rem; line-height: 1.75; color: var(--vp-text); }
.vp-legal-content p { margin: 0 0 .85rem; }
.vp-legal-content p:last-child { margin-bottom: 0; }
.vp-legal-content h1, .vp-legal-content h2, .vp-legal-content h3 { margin: 1.25rem 0 .65rem; font-weight: 700; color: var(--vp-text); line-height: 1.35; }
.vp-legal-content h1 { font-size: 1.2rem; }
.vp-legal-content h2 { font-size: 1.05rem; }
.vp-legal-content h3 { font-size: .95rem; }
.vp-legal-content ul, .vp-legal-content ol { margin: 0 0 .85rem; padding-left: 1.35rem; }
.vp-legal-content li { margin-bottom: .35rem; }
.vp-legal-content strong, .vp-legal-content b { font-weight: 700; color: var(--vp-text); }
.vp-legal-content a { color: var(--vp-orange); text-decoration: underline; }
.vp-legal-content blockquote { margin: 0 0 .85rem; padding-left: .85rem; border-left: 3px solid var(--vp-border); color: var(--vp-muted); }
.vp-legal-meta { font-size: .78rem; color: var(--vp-muted); margin: -.75rem 0 1.25rem; }
.vp-faq-item { border: 1px solid var(--vp-border); border-radius: 12px; margin-bottom: .65rem; overflow: hidden; }
.vp-faq-item summary {
    padding: 1rem 1.15rem; font-weight: 600; font-size: .9rem; cursor: pointer; list-style: none;
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
}
.vp-faq-item summary::-webkit-details-marker { display: none; }
.vp-faq-item summary::after { content: '▾'; color: var(--vp-muted); font-size: .85rem; }
.vp-faq-item[open] { border-color: var(--vp-orange-muted); }
.vp-faq-item[open] summary { color: var(--vp-orange); }
.vp-faq-item[open] summary::after { transform: rotate(180deg); }
.vp-faq-answer { padding: 0 1.15rem 1rem; font-size: .88rem; color: var(--vp-muted); line-height: 1.6; }

@media (max-width: 1100px) {
    .vp-profile-layout { grid-template-columns: 1fr; }
    .vp-portfolio-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .vp-settings-layout { grid-template-columns: 1fr; }
    .vp-form-grid-2 { grid-template-columns: 1fr; }
    .vp-form-grid { grid-template-columns: 1fr; }
    .vp-product-form-grid,
    .vp-product-form-grid--2,
    .vp-product-form-grid--jewellery { grid-template-columns: 1fr; }
    .vp-repeat-row-grid, .vp-repeat-row-grid--damage { grid-template-columns: 1fr; }
    .vp-portfolio-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vp-filters-grid { display: grid; grid-template-columns: 1fr; }
    .vp-filters-field, .vp-filters-field--wide, .vp-filters-field--date { min-width: 0; max-width: none; width: 100%; }
    .vp-filters-actions, .vp-filters-page-actions { width: 100%; margin-left: 0; }
    .vp-filters-actions-btns, .vp-filters-page-actions-btns { width: 100%; }
    .vp-filters-actions .vp-btn { flex: 1 1 auto; }
    .vp-product-form-body { padding: 1.15rem 1rem 1.35rem; }
    .vp-product-form-actions { justify-content: stretch; }
    .vp-product-form-actions .vp-btn { width: 100%; }
    .vp-damage-figma-row { grid-template-columns: 1fr; }
}

/* Auth */
.vp-guest-wrap {
    min-height: 100vh;
    overflow-x: hidden;
    overflow-y: visible;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.25rem 3rem;
    background: #ffffff;
}
.vp-auth-card {
    width: 100%; max-width: 400px; background: transparent; border-radius: 0; padding: 0;
    box-shadow: none; border: 0; text-align: center;
    margin: 0;
}
.vp-auth-card--login {
    max-width: 380px;
}

/* Multi-step vendor registration */
.vp-register-wizard {
    width: 100%; max-width: 560px; margin: 0 auto; flex-shrink: 0;
}
.vp-register-top {
    display: grid; grid-template-columns: 40px 1fr auto; align-items: center; gap: .75rem; margin-bottom: .85rem;
}
.vp-register-back {
    width: 40px; height: 40px; border: 0; background: transparent; color: var(--vp-text);
    display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; cursor: pointer;
}
.vp-register-back:hover { background: rgba(0,0,0,.04); }
.vp-register-heading { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: -.02em; }
.vp-register-step-label { font-size: .82rem; font-weight: 700; color: var(--vp-orange); white-space: nowrap; }
.vp-register-progress {
    height: 4px; border-radius: 99px; background: #e8ecf0; overflow: hidden; margin-bottom: 1.15rem;
}
.vp-register-progress-fill {
    display: block; height: 100%; background: var(--vp-orange); border-radius: 99px; transition: width .25s ease;
}
.vp-register-card {
    background: var(--vp-surface); border-radius: 20px; padding: 1.5rem 1.35rem 1.25rem;
    box-shadow: 0 16px 40px rgba(16, 24, 40, .08); border: 1px solid rgba(255,255,255,.85);
}
.vp-register-panel-title {
    margin: 0 0 1.1rem; font-size: 1.2rem; font-weight: 800; letter-spacing: -.02em;
}
.vp-upload-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: .75rem;
}
.vp-upload-tile {
    position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center;
    min-height: 118px; padding: 1rem .75rem; text-align: center; cursor: pointer;
    border: 1.5px dashed #c9d0d8; border-radius: 14px; background: #fafbfc;
    transition: border-color .15s, background .15s;
}
.vp-upload-tile:hover { border-color: var(--vp-orange); background: var(--vp-orange-soft); }
.vp-upload-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.vp-upload-icon {
    width: 42px; height: 42px; border-radius: 12px; margin-bottom: .55rem;
    display: flex; align-items: center; justify-content: center;
    background: #fff; border: 1px solid var(--vp-border); color: var(--vp-muted);
}
.vp-upload-title { font-size: .86rem; font-weight: 700; color: var(--vp-text); }
.vp-upload-sub { font-size: .72rem; color: var(--vp-muted); margin-top: .2rem; }
.vp-upload-name {
    display: block; margin-top: .35rem; font-size: .68rem; color: var(--vp-orange); font-weight: 600;
    max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.vp-service-stack { display: flex; flex-direction: column; gap: .55rem; }
.vp-service-row {
    position: relative; display: block; cursor: pointer;
}
.vp-service-row input { position: absolute; opacity: 0; pointer-events: none; }
.vp-service-row span {
    display: flex; align-items: center; justify-content: center; width: 100%;
    padding: .95rem 1rem; border-radius: 12px; border: 1.5px solid #d7dde5;
    background: #fff; font-weight: 600; font-size: .95rem; color: var(--vp-text);
    transition: border-color .15s, background .15s, color .15s;
}
.vp-service-row input:checked + span {
    border-color: var(--vp-orange); color: var(--vp-orange); background: var(--vp-orange-soft);
}
.vp-account-type {
    display: grid; grid-template-columns: 1fr 1fr; gap: .65rem;
}
.vp-account-type-option { position: relative; display: block; cursor: pointer; }
.vp-account-type-option input { position: absolute; opacity: 0; pointer-events: none; }
.vp-account-type-option span {
    display: flex; align-items: center; justify-content: center; width: 100%;
    padding: .95rem 1rem; border-radius: 12px; border: 1.5px solid #d7dde5;
    background: #fff; font-weight: 600; font-size: .95rem; color: var(--vp-muted);
    transition: border-color .15s, background .15s, color .15s;
}
.vp-account-type-option input:checked + span {
    border-color: var(--vp-orange); color: var(--vp-orange); background: var(--vp-orange-soft);
}
.vp-register-footer {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    margin-top: 1.35rem; padding-top: .35rem;
}
.vp-register-remaining { margin: 0; font-size: .9rem; color: var(--vp-muted); font-weight: 600; }
.vp-register-continue { min-width: 140px; padding: .8rem 1.35rem; }
.vp-register-panel[hidden] { display: none !important; }
.vp-textarea { min-height: 88px; resize: vertical; }
.vp-register-success {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background: rgba(15, 23, 42, .08);
}
.vp-register-success-card {
    width: 100%; max-width: 420px;
    background: #fff; border-radius: 20px; padding: 2rem 1.5rem 1.75rem; text-align: center;
    box-shadow: 0 20px 50px rgba(16, 24, 40, .12);
}
.vp-register-success-icon {
    width: 72px; height: 72px; margin: 0 auto 1rem; border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    background: #e8f8ef; color: #16a34a; box-shadow: 0 0 0 10px #f0fdf4;
}
.vp-register-success-icon svg { width: 32px; height: 32px; }
.vp-register-success-title { margin: 0 0 .5rem; font-size: 1.45rem; font-weight: 800; }
.vp-register-success-message {
    margin: 0; color: var(--vp-muted); font-size: .95rem; line-height: 1.55;
}
@media (max-width: 560px) {
    .vp-upload-grid { grid-template-columns: 1fr; }
    .vp-register-footer { flex-direction: column; align-items: stretch; }
    .vp-register-continue { width: 100%; }
}

.vp-auth-logo-wrap { margin-bottom: 1.5rem; }
.vp-auth-logo {
    width: 72px; height: 72px; margin: 0 auto; border-radius: 16px; background: transparent;
    display: flex; align-items: center; justify-content: center; box-shadow: none;
}
.vp-auth-logo svg { width: 72px; height: 72px; display: block; border-radius: 16px; }
.vp-auth-brand {
    margin: 0 0 1.5rem;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -.02em;
    color: #1e293b;
    text-align: center;
}
.vp-auth-kicker {
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--vp-teal);
    margin: 0 0 .35rem;
    text-align: center;
}
.vp-auth-title {
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0 0 .45rem;
    letter-spacing: -.03em;
    color: #1e293b;
    text-align: left !important;
}
.vp-auth-sub {
    color: #94a3b8;
    font-size: .95rem;
    margin: 0 0 1.75rem;
    line-height: 1.5;
    text-align: left !important;
}
.vp-auth-form { text-align: left; }
.vp-auth-card--login .vp-auth-form .vp-field {
    margin-bottom: 1.5rem;
}
.vp-auth-form .vp-label {
    font-size: .9rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: .55rem;
}
.vp-auth-card--login .vp-auth-form .vp-label {
    text-align: left;
}
.vp-mobile-input {
    display: flex;
    align-items: center;
    gap: .55rem;
    width: 100%;
    min-height: 52px;
    padding: 0 1rem;
    border-radius: 14px;
    background: #f1f5f9;
    border: 1px solid transparent;
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.vp-mobile-input:focus-within {
    background: #fff;
    border-color: var(--vp-orange);
    box-shadow: 0 0 0 3px rgba(242, 81, 35, .12);
}
.vp-mobile-input--error {
    border-color: #fca5a5;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, .08);
}
.vp-mobile-prefix {
    flex-shrink: 0;
    font-size: .95rem;
    font-weight: 700;
    color: #1e293b;
    padding-right: .65rem;
    border-right: 1px solid #dbe3ec;
    margin-right: .15rem;
    line-height: 1.2;
}
.vp-mobile-field {
    flex: 1;
    width: 100%;
    min-width: 0;
    border: 0;
    background: transparent;
    padding: .95rem 0;
    font: inherit;
    font-size: .95rem;
    font-weight: 500;
    color: var(--vp-text);
    outline: none;
}
.vp-mobile-field::placeholder { color: #94a3b8; font-weight: 500; }
.vp-auth-continue {
    margin-top: .15rem;
    min-height: 52px;
    padding: .95rem 1rem !important;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 700;
    box-shadow: 0 8px 20px rgba(242, 81, 35, .32);
}
.vp-divider {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin: 1.85rem 0 1.35rem;
    color: #94a3b8;
    font-size: .85rem;
    font-weight: 500;
}
.vp-divider::before, .vp-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
.vp-social-btn {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: .65rem;
    padding: .85rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 14px; background: #fff;
    color: #334155; font-weight: 600; font-size: .9rem; margin-bottom: .75rem; cursor: not-allowed;
}
.vp-auth-footer {
    margin-top: 1.85rem;
    font-size: .9rem;
    color: #64748b;
    text-align: center;
}
.vp-auth-footer a { color: var(--vp-orange); font-weight: 700; text-decoration: none; }
.vp-auth-resend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    margin-top: 1.25rem;
    font-size: .875rem;
    color: var(--vp-muted);
}
.vp-auth-resend-btn {
    border: 0;
    background: transparent;
    padding: 0;
    color: var(--vp-orange);
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}
.vp-auth-resend-btn.is-disabled,
.vp-auth-resend-btn:disabled {
    opacity: .45;
    cursor: not-allowed;
}
.vp-auth-timer {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    color: #94a3b8;
}
.vp-otp-input { text-align: center; letter-spacing: .45rem; font-size: 1.35rem; font-weight: 800; }

/* Pagination override */
.pagination { display: flex; gap: .35rem; flex-wrap: wrap; list-style: none; padding: 0; margin: 0; }
.pagination a, .pagination span { display: inline-flex; padding: .4rem .7rem; border-radius: 8px; border: 1px solid var(--vp-border); font-size: .82rem; text-decoration: none; }
.pagination .active span { background: var(--vp-orange-soft); border-color: var(--vp-orange-muted); color: var(--vp-orange); font-weight: 700; }

@media (max-width: 1100px) {
    .vp-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vp-grid-2 { grid-template-columns: 1fr; }
    .vp-settings-layout { grid-template-columns: 1fr; }
}
@media (max-width: 1024px) {
    body.vp-body { overflow: auto; }
    .vp-shell { height: auto; min-height: 100vh; overflow: visible; }
    .vp-sidebar { transform: translateX(-100%); transition: transform .22s ease; height: 100vh; }
    .vp-sidebar.is-open { transform: translateX(0); }
    .vp-main { margin-left: 0; height: auto; overflow: visible; }
    .vp-settings-nav-card { position: static; max-height: none; overflow: visible; }
    .vp-menu-btn { display: inline-flex; }
    .vp-content { padding: 1.15rem; }
    .vp-topbar { padding: .85rem 1.15rem; }
}
@media (max-width: 768px) {
    .vp-topbar-wallets { display: none; }
}
@media (max-width: 640px) {
    .vp-grid-4 { grid-template-columns: 1fr; }
    .vp-wallet-grid { grid-template-columns: 1fr; }
    .vp-earnings-grid { gap: 1.25rem; }
    .vp-page-title { font-size: 1.35rem; }
}

.vp-promo-banner {
    display: flex;
    gap: 1rem;
    align-items: stretch;
    margin-bottom: 1.25rem;
    padding: 1rem;
    border-radius: var(--vp-radius-lg);
    background: linear-gradient(135deg, var(--vp-orange-soft), #fff);
    border: 1px solid var(--vp-orange-muted);
    text-decoration: none;
    color: inherit;
    transition: border-color .15s, box-shadow .15s;
}
.vp-promo-banner:hover {
    border-color: var(--vp-orange);
    box-shadow: var(--vp-shadow);
}
.vp-promo-banner__img {
    width: 7.5rem;
    height: 5.5rem;
    border-radius: 12px;
    object-fit: cover;
    flex-shrink: 0;
}
.vp-promo-banner__body {
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.vp-promo-banner__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    color: var(--vp-text);
    line-height: 1.25;
}
.vp-promo-banner__sub {
    margin: .35rem 0 0;
    font-size: .84rem;
    color: var(--vp-muted);
    line-height: 1.45;
}
@media (max-width: 640px) {
    .vp-promo-banner { flex-direction: column; }
    .vp-promo-banner__img { width: 100%; height: 8rem; }
}

.vp-btn-chat:hover {
    background-color: #cbd5e1;
}
    .vp-actions {
        display: flex;
        gap: .5rem;
        /* flex-wrap: wrap; */
        align-items: center;
    }

    .vp-payout-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .85rem 0;
        border-bottom: 1px solid var(--vp-border);
    }

    .vp-payout-row:last-child {
        border-bottom: none;
    }

    /* Cards */
    .vp-card {
        background: var(--vp-surface);
        border: 1px solid var(--vp-border);
        border-radius: var(--vp-radius-lg);
        box-shadow: var(--vp-shadow);
        overflow: hidden;
    }

    .vp-card-pad {
        padding: 1.35rem;
    }

    .vp-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.35rem;
        border-bottom: 1px solid var(--vp-border);
    }

    .vp-card-head h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    /* Stats */
    .vp-stat {
        background: var(--vp-surface);
        border: 1px solid var(--vp-border);
        border-radius: var(--vp-radius);
        padding: 1.15rem 1.2rem;
        box-shadow: var(--vp-shadow);
        display: flex;
        gap: .9rem;
        align-items: flex-start;
    }

    .vp-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: var(--vp-orange-soft);
        color: var(--vp-orange);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .vp-stat-icon .vp-icon {
        width: 1.35rem;
        height: 1.35rem;
    }

    .vp-stat-body {
        min-width: 0;
    }

    .vp-stat-label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .05em;
        color: var(--vp-muted);
        text-transform: uppercase;
    }

    .vp-stat-value {
        font-size: 1.65rem;
        font-weight: 800;
        margin-top: .2rem;
        line-height: 1.1;
    }

    .vp-stat-sub {
        font-size: .80rem;
        color: var(--vp-muted);
        margin-top: .25rem;
    }

    .vp-earnings {
        background: linear-gradient(135deg, var(--vp-teal) 0%, var(--vp-teal-light) 100%);
        color: #fff;
        border-radius: var(--vp-radius-lg);
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.35rem;
        box-shadow: 0 12px 32px rgba(13, 79, 79, .22);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .vp-earnings.vp-earnings--figma {
        display: block;
        background: #0d2c2f;
        background-image: none;
    }

    .vp-earnings h3 {
        margin: 0 0 .75rem;
        font-size: .72rem;
        letter-spacing: .08em;
        opacity: .85;
        font-weight: 700;
    }

    .vp-earnings-grid {
        display: flex;
        gap: 2.5rem;
        flex-wrap: wrap;
    }

    .vp-earnings-val {
        font-size: 1.85rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .vp-earnings-actions {
        display: flex;
        gap: .65rem;
        align-items: center;
    }

    .vp-wallet-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.35rem;
    }

    .vp-wallet-card {
        border-radius: var(--vp-radius-lg);
        padding: 1.25rem 1.35rem;
        border: 1px solid var(--vp-border);
        background: #fff;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .04);
    }

    .vp-wallet-card--digital {
        border-top: 4px solid #f59e0b;
    }

    .vp-wallet-card--actual {
        border-top: 4px solid #15803d;
    }

    .vp-wallet-card-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--vp-muted);
    }

    .vp-wallet-card-value {
        font-size: 2rem;
        font-weight: 800;
        margin-top: .35rem;
        line-height: 1.1;
        color: var(--vp-text);
    }

    .vp-wallet-card-note {
        margin: .55rem 0 0;
        font-size: .8rem;
        color: var(--vp-muted);
        line-height: 1.45;
    }

    .vp-amount--credit {
        color: #15803d;
    }

    .vp-amount--debit {
        color: #dc2626;
    }

    .vp-export-dropdown {
        position: relative;
        display: inline-flex;
    }

    .vp-export-menu {
        position: absolute;
        top: calc(100% + .35rem);
        right: 0;
        z-index: 30;
        min-width: 7.5rem;
        background: #fff;
        border: 1px solid var(--vp-border-strong);
        border-radius: 10px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
        overflow: hidden;
    }

    .vp-export-menu-item {
        display: block;
        padding: .55rem .85rem;
        font-size: .82rem;
        font-weight: 600;
        color: var(--vp-text);
        text-decoration: none;
    }

    .vp-export-menu-item:hover {
        background: var(--vp-bg-soft);
    }

    .vp-topbar-right {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-left: auto;
    }

    .vp-topbar-wallets {
        display: flex;
        align-items: center;
        gap: .55rem;
    }

    .vp-topbar-wallet {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .1rem;
        padding: .4rem .7rem;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid var(--vp-border);
        background: #fff;
        min-width: 5.5rem;
    }

    .vp-topbar-wallet--digital {
        border-top: 3px solid #f59e0b;
    }

    .vp-topbar-wallet--actual {
        border-top: 3px solid #15803d;
    }

    .vp-topbar-wallet-label {
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--vp-muted);
    }

    .vp-topbar-wallet-value {
        font-size: .92rem;
        font-weight: 800;
        color: var(--vp-text);
        line-height: 1.1;
    }

    /* Buttons */
    .vp-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        padding: .62rem 1.15rem;
        border-radius: 11px;
        font-weight: 700;
        font-size: .875rem;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s, border-color .15s, transform .1s;
    }

    .vp-btn:active {
        transform: translateY(1px);
    }

    .vp-btn--primary {
        background: var(--vp-orange);
        color: #fff;
        box-shadow: 0 4px 14px rgba(242, 81, 35, .28);
    }

    .vp-btn--primary:hover {
        background: var(--vp-orange-hover);
    }

    .vp-btn--dark {
        background: var(--vp-teal);
        color: #fff;
    }

    .vp-btn--outline {
        background: #fff;
        border-color: var(--vp-border-strong);
        color: var(--vp-text);
    }

    .vp-btn--ghost {
        background: transparent;
        border-color: var(--vp-border);
        color: var(--vp-muted);
    }

    .vp-btn--danger {
        background: #fff;
        border-color: #fecaca;
        color: #dc2626;
    }

    .vp-btn--sm {
        padding: .75rem .8rem;
        font-size: .78rem;
        border-radius: 9px;
    }

    .vp-btn--block {
        width: 100%;
    }

    /* Badges */
    .vp-badge {
        display: inline-flex;
        align-items: center;
        padding: .28rem .62rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        white-space: nowrap;
    }

    .vp-badge--new {
        background: #ede9fe;
        color: #6d28d9;
    }

    .vp-badge--accepted {
        background: #ffedd5;
        color: #c2410c;
    }

    .vp-badge--transit {
        background: #fef3c7;
        color: #b45309;
    }

    .vp-badge--done {
        background: #dcfce7;
        color: #15803d;
    }

    .vp-badge--pending {
        background: #fef9c3;
        color: #a16207;
    }

    .vp-badge--cancelled {
        background: #fee2e2;
        color: #b91c1c;
    }

    .vp-badge--failed {
        background: #fee2e2;
        color: #b91c1c;
    }

    /* Table */
    .vp-table-wrap {
        overflow-x: auto;
    }

    .vp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .875rem;
        min-width: 640px;
    }

    .vp-table th {
        text-align: left;
        padding: .9rem 1.15rem;
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--vp-muted);
        border-bottom: 1px solid var(--vp-border);
        background: #fafbfc;
        white-space: nowrap;
    }

    .vp-table td {
        padding: 1rem 1.15rem;
        border-bottom: 1px solid var(--vp-border);
        vertical-align: middle;
        overflow-wrap: anywhere;
        word-break: break-word;
        max-width: 18rem;
    }

    .vp-table tbody tr:hover td {
        background: #fbfcfd;
    }

    .vp-table-product {
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .vp-thumb {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        object-fit: cover;
        background: #f1f5f9;
        border: 1px solid var(--vp-border);
    }


    /* Tabs */
    .vp-tabs {
        display: flex;
        gap: 1.25rem;
        flex-wrap: wrap;
        border-bottom: 1px solid var(--vp-border);
        margin-bottom: 1.15rem;
    }

    .vp-tab {
        padding: .65rem 0;
        font-size: .88rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--vp-muted);
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
    }

    .vp-tab:hover {
        color: var(--vp-text);
    }

    .vp-tab--active {
        color: var(--vp-orange);
        border-bottom-color: var(--vp-orange);
    }

    /* Forms */
    .vp-input,
    .vp-select,
    .vp-textarea {
        width: 100%;
        padding: .72rem .95rem;
        border: 1px solid var(--vp-border-strong);
        border-radius: 11px;
        font: inherit;
        background: #fff;
        color: var(--vp-text);
        transition: border-color .15s, box-shadow .15s;
    }

    .vp-input:focus,
    .vp-select:focus,
    .vp-textarea:focus {
        outline: none;
        border-color: var(--vp-orange);
        box-shadow: 0 0 0 3px rgba(242, 81, 35, .12);
    }

    .vp-input::placeholder {
        color: #9aa8b6;
    }

    .vp-label {
        display: block;
        font-size: .8rem;
        font-weight: 600;
        margin-bottom: .4rem;
        color: var(--vp-text);
    }

    .vp-required {
        color: #dc2626;
    }

    .vp-field {
        margin-bottom: 0.81rem;
    }

    .vp-field-hint {
        font-size: .78rem;
        color: var(--vp-muted);
        margin-top: .35rem;
    }

    .vp-field-error {
        font-size: .78rem;
        color: #dc2626;
        margin-top: .35rem;
        font-weight: 500;
    }

    .vp-input--error,
    .vp-select--error,
    .vp-textarea--error {
        border-color: #fca5a5 !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, .08) !important;
    }

    .vp-portfolio-delete-form {
        margin: 0;
    }

    .vp-file {
        font-size: .85rem;
    }

    /* Modal alerts (matches admin panel) */
    .vp-modal-alert {
        position: fixed;
        inset: 0;
        z-index: 300;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .vp-modal-alert-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .45);
        backdrop-filter: blur(2px);
    }

    .vp-modal-alert-card {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 28rem;
        border-radius: 1rem;
        background: #fff;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, .25);
    }

    .vp-modal-alert-card--animate {
        animation: vp-modal-card-in .22s ease-out;
    }

    @keyframes vp-modal-card-in {
        from {
            opacity: 0;
            transform: scale(.96) translateY(6px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .vp-modal-alert-icon-wrap {
        position: relative;
        margin: 0 auto 1.25rem;
        display: flex;
        width: 5rem;
        height: 5rem;
        align-items: center;
        justify-content: center;
    }

    .vp-modal-alert-icon-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        opacity: .3;
    }

    .vp-modal-alert-icon-wrap--success .vp-modal-alert-icon-ring {
        background: #38bdf8;
    }

    .vp-modal-alert-icon-wrap--error .vp-modal-alert-icon-ring {
        background: #fb7185;
    }

    .vp-modal-alert-icon-wrap--warning .vp-modal-alert-icon-ring {
        background: #fbbf24;
    }

    .vp-modal-alert-icon {
        position: relative;
        display: flex;
        width: 3.5rem;
        height: 3.5rem;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #fff;
    }

    .vp-modal-alert-icon svg {
        width: 1.75rem;
        height: 1.75rem;
    }

    .vp-modal-alert-icon-wrap--success .vp-modal-alert-icon {
        background: #0ea5e9;
        box-shadow: 0 10px 15px -3px rgba(14, 165, 233, .3);
    }

    .vp-modal-alert-icon-wrap--error .vp-modal-alert-icon {
        background: #f43f5e;
        box-shadow: 0 10px 15px -3px rgba(244, 63, 94, .3);
    }

    .vp-modal-alert-icon-wrap--warning .vp-modal-alert-icon {
        background: #f59e0b;
        box-shadow: 0 10px 15px -3px rgba(245, 158, 11, .3);
    }

    .vp-modal-alert-title {
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: -.02em;
        color: #0f172a;
        margin: 0;
    }

    .vp-modal-alert-message {
        margin: .5rem auto 0;
        max-width: 20rem;
        font-size: .875rem;
        line-height: 1.6;
        color: #64748b;
    }

    .vp-modal-alert-btn {
        margin-top: 1.5rem;
        width: 100%;
        border: none;
        border-radius: .75rem;
        padding: .75rem 1rem;
        font-size: .875rem;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        transition: background .15s;
        background: var(--vp-orange);
        box-shadow: 0 4px 14px rgba(242, 81, 35, .28);
    }

    .vp-modal-alert-btn:hover {
        background: var(--vp-orange-hover);
    }

    .vp-modal-alert-actions {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
    }

    .vp-modal-alert-actions .vp-modal-alert-btn {
        margin-top: 0;
        flex: 1 1 0;
    }

    .vp-modal-alert-btn--ghost {
        background: #f1f5f9;
        color: #334155;
        box-shadow: none;
    }

    .vp-modal-alert-btn--ghost:hover {
        background: #e2e8f0;
    }

    .vp-modal-enter {
        transition: opacity .25s ease-out;
    }

    .vp-modal-enter-start {
        opacity: 0;
    }

    .vp-modal-enter-end {
        opacity: 1;
    }

    .vp-modal-leave {
        transition: opacity .2s ease-in;
    }

    .vp-modal-leave-start {
        opacity: 1;
    }

    .vp-modal-leave-end {
        opacity: 0;
    }

    .vp-modal-card-enter {
        transition: all .3s cubic-bezier(.16, 1, .3, 1);
    }

    .vp-modal-card-enter-start {
        opacity: 0;
        transform: scale(.92) translateY(8px);
    }

    .vp-modal-card-enter-end {
        opacity: 1;
        transform: scale(1) translateY(0);
    }

    .vp-modal-card-leave {
        transition: all .2s ease-in;
    }

    .vp-modal-card-leave-start {
        opacity: 1;
        transform: scale(1) translateY(0);
    }

    .vp-modal-card-leave-end {
        opacity: 0;
        transform: scale(.95) translateY(4px);
    }

    .vp-pending-banner {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        padding: .85rem 1rem;
        border-radius: 12px;
        margin-bottom: 1.25rem;
        font-size: .875rem;
    }

    /* Schedule */
    .vp-schedule-item {
        display: flex;
        gap: .85rem;
        padding: .9rem 0;
        border-bottom: 1px solid var(--vp-border);
    }

    .vp-schedule-item:last-child {
        border-bottom: none;
    }

    /* Chat */
    .vp-chat-item {
        display: block;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid var(--vp-border);
        text-decoration: none;
        color: inherit;
        transition: background .15s;
    }

    .vp-chat-item:hover,
    .vp-chat-item--active {
        background: rgb(242 81 35 / 0.06);
    }

.vp-msg-image{
    margin-top:8px !important;
}

.vp-msg:has(.vp-msg-image){
    width:auto;
    max-width:300px;
}

.vp-msg-time--mine{
    color: var(--vp-primary);
     font-weight: 700;
}

.messagejnd {
    margin: 0px;
    /* padding: 0px;
    padding-left: 13px;
    margin-top: 10px; */

  margin: 0;
  padding: 1rem 1.25rem 0.0rem;
  font-size: 0.7875rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--c-muted);

}
.marginbottom {
    margin-bottom: 20px;
}
/* Date Slider Wrapper */
.vp-date-slider {

    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    background-color: #ffffff;
    flex-wrap: wrap;
}

/* Slider Arrows */
.vp-slider-arrow {
    background: none;
    border: none;
    cursor: pointer;
    color: #a0aec0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background-color 0.2s, color 0.2s;
}

.vp-slider-arrow:hover {
    background-color: #f7fafc;
    color: #4a5568;
}

.vp-slider-arrow svg {
    width: 20px;
    height: 20px;
}

/* Days Layout Container */
.vp-slider-days {
    display: flex;
    gap: 1.25rem;
    align-items: center;
}

/* Individual Day Configurations */
.vp-day-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    padding: 0.4rem 0.5rem;
    cursor: pointer;
    border-radius: 10px;
    transition: all 0.2s ease-in-out;
}

.vp-day-name {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #a0aec0;
    font-weight: 600;
    letter-spacing: 0.05em;
}

.vp-day-num {
    font-size: 0.95rem;
    font-weight: 700;
    color: #2d3748;
    margin-top: 0.2rem;
}

/* Hover effect for unselected days */
.vp-day-item:not(.active):hover {
    background-color: #f7fafc;
}

/* Active Highlighted Pill State (Matches image) */
.vp-day-item.active {
    background-color: #ff4500; /* Deep Vibrant Orange */
    box-shadow: 0 4px 10px rgba(255, 69, 0, 0.3);
}

.vp-day-item.active .vp-day-name {
    color: #ffffff;
}

.vp-day-item.active .vp-day-num {
    color: #ffffff;
}
.bordernone {
    border-bottom: 0px;
}

.bordernones {
border-bottom: 0px !important;
}
.paddingvp {
    padding-top: 0px;
}
/* Responsive container wrapper */
.vp-table-responsive {
    width: 100%;
    overflow-x: auto;
    background-color: #ffffff;
    border-radius: 8px;
}

/* Master Table Configuration */
.vp-bookings-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-family: system-ui, -apple-system, sans-serif;
}

/* Table Header Styles */
.vp-bookings-table thead tr {
    background-color: #f8f9fa;
    border-bottom: 1px solid #edf2f7;
}

.vp-bookings-table th {
    padding: 1rem 1.25rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #718096;
    letter-spacing: 0.05em;
}

/* Row Item Configurations */
.vp-bookings-table tbody tr {
    border-bottom: 1px solid #f0f4f8;
    transition: background-color 0.15s;
}

.vp-bookings-table tbody tr:hover {
    background-color: #fafbfc;
}

.vp-bookings-table td {
    padding: 1.25rem;
    vertical-align: middle;
}

/* Common Typography Subunits */
.vp-text-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #0f2d3a;
}

.vp-text-main {
    font-size: 0.88rem;
    font-weight: 600;
    color: #334155;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.vp-text-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #0f2d3a;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vp-text-sub {
    font-size: 0.78rem;
    color: #a0aec0;
    margin-top: 0.15rem;
}

.vp-text-price {
    font-size: 0.9rem;
    font-weight: 700;
    color: #0f2d3a;
}

/* Image & Profile Avatars Elements */
.vp-cell-product, .vp-cell-customer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.vp-product-img {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
}

/* Update your avatar layout styles */
.vp-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;

    /* CRITICAL FIX: Stops the image from squishing */
    flex-shrink: 0;
}

.vp-avatar-placeholder {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;

    /* CRITICAL FIX: Stops the placeholder from squishing */
    flex-shrink: 0;
}

/* Native Custom Dropdown Pill Design */
.vp-select-wrapper {
    position: relative;
    display: inline-block;
    border-radius: 8px;
    width: 110px;
}

.vp-status-select {
    width: 100%;
    padding: 0.45rem 1.5rem 0.45rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
    border: none;
    background: transparent;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    outline: none;
}

/* Chevron arrow implementation for custom select */
.vp-select-wrapper::after {
    content: "▼";
    font-size: 0.55rem;
    position: absolute;
    right: 0.75rem;
    top: 53%;
    transform: translateY(-50%);
    pointer-events: none;
    opacity: 0.5;
}

/* Status variants matching colors in image */
.vp-status-blue {
    background-color: #e1effe;
    color: #1e429f;
}
.vp-status-blue::after { color: #1e429f; }

.vp-status-orange {
    background-color: #fef3c7;
    color: #b45309;
}
.vp-status-orange::after { color: #b45309; }

/* Action Container & Button Elements */
.vp-action-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vp-btn-accept {
    background-color: #f24e1e;
    color: #ffffff;
    border: none;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.45rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: opacity 0.2s;
}

.vp-btn-reject {
    background-color: #ffffff;
    color: #f24e1e;
    border: 1px solid #f24e1e;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.42rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.vp-btn-accept:hover { opacity: 0.9; }
.vp-btn-reject:hover { background-color: #fff5f2; }

/* Action Eye Icon */
.vp-btn-view {
    color: #a0aec0;
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: 4px;
    transition: color 0.2s;
}

.vp-btn-view:hover {
    color: #4a5568;
}

.vp-btn-view svg {
    width: 18px;
    height: 18px;
}

/* Empty State Styling */
.vp-empty-row {
    text-align: center;
    color: #a0aec0;
    padding: 3rem 0 !important;
    font-size: 0.9rem;
}
/* Layout Container */
.vp-bookings-header {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding: 1.25rem 1.5rem;
    background-color: #ffffff;
    font-family: system-ui, -apple-system, sans-serif;


}

/* Top Row Alignments */
.vp-bookings-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.vp-bookings-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
}

/* Form Actions Row */
.vp-bookings-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Input Fields & Buttons Baselines */
.vp-search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.vp-input-icon {
    position: absolute;
    left: 0.75rem;
    width: 16px;
    height: 16px;
    color: #a0aec0;
}

.vp-input-search {
    padding: 0.5rem 0.75rem 0.5rem 2.25rem;
    font-size: 0.9rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background-color: #f7fafc;
    color: #4a5568;
    outline: none;
    width: 200px;
    transition: border-color 0.2s;
}

.vp-input-search:focus {
    border-color: #cbd5e0;
}

/* Filter/Date Buttons */
.vp-btn-filter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem 0.5rem 2.25rem;
    position: relative;
    font-size: 0.9rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background-color: #ffffff;
    color: #4a5568;
    cursor: pointer;
}

.vp-filter-placeholder {
    width: 100px;
    height: 38px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background-color: #ffffff;
}

/* Export Button (Dark Teal State) */
.vp-btn-export {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem 0.5rem 2.25rem;
    position: relative;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    border-radius: 20px; /* Pillow oval shape */
    background-color: #0c3336; /* Dark teal hue */
    color: #ffffff;
    cursor: pointer;
    transition: background-color 0.2s;
}

.vp-btn-export:hover {
    background-color: #14494d;
}

.vp-btn-export .vp-input-icon {
    color: #ffffff;
}

/* Navigation Tabs Row */
.vp-bookings-tabs {
    display: flex;
    gap: 1.5rem;
    border-bottom: 1px solid #edf2f7;
    margin-top: 0.25rem;
}

.vp-tab-item {
    font-size: 0.95rem;
    font-weight: 500;
    color: #718096;
    text-decoration: none;
    padding-bottom: 0.75rem;
    position: relative;
    transition: color 0.2s;
}

.vp-tab-item:hover {
    color: #4a5568;
}

/* Active Indicator Line (Matches image highlight) */
.vp-tab-item.active {
    color: #ff4500; /* Vibrant Orange */
    font-weight: 600;
}

.vp-tab-item.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: #ff4500;
    border-radius: 2px 2px 0 0;
}
.jdv {
    padding: 10px;
}
.jbw-input {
    width: 100%;
  border: 1.5px solid var(--vp-border-strong);
  border-radius: 10px;
  background: var(--c-surface);
  color: var(--c-text);
  transition: border-color var(--trans), box-shadow var(--trans);
  appearance: none;
}
.vp-msg-time--theirs{
    color: black;
    font-weight: 700;
}
.vp-msg-image img{
    display:block !important;
    width:250px !important;
    max-width:100% !important;
    height:auto !important;
    border-radius:8px !important;
}
    .vp-msg {
        max-width: 72%;
        padding: .8rem 1rem;
        border-radius: 16px;
        margin-bottom: .85rem;
        font-size: .9rem;
        line-height: 1.45;
    }

    .vp-msg--mine {
        margin-left: auto;
        background: var(--vp-teal);
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .vp-msg--theirs {
        background: #fff;
        border: 1px solid var(--vp-border);
        border-bottom-left-radius: 4px;
    }

    .vp-msg-time {
        font-size: .7rem;
        opacity: .75;
        margin-top: .35rem;
    }

    /* Settings */
    .vp-settings-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }

    .vp-settings-layout {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 1.25rem;
        align-items: start;
    }

    .vp-settings-nav-card {
        padding: .65rem;
        position: sticky;
        top: 5rem;
        align-self: start;
        max-height: calc(100vh - 6.5rem);
        overflow-y: auto;
    }

    .vp-settings-nav a {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .72rem .85rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: .88rem;
        color: var(--vp-muted);
        margin-bottom: 2px;
        border-left: 3px solid transparent;
    }

    .vp-settings-nav a:hover {
        background: #f8fafc;
        color: var(--vp-text);
    }

    .vp-settings-nav a.active {
        background: var(--vp-orange-soft);
        color: var(--vp-orange);
        border-left-color: var(--vp-orange);
    }

    .vp-settings-nav a.active .vp-icon {
        color: var(--vp-orange);
    }

    .vp-settings-panel {
        padding: 1.5rem 1.65rem;
    }

    .vp-settings-panel-title {
        margin: 0 0 1.35rem;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .vp-settings-panel-foot {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--vp-border);
    }

    .vp-toggle-wrap {
        display: flex;
        align-items: center;
        gap: .65rem;
    }

    .vp-toggle-label {
        font-size: .88rem;
        font-weight: 600;
        color: var(--vp-text);
    }

    .vp-toggle {
        position: relative;
        width: 48px;
        height: 26px;
        flex-shrink: 0;
    }

    .vp-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }

    .vp-toggle-track {
        position: absolute;
        inset: 0;
        background: #d5dce3;
        border-radius: 999px;
        cursor: pointer;
        transition: background .2s;
    }

    .vp-toggle-track::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        left: 3px;
        top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: transform .2s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .15);
    }

    .vp-toggle input:checked+.vp-toggle-track {
        background: var(--vp-orange);
    }

    .vp-toggle input:checked+.vp-toggle-track::after {
        transform: translateX(22px);
    }

    .vp-input-icon-wrap {
        position: relative;
    }

    .vp-input-icon-wrap .vp-icon {
        position: absolute;
        left: .9rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.1rem;
        height: 1.1rem;
        color: var(--vp-muted);
    }

    .vp-input-icon-wrap .vp-input {
        padding-left: 2.65rem;
        background: #f8fafc;
        border-color: #edf1f5;
    }
.vp-page-heads {
    padding: 15px;

    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--vp-border-strong);
}
.vptitle {
    font-weight: 600;
    font-size: 1.45rem;
}
.vp-chat-compose{
    display:flex;
    align-items:center;
    gap:12px;
    padding:6px 8px 6px 10px;
    border:1px solid #ead8d1;
    border-radius:30px;
    background:#fff;
    margin: 0;
    box-sizing: border-box;
}

.vp-chat-compose-stack {
    margin: 12px 18px 18px;
    display: flex;
    flex-direction: column;
    gap: .55rem;
    flex-shrink: 0;
}

.vp-chat-attach-preview {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .55rem .7rem;
    border: 1px solid #ead8d1;
    border-radius: 1rem;
    background: #fff8f5;
}

.vp-chat-attach-preview[hidden] {
    display: none !important;
}

.vp-chat-attach-preview-body {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: .65rem;
}

.vp-chat-attach-preview-thumb {
    width: 3rem;
    height: 3rem;
    max-width: 3rem;
    max-height: 3rem;
    border-radius: .65rem;
    object-fit: cover;
    flex-shrink: 0;
    background: #e2e8f0;
}

.vp-chat-attach-preview-thumb--video {
    object-fit: cover;
}

.vp-chat-attach-preview-icon {
    width: 3rem;
    height: 3rem;
    border-radius: .65rem;
    display: grid;
    place-items: center;
    background: #ffe8e0;
    color: #c53b11;
    flex-shrink: 0;
}

.vp-chat-attach-preview-meta {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: .1rem;
}

.vp-chat-attach-preview-name {
    font-size: .82rem;
    font-weight: 600;
    color: #0f172a;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vp-chat-attach-preview-size {
    font-size: .72rem;
    color: #64748b;
}

.vp-chat-attach-preview-clear {
    width: 1.75rem;
    height: 1.75rem;
    border: none;
    border-radius: 999px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 1.1rem;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
}

.vp-chat-attach-preview-clear:hover {
    background: #fecaca;
}

.vp-chat-attach{
    width:32px;
    height:32px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    color:#777;
    flex-shrink:0;
}

.vp-chat-attach input{
    display:none;
}

.vp-chat-input{
    flex:1;
    border:none;
    outline:none;
    resize:none;
    background:transparent;
    height:34px;
    min-height:34px;
    max-height:100px;
    padding:7px 4px;
    margin:0;
    font-size:14px;
    line-height:20px;
    box-sizing:border-box;
    overflow-y:auto;
    overflow-x:hidden;
    vertical-align:middle;
}

.vp-chat-input::placeholder{
    color:#999;
    line-height:20px;
    opacity:1;
}

.vp-chat-send{
    width:34px;
    height:34px;
    border:none;
    border-radius:50%;
    background:#c53b11;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    flex-shrink:0;
}

.vp-chat-send:hover{
    opacity:.9;
}
    .vp-input-icon-wrap .vp-input-toggle {
        position: absolute;
        right: 2.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--vp-muted);
        cursor: pointer;
        padding: .25rem;
    }

    .vp-profile-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 1.5rem;
        align-items: start;
    }

    .vp-cover-block {
        position: relative;
    }

    .vp-cover-label {
        font-size: .8rem;
        font-weight: 600;
        margin-bottom: .55rem;
        display: block;
    }

    .vp-cover-frame {
        position: relative;
        height: 160px;
        border-radius: 14px;
        overflow: visible;
        background: linear-gradient(135deg, #ffe8de, #fff4ef);
        border: 1px solid var(--vp-border);
    }
    /* .vp-cover-frame {
    position: relative;
    height: 160px;
    border-radius: 14px;
    overflow: visible;
    background: linear-gradient(135deg, #ffe8de, #fff4ef);
    border: 1px solid var(--vp-border);
    margin-bottom: 50px;
} */

    .vp-cover-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 14px;
    }

    .vp-cover-placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        color: var(--vp-muted);
        font-size: .82rem;
    }

    .vp-cover-edit,
    .vp-profile-edit {
        position: absolute;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--vp-orange);
        color: #fff;
        border: 2px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(242, 81, 35, .35);
    }

    .vp-cover-edit .vp-icon,
    .vp-profile-edit .vp-icon,
    .vp-portfolio-edit .vp-icon {
        width: .95rem;
        height: .95rem;
    }

    .vp-cover-edit {
        top: .65rem;
        right: .65rem;
    }

    .vp-profile-edit {
        width: 28px;
        height: 28px;
        bottom: 9px;
        right: 7px;
    }

    .vp-profile-avatar-wrap {
        position: absolute;
        left: 7rem;
        bottom: -40px;
        width: 88px;
        height: 88px;
        border-radius: 50%;
        border: 4px solid #fff;
        overflow: hidden;
        background: var(--vp-orange-soft);
        box-shadow: 0 4px 14px rgba(16, 24, 40, .1);
    }

    .vp-profile-avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vp-profile-avatar-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.5rem;
        color: var(--vp-orange);
    }

    .vp-cover-block {
        margin-bottom: 2.75rem;
    }

    .vp-editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: .25rem;
        padding: .55rem .65rem;
        border: 1px solid var(--vp-border);
        border-bottom: none;
        border-radius: 11px 11px 0 0;
        background: #fafbfc;
    }

    .vp-editor-btn {
        width: 32px;
        height: 32px;
        border: none;
        background: transparent;
        border-radius: 7px;
        color: var(--vp-muted);
        font-weight: 700;
        cursor: pointer;
        font-size: .82rem;
    }

    .vp-editor-btn:hover {
        background: #eef2f6;
        color: var(--vp-text);
    }

    .vp-editor-area {
        width: 100%;
        min-height: 220px;
        padding: 1rem;
        border: 1px solid var(--vp-border);
        border-radius: 0 0 11px 11px;
        font: inherit;
        resize: vertical;
        background: #fff;
    }

    .vp-editor-area:focus {
        outline: none;
        border-color: var(--vp-orange);
        box-shadow: 0 0 0 3px rgba(242, 81, 35, .1);
    }

    .vp-portfolio-tabs {
        display: flex;
        gap: 1.75rem;
        border-bottom: 1px solid var(--vp-border);
        margin: 0 0 1.35rem;
    }

    .vp-portfolio-tab {
        padding: .35rem 0 .7rem;
        font-weight: 600;
        font-size: .95rem;
        text-decoration: none;
        color: #94a3b8;
        border-bottom: 2.5px solid transparent;
        margin-bottom: -1px;
        transition: color .15s, border-color .15s;
    }

    .vp-portfolio-tab:hover {
        color: var(--vp-text);
    }

    .vp-portfolio-tab--active {
        color: var(--vp-orange);
        border-bottom-color: var(--vp-orange);
    }

    .vp-portfolio-panel {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .vp-portfolio-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .vp-portfolio-item {
        position: relative;
        border-radius: 14px;
        overflow: hidden;
        aspect-ratio: 1;
        background: #f1f5f9;
    }

    .vp-portfolio-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .vp-portfolio-edit {
        position: absolute;
        bottom: .6rem;
        left: .6rem;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--vp-orange);
        color: #fff;
        border: 2px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(242, 81, 35, .35);
        cursor: pointer;
        padding: 0;
    }

    .vp-portfolio-edit .vp-icon {
        width: .95rem;
        height: .95rem;
    }

    .vp-portfolio-empty {
        margin: 0;
        padding: 1.5rem 0 .25rem;
        text-align: center;
        color: var(--vp-muted);
        font-size: .9rem;
    }

    .vp-portfolio-upload {
        width: 100%;
        margin: 0;
    }

    .vp-portfolio-upload-error {
        margin-top: .65rem;
        text-align: center;
    }

    .vp-portfolio-add {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .55rem;
        width: 100%;
        min-height: 132px;
        padding: 1.5rem 1.25rem;
        text-align: center;
        border: 1.5px dashed #d0d5dd;
        border-radius: 14px;
        background: #fafbfc;
        color: var(--vp-muted);
        cursor: pointer;
        transition: border-color .15s, background .15s, color .15s;
    }

    .vp-portfolio-add:hover {
        border-color: var(--vp-orange);
        background: var(--vp-orange-soft);
        color: var(--vp-text);
    }

    .vp-portfolio-add:hover .vp-portfolio-add-icon {
        background: transparent;
        color: var(--vp-orange);
        border: none;
    }

    .vp-portfolio-add-icon {
        width: auto;
        height: auto;
        margin: 0;
        border: none;
        border-radius: 0;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        color: #98a2b3;
    }

    .vp-portfolio-add-icon .vp-icon {
        width: 1.65rem;
        height: 1.65rem;
    }

    .vp-portfolio-add-title {
        font-weight: 600;
        font-size: .95rem;
        color: #667085;
        margin: 0;
    }

    .vp-portfolio-add-sub {
        display: none;
    }

    .vp-portfolio-add:hover .vp-portfolio-add-title {
        color: var(--vp-orange);
    }

    .vp-service-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
    }

    .vp-service-chip {
        position: relative;
    }

    .vp-service-chip input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .vp-service-chip span {
        display: inline-flex;
        padding: .62rem 1.1rem;
        border-radius: 11px;
        border: 1.5px solid var(--vp-border-strong);
        font-size: .875rem;
        font-weight: 600;
        color: var(--vp-muted);
        cursor: pointer;
        background: #fff;
        transition: all .15s;
    }

    .vp-service-chip input:checked+span {
        border-color: var(--vp-orange);
        color: var(--vp-orange);
        background: var(--vp-orange-soft);
    }

    .vp-form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .vp-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.25rem;
    }

    .vp-form-grid .vp-field--full {
        grid-column: 1 / -1;
    }

    .vp-form-section-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .85rem;
    }

    .vp-form-section-head .vp-label {
        margin-bottom: 0;
    }

    .vp-form-section {
        padding-top: 1.25rem;
        margin-top: .25rem;
        border-top: 1px solid var(--vp-border);
    }

    .vp-form-section:first-child {
        padding-top: 0;
        margin-top: 0;
        border-top: none;
    }

    .vp-form-actions {
        justify-content: end;
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        padding-top: 1.25rem;
        margin-top: 1.25rem;
        border-top: 1px solid var(--vp-border);
    }

    .vp-repeat-row {
        border: 1px solid var(--vp-border);
        border-radius: 12px;
        padding: 1rem;
        background: #fafbfc;
    }

    .vp-repeat-row-grid {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
        align-items: end;
    }

    .vp-repeat-row-grid--damage {
        grid-template-columns: minmax(0, 1fr) 8rem auto;
    }

    .vp-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
        gap: .75rem;
        margin-bottom: .85rem;
    }

    .vp-gallery-item {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        border: 1px solid var(--vp-border);
        background: #fff;
    }

    .vp-gallery-item img {
        aspect-ratio: 1;
        width: 100%;
        object-fit: cover;
        display: block;
    }

    .vp-gallery-remove {
        position: absolute;
        top: .35rem;
        right: .35rem;
        min-width: 1.65rem;
        height: 1.65rem;
        padding: 0;
        border-radius: 8px;
        font-size: .85rem;
        line-height: 1;
    }

    .vp-product-preview {
        width: 6rem;
        height: 6rem;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid var(--vp-border);
        margin-bottom: .75rem;
    }

    .vp-section-title {
        font-size: .95rem;
        font-weight: 700;
        margin: 1.5rem 0 1rem;
        padding-top: .5rem;
        border-top: 1px solid var(--vp-border);
    }

    .vp-section-title:first-child {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }

    .vp-account-type {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .75rem;
    }

    .vp-account-type label {
        position: relative;
    }

    .vp-account-type input {
        position: absolute;
        opacity: 0;
    }

    .vp-account-type span {
        display: block;
        text-align: center;
        padding: .85rem 1rem;
        border-radius: 11px;
        border: 1.5px solid var(--vp-border-strong);
        font-weight: 600;
        color: var(--vp-muted);
        cursor: pointer;
        background: #fff;
    }

    .vp-account-type input:checked+span {
        border-color: var(--vp-orange);
        color: var(--vp-orange);
        background: var(--vp-orange-soft);
    }

    .vp-legal-content {
        font-size: .9rem;
        line-height: 1.75;
        color: var(--vp-text);
    }

    .vp-legal-content p {
        margin: 0 0 .85rem;
    }

    .vp-legal-content p:last-child {
        margin-bottom: 0;
    }

    .vp-legal-content h1,
    .vp-legal-content h2,
    .vp-legal-content h3 {
        margin: 1.25rem 0 .65rem;
        font-weight: 700;
        color: var(--vp-text);
        line-height: 1.35;
    }

    .vp-legal-content h1 {
        font-size: 1.2rem;
    }

    .vp-legal-content h2 {
        font-size: 1.05rem;
    }

    .vp-legal-content h3 {
        font-size: .95rem;
    }

    .vp-legal-content ul,
    .vp-legal-content ol {
        margin: 0 0 .85rem;
        padding-left: 1.35rem;
    }

    .vp-legal-content li {
        margin-bottom: .35rem;
    }

    .vp-legal-content strong,
    .vp-legal-content b {
        font-weight: 700;
        color: var(--vp-text);
    }

    .vp-legal-content a {
        color: var(--vp-orange);
        text-decoration: underline;
    }

    .vp-legal-content blockquote {
        margin: 0 0 .85rem;
        padding-left: .85rem;
        border-left: 3px solid var(--vp-border);
        color: var(--vp-muted);
    }

    .vp-legal-meta {
        font-size: .78rem;
        color: var(--vp-muted);
        margin: -.75rem 0 1.25rem;
    }

    .vp-faq-item {
        border: 1px solid var(--vp-border);
        border-radius: 12px;
        margin-bottom: .65rem;
        overflow: hidden;
    }

    .vp-faq-item summary {
        padding: 1rem 1.15rem;
        font-weight: 600;
        font-size: .9rem;
        cursor: pointer;
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .vp-faq-item summary::-webkit-details-marker {
        display: none;
    }

    .vp-faq-item summary::after {
        content: '▾';
        color: var(--vp-muted);
        font-size: .85rem;
    }

    .vp-faq-item[open] {
        border-color: var(--vp-orange-muted);
    }

    .vp-faq-item[open] summary {
        color: var(--vp-orange);
    }

    .vp-faq-item[open] summary::after {
        transform: rotate(180deg);
    }

    .vp-faq-answer {
        padding: 0 1.15rem 1rem;
        font-size: .88rem;
        color: var(--vp-muted);
        line-height: 1.6;
    }

    @media (max-width: 1100px) {
        .vp-profile-layout {
            grid-template-columns: 1fr;
        }

        .vp-portfolio-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .vp-settings-layout {
            grid-template-columns: 1fr;
        }

        .vp-form-grid-2 {
            grid-template-columns: 1fr;
        }

        .vp-form-grid {
            grid-template-columns: 1fr;
        }

        .vp-repeat-row-grid,
        .vp-repeat-row-grid--damage {
            grid-template-columns: 1fr;
        }

        .vp-portfolio-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .vp-filters-grid {
            display: grid;
            grid-template-columns: 1fr;
        }

        .vp-filters-field,
        .vp-filters-field--wide,
        .vp-filters-field--date {
            min-width: 0;
            max-width: none;
            width: 100%;
        }

        .vp-filters-actions,
        .vp-filters-page-actions {
            width: 100%;
            margin-left: 0;
        }

        .vp-filters-actions-btns,
        .vp-filters-page-actions-btns {
            width: 100%;
        }

        .vp-filters-actions .vp-btn {
            flex: 1 1 auto;
        }
    }

    /* Auth */
    .vp-guest-wrap {
        min-height: 100vh;
        overflow-x: hidden;
        overflow-y: visible;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem 1.25rem 2.5rem;
        background: #ffffff;
    }

    .vp-auth-card {
        width: 100%;
        max-width: 380px;
        background: transparent;
        border-radius: 0;
        padding: 0;
        box-shadow: none;
        border: 0;
        text-align: center;
    }

    .vp-auth-logo-wrap {
        margin-bottom: 1.5rem;
    }

    .vp-auth-logo {
        width: 72px;
        height: 72px;
        margin: 0 auto;
        border-radius: 16px;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: none;
    }

    .vp-auth-logo svg {
        width: 72px;
        height: 72px;
        display: block;
        border-radius: 16px;
    }

    .vp-auth-brand {
        margin: 0 0 1.5rem;
        font-size: 1.3rem;
        font-weight: 800;
        letter-spacing: -.02em;
        color: #1e293b;
        text-align: center;
    }

    .vp-auth-kicker {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--vp-teal);
        margin: 0 0 .35rem;
        text-align: center;
    }

    .vp-auth-title {
        font-size: 1.65rem;
        font-weight: 800;
        margin: 0 0 .45rem;
        letter-spacing: -.03em;
        text-align: left !important;
        color: #1e293b;
    }

    .vp-auth-sub {
        color: #94a3b8;
        font-size: .95rem;
        margin: 0 0 1.75rem;
        line-height: 1.5;
        text-align: left !important;
    }

    .vp-auth-form {
        text-align: left;
    }

    .vp-divider {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin: 1.85rem 0 1.35rem;
        color: #94a3b8;
        font-size: .85rem;
        font-weight: 500;
    }

    .vp-divider::before,
    .vp-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }

    .vp-social-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .65rem;
        padding: .85rem 1rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        color: #334155;
        font-weight: 600;
        font-size: .9rem;
        margin-bottom: .75rem;
        cursor: not-allowed;
    }

    .vp-auth-footer {
        margin-top: 1.85rem;
        font-size: .9rem;
        color: #64748b;
        text-align: center;
    }

    .vp-auth-footer a {
        color: var(--vp-orange);
        font-weight: 700;
        text-decoration: none;
    }

    .vp-otp-input {
        text-align: center;
        letter-spacing: .45rem;
        font-size: 1.35rem;
        font-weight: 800;
    }

    /* Pagination override */
    .pagination {
        display: flex;
        gap: .35rem;
        flex-wrap: wrap;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .pagination a,
    .pagination span {
        display: inline-flex;
        padding: .4rem .7rem;
        border-radius: 8px;
        border: 1px solid var(--vp-border);
        font-size: .82rem;
        text-decoration: none;
    }

    .pagination .active span {
        background: var(--vp-orange-soft);
        border-color: var(--vp-orange-muted);
        color: var(--vp-orange);
        font-weight: 700;
    }

    .vp-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 5px;
}

.vp-field-full {
    grid-column: 1 / -1;
}

@media (max-width: 768px) {
    .vp-form-grid {
        grid-template-columns: 1fr;
    }

    .vp-field-full {
        grid-column: auto;
    }
}
    @media (max-width: 1100px) {
        .vp-grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .vp-grid-2 {
            grid-template-columns: 1fr;
        }

        .vp-settings-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1024px) {
        body.vp-body {
            overflow: auto;
        }

        .vp-shell {
            height: auto;
            min-height: 100vh;
            overflow: visible;
        }

        .vp-sidebar {
            transform: translateX(-100%);
            transition: transform .22s ease;
            height: 100vh;
        }

        .vp-sidebar.is-open {
            transform: translateX(0);
        }

        .vp-main {
            margin-left: 0;
            height: auto;
            overflow: visible;
        }

        .vp-settings-nav-card {
            position: static;
            max-height: none;
            overflow: visible;
        }

        .vp-menu-btn {
            display: inline-flex;
        }

        .vp-content {
            padding: 1.15rem;
        }

        .vp-topbar {
            padding: .85rem 1.15rem;
        }
    }

    @media (max-width: 768px) {
        .vp-topbar-wallets {
            display: none;
        }
    }

    @media (max-width: 640px) {
        .vp-grid-4 {
            grid-template-columns: 1fr;
        }

        .vp-wallet-grid {
            grid-template-columns: 1fr;
        }

        .vp-earnings-grid {
            gap: 1.25rem;
        }

        .vp-page-title {
            font-size: 1.35rem;
        }
    }

    .vp-promo-banner {
        display: flex;
        gap: 1rem;
        align-items: stretch;
        margin-bottom: 1.25rem;
        padding: 1rem;
        border-radius: var(--vp-radius-lg);
        background: linear-gradient(135deg, var(--vp-orange-soft), #fff);
        border: 1px solid var(--vp-orange-muted);
        text-decoration: none;
        color: inherit;
        transition: border-color .15s, box-shadow .15s;
    }

    .vp-promo-banner:hover {
        border-color: var(--vp-orange);
        box-shadow: var(--vp-shadow);
    }

    .vp-promo-banner__img {
        width: 7.5rem;
        height: 5.5rem;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .vp-promo-banner__body {
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .vp-promo-banner__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: var(--vp-text);
        line-height: 1.25;
    }

    .vp-promo-banner__sub {
        margin: .35rem 0 0;
        font-size: .84rem;
        color: var(--vp-muted);
        line-height: 1.45;
    }

    @media (max-width: 640px) {
        .vp-promo-banner {
            flex-direction: column;
        }

        .vp-promo-banner__img {
            width: 100%;
            height: 8rem;
        }
    }

    @media (max-width: 768px) {
    .vp-grid-4,
    .vp-stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }
}

@media (max-width: 480px) {
    .vp-grid-4,
    .vp-stat-grid {
        grid-template-columns: 1fr;
    }
}

/* ─── Booking detail page ───────────────────────────────────────── */
.vp-booking-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem 1.25rem;
    margin-bottom: 1.25rem;
}
.vp-booking-id {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -.02em;
}
.vp-booking-checkout-ref {
    margin: .35rem 0 0;
    font-size: .8125rem;
    color: var(--vp-muted);
}
.vp-booking-header-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-top: .5rem;
}
.vp-booking-booked-on {
    margin: 0;
    font-size: .8125rem;
    color: var(--vp-muted);
    white-space: nowrap;
}
.vp-type-pill {
    display: inline-flex;
    padding: .22rem .55rem;
    border-radius: 999px;
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    background: #f1f5f9;
    color: #475569;
}
.vp-booking-layout {
    display: grid;
    gap: 1.25rem;
    min-width: 0;
}
@media (min-width: 1024px) {
    .vp-booking-layout {
        grid-template-columns: minmax(0, 1fr) 22rem;
        align-items: start;
    }
}
.vp-booking-main,
.vp-booking-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 0;
}
.vp-booking-card {
    background: var(--vp-surface);
    border: 1px solid var(--vp-border);
    border-radius: var(--vp-radius-lg);
    padding: 1.1rem 1.2rem;
    box-shadow: var(--vp-shadow);
    min-width: 0;
}
.vp-booking-card--compact { padding: 1rem 1.1rem; }
.vp-booking-card-title {
    margin: 0 0 .85rem;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--vp-muted);
}
.vp-booking-card-title--flush { margin-bottom: .25rem; }
.vp-booking-muted { margin: 0; font-size: .875rem; color: var(--vp-muted); }
.vp-booking-product-row {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    min-width: 0;
}
.vp-booking-line-items { display: grid; gap: 1rem; }
.vp-booking-product-media { flex-shrink: 0; }
.vp-booking-product-img {
    width: 5.5rem;
    height: 5.5rem;
    border-radius: .65rem;
    object-fit: cover;
    background: #f8fafc;
}
.vp-booking-product-placeholder {
    width: 5.5rem;
    height: 5.5rem;
    border-radius: .65rem;
    background: #f8fafc;
    border: 1px dashed var(--vp-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.vp-booking-product-info { min-width: 0; flex: 1; }
.vp-booking-product-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.35;
    overflow-wrap: anywhere;
}
.vp-booking-product-meta {
    margin: .25rem 0 0;
    font-size: .8125rem;
    color: var(--vp-muted);
    overflow-wrap: anywhere;
}
.vp-booking-product-price {
    margin: .5rem 0 0;
    font-size: 1.125rem;
    font-weight: 800;
}
.vp-booking-product-qty {
    margin: .15rem 0 0;
    font-size: .75rem;
    color: var(--vp-muted);
}
.vp-booking-split {
    display: grid;
    gap: 1rem;
}
@media (min-width: 640px) {
    .vp-booking-split { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vp-booking-split--single { grid-template-columns: minmax(0, 1fr); }
}
.vp-booking-person {
    display: flex;
    align-items: center;
    gap: .75rem;
    min-width: 0;
}
.vp-booking-person-avatar {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 999px;
    background: var(--vp-primary-soft, #fff7ed);
    color: var(--vp-primary, #ea580c);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    flex-shrink: 0;
}
.vp-booking-person-info { min-width: 0; flex: 1; }
.vp-booking-person-name {
    margin: 0;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.vp-booking-person-meta {
    margin: .15rem 0 0;
    font-size: .78rem;
    color: var(--vp-muted);
}
.vp-booking-call-btn {
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    background: #dcfce7;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}
.vp-booking-rental-dates {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
}
.vp-booking-rental-days {
    margin: .25rem 0 0;
    font-size: .8125rem;
    color: var(--vp-muted);
}
.vp-booking-address-name {
    margin: 0 0 .35rem;
    font-weight: 700;
}
.vp-booking-address-text {
    margin: 0;
    font-size: .875rem;
    color: var(--vp-muted);
    line-height: 1.55;
    overflow-wrap: anywhere;
}
.vp-booking-measures {
    display: grid;
    gap: .65rem;
}
.vp-booking-measures--grid {
    grid-template-columns: repeat(auto-fill, minmax(6.5rem, 1fr));
}
.vp-measure-section {
    margin-bottom: 1rem;
}
.vp-measure-section:last-child {
    margin-bottom: 0;
}
.vp-measure-section-title {
    margin: 0 0 .5rem;
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--vp-muted);
}
.vp-booking-measure {
    padding: .7rem .75rem;
    border-radius: .65rem;
    background: #f8fafc;
    border: 1px solid var(--vp-border);
    text-align: center;
    min-height: 4.25rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.vp-booking-measure-label {
    display: block;
    font-size: .65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--vp-muted);
}
.vp-booking-measure-value {
    display: block;
    margin-top: .3rem;
    font-weight: 800;
    font-size: .9375rem;
    color: var(--vp-text, #0f172a);
    word-break: break-word;
}
.vp-booking-extra-measures {
    display: grid;
    gap: .5rem;
    margin: .85rem 0 0;
    padding-top: .85rem;
    border-top: 1px solid var(--vp-border);
}
.vp-booking-extra-measures dt {
    font-size: .65rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--vp-muted);
}
.vp-booking-extra-measures dd {
    margin: .15rem 0 0;
    font-weight: 600;
}
.vp-booking-notes {
    margin: 0;
    font-size: .9rem;
    line-height: 1.55;
    overflow-wrap: anywhere;
}
.vp-booking-ref-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(5.5rem, 1fr));
    gap: .65rem;
}
.vp-booking-ref-thumb {
    display: block;
    border-radius: .65rem;
    overflow: hidden;
    border: 1px solid var(--vp-border);
    aspect-ratio: 1;
}
.vp-booking-ref-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.vp-booking-proof { margin-top: 1rem; }
.vp-booking-proof-img {
    display: block;
    margin-top: .5rem;
    max-width: 10rem;
    border-radius: .65rem;
    border: 1px solid var(--vp-border);
}
.vp-booking-track {
    list-style: none;
    margin: 0;
    padding: 0;
}
.vp-booking-track-step {
    position: relative;
    display: flex;
    gap: .75rem;
    padding-bottom: 1rem;
}
.vp-booking-track-step:not(:last-child)::before {
    content: '';
    position: absolute;
    left: .72rem;
    top: 1.5rem;
    bottom: 0;
    width: 2px;
    background: var(--vp-border);
}
.vp-booking-track-step--done:not(:last-child)::before { background: #86efac; }
.vp-booking-track-marker {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 999px;
    border: 2px solid var(--vp-border);
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    z-index: 1;
}
.vp-booking-track-marker svg { width: .75rem; height: .75rem; color: #fff; }
.vp-booking-track-step--done .vp-booking-track-marker { background: #16a34a; border-color: #16a34a; }
.vp-booking-track-step--current .vp-booking-track-marker { border-color: var(--vp-primary, #ea580c); box-shadow: 0 0 0 3px rgb(234 88 12 / .15); }
.vp-booking-track-step--cancelled .vp-booking-track-marker { background: #fee2e2; border-color: #fca5a5; }
.vp-booking-track-label {
    margin: 0;
    font-size: .875rem;
    font-weight: 700;
}
.vp-booking-track-step--upcoming .vp-booking-track-label { color: var(--vp-muted); font-weight: 600; }
.vp-booking-track-time {
    margin: .15rem 0 0;
    font-size: .75rem;
    color: var(--vp-muted);
}
.vp-booking-payment-lines {
    display: grid;
    gap: .5rem;
    margin: 0;
}
.vp-booking-payment-lines > div {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    font-size: .875rem;
}
.vp-booking-payment-lines dt { color: var(--vp-muted); font-weight: 600; }
.vp-booking-payment-lines dd { margin: 0; font-weight: 700; }
.vp-booking-payment-damage dd { color: #b91c1c; }
.vp-booking-payment-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-top: .85rem;
    padding-top: .85rem;
    border-top: 1px solid var(--vp-border);
    font-weight: 700;
}
.vp-booking-payment-total strong {
    font-size: 1.125rem;
    color: var(--vp-primary, #ea580c);
}
.vp-booking-actions {
    display: grid;
    gap: .5rem;
}
.vp-booking-manage-form .vp-select { width: 100%; }
.vp-btn--block { width: 100%; justify-content: center; }
.vp-btn--success { background: #16a34a; color: #fff; border: none; }
.vp-rent-tracking-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}
.vp-rent-tracking-phase {
    margin: .35rem 0 0;
    font-size: .8125rem;
    font-weight: 700;
}
.vp-rent-tracking-phase--active { color: #059669; }
.vp-rent-tracking-phase--upcoming { color: #2563eb; }
.vp-rent-tracking-phase--awaiting_return,
.vp-rent-tracking-phase--overdue { color: #c2410c; }
.vp-rent-tracking-phase--unscheduled,
.vp-rent-tracking-phase--cancelled { color: var(--vp-muted); }
.vp-rent-duration-badge {
    flex-shrink: 0;
    min-width: 4.25rem;
    padding: .45rem .65rem;
    border-radius: .65rem;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    text-align: center;
}
.vp-rent-duration-badge__value {
    display: block;
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1;
    color: #9a3412;
}
.vp-rent-duration-badge__label {
    display: block;
    margin-top: .1rem;
    font-size: .62rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #c2410c;
}
.vp-rent-tracking-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .65rem;
    margin-bottom: 1rem;
}
@media (min-width: 640px) {
    .vp-rent-tracking-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
.vp-rent-stat {
    padding: .65rem;
    border-radius: .55rem;
    background: #f8fafc;
    border: 1px solid var(--vp-border);
}
.vp-rent-stat__label {
    display: block;
    font-size: .62rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--vp-muted);
}
.vp-rent-stat__value {
    display: block;
    margin-top: .2rem;
    font-size: .9rem;
    font-weight: 700;
}
.vp-rent-progress { margin-bottom: 1rem; }
.vp-rent-progress__meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: .35rem;
    font-size: .75rem;
    font-weight: 700;
    color: var(--vp-muted);
}
.vp-rent-progress__bar {
    height: .45rem;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}
.vp-rent-progress__fill {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #fb923c, #ea580c);
}
.vp-rent-date-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
    gap: .65rem;
    margin-bottom: 1rem;
}
.vp-rent-date-grid dt {
    font-size: .62rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--vp-muted);
}
.vp-rent-date-grid dd {
    margin: .15rem 0 0;
    font-weight: 700;
    font-size: .875rem;
}
.vp-rent-track-detail {
    margin: .15rem 0 0;
    font-size: .75rem;
    color: var(--vp-muted);
}
@media (max-width: 768px) {
    .vp-booking-measures--grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

/* Vendor panel global overflow containment */
.vp-shell,
.vp-main,
.vp-content,
.vp-card,
.vp-filters,
.vp-wallet-grid,
.vp-table-wrap {
    max-width: 100%;
}
.vp-content > * {
    max-width: 100%;
}
.vp-wallet-card-note,
.vp-page-sub,
.vp-td-note {
    overflow-wrap: anywhere;
    word-break: break-word;
}
.vp-table td:not(.vp-td-note) {
    max-width: 12rem;
}
.vp-table td:first-child,
.vp-table td:nth-child(2) {
    max-width: none;
}
@media (max-width: 1100px) {
    .vp-filters-page-actions {
        margin-left: 0;
        width: 100%;
    }
    .vp-filters-grid {
        width: 100%;
    }
}
</style>
  