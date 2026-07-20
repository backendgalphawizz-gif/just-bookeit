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

    .jb-main>.jb-card,
    .jb-main>form.jb-card,
    .jb-main>.jb-filters {
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
        .jb-topbar {
            flex-direction: row;
            align-items: stretch;
            gap: 0.75rem;
        }

        .jb-topbar-actions {
            /* width: 100%; */
            justify-content: flex-start;
        }

        .jb-topbar-title {
            font-size: 16px;
            line-height: 1.75rem;
        }

        .jb-topbar-sub {
            font-size: 12px;
            margin-top: 0;
        }

        .jb-filters-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .jb-filters-field,
        .jb-filters-field--wide,
        .jb-filters-field--date {
            min-width: 0;
            max-width: none;
            width: 100%;
        }

        .jb-filters-actions,
        .jb-filters-page-actions {
            width: 100%;
            margin-left: 0;
        }

        .jb-filters-actions-btns,
        .jb-filters-page-actions-btns {
            flex-wrap: wrap;
        }

        .jb-filters-actions .jb-btn {
            flex: 1 1 auto;
        }

        .jb-table th,
        .jb-table td {
            padding: 0.75rem;
        }

        .jb-table th.jb-table-actions-col,
        .jb-table td.jb-table-actions-col {
            min-width: 6.5rem;
        }

        .jb-action-btn {
            min-width: 0;
            padding-left: 0.625rem;
            padding-right: 0.625rem;
        }

        .jb-main {
            padding: 1rem;
        }

        .jb-card-body,
        .jb-card-header {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .jb-detail-grid {
            grid-template-columns: 1fr;
        }

        .jb-tabs-row {
            flex-direction: column;
            align-items: stretch;
        }

        .jb-tabs-list {
            width: 100%;
        }
    }

    @media (min-width: 640px) and (max-width: 1279px) {
        .jb-filters-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .jb-filters-field,
        .jb-filters-field--wide,
        .jb-filters-field--date {
            min-width: 0;
            width: 100%;
        }

        .jb-filters-field--wide {
            grid-column: span 2;
        }

        .jb-filters-actions,
        .jb-filters-page-actions {
            width: auto;
        }
    }

    @media (min-width: 1280px) {
        .jb-filters-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.75rem;
        }

        .jb-filters-field {
            width: auto;
            flex: 0 1 11rem;
            min-width: 8.5rem;
        }

        .jb-filters-field--wide {
            flex: 1 1 14rem;
            max-width: 16rem;
        }

        .jb-filters-page-actions {
            margin-left: auto;
        }
    }

    .jb-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: rgb(203 213 225) transparent;
    }

    .jb-table-wrap::-webkit-scrollbar {
        height: 6px;
    }

    .jb-table-wrap::-webkit-scrollbar-thumb {
        background: rgb(203 213 225);
        border-radius: 9999px;
    }

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

    .jb-table thead .jb-table-sticky-col {
        background: rgba(248, 250, 252, 0.95);
    }

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

    .jb-tabs-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 10px;
    }

    .jb-tabs-row--nested {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .jb-modal-alert {
        position: fixed;
        inset: 0;
        z-index: 300;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        overflow-x: hidden;
    }

    .jb-modal-alert-card {
        min-width: 0;
        max-width: min(28rem, 100%);
        overflow: hidden;
    }

    .jb-modal-alert-title,
    .jb-modal-alert-message {
        max-width: 100%;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .jb-account-status-banner__layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem 1.25rem;
        align-items: start;
    }

    @media (max-width: 640px) {
        .jb-account-status-banner__layout {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .jb-account-status-banner__content {
        min-width: 0;
        max-width: 100%;
    }

    .jb-account-status-banner__title {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .jb-account-status-banner__reason {
        margin: 0.5rem 0 0;
        font-size: 0.875rem;
        line-height: 1.6;
        overflow-wrap: anywhere;
        word-break: break-word;
        white-space: pre-wrap;
        max-width: 100%;
    }

    .jb-account-status-banner__meta {
        margin: 1rem 0 0;
        display: grid;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    @media (min-width: 640px) {
        .jb-account-status-banner__meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .jb-account-status-banner__meta dt {
        font-weight: 600;
    }

    .jb-account-status-banner__meta dd {
        margin: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .jb-account-status-banner__actions {
        flex-shrink: 0;
        justify-self: end;
    }

    @media (max-width: 640px) {
        .jb-account-status-banner__actions {
            justify-self: start;
        }
    }

    .jb-modal-alert-reason {
        margin-top: 1rem;
        text-align: left;
    }

    .jb-modal-alert-reason .jb-label {
        display: block;
        margin-bottom: 0.375rem;
    }

    .jb-modal-alert-reason-count {
        margin: 0.375rem 0 0;
        font-size: 0.75rem;
        color: rgb(100 116 139);
    }

    .jb-modal-alert-reason-hint {
        margin: 0.375rem 0 0;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgb(225 29 72);
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
        background: linear-gradient(90deg, rgba(239, 66, 0, 1) 0%, rgba(237, 109, 81, 1) 35%, rgba(233, 84, 51, 1) 100%);
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

    .jb-actor-cell>span,
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

    .jb-upload-hint-alert {
        padding: 0.75rem 1rem;
        border-radius: 0.625rem;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.5;
        color: rgb(146 64 14);
        background: rgb(255 251 235);
        border: 1px solid rgb(253 230 138);
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
        .jb-doc-image-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
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

    .jb-sr-only {
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

    .jb-multi-image-upload-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr));
        gap: 1rem;
    }

    .jb-multi-image-upload-item {
        position: relative;
        overflow: hidden;
        border-radius: 0.75rem;
        border: 1px solid rgb(226 232 240);
        background: rgb(248 250 252);
        transition: opacity 0.15s ease, border-color 0.15s ease;
    }

    .jb-multi-image-upload-item__media {
        position: relative;
    }

    .jb-multi-image-upload-item img {
        display: block;
        width: 100%;
        height: 9.5rem;
        object-fit: cover;
    }

    .jb-multi-image-upload-item--preview {
        border-color: rgb(167 243 208);
        background: rgb(236 253 245);
    }

    .jb-multi-image-upload-item--marked {
        opacity: 0.65;
        border-color: rgb(253 186 116);
        background: rgb(255 251 235);
    }

    .jb-multi-image-upload-item__dismiss {
        position: absolute;
        top: 0.3rem;
        right: 0.3rem;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 1.125rem;
        height: 1.125rem;
        padding: 0;
        border: 1px solid rgb(226 232 240);
        border-radius: 9999px;
        background: rgb(255 255 255 / 0.96);
        color: rgb(100 116 139);
        font-size: 0.6875rem;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.1);
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .jb-multi-image-upload-item__dismiss:hover {
        background: rgb(254 242 242);
        color: rgb(220 38 38);
        border-color: rgb(254 202 202);
    }

    .jb-multi-image-upload-item__dismiss--active {
        background: rgb(254 226 226);
        color: rgb(220 38 38);
        border-color: rgb(252 165 165);
    }

    .jb-multi-image-upload-item__label,
    .jb-multi-image-upload-item__status {
        display: block;
        padding: 0.45rem 0.5rem;
        font-size: 0.6875rem;
        font-weight: 600;
        line-height: 1.25;
        text-align: center;
    }

    .jb-multi-image-upload-item__label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: rgb(71 85 105);
        background: rgb(241 245 249);
    }

    .jb-multi-image-upload-item__status {
        color: rgb(180 83 9);
        background: rgb(254 243 199);
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
        .jb-dl--grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
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
        .jb-booking-split {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .jb-booking-split--single {
            grid-template-columns: minmax(0, 1fr);
        }
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

    .jb-product-hero {
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
        align-items: flex-start;
        padding: 1.25rem;
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.875rem;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
        margin-bottom: 1.25rem;
        min-width: 0;
    }

    .jb-product-hero-cover {
        flex-shrink: 0;
        width: 9rem;
        height: 9rem;
        border-radius: 0.75rem;
        overflow: hidden;
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240);
    }

    .jb-product-hero-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .jb-product-hero-cover--empty {
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgb(148 163 184);
    }

    .jb-product-hero-body {
        flex: 1 1 16rem;
        min-width: 0;
    }

    .jb-product-hero-badges {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.65rem;
    }

    .jb-product-type-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        background: rgb(241 245 249);
        color: rgb(71 85 105);
    }

    .jb-product-hero-price {
        font-size: 1.375rem;
        font-weight: 800;
        color: rgb(15 23 42);
        line-height: 1.2;
    }

    .jb-product-hero-price span {
        font-size: 0.8125rem;
        font-weight: 600;
        color: rgb(100 116 139);
    }

    .jb-product-hero-vendor {
        margin-top: 0.45rem;
        font-size: 0.875rem;
        color: rgb(71 85 105);
    }

    .jb-product-hero-vendor a {
        font-weight: 600;
        color: var(--jb-primary, #be123c);
        text-decoration: none;
    }

    .jb-product-hero-vendor a:hover {
        text-decoration: underline;
    }

    .jb-product-hero-desc {
        margin-top: 0.75rem;
        font-size: 0.875rem;
        line-height: 1.55;
        color: rgb(71 85 105);
        overflow-wrap: anywhere;
    }

    .jb-product-layout {
        display: grid;
        gap: 1.25rem;
        min-width: 0;
    }

    @media (min-width: 1024px) {
        .jb-product-layout {
            grid-template-columns: minmax(0, 1fr) 20rem;
            align-items: start;
        }
    }

    .jb-product-main,
    .jb-product-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-width: 0;
    }

    .jb-product-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(7.5rem, 1fr));
        gap: 0.75rem;
    }

    .jb-product-gallery-item {
        position: relative;
        overflow: hidden;
        border-radius: 0.75rem;
        border: 1px solid rgb(226 232 240);
        background: rgb(248 250 252);
        aspect-ratio: 1;
    }

    .jb-product-gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        cursor: zoom-in;
    }

    .jb-product-gallery--videos {
        grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr));
    }

    .jb-product-gallery-item--video {
        aspect-ratio: 16 / 9;
    }

    .jb-product-gallery-item--video video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        background: #0f172a;
    }

    .jb-product-facts {
        display: grid;
        gap: 0.75rem;
    }

    .jb-product-fact {
        display: grid;
        gap: 0.2rem;
    }

    .jb-product-fact dt {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .jb-product-fact dd {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(15 23 42);
        margin: 0;
        overflow-wrap: anywhere;
    }

    .jb-product-reject-box {
        margin-top: 0.75rem;
        padding: 0.75rem 0.85rem;
        border-radius: 0.625rem;
        background: rgb(255 241 242);
        border: 1px solid rgb(254 205 211);
        font-size: 0.8125rem;
        color: rgb(190 18 60);
        line-height: 1.45;
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

    .jb-rent-tracking-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .jb-rent-tracking-phase {
        margin: 0.35rem 0 0;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .jb-rent-tracking-phase--active {
        color: rgb(5 150 105);
    }

    .jb-rent-tracking-phase--upcoming {
        color: rgb(37 99 235);
    }

    .jb-rent-tracking-phase--awaiting_return,
    .jb-rent-tracking-phase--overdue {
        color: rgb(194 65 12);
    }

    .jb-rent-tracking-phase--unscheduled,
    .jb-rent-tracking-phase--cancelled {
        color: rgb(100 116 139);
    }

    .jb-rent-duration-badge {
        flex-shrink: 0;
        min-width: 4.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        background: rgb(255 247 237);
        border: 1px solid rgb(254 215 170);
        text-align: center;
    }

    .jb-rent-duration-badge__value {
        display: block;
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1;
        color: rgb(154 52 18);
    }

    .jb-rent-duration-badge__label {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: rgb(194 65 12);
    }

    .jb-rent-tracking-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    @media (min-width: 640px) {
        .jb-rent-tracking-stats {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .jb-rent-stat {
        padding: 0.75rem;
        border-radius: 0.625rem;
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240);
    }

    .jb-rent-stat__label {
        display: block;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: rgb(100 116 139);
    }

    .jb-rent-stat__value {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.9375rem;
        color: rgb(15 23 42);
    }

    .jb-rent-progress {
        margin-bottom: 1rem;
    }

    .jb-rent-progress__meta {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgb(100 116 139);
    }

    .jb-rent-progress__bar {
        height: 0.5rem;
        border-radius: 9999px;
        background: rgb(226 232 240);
        overflow: hidden;
    }

    .jb-rent-progress__fill {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, rgb(251 146 60), rgb(234 88 12));
    }

    .jb-rent-date-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }

    .jb-rent-date-grid dt {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: rgb(100 116 139);
    }

    .jb-rent-date-grid dd {
        margin: 0.15rem 0 0;
        font-weight: 600;
        color: rgb(15 23 42);
    }

    .jb-rent-tracking-timeline {
        margin-top: 0.5rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgb(241 245 249);
    }

    .jb-rent-track-detail {
        margin: 0.15rem 0 0;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgb(234 88 12);
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

    .jb-measure-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .jb-measure-type {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: rgb(241 245 249);
        color: rgb(71 85 105);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    .jb-measure-section {
        margin: 0.75rem 0 0.4rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .jb-measure-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.4rem;
    }

    .jb-measure-cell {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
        padding: 0.45rem 0.55rem;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.5rem;
        background: #fff;
        min-width: 0;
    }

    .jb-measure-cell-label {
        font-size: 0.625rem;
        font-weight: 600;
        color: rgb(148 163 184);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        line-height: 1.2;
    }

    .jb-measure-cell-value {
        font-size: 0.8125rem;
        font-weight: 700;
        color: rgb(15 23 42);
        line-height: 1.25;
    }

    .jb-schedule-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.5rem;
    }

    .jb-schedule-item {
        padding: 0.65rem 0.75rem;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.625rem;
        background: rgb(248 250 252);
    }

    .jb-schedule-label {
        display: block;
        font-size: 0.6875rem;
        font-weight: 600;
        color: rgb(100 116 139);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 0.25rem;
    }

    .jb-schedule-value {
        display: block;
        font-size: 0.9375rem;
        font-weight: 700;
        color: rgb(15 23 42);
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

    .jb-booking-payment-lines>div {
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

    .jb-booking-billing {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .jb-booking-billing-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 1rem;
        font-size: 0.875rem;
        line-height: 1.35;
    }

    .jb-booking-billing-row > span:first-child,
    .jb-booking-billing-label {
        color: rgb(100 116 139);
        font-weight: 500;
        flex: 1 1 auto;
        min-width: 0;
    }

    .jb-booking-billing-row > span:last-child,
    .jb-booking-billing-row > strong,
    .jb-booking-billing-value {
        color: rgb(15 23 42);
        font-weight: 650;
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .jb-booking-billing-row--total {
        margin-top: 0.35rem;
        padding-top: 0.7rem;
        border-top: 1px solid rgb(226 232 240);
        font-size: 0.9375rem;
    }

    .jb-booking-billing-row--total > span:first-child,
    .jb-booking-billing-row--total .jb-booking-billing-label {
        color: rgb(15 23 42);
        font-weight: 700;
    }

    .jb-booking-billing-row--total > strong,
    .jb-booking-billing-row--total .jb-booking-billing-value {
        font-size: 1.05rem;
        font-weight: 800;
    }

    .jb-booking-billing-row--accent > span:last-child,
    .jb-booking-billing-row--accent .jb-booking-billing-value {
        color: rgb(194 65 12);
    }

    .jb-booking-billing-row--muted > span:last-child,
    .jb-booking-billing-row--muted .jb-booking-billing-value {
        color: rgb(71 85 105);
    }

    .jb-booking-billing-meta {
        margin-top: 0.85rem;
        padding-top: 0.75rem;
        border-top: 1px dashed rgb(226 232 240);
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .jb-booking-billing-meta p {
        margin: 0;
        font-size: 0.8125rem;
        color: rgb(100 116 139);
    }

    .jb-booking-billing-meta strong {
        color: rgb(51 65 85);
        font-weight: 650;
        text-transform: capitalize;
    }

    .jb-booking-card--accent .jb-booking-billing-row--total {
        border-top-color: rgba(148, 163, 184, 0.45);
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
        .jb-booking-measures,
        .jb-measure-grid,
        .jb-schedule-grid {
            grid-template-columns: 1fr 1fr;
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

    @media (min-width: 640px) and (max-width: 1023px) {
        .jb-measure-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .jb-history-modal-card {
        position: relative;
        z-index: 10;
        width: min(94vw, 72rem);
        max-width: min(94vw, 72rem);
        max-height: min(88vh, 44rem);
        display: flex;
        flex-direction: column;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
        text-align: left;
        overflow: hidden;
    }

    .jb-history-modal-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem 1rem;
        border-bottom: 1px solid rgb(226 232 240);
    }

    .jb-history-modal-title {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-history-modal-subtitle {
        margin: 0.35rem 0 0;
        font-size: 0.82rem;
        color: rgb(100 116 139);
    }

    .jb-history-modal-close {
        border: 0;
        background: transparent;
        color: rgb(100 116 139);
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        padding: 0.15rem 0.35rem;
    }

    .jb-history-modal-body {
        padding: 0 1.5rem;
        overflow-x: hidden;
        overflow-y: auto;
        flex: 1 1 auto;
    }

    .jb-history-modal-foot {
        display: flex;
        justify-content: flex-end;
        padding: 1rem 1.5rem 1.25rem;
        border-top: 1px solid rgb(226 232 240);
    }

    .jb-history-empty {
        padding: 2rem 0;
        text-align: center;
        font-size: 0.9rem;
        color: rgb(100 116 139);
    }

    .jb-history-table-wrap {
        padding: 1rem 0 1.25rem;
        overflow-x: auto;
    }

    .jb-history-table-wrap .jb-history-table {
        width: 100%;
        min-width: 52rem;
        table-layout: auto;
    }

    .jb-history-table th,
    .jb-history-table td {
        font-size: 0.8rem;
        vertical-align: top;
    }

    .jb-history-table th {
        white-space: nowrap;
    }

    .jb-history-table td.jb-history-date,
    .jb-history-table th:first-child {
        white-space: nowrap;
        width: 1%;
        min-width: 12.5rem;
    }

    .jb-history-table td.jb-history-status,
    .jb-history-table th:nth-child(3),
    .jb-history-table th:nth-child(4) {
        white-space: nowrap;
        width: 1%;
        min-width: 6.25rem;
    }

    .jb-history-table td:nth-child(2) {
        white-space: nowrap;
        width: 1%;
    }

    .jb-history-table td.jb-history-admin,
    .jb-history-table th:last-child {
        white-space: nowrap;
        width: 1%;
        min-width: 6.5rem;
    }

    .jb-history-reason {
        width: auto;
        min-width: 10rem;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .jb-history-action {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.15rem 0.55rem;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .jb-history-action--success {
        background: rgb(220 252 231);
        color: rgb(21 128 61);
    }

    .jb-history-action--error {
        background: rgb(255 228 230);
        color: rgb(190 18 60);
    }

    .jb-history-action--warning {
        background: rgb(254 243 199);
        color: rgb(180 83 9);
    }

    .jb-history-action--neutral {
        background: rgb(241 245 249);
        color: rgb(71 85 105);
    }

    .jb-export-dropdown {
        position: relative;
        display: inline-flex;
    }

    .jb-export-menu {
        position: absolute;
        top: calc(100% + 0.35rem);
        right: 0;
        z-index: 30;
        min-width: 7.5rem;
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.65rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }

    .jb-export-menu-item {
        display: block;
        padding: 0.55rem 0.85rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: rgb(51 65 85);
        text-decoration: none;
    }

    .jb-export-menu-item:hover {
        background: rgb(248 250 252);
        color: rgb(15 23 42);
    }

    .jb-wallet-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .jb-wallet-card {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.85rem;
        padding: 1rem 1.1rem;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .jb-wallet-card--digital {
        border-top: 4px solid rgb(245 158 11);
    }

    .jb-wallet-card--actual {
        border-top: 4px solid rgb(21 128 61);
    }

    .jb-wallet-card-label {
        margin: 0;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .jb-wallet-card-value {
        margin: 0.35rem 0 0;
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1.1;
        color: rgb(15 23 42);
    }

    .jb-wallet-card-note {
        margin: 0.45rem 0 0;
        font-size: 0.78rem;
        color: rgb(100 116 139);
    }

    @media (max-width: 900px) {
        .jb-wallet-grid {
            grid-template-columns: 1fr;
        }
    }

    .jb-card-header--stack {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .jb-analytics-filters {
        width: 100%;
    }

    .jb-analytics-filters-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .jb-analytics-filters-grid .jb-filters-field--date {
        min-width: 10.5rem;
        flex: 1 1 10.5rem;
        max-width: 12rem;
    }

    .jb-analytics-filters-grid .jb-filters-actions {
        width: auto;
        margin-left: 0;
    }

    /* Orders list — structured filter panel + table polish */
    .jb-orders-filters {
        padding: 1.25rem 1.5rem;
    }

    .jb-orders-filters__head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgb(241 245 249);
    }

    .jb-orders-filters__title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-orders-filters__hint {
        margin: 0.25rem 0 0;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: rgb(100 116 139);
    }

    .jb-orders-filters__toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    .jb-orders-filters__body {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .jb-orders-filters__search {
        max-width: 18rem;
    }

    .jb-orders-filters__grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .jb-orders-filters__grid .jb-filters-field {
        min-width: 0;
        width: 100%;
        flex: none;
    }

    .jb-orders-filters__footer {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .jb-orders-filters__dates {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 0.75rem;
        flex: 1 1 auto;
    }

    .jb-orders-filters__dates .jb-filters-field--date {
        min-width: 9.5rem;
        flex: 0 1 9.5rem;
    }

    .jb-orders-filters__footer .jb-filters-actions {
        margin-left: 0;
    }

    .jb-orders-filters__active {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgb(241 245 249);
    }

    .jb-orders-filters__active-label {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgb(148 163 184);
    }

    .jb-orders-filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.2;
        color: rgb(190 18 60);
        background: rgb(255 241 242);
        border: 1px solid rgb(254 205 211);
    }

    .jb-orders-card__header {
        align-items: flex-start;
    }

    .jb-orders-card__subtitle {
        margin: 0.2rem 0 0;
        font-size: 0.8125rem;
        color: rgb(100 116 139);
    }

    .jb-orders-table tbody tr:hover {
        background: rgb(248 250 252);
    }

    .jb-orders-id {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.75rem;
        font-weight: 700;
        color: rgb(15 23 42);
        letter-spacing: 0.02em;
    }

    .jb-orders-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(30 41 59);
    }

    .jb-orders-category {
        display: inline-block;
        max-width: 9rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding: 0.25rem 0.55rem;
        border-radius: 0.375rem;
        font-size: 0.6875rem;
        font-weight: 600;
        color: rgb(67 56 202);
        background: rgb(238 242 255);
        vertical-align: middle;
    }

    .jb-orders-type {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .jb-orders-amount {
        font-variant-numeric: tabular-nums;
        font-size: 0.875rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-orders-date {
        font-size: 0.8125rem;
        color: rgb(100 116 139);
    }

    @media (max-width: 1023px) {
        .jb-orders-filters__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 639px) {
        .jb-orders-filters {
            padding: 1rem;
        }

        .jb-orders-filters__search {
            max-width: none;
        }

        .jb-orders-filters__grid {
            grid-template-columns: 1fr;
        }

        .jb-orders-filters__footer {
            flex-direction: column;
            align-items: stretch;
        }

        .jb-orders-filters__footer .jb-filters-actions-btns {
            width: 100%;
        }

        .jb-orders-filters__footer .jb-filters-actions .jb-btn {
            flex: 1 1 auto;
        }
    }

    .jb-col-check {
        width: 2.75rem;
        text-align: center;
        vertical-align: middle;
    }

    .jb-bulk-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .jb-bulk-actions-count {
        font-size: 0.82rem;
        font-weight: 600;
        color: rgb(100 116 139);
    }

    /* Banner editor live preview */
    .jb-banner-editor {
        display: grid;
        gap: 1.25rem;
        align-items: start;
    }

    @media (min-width: 1100px) {
        .jb-banner-editor {
            grid-template-columns: minmax(0, 1fr) minmax(320px, 400px);
        }

        .jb-banner-preview-panel {
            position: sticky;
            top: 1rem;
        }
    }

    .jb-banner-preview-panel {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgb(15 23 42 / 0.06);
    }

    .jb-banner-preview-panel__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .jb-banner-preview-panel__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-banner-preview-panel__sub {
        margin: 0.25rem 0 0;
        font-size: 0.75rem;
        color: rgb(100 116 139);
    }

    .jb-banner-preview-panel__badge {
        flex-shrink: 0;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgb(180 83 9);
        background: rgb(255 237 213);
        border-radius: 999px;
        padding: 0.25rem 0.625rem;
    }

    .jb-banner-preview-tabs {
        display: flex;
        gap: 0.375rem;
        padding: 0.25rem;
        background: rgb(241 245 249);
        border-radius: 0.75rem;
        margin-bottom: 1rem;
    }

    .jb-banner-preview-tab {
        flex: 1;
        border: 0;
        background: transparent;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 700;
        color: rgb(100 116 139);
        cursor: pointer;
    }

    .jb-banner-preview-tab.is-active {
        background: #fff;
        color: rgb(15 23 42);
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.08);
    }

    .jb-banner-preview-note {
        margin: 0 0 1rem;
        padding: 0.625rem 0.75rem;
        border-radius: 0.625rem;
        background: rgb(254 243 199);
        color: rgb(146 64 14);
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .jb-banner-preview-stage__label {
        margin: 0 0 0.625rem;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgb(100 116 139);
    }

    .jb-banner-preview-web {
        position: relative;
        overflow: hidden;
        border-radius: 0.875rem;
        min-height: 220px;
        background: #111;
    }

    .jb-banner-preview-web__slide {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
    }

    .jb-banner-preview-web__slide--empty {
        background: linear-gradient(135deg, rgb(51 65 85), rgb(30 41 59));
    }

    .jb-banner-preview-web__overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(105deg, rgb(0 0 0 / 0.72) 0%, rgb(0 0 0 / 0.35) 55%, rgb(0 0 0 / 0.12) 100%);
    }

    .jb-banner-preview-web__content {
        position: relative;
        z-index: 1;
        padding: 1.5rem 1.25rem;
        color: #fff;
        max-width: 85%;
    }

    .jb-banner-preview-web__kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 0 0.625rem;
        font-size: 0.5625rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #f25123;
    }

    .jb-banner-preview-web__kicker::before {
        content: '';
        width: 1.25rem;
        height: 2px;
        background: #f25123;
    }

    .jb-banner-preview-web__title {
        margin: 0 0 0.5rem;
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1.5rem;
        font-weight: 600;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }

    .jb-banner-preview-web-frame {
        border: 1px solid rgb(226 232 240);
        border-radius: 0.875rem;
        overflow: hidden;
        background: #fff;
    }

    .jb-banner-preview-web-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid rgb(241 245 249);
        background: #fff;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    }

    .jb-banner-preview-web-logo {
        font-size: 0.6875rem;
        font-weight: 800;
        color: #1a2f38;
    }

    .jb-banner-preview-web-nav {
        font-size: 0.5625rem;
        font-weight: 600;
        color: rgb(100 116 139);
    }

    .jb-banner-preview-web-frame .jb-banner-preview-web {
        border-radius: 0;
        min-height: 200px;
    }

    .jb-banner-preview-web__text {
        margin: 0 0 0.875rem;
        font-size: 0.75rem;
        line-height: 1.55;
        color: rgb(255 255 255 / 0.78);
    }

    .jb-banner-preview-web__cta {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 999px;
        background: #f25123;
        color: #fff;
        font-size: 0.6875rem;
        font-weight: 800;
    }

    .jb-banner-preview-app {
        display: flex;
        justify-content: center;
    }

    .jb-banner-preview-app__device {
        width: 100%;
        max-width: 280px;
        border-radius: 1.75rem;
        border: 3px solid rgb(15 23 42);
        background: rgb(15 23 42);
        padding: 0.5rem 0.5rem 0.75rem;
        box-shadow: 0 12px 32px rgb(15 23 42 / 0.18);
    }

    .jb-banner-preview-app__notch {
        width: 5rem;
        height: 0.375rem;
        margin: 0.25rem auto 0.5rem;
        border-radius: 999px;
        background: rgb(30 41 59);
    }

    .jb-banner-preview-app__screen {
        border-radius: 1.25rem;
        background: #f8f7f5;
        overflow: hidden;
        padding: 0.625rem 0.75rem 0.875rem;
    }

    .jb-banner-preview-app__topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.5625rem;
        font-weight: 700;
        color: rgb(100 116 139);
        margin-bottom: 0.5rem;
    }

    .jb-banner-preview-app__topbar-title {
        color: rgb(15 23 42);
        font-size: 0.625rem;
    }

    .jb-banner-preview-app__location {
        font-size: 0.6875rem;
        font-weight: 700;
        color: rgb(15 23 42);
        margin-bottom: 0.625rem;
    }

    .jb-banner-preview-app__banner {
        position: relative;
        overflow: hidden;
        border-radius: 0.875rem;
        min-height: 130px;
        background: rgb(226 232 240);
    }

    .jb-banner-preview-app__banner-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .jb-banner-preview-app__banner-img--empty {
        background: linear-gradient(135deg, rgb(148 163 184), rgb(100 116 139));
    }

    .jb-banner-preview-app__banner-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgb(0 0 0 / 0.05) 0%, rgb(0 0 0 / 0.65) 100%);
    }

    .jb-banner-preview-app__banner-body {
        position: relative;
        z-index: 1;
        padding: 0.75rem;
        margin-top: 3.5rem;
        color: #fff;
    }

    .jb-banner-preview-app__banner-title {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .jb-banner-preview-app__banner-sub {
        margin: 0.25rem 0 0.5rem;
        font-size: 0.625rem;
        line-height: 1.4;
        color: rgb(255 255 255 / 0.85);
    }

    .jb-banner-preview-app__banner-cta {
        display: inline-flex;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        background: #f25123;
        color: #fff;
        font-size: 0.5625rem;
        font-weight: 800;
    }

    .jb-banner-preview-app__dots {
        display: flex;
        justify-content: center;
        gap: 0.3rem;
        margin: 0.625rem 0;
    }

    .jb-banner-preview-app__dots span {
        width: 0.375rem;
        height: 0.375rem;
        border-radius: 999px;
        background: rgb(203 213 225);
    }

    .jb-banner-preview-app__dots span.is-active {
        width: 1rem;
        background: #f25123;
    }

    .jb-banner-preview-app__section-title {
        font-size: 0.6875rem;
        font-weight: 800;
        color: rgb(15 23 42);
        margin-bottom: 0.5rem;
    }

    .jb-banner-preview-app__tiles {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.375rem;
    }

    .jb-banner-preview-app__tiles span {
        height: 2.5rem;
        border-radius: 0.5rem;
        background: rgb(226 232 240);
    }

    .jb-banner-preview-meta {
        display: grid;
        gap: 0.5rem;
        margin: 1rem 0 0;
        padding-top: 1rem;
        border-top: 1px solid rgb(241 245 249);
    }

    .jb-banner-preview-meta div {
        display: grid;
        gap: 0.15rem;
    }

    .jb-banner-preview-meta dt {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: rgb(100 116 139);
    }

    .jb-banner-preview-meta dd {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgb(15 23 42);
        overflow-wrap: anywhere;
    }

    .jb-banner-preview-vp-frame {
        display: flex;
        border: 1px solid #e6eaee;
        border-radius: 14px;
        overflow: hidden;
        min-height: 220px;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        background: #f3f5f7;
    }

    .jb-banner-preview-vp-sidebar {
        width: 4.5rem;
        flex-shrink: 0;
        background: #fff;
        border-right: 1px solid #e6eaee;
    }

    .jb-banner-preview-vp-main {
        flex: 1;
        padding: 0.75rem;
        min-width: 0;
    }

    .jb-banner-preview-vp-head {
        font-size: 0.6875rem;
        font-weight: 700;
        color: #152536;
        margin-bottom: 0.625rem;
    }

    .jb-banner-preview-vp-promo {
        display: flex;
        gap: 0.75rem;
        align-items: stretch;
        padding: 0.75rem;
        border-radius: 14px;
        background: linear-gradient(135deg, #fff4ef, #fff);
        border: 1px solid #ffd8c8;
        text-decoration: none;
        color: inherit;
        margin-bottom: 0.75rem;
    }

    .jb-banner-preview-vp-promo__img {
        width: 5.5rem;
        height: 4.25rem;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        background: #e6eaee;
    }

    .jb-banner-preview-vp-promo__img--empty {
        background: linear-gradient(135deg, #ffd8c8, #fff4ef);
    }

    .jb-banner-preview-vp-promo__body {
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .jb-banner-preview-vp-promo__title {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 800;
        color: #152536;
        line-height: 1.25;
    }

    .jb-banner-preview-vp-promo__sub {
        margin: 0.25rem 0 0;
        font-size: 0.6875rem;
        color: #6b7c8f;
        line-height: 1.4;
    }

    .jb-banner-preview-vp-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.375rem;
    }

    .jb-banner-preview-vp-stats span {
        height: 2.75rem;
        border-radius: 10px;
        background: #fff;
        border: 1px solid #e6eaee;
    }

    .jb-banner-preview-app__topbar--vendor .jb-banner-preview-app__topbar-title,
    .jb-banner-preview-app--vendor .jb-banner-preview-app__screen {
        background: #fff;
    }

    .jb-banner-preview-app__vendor-strip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.625rem;
        font-weight: 600;
        color: #6b7c8f;
        margin-bottom: 0.5rem;
    }

    .jb-banner-preview-app__vendor-strip strong {
        color: #f25123;
        font-size: 0.6875rem;
    }

    .jb-banner-preview-app__banner--vendor {
        min-height: 118px;
    }

    .jb-banner-preview-app__tiles--2 {
        grid-template-columns: repeat(2, 1fr);
    }

    .jb-banner-preview-app__screen--driver {
        background: #f0f4f8;
    }

    .jb-banner-preview-app__topbar--driver .jb-banner-preview-app__topbar-title {
        color: #1e3a5f;
    }

    .jb-banner-preview-app__driver-status {
        font-size: 0.625rem;
        font-weight: 700;
        color: #15803d;
        background: #dcfce7;
        border-radius: 999px;
        padding: 0.25rem 0.5rem;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .jb-banner-preview-app__banner--driver {
        min-height: 112px;
        border: 1px solid #cbd5e1;
    }

    .jb-banner-preview-app__banner--driver .jb-banner-preview-app__banner-overlay {
        background: linear-gradient(180deg, rgb(0 0 0 / 0.08) 0%, rgb(30 58 95 / 0.72) 100%);
    }

    .jb-banner-preview-app__driver-list {
        display: grid;
        gap: 0.375rem;
    }

    .jb-banner-preview-app__driver-list span {
        height: 2.25rem;
        border-radius: 10px;
        background: #fff;
        border: 1px solid #dbe4ee;
    }

    /* Customer app home mockup (banner preview) */
    .jb-mock-phone {
        display: flex;
        justify-content: center;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    }

    .jb-mock-phone__shell {
        width: 100%;
        max-width: 300px;
        border-radius: 2rem;
        border: 3px solid #1a1a2e;
        background: #fff;
        box-shadow: 0 16px 40px rgb(15 23 42 / 0.16);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 580px;
    }

    .jb-mock-phone__status {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.45rem 1rem 0.2rem;
        font-size: 0.6875rem;
        font-weight: 700;
        color: #1a1a2e;
    }

    .jb-mock-phone__status-icons {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        color: #1a1a2e;
    }

    .jb-mock-home__body {
        flex: 1;
        overflow: hidden;
        padding: 0 0.875rem 0.5rem;
        background: #fff;
    }

    .jb-mock-home__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .jb-mock-home__location {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        min-width: 0;
    }

    .jb-mock-home__loc-pin {
        display: inline-flex;
        color: #f25123;
        margin-top: 0.1rem;
        flex-shrink: 0;
    }

    .jb-mock-home__loc-text {
        min-width: 0;
    }

    .jb-mock-home__loc-title {
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.15rem;
        font-size: 0.8125rem;
        font-weight: 800;
        color: #1a1a2e;
        line-height: 1.2;
    }

    .jb-mock-home__loc-addr {
        margin: 0.1rem 0 0;
        font-size: 0.5625rem;
        font-weight: 600;
        color: #717585;
        line-height: 1.35;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 11.5rem;
    }

    .jb-mock-home__bell {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 0;
        border-radius: 999px;
        background: #f8f7f5;
        color: #1a1a2e;
        cursor: default;
    }

    .jb-mock-home__search {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 0.875rem;
        margin-bottom: 0.875rem;
        border-radius: 999px;
        background: #f8f7f5;
        border: 1px solid #e8e6e1;
        color: #717585;
        font-size: 0.625rem;
        font-weight: 600;
    }

    .jb-mock-home__search svg {
        flex-shrink: 0;
        color: #717585;
    }

    .jb-mock-home__carousel {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        aspect-ratio: 16 / 9;
        background: #f5efe6;
        box-shadow: 0 2px 12px rgb(0 0 0 / 0.06);
    }

    .jb-mock-home__banner-img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .jb-mock-home__banner-empty {
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
        padding: 1rem 1.125rem;
        background: linear-gradient(135deg, #faf6f0 0%, #f0e8dc 100%);
        text-align: left;
    }

    .jb-mock-home__banner-empty-title {
        margin: 0;
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.15;
        color: #1a1a2e;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .jb-mock-home__banner-empty-sub {
        margin: 0.4rem 0 0;
        font-size: 0.5625rem;
        font-weight: 600;
        line-height: 1.45;
        color: #717585;
    }

    .jb-mock-home__dots {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.35rem;
        margin: 0.625rem 0 0.875rem;
    }

    .jb-mock-home__dots span {
        width: 0.375rem;
        height: 0.375rem;
        border-radius: 999px;
        background: #e8e6e1;
    }

    .jb-mock-home__dots span.is-active {
        width: 1.125rem;
        background: #f25123;
    }

    .jb-mock-home__section {
        margin: 0 0 0.5rem;
        font-size: 0.8125rem;
        font-weight: 800;
        color: #1a1a2e;
        letter-spacing: -0.01em;
    }

    .jb-mock-home__services {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-bottom: 0.875rem;
    }

    .jb-mock-home__service {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 0;
    }

    .jb-mock-home__service img {
        width: 100%;
        aspect-ratio: 1;
        border-radius: 0.75rem;
        object-fit: cover;
        background: #f1f5f9;
    }

    .jb-mock-home__service span {
        font-size: 0.5rem;
        font-weight: 700;
        line-height: 1.3;
        color: #1a1a2e;
        text-align: center;
    }

    .jb-mock-home__categories {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        padding-bottom: 0.5rem;
    }

    .jb-mock-home__category {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
    }

    .jb-mock-home__category img {
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 999px;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
    }

    .jb-mock-home__category span {
        font-size: 0.5625rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        color: #1a1a2e;
        text-transform: uppercase;
    }

    .jb-mock-home__nav {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.25rem;
        padding: 0.5rem 0.75rem 0.75rem;
        border-top: 1px solid #e8e6e1;
        background: #fff;
    }

    .jb-mock-home__nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
        font-size: 0.5625rem;
        font-weight: 700;
        color: #717585;
    }

    .jb-mock-home__nav-item svg {
        color: #717585;
    }

    .jb-mock-home__nav-item.is-active,
    .jb-mock-home__nav-item.is-active svg {
        color: #f25123;
    }

    /* Dispute admin–customer chat */
    .jb-dispute-layout {
        display: grid;
        gap: 1.25rem;
        align-items: start;
    }

    @media (min-width: 1024px) {
        .jb-dispute-layout {
            grid-template-columns: minmax(0, 340px) minmax(0, 1fr);
        }
    }

    .jb-dispute-chat {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgb(15 23 42 / 0.06);
    }

    .jb-dispute-chat__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgb(241 245 249);
        background: rgb(248 250 252);
    }

    .jb-dispute-chat__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-dispute-chat__sub {
        margin: 0.25rem 0 0;
        font-size: 0.75rem;
        color: rgb(100 116 139);
    }

    .jb-dispute-chat__badge {
        flex-shrink: 0;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-radius: 999px;
        padding: 0.25rem 0.625rem;
    }

    .jb-dispute-chat__badge--open {
        color: rgb(21 128 61);
        background: rgb(220 252 231);
    }

    .jb-dispute-chat__badge--closed {
        color: rgb(100 116 139);
        background: rgb(241 245 249);
    }

    .jb-dispute-chat__thread {
        min-height: 280px;
        max-height: 420px;
        overflow-y: auto;
        padding: 1rem 1.25rem;
        background: #fafbfc;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .jb-dispute-chat__msg {
        max-width: 88%;
        padding: 0.75rem 0.875rem;
        border-radius: 0.875rem;
        background: #fff;
        border: 1px solid rgb(226 232 240);
    }

    .jb-dispute-chat__msg--admin {
        align-self: flex-end;
        background: rgb(255 244 240);
        border-color: rgb(255 216 200);
    }

    .jb-dispute-chat__msg--customer {
        align-self: flex-start;
    }

    .jb-dispute-chat__msg-meta {
        margin: 0 0 0.35rem;
        font-size: 0.6875rem;
        font-weight: 700;
        color: rgb(100 116 139);
    }

    .jb-dispute-chat__msg-body {
        margin: 0;
        font-size: 0.875rem;
        line-height: 1.5;
        color: rgb(15 23 42);
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .jb-dispute-chat__attachment img,
    .jb-dispute-chat__attachment video {
        display: block;
        max-width: 180px;
        margin-top: 0.5rem;
        border-radius: 0.5rem;
        border: 1px solid rgb(226 232 240);
    }

    .jb-dispute-chat__attachment video {
        background: #000;
    }

    .jb-dispute-chat__empty {
        margin: auto 0;
        text-align: center;
        font-size: 0.8125rem;
        color: rgb(100 116 139);
        padding: 2rem 1rem;
    }

    .jb-dispute-chat__compose,
    .jb-dispute-chat__resolve,
    .jb-dispute-chat__closed-note {
        padding: 1rem 1.25rem;
        border-top: 1px solid rgb(241 245 249);
    }

    .jb-dispute-chat__compose-actions,
    .jb-dispute-chat__resolve-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.75rem;
    }

    .jb-dispute-chat__close-form {
        margin-top: 0.75rem;
    }

    .jb-dispute-chat__closed-note p {
        margin: 0;
        font-size: 0.8125rem;
        color: rgb(71 85 105);
        line-height: 1.5;
    }

    .jb-dispute-chat__closed-note p + p {
        margin-top: 0.5rem;
    }

    .jb-dispute-chat__resolution-note span {
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-multi-select {
        position: relative;
    }

    .jb-multi-select-trigger {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid rgb(226 232 240);
        background: #fff;
        padding: 0.625rem 0.875rem;
        text-align: left;
        font-size: 0.875rem;
        color: rgb(15 23 42);
        box-shadow: 0 1px 2px 0 rgb(15 23 42 / 0.05);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .jb-multi-select-trigger:hover {
        border-color: rgb(203 213 225);
    }

    .jb-multi-select-trigger.is-open,
    .jb-multi-select-trigger:focus {
        border-color: color-mix(in srgb, var(--jb-primary, #be123c) 40%, #e2e8f0);
        outline: none;
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--jb-primary, #be123c) 12%, transparent);
    }

    .jb-multi-select-trigger-text {
        min-width: 0;
        flex: 1 1 auto;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .jb-multi-select-chevron {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
        color: rgb(148 163 184);
        transition: transform 0.15s ease;
    }

    .jb-multi-select-trigger.is-open .jb-multi-select-chevron {
        transform: rotate(180deg);
    }

    .jb-multi-select-menu {
        position: absolute;
        z-index: 30;
        margin-top: 0.25rem;
        max-height: 14rem;
        width: 100%;
        overflow-y: auto;
        border-radius: 0.75rem;
        border: 1px solid rgb(226 232 240);
        background: #fff;
        padding: 0.5rem;
        box-shadow: 0 10px 25px -12px rgb(15 23 42 / 0.25);
    }

    .jb-multi-select-option {
        display: flex;
        cursor: pointer;
        align-items: center;
        gap: 0.625rem;
        border-radius: 0.5rem;
        padding: 0.5rem 0.625rem;
        font-size: 0.875rem;
        color: rgb(51 65 85);
    }

    .jb-multi-select-option:hover {
        background: rgb(248 250 252);
    }

    .jb-multi-select-option input {
        width: 1rem;
        height: 1rem;
        border-radius: 0.25rem;
        accent-color: var(--jb-primary, #be123c);
    }

    .jb-multi-select-empty {
        padding: 0.5rem 0.625rem;
        font-size: 0.875rem;
        color: rgb(100 116 139);
    }

    .jb-location-picker .jb-select:disabled {
        background: rgb(248 250 252);
        color: rgb(148 163 184);
        cursor: not-allowed;
    }

    /* Payout detail page */
    .jb-payout-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem 1.5rem;
        margin-bottom: 1.25rem;
    }

    .jb-payout-code {
        font-size: 1.25rem;
        font-weight: 700;
        color: rgb(15 23 42);
        letter-spacing: -0.02em;
    }

    .jb-payout-header-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.375rem;
    }

    .jb-payout-created {
        font-size: 0.8125rem;
        color: rgb(100 116 139);
        white-space: nowrap;
    }

    .jb-payout-amount-card {
        background: linear-gradient(135deg, rgb(255 251 235) 0%, rgb(254 243 199 / 0.45) 100%);
        border: 1px solid rgb(253 230 138);
        border-radius: 0.875rem;
        padding: 1.25rem 1.375rem;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
    }

    .jb-payout-amount-card--paid {
        background: linear-gradient(135deg, rgb(236 253 245) 0%, rgb(209 250 229 / 0.5) 100%);
        border-color: rgb(167 243 208);
    }

    .jb-payout-amount-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgb(146 64 14);
    }

    .jb-payout-amount-card--paid .jb-payout-amount-label {
        color: rgb(4 120 87);
    }

    .jb-payout-amount-value {
        margin-top: 0.25rem;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: rgb(120 53 15);
        line-height: 1.1;
    }

    .jb-payout-amount-card--paid .jb-payout-amount-value {
        color: rgb(6 95 70);
    }

    .jb-payout-breakdown {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 0.75rem 1rem;
        margin-top: 1.25rem;
        padding-top: 1.125rem;
        border-top: 1px solid rgb(0 0 0 / 0.06);
    }

    .jb-payout-breakdown-item {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
    }

    .jb-payout-breakdown-item--total .jb-payout-breakdown-value {
        font-weight: 800;
        color: rgb(15 23 42);
    }

    .jb-payout-breakdown-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: rgb(100 116 139);
    }

    .jb-payout-breakdown-muted {
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
    }

    .jb-payout-breakdown-value {
        font-size: 0.9375rem;
        font-weight: 700;
        color: rgb(51 65 85);
    }

    .jb-payout-breakdown-value--deduct {
        color: rgb(190 18 60);
    }

    .jb-payout-breakdown-op {
        font-size: 1.125rem;
        font-weight: 600;
        color: rgb(148 163 184);
        padding-bottom: 0.125rem;
    }

    .jb-payout-paid-card {
        background: linear-gradient(160deg, rgb(236 253 245) 0%, rgb(255 255 255) 55%);
        border: 1px solid rgb(167 243 208);
        border-radius: 0.875rem;
        padding: 1.375rem 1.25rem;
        text-align: center;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
    }

    .jb-payout-paid-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        margin: 0 auto 0.75rem;
        border-radius: 9999px;
        background: rgb(209 250 229);
        color: rgb(5 150 105);
    }

    .jb-payout-paid-title {
        font-size: 1.0625rem;
        font-weight: 700;
        color: rgb(6 95 70);
    }

    .jb-payout-paid-text {
        margin-top: 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        color: rgb(51 65 85);
    }

    .jb-payout-paid-meta,
    .jb-payout-paid-ref {
        margin-top: 0.75rem;
        font-size: 0.8125rem;
        color: rgb(100 116 139);
    }

    .jb-payout-paid-ref span {
        font-weight: 600;
        color: rgb(51 65 85);
        word-break: break-all;
    }

    .jb-payout-record-card {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.875rem;
        overflow: hidden;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
    }

    .jb-payout-record-head {
        padding: 1.125rem 1.25rem;
        background: linear-gradient(180deg, rgb(248 250 252) 0%, rgb(255 255 255) 100%);
        border-bottom: 1px solid rgb(226 232 240);
    }

    .jb-payout-record-title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .jb-payout-record-sub {
        margin-top: 0.25rem;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: rgb(100 116 139);
    }

    .jb-payout-record-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.125rem 1.25rem 1.25rem;
    }

    .jb-payout-record-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border-radius: 0.625rem;
        background: rgb(255 251 235);
        border: 1px solid rgb(253 230 138);
        font-size: 0.8125rem;
        color: rgb(120 53 15);
    }

    .jb-payout-record-summary strong {
        font-size: 1.0625rem;
        font-weight: 800;
        color: rgb(146 64 14);
    }

    @media (max-width: 639px) {
        .jb-payout-breakdown {
            flex-direction: column;
            align-items: stretch;
        }

        .jb-payout-breakdown-op {
            display: none;
        }

        .jb-payout-breakdown-item--total {
            padding-top: 0.5rem;
            border-top: 1px dashed rgb(0 0 0 / 0.08);
        }
    }
</style>
