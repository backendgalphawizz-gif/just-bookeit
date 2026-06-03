<style>
    /* Admin create/edit/show: wide panels use full main area; compact panels stay centered */
    .jb-main {
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }

    .jb-main-column {
        min-width: 0;
    }

    .jb-main > .jb-card,
    .jb-main > form.jb-card,
    .jb-main > .jb-filters {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .jb-card .jb-table-wrap {
        margin-left: -0.25rem;
        margin-right: -0.25rem;
        padding-left: 0.25rem;
        padding-right: 0.25rem;
    }

    @media (min-width: 640px) {
        .jb-card .jb-table-wrap {
            margin-left: 0;
            margin-right: 0;
            padding-left: 0;
            padding-right: 0;
        }
    }

    .jb-main .jb-card.max-w-5xl,
    .jb-main .jb-card.max-w-4xl,
    .jb-main .jb-card.max-w-3xl,
    .jb-main form.jb-card.max-w-5xl,
    .jb-main form.jb-card.max-w-4xl,
    .jb-main form.jb-card.max-w-3xl,
    .jb-main .jb-detail-grid.max-w-3xl,
    .jb-main .grid.max-w-5xl {
        max-width: none;
        width: 100%;
    }

    .jb-main .jb-card.max-w-2xl,
    .jb-main form.jb-card.max-w-2xl,
    .jb-main .jb-detail-card.max-w-2xl,
    .jb-main .jb-detail-card.max-w-xl,
    .jb-main .jb-panel--centered {
        width: 100%;
        max-width: 42rem;
        margin-left: auto;
        margin-right: auto;
    }

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

    /* Keep columns from overlapping when the main panel is narrow */
    .jb-table-wrap .jb-table {
        min-width: 56rem;
    }

    .jb-table-wrap .jb-table.jb-table--wide {
        min-width: 72rem;
    }

    .jb-table th {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }

    .jb-table td {
        vertical-align: middle;
    }

    .jb-table--balanced {
        table-layout: auto;
    }

    .jb-table--balanced .jb-col-name {
        width: auto;
        min-width: 6rem;
        max-width: 11rem;
    }

    .jb-table th.jb-col-name,
    .jb-table td.jb-col-name,
    .jb-table th.jb-col-username,
    .jb-table td.jb-col-username,
    .jb-table th.jb-col-email,
    .jb-table td.jb-col-email,
    .jb-table th.jb-col-role,
    .jb-table td.jb-col-role,
    .jb-table th.jb-col-city,
    .jb-table td.jb-col-city,
    .jb-table th.jb-col-category,
    .jb-table td.jb-col-category {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .jb-col-username {
        width: 7.5rem;
        min-width: 7rem;
        max-width: 9rem;
    }

    .jb-col-email {
        width: 12rem;
        min-width: 10.5rem;
        max-width: 15rem;
    }

    .jb-col-role {
        width: 8.5rem;
        min-width: 7.5rem;
        max-width: 11rem;
    }

    .jb-col-city {
        width: 6.5rem;
        min-width: 6rem;
        max-width: 8rem;
        white-space: nowrap;
    }

    .jb-col-category {
        width: 7rem;
        min-width: 6.5rem;
        max-width: 9rem;
    }

    .jb-table th.jb-col-date,
    .jb-table td.jb-col-date {
        width: 10.5rem;
        min-width: 10rem;
        max-width: 12rem;
        white-space: nowrap;
    }

    .jb-table th.jb-col-status,
    .jb-table td.jb-col-status {
        width: 7.5rem;
        min-width: 7.5rem;
        max-width: 8.5rem;
    }

    .jb-table th.jb-table-actions-col,
    .jb-table td.jb-table-actions-col {
        width: 10rem;
        min-width: 9.5rem;
        max-width: 11rem;
    }

    .jb-table td.jb-col-status .jb-badge {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
        white-space: nowrap;
    }

    .jb-table td .jb-actions {
        flex-wrap: nowrap;
        justify-content: flex-end;
    }

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

    .jb-actor-cell > span,
    .jb-actor-cell .font-semibold {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .jb-dl dd {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .jb-textarea-break {
        overflow-wrap: anywhere;
        word-wrap: break-word;
    }

    .jb-file-error-alert {
        margin-top: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.4;
        color: rgb(185 28 28);
        background: rgb(254 242 242);
        border: 1px solid rgb(254 202 202);
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

    .jb-form-section-title {
        margin: 0.5rem 0 0;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .jb-order-summary {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.5rem;
        padding: 1.5rem;
        border-radius: 1rem;
        border: 1px solid rgb(226 232 240);
        background: linear-gradient(135deg, rgb(255 255 255), rgb(248 250 252));
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }

    .jb-order-summary-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .jb-order-type-badge {
        display: inline-flex;
        border-radius: 9999px;
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .jb-order-type-badge--rental {
        background: rgb(254 243 199);
        color: rgb(146 64 14);
    }

    .jb-order-type-badge--sale {
        background: rgb(219 234 254);
        color: rgb(30 64 175);
    }

    .jb-order-summary-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-order-summary-meta {
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: rgb(100 116 139);
    }

    .jb-order-timeline {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 0;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    @media (min-width: 768px) {
        .jb-order-timeline {
            flex-wrap: nowrap;
            justify-content: space-between;
        }
    }

    .jb-order-timeline-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 auto;
        min-width: 4.5rem;
        text-align: center;
    }

    .jb-order-timeline-dot {
        width: 0.75rem;
        height: 0.75rem;
        border-radius: 9999px;
        background: rgb(226 232 240);
    }

    .jb-order-timeline-step--done .jb-order-timeline-dot {
        background: rgb(34 197 94);
    }

    .jb-order-timeline-step--current .jb-order-timeline-dot {
        background: var(--jb-primary, #be123c);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--jb-primary, #be123c) 25%, transparent);
    }

    .jb-order-timeline-label {
        font-size: 0.6875rem;
        font-weight: 600;
        color: rgb(100 116 139);
        line-height: 1.2;
    }

    .jb-order-timeline-step--current .jb-order-timeline-label {
        color: rgb(15 23 42);
    }

    .jb-dl--grid {
        display: grid;
        gap: 1rem 1.5rem;
    }

    @media (min-width: 640px) {
        .jb-dl--grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    /* Booking detail page (mockup layout) */
    .jb-booking-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem 1.5rem;
        margin-bottom: 1.25rem;
    }

    .jb-booking-id {
        font-size: 1.25rem;
        font-weight: 700;
        color: rgb(15 23 42);
        letter-spacing: -0.02em;
    }

    .jb-booking-header-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.375rem;
    }

    .jb-booking-booked-on {
        font-size: 0.8125rem;
        color: rgb(100 116 139);
        white-space: nowrap;
    }

    .jb-booking-layout {
        display: grid;
        gap: 1.25rem;
        min-width: 0;
        max-width: 100%;
    }

    @media (min-width: 1024px) {
        .jb-booking-layout {
            grid-template-columns: minmax(0, 1fr) 22rem;
            align-items: start;
        }
    }

    .jb-booking-main,
    .jb-booking-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-width: 0;
        max-width: 100%;
    }

    .jb-booking-card {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.875rem;
        padding: 1.125rem 1.25rem;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .jb-booking-card--compact {
        padding: 1rem 1.125rem;
    }

    .jb-booking-card-title {
        font-size: 0.8125rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: rgb(100 116 139);
        margin-bottom: 0.875rem;
    }

    .jb-booking-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.875rem;
        min-width: 0;
    }

    .jb-booking-card-head .jb-booking-card-title {
        flex: 1 1 auto;
        min-width: 0;
        margin-bottom: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .jb-booking-card-head .jb-booking-link {
        flex-shrink: 0;
        white-space: nowrap;
    }

    .jb-booking-link {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--jb-primary, #be123c);
        text-decoration: none;
    }

    .jb-booking-link:hover {
        text-decoration: underline;
    }

    .jb-booking-split {
        display: grid;
        gap: 1rem;
    }

    @media (min-width: 640px) {
        .jb-booking-split { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    .jb-booking-product-row {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        min-width: 0;
    }

    .jb-booking-product-info {
        min-width: 0;
        flex: 1 1 auto;
    }

    .jb-booking-product-media {
        flex-shrink: 0;
    }

    .jb-booking-product-img {
        width: 5.5rem;
        height: 5.5rem;
        border-radius: 0.625rem;
        object-fit: cover;
        background: rgb(248 250 252);
    }

    .jb-booking-product-placeholder {
        width: 5.5rem;
        height: 5.5rem;
        border-radius: 0.625rem;
        background: rgb(248 250 252);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed rgb(203 213 225);
    }

    .jb-booking-product-name {
        font-size: 1rem;
        font-weight: 700;
        color: rgb(15 23 42);
        line-height: 1.3;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .jb-booking-product-meta {
        font-size: 0.8125rem;
        color: rgb(100 116 139);
        margin-top: 0.25rem;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .jb-booking-product-price {
        font-size: 1.125rem;
        font-weight: 700;
        color: rgb(15 23 42);
        margin-top: 0.5rem;
    }

    .jb-booking-product-qty {
        font-size: 0.75rem;
        color: rgb(148 163 184);
        margin-top: 0.125rem;
    }

    .jb-booking-designer {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .jb-booking-designer-name {
        font-weight: 600;
        color: rgb(15 23 42);
        text-decoration: none;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .jb-booking-designer-name:hover {
        color: var(--jb-primary, #be123c);
    }

    .jb-booking-designer-meta {
        font-size: 0.75rem;
        color: rgb(100 116 139);
        margin-top: 0.125rem;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .jb-booking-call-btn {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 9999px;
        background: rgb(220 252 231);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 1rem;
    }

    .jb-booking-rental-dates {
        font-size: 1.125rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-booking-rental-days {
        font-size: 0.8125rem;
        color: rgb(100 116 139);
        margin-top: 0.25rem;
    }

    .jb-booking-icon-pin {
        margin-right: 0.25rem;
    }

    .jb-booking-address-name {
        font-weight: 600;
        color: rgb(15 23 42);
        font-size: 0.9375rem;
        overflow-wrap: anywhere;
        word-break: break-word;
        max-width: 100%;
    }

    .jb-booking-address-text {
        font-size: 0.875rem;
        color: rgb(71 85 105);
        margin-top: 0.25rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
        word-break: break-word;
        max-width: 100%;
    }

    .jb-booking-measures {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.625rem;
    }

    .jb-booking-measure {
        background: rgb(248 250 252);
        border-radius: 0.625rem;
        padding: 0.75rem 0.625rem;
        text-align: center;
    }

    .jb-booking-measure-label {
        display: block;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgb(148 163 184);
    }

    .jb-booking-measure-value {
        display: block;
        font-size: 0.9375rem;
        font-weight: 700;
        color: rgb(15 23 42);
        margin-top: 0.25rem;
    }

    .jb-booking-notes {
        font-size: 0.875rem;
        color: rgb(71 85 105);
        line-height: 1.6;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .jb-booking-ref-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .jb-booking-ref-thumb {
        display: block;
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid rgb(226 232 240);
    }

    .jb-booking-ref-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .jb-booking-track {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .jb-booking-track-step {
        display: flex;
        gap: 0.75rem;
        position: relative;
        padding-bottom: 1.25rem;
    }

    .jb-booking-track-step:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 0.6875rem;
        top: 1.375rem;
        bottom: 0;
        width: 2px;
        background: rgb(226 232 240);
    }

    .jb-booking-track-step--done:not(:last-child)::before {
        background: var(--jb-primary, #be123c);
    }

    .jb-booking-track-marker {
        flex-shrink: 0;
        width: 1.375rem;
        height: 1.375rem;
        border-radius: 9999px;
        border: 2px solid rgb(226 232 240);
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
    }

    .jb-booking-track-marker svg {
        width: 0.75rem;
        height: 0.75rem;
        color: #fff;
    }

    .jb-booking-track-step--done .jb-booking-track-marker {
        background: var(--jb-primary, #be123c);
        border-color: var(--jb-primary, #be123c);
    }

    .jb-booking-track-step--current .jb-booking-track-marker {
        border-color: var(--jb-primary, #be123c);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--jb-primary, #be123c) 20%, transparent);
    }

    .jb-booking-track-step--cancelled .jb-booking-track-marker {
        background: rgb(248 250 252);
        border-color: rgb(203 213 225);
    }

    .jb-booking-track-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(15 23 42);
    }

    .jb-booking-track-step--upcoming .jb-booking-track-label {
        color: rgb(148 163 184);
    }

    .jb-booking-track-time {
        font-size: 0.75rem;
        color: rgb(100 116 139);
        margin-top: 0.125rem;
    }

    .jb-booking-payment-lines {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
        margin: 0;
    }

    .jb-booking-payment-lines > div {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 1rem;
        font-size: 0.875rem;
    }

    .jb-booking-payment-lines dt {
        color: rgb(100 116 139);
        font-weight: 500;
    }

    .jb-booking-payment-lines dd {
        font-weight: 600;
        color: rgb(15 23 42);
        margin: 0;
    }

    .jb-booking-payment-damage dd {
        color: rgb(225 29 72);
    }

    .jb-booking-payment-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgb(226 232 240);
        font-size: 0.9375rem;
    }

    .jb-booking-payment-total strong {
        font-size: 1.375rem;
        font-weight: 800;
        color: var(--jb-primary, #be123c);
    }

    .jb-booking-manage-form {
        display: flex;
        flex-direction: column;
        gap: 0.875rem;
    }

    .jb-booking-quick-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .jb-order-type-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .jb-order-type-badge--rental {
        background: rgb(254 243 199);
        color: rgb(146 64 14);
    }

    .jb-order-type-badge--sale {
        background: rgb(219 234 254);
        color: rgb(29 78 216);
    }

    @media (max-width: 639px) {
        .jb-booking-measures {
            grid-template-columns: 1fr;
        }

        .jb-booking-product-row {
            flex-direction: column;
        }

        .jb-booking-product-img,
        .jb-booking-product-placeholder {
            width: 100%;
            height: 10rem;
        }
    }
</style>
