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
    position: sticky; top: 0; z-index: 30;
}
.vp-topbar-left { display: flex; align-items: center; gap: .85rem; min-width: 0; }
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
.vp-content { padding: 1.5rem 1.75rem 2rem; flex: 1; }

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
}
.vp-filters-grid { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; }
.vp-filters-field { flex: 0 1 auto; min-width: 8.5rem; }
.vp-filters-field--wide { min-width: 11rem; max-width: 16rem; flex: 1 1 14rem; }
.vp-filters-field--date { min-width: 10.75rem; }
.vp-filters-date-group { display: contents; }
.vp-filters-actions, .vp-filters-page-actions { display: flex; flex-direction: column; flex-shrink: 0; }
.vp-filters-page-actions { margin-left: auto; }
.vp-filters-actions-btns, .vp-filters-page-actions-btns { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
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
.vp-card { background: var(--vp-surface); border: 1px solid var(--vp-border); border-radius: var(--vp-radius-lg); box-shadow: var(--vp-shadow); overflow: hidden; }
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
.vp-earnings h3 { margin: 0 0 .75rem; font-size: .72rem; letter-spacing: .08em; opacity: .85; font-weight: 700; }
.vp-earnings-grid { display: flex; gap: 2.5rem; flex-wrap: wrap; }
.vp-earnings-val { font-size: 1.85rem; font-weight: 800; line-height: 1.1; }
.vp-earnings-actions { display: flex; gap: .65rem; align-items: center; }

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

.vp-topbar-right { display: flex; align-items: center; gap: .65rem; margin-left: auto; }
.vp-topbar-wallets { display: flex; align-items: center; gap: .55rem; }
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
.vp-table-wrap { overflow-x: auto; }
.vp-table { width: 100%; border-collapse: collapse; font-size: .875rem; min-width: 880px; }
.vp-table th {
    text-align: left; padding: .9rem 1.15rem; font-size: .68rem; text-transform: uppercase;
    letter-spacing: .05em; color: var(--vp-muted); border-bottom: 1px solid var(--vp-border); background: #fafbfc; white-space: nowrap;
}
.vp-table td { padding: 1rem 1.15rem; border-bottom: 1px solid var(--vp-border); vertical-align: middle; }
.vp-table tbody tr:hover td { background: #fbfcfd; }
.vp-table-product { display: flex; align-items: center; gap: .75rem; }
.vp-thumb { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; background: #f1f5f9; border: 1px solid var(--vp-border); }


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
.vp-schedule-icon { width: 40px; height: 40px; border-radius: 10px; background: var(--vp-orange-soft); color: var(--vp-orange); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

/* Chat */
.vp-page-head--compact { margin-bottom: 1rem; }
.vp-chat-layout {
    display: grid;
    grid-template-columns: minmax(260px, 320px) 1fr;
    gap: 1rem;
    height: calc(100vh - 10.5rem);
    max-height: calc(100vh - 10.5rem);
    min-height: 24rem;
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
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.vp-chat-threads::-webkit-scrollbar { display: none; }
.vp-chat-thread {
    display: flex;
    gap: .75rem;
    padding: .9rem 1.15rem;
    text-decoration: none;
    color: inherit;
    border-left: 3px solid transparent;
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
    height: 100%;
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
    text-decoration: none;
    color: var(--vp-muted);
    font-size: 1.25rem;
    line-height: 1;
    padding-right: .15rem;
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
    padding: 1.15rem;
    display: flex;
    flex-direction: column;
    gap: .85rem;
    background: #f8fafc;
}
.vp-chat-messages::before {
    content: "";
    flex: 1 1 auto;
    min-height: 0;
}
.vp-chat-messages::-webkit-scrollbar { display: none; }
.vp-chat-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    max-width: 78%;
    min-width: 0;
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
    border: 1px solid var(--vp-border);
    border-bottom-left-radius: .25rem;
}
.vp-chat-bubble--mine {
    background: var(--vp-teal);
    color: #fff;
    border-bottom-right-radius: .25rem;
}
.vp-chat-time {
    margin-top: .3rem;
    font-size: .68rem;
    color: var(--vp-muted);
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
    .vp-chat-layout {
        grid-template-columns: 1fr;
        height: calc(100vh - 9.5rem);
        max-height: calc(100vh - 9.5rem);
    }
    .vp-chat-sidebar,
    .vp-chat-main { min-height: 0; }
    .vp-chat-sidebar--mobile-hide { display: none; }
    .vp-chat-main--mobile-hide { display: none; }
    .vp-chat-back { display: inline-block; }
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

.vp-portfolio-tabs { display: flex; gap: 1.5rem; border-bottom: 1px solid var(--vp-border); margin-bottom: 1.25rem; }
.vp-portfolio-tab {
    padding: .55rem 0; font-weight: 600; font-size: .9rem; text-decoration: none; color: var(--vp-muted);
    border-bottom: 2px solid transparent; margin-bottom: -1px;
}
.vp-portfolio-tab--active { color: var(--vp-orange); border-bottom-color: var(--vp-orange); }
.vp-portfolio-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
.vp-portfolio-item { position: relative; border-radius: 12px; overflow: hidden; aspect-ratio: 1; background: #f1f5f9; }
.vp-portfolio-item img { width: 100%; height: 100%; object-fit: cover; }
.vp-portfolio-edit {
    position: absolute; bottom: .55rem; left: .55rem; width: 30px; height: 30px; border-radius: 50%;
    background: var(--vp-orange); color: #fff; border: 2px solid #fff; display: flex; align-items: center; justify-content: center;
    text-decoration: none; box-shadow: 0 2px 8px rgba(242,81,35,.35);
}
.vp-portfolio-upload { width: 100%; margin-top: 1.25rem; }
.vp-portfolio-add {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    width: 100%; min-height: 148px; padding: 1.75rem 1.5rem; text-align: center;
    border: 2px dashed var(--vp-border-strong); border-radius: 14px; background: #fafbfc;
    color: var(--vp-muted); cursor: pointer; transition: border-color .15s, background .15s, color .15s;
}
.vp-portfolio-add:hover {
    border-color: var(--vp-orange); background: var(--vp-orange-soft); color: var(--vp-text);
}
.vp-portfolio-add:hover .vp-portfolio-add-icon { background: #fff; color: var(--vp-orange); border-color: var(--vp-orange-muted); }
.vp-portfolio-add-icon {
    width: 52px; height: 52px; border-radius: 14px; margin-bottom: .85rem;
    display: flex; align-items: center; justify-content: center;
    background: #fff; border: 1px solid var(--vp-border); color: var(--vp-muted);
    transition: background .15s, color .15s, border-color .15s;
}
.vp-portfolio-add-icon .vp-icon { width: 1.5rem; height: 1.5rem; }
.vp-portfolio-add-title { font-weight: 700; font-size: .95rem; color: var(--vp-text); margin: 0; }
.vp-portfolio-add-sub { font-size: .8rem; color: var(--vp-muted); margin: .35rem 0 0; }
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
    .vp-repeat-row-grid, .vp-repeat-row-grid--damage { grid-template-columns: 1fr; }
    .vp-portfolio-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vp-filters-grid { display: grid; grid-template-columns: 1fr; }
    .vp-filters-field, .vp-filters-field--wide, .vp-filters-field--date { min-width: 0; max-width: none; width: 100%; }
    .vp-filters-actions, .vp-filters-page-actions { width: 100%; margin-left: 0; }
    .vp-filters-actions-btns, .vp-filters-page-actions-btns { width: 100%; }
    .vp-filters-actions .vp-btn { flex: 1 1 auto; }
}

/* Auth */
.vp-guest-wrap { min-height: 100vh; display: grid; place-items: center; padding: 2rem 1.25rem; background: radial-gradient(circle at top right, #fff4ef 0%, #f3f5f7 42%, #eef2f6 100%); }
.vp-auth-card {
    width: 100%; max-width: 440px; background: var(--vp-surface); border-radius: 24px; padding: 2.25rem 2rem 2rem;
    box-shadow: 0 20px 50px rgba(16, 24, 40, .08); border: 1px solid rgba(255,255,255,.8); text-align: center;
}
.vp-auth-logo-wrap { margin-bottom: 1.25rem; }
.vp-auth-logo {
    width: 72px; height: 72px; margin: 0 auto; border-radius: 18px; background: var(--vp-orange-soft);
    display: flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px var(--vp-orange-muted);
}
.vp-auth-logo svg { width: 44px; height: 44px; }
.vp-auth-kicker { font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vp-teal); margin: 0 0 .35rem; }
.vp-auth-title { font-size: 1.5rem; font-weight: 800; margin: 0 0 .35rem; letter-spacing: -.02em; }
.vp-auth-sub { color: var(--vp-muted); font-size: .92rem; margin: 0 0 1.5rem; line-height: 1.5; }
.vp-auth-form { text-align: left; }
.vp-divider { display: flex; align-items: center; gap: .75rem; margin: 1.35rem 0; color: var(--vp-muted); font-size: .8rem; }
.vp-divider::before, .vp-divider::after { content: ''; flex: 1; height: 1px; background: var(--vp-border); }
.vp-social-btn {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: .65rem;
    padding: .72rem 1rem; border: 1px solid var(--vp-border-strong); border-radius: 11px; background: #fff;
    color: var(--vp-muted); font-weight: 600; font-size: .875rem; margin-bottom: .55rem; cursor: not-allowed;
}
.vp-auth-footer { margin-top: 1.35rem; font-size: .875rem; color: var(--vp-muted); }
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
</style>
