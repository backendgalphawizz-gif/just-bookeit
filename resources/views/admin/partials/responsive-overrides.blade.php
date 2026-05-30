<style>
    @media (max-width: 639px) {
        .jb-topbar { flex-direction: column; align-items: stretch; gap: 0.75rem; }
        .jb-topbar-actions { width: 100%; justify-content: flex-start; }
        .jb-topbar-title { font-size: 1.125rem; line-height: 1.75rem; }
        .jb-filters-grid { display: grid; grid-template-columns: 1fr; gap: 0.75rem; }
        .jb-filters-field, .jb-filters-field--wide, .jb-filters-field--date { min-width: 0; max-width: none; width: 100%; }
        .jb-filters-actions, .jb-filters-page-actions { width: 100%; margin-left: 0; }
        .jb-filters-actions-btns, .jb-filters-page-actions-btns { flex-wrap: wrap; }
        .jb-filters-actions .jb-btn { flex: 1 1 auto; }
        .jb-table th, .jb-table td { padding: 0.75rem; }
        .jb-table th.jb-table-actions-col, .jb-table td.jb-table-actions-col { min-width: 6.5rem; }
        .jb-action-btn { min-width: 0; padding-left: 0.625rem; padding-right: 0.625rem; }
        .jb-main { padding: 1rem; }
        .jb-card-body, .jb-card-header { padding-left: 1rem; padding-right: 1rem; }
        .jb-detail-grid { grid-template-columns: 1fr; }
        .jb-tabs-row { flex-direction: column; align-items: stretch; }
        .jb-tabs-list { width: 100%; }
    }

    @media (min-width: 640px) and (max-width: 1279px) {
        .jb-filters-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
        .jb-filters-field, .jb-filters-field--wide, .jb-filters-field--date { min-width: 0; width: 100%; }
        .jb-filters-field--wide { grid-column: span 2; }
        .jb-filters-actions, .jb-filters-page-actions { width: auto; }
    }

    @media (min-width: 1280px) {
        .jb-filters-grid { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem; }
        .jb-filters-field { width: auto; flex: 0 1 11rem; min-width: 8.5rem; }
        .jb-filters-field--wide { flex: 1 1 14rem; max-width: 16rem; }
        .jb-filters-page-actions { margin-left: auto; }
    }

    .jb-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: rgb(203 213 225) transparent;
    }

    .jb-table-wrap::-webkit-scrollbar { height: 6px; }
    .jb-table-wrap::-webkit-scrollbar-thumb { background: rgb(203 213 225); border-radius: 9999px; }

    .jb-table-sticky-col {
        position: sticky;
        left: 0;
        z-index: 10;
        background: #fff;
        box-shadow: 4px 0 8px -4px rgba(15, 23, 42, 0.12);
    }

    .jb-table thead .jb-table-sticky-col { background: rgba(248, 250, 252, 0.95); }

    .jb-tabs-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgb(226 232 240);
    }

    .jb-tabs-list { display: flex; flex-wrap: wrap; gap: 0.5rem; }

    .jb-modal-alert {
        position: fixed;
        inset: 0;
        z-index: 300;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .jb-modal-alert-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .jb-modal-alert-actions .jb-modal-alert-btn {
        margin-top: 0;
        flex: 1 1 0;
    }

    .jb-modal-alert-btn--ghost {
        background: rgb(241 245 249);
        color: rgb(51 65 85);
        box-shadow: none;
    }

    .jb-modal-alert-btn--ghost:hover {
        background: rgb(226 232 240);
    }

    .jb-actor-avatar {
        display: block;
        width: 2.25rem;
        height: 2.25rem;
        flex-shrink: 0;
        border-radius: 9999px;
        object-fit: cover;
    }

    .jb-actor-avatar--md {
        width: 3rem;
        height: 3rem;
    }

    .jb-actor-avatar--lg {
        width: 6rem;
        height: 6rem;
        box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.1);
    }

    .jb-actor-avatar--initials {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgb(244 63 94), rgb(190 18 60));
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .jb-actor-avatar--lg.jb-actor-avatar--initials {
        font-size: 1.5rem;
    }

    .jb-actor-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .jb-actor-profile {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 1.25rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid rgb(226 232 240);
    }

    .jb-doc-image-grid {
        display: grid;
        gap: 1rem;
        margin-top: 1rem;
    }

    @media (min-width: 640px) {
        .jb-doc-image-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    .jb-doc-image {
        width: 100%;
        max-height: 20rem;
        border-radius: 0.75rem;
        border: 1px solid rgb(226 232 240);
        background: rgb(248 250 252);
        padding: 0.5rem;
        object-fit: contain;
    }
</style>
