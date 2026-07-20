<style>
/* ─── E-commerce visual refresh (2026) ───────────────────────────── */
:root {
    --c-primary: #e85d3a;
    --c-primary-dk: #c94a2a;
    --c-primary-soft: #fff5f1;
    --c-accent: #1a2f38;
    --c-bg: #f6f4f1;
    --c-surface: #ffffff;
    --c-text: #1c1c28;
    --c-muted: #6b7280;
    --c-border: #ebe8e3;
    --c-success: #059669;
    --r-card: 20px;
    --r-btn: 12px;
    --r-pill: 999px;
    --shadow-card: 0 2px 8px rgb(28 28 40 / 0.04), 0 12px 32px rgb(28 28 40 / 0.06);
    --shadow-hover: 0 8px 30px rgb(28 28 40 / 0.12);
    --jbw-primary: var(--c-primary);
    --jbw-primary-dark: var(--c-primary-dk);
    --jbw-primary-soft: var(--c-primary-soft);
    --jbw-border: var(--c-border);
    --jbw-page-bg: var(--c-bg);
}

body {
    background: var(--c-bg);
    color: var(--c-text);
    font-size: 1rem;
    line-height: 1.65;
}

.jbw-main {
    overflow-x: clip;
    padding-bottom: 0;
}

/* overflow-x: clip breaks sticky and cuts the profile sidebar */
.jbw-main--profile {
    overflow: visible;
}

/* Header */
.jbw-header {
    background: rgb(255 255 255 / 0.96);
    border-bottom: 1px solid var(--c-border);
    box-shadow: 0 4px 24px rgb(17 24 39 / 0.04);
}

.jbw-header-inner {
    min-height: 4.25rem;
    height: auto;
}

.jbw-nav-link {
    font-weight: 600;
    font-size: 0.9rem;
    color: #4b5563;
    padding: 0.45rem 0.75rem;
    /* border-radius: var(--r-pill); */
    transition: color 0.2s, background 0.2s;
}

.jbw-nav-link:hover,
.jbw-nav-link.is-active {
    color: var(--c-primary);
    background: var(--c-primary-soft);
}

.jbw-icon-btn {
    border-radius: var(--r-pill);
    transition: background 0.2s, transform 0.15s;
}

.jbw-icon-btn:hover {
    background: var(--c-primary-soft);
}

/* Buttons */
.jbw-btn {
    border-radius: var(--r-btn);
    font-weight: 700;
    letter-spacing: 0.01em;
    transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s;
}

.jbw-btn:active { transform: scale(0.98); }

.jbw-btn--primary {
    background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-dk) 100%);
    border: none;
    box-shadow: 0 4px 14px rgb(232 93 58 / 0.35);
}

.jbw-btn--primary:hover {
    background: linear-gradient(135deg, var(--c-primary-dk) 0%, #a83d22 100%);
    box-shadow: 0 8px 22px rgb(232 93 58 / 0.4);
}

.jbw-btn--outline {
    border: 1.5px solid var(--c-border);
    background: #fff;
}

.jbw-btn--outline:hover {
    border-color: var(--c-primary);
    color: var(--c-primary);
    background: var(--c-primary-soft);
}

.jbw-btn--lg {
    padding: 0.9rem 1.75rem;
    font-size: 0.9375rem;
}

.lookbutton {
    border-radius: var(--r-btn) !important;
    padding: 0.85rem 1.5rem !important;
    font-size: 0.875rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.06em;
    background: linear-gradient(135deg, var(--c-primary), var(--c-primary-dk)) !important;
}

.borderbanner {
    border-radius: 0 !important;
    margin-bottom: 0 !important;
}

/* Hero */
.jbw-hero {
    height: clamp(460px, 62vh, 620px);
    min-height: 460px;
    margin-bottom: 0;
}

.jbw-hero-overlay {
    background: linear-gradient(
        105deg,
        rgb(26 47 56 / 0.82) 0%,
        rgb(26 47 56 / 0.45) 45%,
        rgb(26 47 56 / 0.15) 100%
    );
}

.jbw-hero-content {
    max-width: 36rem;
    padding: 2rem;
    background: rgb(255 255 255 / 0.08);
    backdrop-filter: blur(8px);
    border-radius: var(--r-card);
    border: 1px solid rgb(255 255 255 / 0.12);
}

.jbw-hero-title {
    font-family: var(--font-serif);
    font-size: clamp(2rem, 4.5vw, 3.25rem);
    font-weight: 600;
    line-height: 1.12;
    margin: 0 0 1rem;
    color: #fff;
    text-shadow: 0 2px 20px rgb(0 0 0 / 0.2);
}

.jbw-hero-text {
    font-size: 1.0625rem;
    line-height: 1.7;
    color: rgb(255 255 255 / 0.92);
    margin-bottom: 1.5rem;
}

.jbw-hero-arrow {
    background: rgb(255 255 255 / 0.95);
    transition: transform 0.2s, box-shadow 0.2s;
}

.jbw-hero-arrow:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: var(--shadow-hover);
}

/* Trust strip */
.jbw-trust-strip {
    background: var(--c-surface);
    border-bottom: 1px solid var(--c-border);
    padding: 0.65rem 0;
    margin-bottom: 0.25rem;
}

.jbw-trust-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (min-width: 768px) {
    .jbw-trust-grid { grid-template-columns: repeat(4, 1fr); }
}

.jbw-trust-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
}

.jbw-trust-icon {
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: var(--c-primary-soft);
    color: var(--c-primary);
}

.jbw-trust-label {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 700;
    line-height: 1.3;
    color: var(--c-text);
}

.jbw-trust-sub {
    margin: 0.1rem 0 0;
    font-size: 0.75rem;
    color: var(--c-muted);
}

/* Sections — tighter vertical rhythm */
.jbw-section-band {
    padding: 1.00rem 0 1.000rem;
}

.jbw-section-band + .jbw-section-band {
    padding-top: 0.5rem;
}

.jbw-section-band--warm {
    padding: 1.5rem 0 0.5000rem;
}

.jbw-section-band--compact {
    padding-top: 0.25rem;
    padding-bottom: 1.25rem;
}

.jbw-section-head {
    margin-bottom: 1rem;
}

.designers-header {
    margin-bottom: 0.75rem !important;
}

.jbw-section-title {
    font-family: var(--font-serif);
    font-size: clamp(1.5rem, 2.5vw, 2rem);
    font-weight: 600;
    margin: 0;
    color: var(--c-text);
}

.jbw-eyebrow {
    display: inline-block;
    font-size: 0.7875rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--c-primary);
    margin-bottom: 0.25rem;
}

.designer-arrow {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--r-pill);
    border: 1px solid var(--c-border);
    background: #fff;
    color: var(--c-text);
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: var(--shadow-card);
}

.designer-arrow:hover {
    border-color: var(--c-primary);
    color: var(--c-primary);
    background: var(--c-primary-soft);
}

/* Service & category tiles */
.service-card,
.category-card {
    text-decoration: none;
    color: inherit;
    transition: transform 0.25s ease;
}

.service-card:hover,
.category-card:hover {
    transform: translateY(-4px);
}

.jbw-tile {
    border-radius: var(--r-card);
    overflow: hidden;
    box-shadow: var(--shadow-card);
    border: 1px solid var(--c-border);
    background: #fff;
}

.jbw-tile img {
    transition: transform 0.4s ease;
}

.service-card:hover .jbw-tile img,
.category-card:hover .jbw-tile img {
    transform: scale(1.05);
}

.service-slider,
.category-slider {
    gap: 1rem;
}

.category-card .jbw-step-title,
.service-card .jbw-step-title {
    margin-top: 0.5rem;
}

.jbw-step-title {
    font-weight: 700;
    font-size: 0.9375rem;
    margin-top: 0.5rem;
    margin-bottom: 0;
    color: var(--c-text);
}

/* Product grid — e-commerce cards */
.jbw-product-grid {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.0rem;
}

.jbw-product-card {
    border-radius: var(--r-card);
    border: 1px solid var(--c-border);
    background: #fff;
    box-shadow: var(--shadow-card);
    display: flex;
    flex-direction: column;
}

.jbw-product-card:hover {
    /* transform: translateY(-6px); */
     transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
    border-color: rgb(232 93 58 / 0.2);
}

.jbw-product-card-img {
    position: relative;
    overflow: hidden;
    background: linear-gradient(145deg, #f3f0eb, #e8e4dd);
}

.jbw-product-card-img::after {
    content: 'Rent';
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.25rem 0.65rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #fff;
    background: rgb(26 47 56 / 0.75);
    backdrop-filter: blur(4px);
    border-radius: var(--r-pill);
}

.jbw-product-card-body {
    padding: 0.55rem 1rem 0.55rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
}

.jbw-product-brand {
    font-size: 0.6875rem;
    color: var(--c-muted);
}

.jbw-product-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--c-text);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.jbw-product-price {
    margin-top: auto;
    /* padding-top: 0.5rem; */
     padding-top: 0.0rem;
    font-size: 1rem;
    font-weight: 800;
    color: var(--c-primary);
}

.rating-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #374151;
    background: #fef9c3;
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
}

/* Page shells */
.jbw-page-shell {
    padding: 0.500rem 0 1rem;
}

.shell-manage {
   display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;
   margin-bottom: 0.000rem !important;

}

.jbw-page-head {
    margin-bottom: 1.05rem;
    padding-top: 0.000rem;
}

.jbw-page-title {
    font-family: var(--font-serif);
    font-size: clamp(1.75rem, 3vw, 2.00rem);
}

.jbw-overview-card,
.jbw-booking-card {
    border-radius: var(--r-card);
    border: 1px solid var(--c-border);
    box-shadow: var(--shadow-card);
    background: #fff;
}

.jbw-overview-card--accent {
    border-color: rgb(232 93 58 / 0.15);
    background: linear-gradient(180deg, #fff 0%, var(--c-primary-soft) 100%);
}

/* Cart */
.jbw-cart-layout {
    display: grid;
    gap: 1.5rem;
    align-items: start;
}

@media (min-width: 900px) {
    .jbw-cart-layout {
        grid-template-columns: 1fr 360px;
    }
}

.jbw-cart-vendor {
    background: #fff;
    border: 1px solid var(--c-border);
    border-radius: var(--r-card);
    box-shadow: var(--shadow-card);
    padding: 1.25rem;
    margin-bottom: 1rem;
}

.jbw-cart-vendor-head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--c-border);
}

.jbw-cart-vendor-name {
    margin: 0;
    font-weight: 800;
    font-size: 0.9375rem;
    flex: 1;
}

.jbw-cart-vendor-count {
    font-size: 0.75rem;
    font-weight: 600;
    color: #fff;
    background: var(--c-primary);
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
}

.jbw-cart-summary-note {
    margin: 0.75rem 0 0;
    font-size: 0.80rem;
    color: var(--c-muted);
    line-height: 1.45;
}

/* Shared line item rows (cart, checkout, payment, orders) */
.jbw-line-item-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.jbw-line-item {
    display: grid;
    grid-template-columns: 88px 1fr auto;
    gap: 1rem;
    align-items: start;
    padding: 1rem 0;
    border-bottom: 1px solid var(--c-border);
}

.jbw-line-item:last-child { border-bottom: none; padding-bottom: 0; }
.jbw-line-item:first-child { padding-top: 0; }

.jbw-line-item--compact {
    grid-template-columns: 72px 1fr;
    padding: 0.85rem 0;
}

.jbw-line-item--compact .jbw-line-item-actions { display: none; }

.jbw-line-item-img {
    width: 100%;
    aspect-ratio: 4/5;
    object-fit: cover;
    border-radius: 12px;
    background: #f0ede8;
    display: block;
}

.jbw-line-item--compact .jbw-line-item-img { border-radius: 10px; }

.jbw-line-item-brand {
    margin: 0 0 0.15rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--c-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.jbw-line-item-title {
    margin: 0 0 0.5rem;
    font-weight: 700;
    font-size: 0.9375rem;
    line-height: 1.35;
}

.jbw-line-item-details {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.35rem 1rem;
    margin: 0;
}

.jbw-line-item-details > div {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    min-width: 0;
}

.jbw-line-item-details dt {
    margin: 0;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--c-muted);
}

.jbw-line-item-details dd {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--c-text, #1a1a1a);
}

.jbw-line-item-total {
    color: var(--c-primary) !important;
    font-weight: 800 !important;
}

.jbw-line-item-actions {
    align-self: center;
}

.jbw-line-item-remove-form { margin: 0; }

.jbw-required {
    color: var(--c-primary);
    font-weight: 700;
}

/* Checkout */
.jbw-checkout-vendor-block {
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--c-border);
}

.jbw-checkout-vendor-block:last-child {
    padding-bottom: 0;
    margin-bottom: 0;
    border-bottom: none;
}

.jbw-checkout-vendor-name {
    margin: 0 0 0.75rem;
    font-weight: 800;
    font-size: 0.875rem;
}

.checkout-vendor-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--c-border);
}

.checkout-vendor-row:last-child { border-bottom: none; }

.checkout-vendor-delivery-hint {
    margin: 0.15rem 0 0;
    font-size: 0.8125rem;
    color: var(--c-muted);
}

.checkout-shipment-label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.875rem;
    white-space: nowrap;
    font-weight: 600;
}

/* Toggle switch (used for per-vendor delivery selection) */
.jbw-toggle-switch {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    cursor: pointer;
    user-select: none;
    font-size: 0.8125rem;
    font-weight: 700;
}
.jbw-toggle-switch input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
    width: 0;
    height: 0;
}
.jbw-toggle-track {
    position: relative;
    display: inline-block;
    width: 2.5rem;
    height: 1.375rem;
    background: #d4d4d0;
    border-radius: 999px;
    transition: background 0.2s ease;
    flex-shrink: 0;
}
.jbw-toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 1.125rem;
    height: 1.125rem;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);
    transition: transform 0.22s ease;
}
.jbw-toggle-switch input:checked + .jbw-toggle-track {
    background: var(--c-primary);
}
.jbw-toggle-switch input:checked + .jbw-toggle-track .jbw-toggle-thumb {
    transform: translateX(1.125rem);
}
.jbw-toggle-switch input:focus-visible + .jbw-toggle-track {
    outline: 2px solid color-mix(in srgb, var(--c-primary) 40%, transparent);
    outline-offset: 2px;
}
.jbw-toggle-label {
    color: var(--c-muted);
    letter-spacing: 0.01em;
}
.jbw-toggle-switch input:checked ~ .jbw-toggle-label {
    color: var(--c-primary);
}

.jbw-overview-help {
    margin: -0.5rem 0 1rem;
    font-size: 0.8125rem;
    color: var(--c-muted);
}

.checkout-summary-vendor {
    margin-bottom: 0.85rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid var(--c-border);
}

.checkout-summary-vendor:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.checkout-summary-vendor-name {
    margin: 0 0 0.35rem;
    font-size: 0.8125rem;
    font-weight: 700;
}

/* Payment page */
.jbw-payment-page {
     /* max-width: 1100px; */
     }

.jbw-payment-layout {
    display: grid;
    gap: 1.5rem;
    align-items: start;
}

@media (min-width: 900px) {
    .jbw-payment-layout {
        grid-template-columns: 1fr 340px;
    }
}

.jbw-payment-methods {
    display: grid;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.jbw-payment-method {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.85rem 1rem;
    border: 1.5px solid var(--c-border);
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: border-color 0.15s, background 0.15s;
}

.jbw-payment-method:has(input:checked) {
    border-color: var(--c-primary);
    background: var(--c-primary-soft, rgba(232,93,58,0.06));
}

.jbw-payment-submit {
    border-radius: 10px;
    padding: 0.9375rem;
}

.jbw-payment-secure-note {
    text-align: center;
    font-size: 0.75rem;
    color: var(--c-muted);
    margin: 0.75rem 0 0;
}

.jbw-payment-rental-dates {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--c-border);
    font-size: 0.875rem;
}

.jbw-payment-rental-dates span { color: var(--c-muted); font-weight: 600; }

.jbw-payment-sidebar { position: sticky; top: 5.5rem; }

/* ═════════════════════════════════════════════════════════════════
   ORDER DETAIL PAGE — flat layout, no nested cards
   ═════════════════════════════════════════════════════════════════ */
.jbw-order-detail {
    padding: 0;
    background: transparent;
    box-shadow: none;
    border: 0;
}

/* Hero band at the top */
.jbw-order-hero {
    position: relative;
    padding: 1.5rem 1.75rem 1.5rem;
    border-radius: 20px;
    color: #fff;
    background: linear-gradient(135deg, #1a2f38 0%, #243b47 55%, #2a4451 100%);
    overflow: hidden;
    margin-bottom: 1.25rem;
    box-shadow: 0 20px 40px rgb(26 47 56 / 0.18);
}
.jbw-order-hero::before {
    content: '';
    position: absolute;
    inset: auto -20% -60% auto;
    width: 22rem;
    height: 22rem;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(232,93,58,0.35), transparent 65%);
    pointer-events: none;
}
.jbw-order-hero--paid::after {
    content: '';
    position: absolute;
    inset: 0 0 auto 0;
    height: 3px;
    /* background: linear-gradient(90deg, #10b981, #34d399); */
}
.jbw-order-hero--pending::after {
    content: '';
    position: absolute;
    inset: 0 0 auto 0;
    height: 3px;
    background: linear-gradient(90deg, #f59e0b, var(--c-primary));
}

.jbw-order-hero-back {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem 0.4rem 0.65rem;
    background: rgb(255 255 255 / 0.12);
    color: #fff;
    border-radius: 999px;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    transition: background 0.2s ease;
    backdrop-filter: blur(6px);
}
.jbw-order-hero-back:hover { background: rgb(255 255 255 / 0.22); }

.jbw-order-hero-row {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.25rem;
    margin-top: 1.15rem;
}
.jbw-order-hero-main { min-width: 0; }

.jbw-order-hero-tag {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgb(255 255 255 / 0.75);
}

.jbw-order-hero-id {
    margin: 0.3rem 0 0;
    font-family: var(--font-serif);
    font-size: clamp(1.75rem, 3.4vw, 2.25rem);
    font-weight: 700;
    line-height: 1.05;
    letter-spacing: -0.02em;
    color: #fff;
}

.jbw-order-hero-meta {
    margin: 0.85rem 0 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.8125rem;
    color: rgb(255 255 255 / 0.75);
}
.jbw-order-hero-meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    line-height: 1.35;
}
.jbw-order-hero-meta svg { opacity: 0.75; flex-shrink: 0; }

.jbw-order-hero-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    flex-shrink: 0;
}
.jbw-order-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    line-height: 1;
    background: rgb(255 255 255 / 0.12);
    color: #fff;
    backdrop-filter: blur(6px);
}
.jbw-order-hero-badge--new { background: #dbeafe; color: #1e3a8a; }
.jbw-order-hero-badge--in_progress { background: #fed7aa; color: #9a3412; }
.jbw-order-hero-badge--delivered { background: #d1fae5; color: #065f46; }
.jbw-order-hero-badge--cancelled { background: #fee2e2; color: #991b1b; }
.jbw-order-hero-badge--default { background: rgb(255 255 255 / 0.18); color: #fff; }
.jbw-order-hero-badge--pay-paid { background: #10b981; color: #fff; }
.jbw-order-hero-badge--pay-pending { background: #f59e0b; color: #fff; }
.jbw-order-hero-badge--pay-failed { background: #ef4444; color: #fff; }

/* Pay-now banner (only when pending) */
.jbw-order-pay-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin: 0 0 1.25rem;
    padding: 1rem 1.25rem;
    border-radius: 16px;
    border: 1px solid rgb(232 93 58 / 0.28);
    background: linear-gradient(180deg, #fff6f1 0%, #fffaf7 100%);
    box-shadow: 0 8px 24px rgb(232 93 58 / 0.1);
}
.jbw-order-pay-banner-msg {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    color: var(--c-primary-dk, #c2410c);
}
.jbw-order-pay-banner p { margin: 0; font-size: 0.875rem; color: inherit; }
.jbw-order-pay-banner strong { font-weight: 800; }

/* Main + aside layout */
.jbw-order-detail-layout {
    display: grid;
    gap: 1.5rem;
    align-items: start;
}
@media (min-width: 960px) {
    .jbw-order-detail-layout {
        grid-template-columns: minmax(0, 1fr) 320px;
    }
}
.jbw-order-detail-main { min-width: 0; display: grid; gap: 1.5rem; }
.jbw-order-detail-aside { display: grid; gap: 1rem; align-self: start; }
@media (min-width: 960px) {
    .jbw-order-detail-aside {
        position: sticky;
        top: 5.75rem;
    }
}

/* Info tiles */
.jbw-order-info-grid {
    display: grid;
    gap: 0.85rem;
    margin: 0;
}
@media (min-width: 640px) {
    .jbw-order-info-grid { grid-template-columns: minmax(0, 260px) 1fr; }
}

.jbw-order-info-tile {
    padding: 1.1rem 1.2rem;
    border: 1px solid var(--c-border);
    border-radius: 16px;
    background: #fff;
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    min-width: 0;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.03);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}
.jbw-order-info-tile:hover {
    border-color: color-mix(in srgb, var(--c-primary) 30%, var(--c-border));
    box-shadow: 0 8px 24px rgb(232 93 58 / 0.08);
    transform: translateY(-1px);
}
.jbw-order-info-tile--wide { min-width: 0; flex: 1; }
.jbw-order-info-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 12px;
    background: var(--c-primary-soft, #fef3ee);
    color: var(--c-primary);
    flex-shrink: 0;
}
.jbw-order-info-body { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; flex: 1; }
.jbw-order-info-label {
    font-size: 0.6875rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--c-muted);
}
.jbw-order-info-tile strong {
    font-size: 0.9375rem;
    line-height: 1.45;
    font-weight: 700;
    color: var(--c-text);
    overflow-wrap: anywhere;
}
.jbw-order-info-sub { font-size: 0.8125rem; color: var(--c-muted); }

/* Section titles with line divider */
.jbw-order-section-title {
    margin: 0 0 0.85rem;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--c-muted);
    display: flex;
    align-items: center;
    gap: 0.65rem;
}
.jbw-order-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--c-border);
}

/* Notes / refund sections */
.jbw-order-notes {
    padding: 1.1rem 1.2rem;
    border: 1px solid var(--c-border);
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.03);
}

/* Sidebar summary card */
.jbw-order-summary-card {
    padding: 1.4rem 1.5rem;
    border-radius: 20px;
    background: #fff;
    border: 1px solid var(--c-border);
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.03), 0 12px 32px rgb(15 23 42 / 0.06);
}
.jbw-order-summary-card .jbw-overview-label {
    margin: 0 0 1rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid var(--c-border);
}
.jbw-order-summary-card .jbw-payment-lines {
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px dashed var(--c-border);
}
.jbw-order-summary-card .jbw-payment-lines > div {
    font-size: 0.875rem;
    padding: 0.2rem 0;
}
.jbw-order-summary-card .jbw-payment-total { margin: 0; }
.jbw-order-summary-card .jbw-payment-total strong {
    font-size: 1.5rem;
    font-family: var(--font-serif);
    letter-spacing: -0.01em;
}
.jbw-order-paid-check {
    margin-top: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    background: #d1fae5;
    color: #065f46;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.02em;
}
.jbw-order-refund-note {
    margin-top: 0.75rem;
    padding: 0.55rem 0.75rem;
    border-radius: 10px;
    background: #fef3c7;
    color: #92400e;
    font-size: 0.8125rem;
    font-weight: 700;
}

@media (max-width: 640px) {
    .jbw-order-hero { padding: 1.25rem 1.15rem; border-radius: 16px; }
    .jbw-order-hero-row { flex-direction: column; align-items: flex-start; gap: 0.85rem; margin-top: 0.9rem; }
    .jbw-order-pay-banner { padding: 0.85rem 1rem; }
}

.jbw-order-vendor-card {
    border: 1px solid var(--c-border);
    border-radius: 16px;
    background: #fff;
    overflow: hidden;
    margin-bottom: 1rem;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.03), 0 8px 24px rgb(15 23 42 / 0.04);
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.jbw-order-vendor-card:hover {
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.04), 0 14px 32px rgb(15 23 42 / 0.06);
}
.jbw-order-vendor-card:last-child { margin-bottom: 0; }

.jbw-order-vendor-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 1.05rem 1.25rem;
    border-bottom: 1px solid var(--c-border);
    background: linear-gradient(180deg, #fbfaf7 0%, #fff 100%);
}

.jbw-order-vendor-identity {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.jbw-order-vendor-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid var(--c-border);
}

.jbw-order-vendor-avatar--fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--c-primary-soft, rgba(232,93,58,0.1));
    color: var(--c-primary);
    font-weight: 800;
    font-size: 1rem;
}

.jbw-order-vendor-name {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 800;
    line-height: 1.3;
}

.jbw-order-vendor-sub {
    margin: 0.15rem 0 0;
    font-size: 0.75rem;
    color: var(--c-muted);
}

/* Horizontal order tracker */
.jbw-order-track-wrap {
    padding: 1rem 1.15rem;
    border-bottom: 1px solid var(--c-border);
    background: #fafafa;
}

.jbw-order-track-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.65rem;
}

.jbw-order-track-title {
    font-size: 0.6875rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--c-muted);
}

.jbw-order-track-bar {
    height: 8px;
    border-radius: 999px;
    background: #e8e4df;
    overflow: hidden;
    margin-bottom: 1rem;
}

.jbw-order-track-bar--sub {
    height: 6px;
    margin-bottom: 0.35rem;
}

.jbw-order-track-bar-fill {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--c-primary), #e9a87c);
    min-width: 4px;
    transition: width 0.35s ease;
}

.jbw-order-track-steps {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0;
}

.jbw-order-track-step {
    position: relative;
    padding: 0 0.25rem;
    text-align: center;
}

.jbw-order-track-step:not(.jbw-order-track-step--last)::after {
    content: '';
    position: absolute;
    top: 14px;
    left: calc(50% + 16px);
    right: calc(-50% + 16px);
    height: 2px;
    background: #e0dbd4;
    z-index: 0;
}

.jbw-order-track-step--done:not(.jbw-order-track-step--last)::after {
    background: var(--c-primary);
}

.jbw-order-track-step-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
}

.jbw-order-track-marker {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid #d8d2ca;
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6875rem;
    font-weight: 800;
    color: var(--c-muted);
    flex-shrink: 0;
}

.jbw-order-track-step--done .jbw-order-track-marker {
    border-color: var(--c-primary);
    background: var(--c-primary);
    color: #fff;
}

.jbw-order-track-step--current .jbw-order-track-marker {
    border-color: var(--c-primary);
    background: #fff;
    color: var(--c-primary);
    box-shadow: 0 0 0 4px rgba(232, 93, 58, 0.15);
}

.jbw-order-track-step--upcoming .jbw-order-track-marker {
    background: #f5f3f0;
}

.jbw-order-track-step--cancelled .jbw-order-track-marker {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #b91c1c;
}

.jbw-order-track-label {
    display: block;
    font-size: 0.625rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--c-muted);
    max-width: 5.5rem;
}

.jbw-order-track-step--done .jbw-order-track-label,
.jbw-order-track-step--current .jbw-order-track-label {
    color: var(--c-text, #1a1a1a);
}

.jbw-order-track-step--current .jbw-order-track-label {
    color: var(--c-primary);
}

@media (max-width: 520px) {
    .jbw-order-track-steps {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem 0.5rem;
    }

    .jbw-order-track-step::after { display: none; }

    .jbw-order-track-step-inner {
        flex-direction: row;
        text-align: left;
        justify-content: flex-start;
    }

    .jbw-order-track-label { max-width: none; font-size: 0.6875rem; }
}

.jbw-order-rental-strip {
    margin-top: 1rem;
    padding-top: 0.85rem;
    border-top: 1px dashed #ddd6cc;
}

.jbw-order-rental-strip-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    margin-bottom: 0.35rem;
}

.jbw-order-rental-strip-head span { color: var(--c-muted); font-weight: 700; }
.jbw-order-rental-strip-head strong { color: var(--c-primary); font-size: 0.75rem; }

.jbw-order-rental-dates {
    margin: 0 0 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
}

.jbw-order-rental-phase {
    margin: 0.35rem 0 0;
    font-size: 0.75rem;
    color: var(--c-muted);
}

/* Compact item lines in order detail */
.jbw-order-lines {
    padding: 0.75rem 1.15rem 1.15rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.jbw-order-item-block {
    border: 1px solid var(--c-border);
    border-radius: 14px;
    background: #fff;
    overflow: hidden;
}

.jbw-order-line {
    display: grid;
    grid-template-columns: 56px 1fr auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--c-border);
}

.jbw-order-line-img {
    width: 56px;
    height: 68px;
    object-fit: cover;
    border-radius: 10px;
    background: #f0ede8;
}

.jbw-order-line-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 700;
    line-height: 1.35;
}

.jbw-order-line-meta {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    color: var(--c-muted);
    line-height: 1.4;
}

.jbw-order-line-price {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 800;
    color: var(--c-primary);
    white-space: nowrap;
}

/* Per-item progress */
.jbw-order-item-progress {
    padding: 0.85rem 1rem 1rem;
    background: #faf9f7;
}

.jbw-order-item-progress-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.jbw-order-item-progress-label {
    font-size: 0.700rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--c-muted);
}

.jbw-status--sm {
    font-size: 0.625rem;
    padding: 0.2rem 0.5rem;
}

.jbw-order-item-progress-bar {
    height: 6px;
    border-radius: 999px;
    background: #e8e4df;
    overflow: hidden;
    margin-bottom: 0.65rem;
}

.jbw-order-item-progress-bar--thin {
    height: 4px;
    margin-bottom: 0;
}

.jbw-order-item-progress-fill {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--c-primary), #e9a87c);
    min-width: 3px;
}

.jbw-order-item-steps {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.25rem;
}

.jbw-order-item-step {
    position: relative;
    text-align: center;
    min-width: 0;
}

.jbw-order-item-step:not(.is-last)::before {
    content: '';
    position: absolute;
    top: 9px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #e0dbd4;
    z-index: 0;
}

.jbw-order-item-step--done:not(.is-last)::before {
    background: var(--c-primary);
}

.jbw-order-item-step-dot {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #d8d2ca;
    background: #fff;
    margin: 0 auto 0.3rem;
}

.jbw-order-item-step--done .jbw-order-item-step-dot {
    border-color: var(--c-primary);
    background: var(--c-primary);
    color: #fff;
}

.jbw-order-item-step--current .jbw-order-item-step-dot {
    border-color: var(--c-primary);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(232, 93, 58, 0.18);
}

.jbw-order-item-step--upcoming .jbw-order-item-step-dot {
    background: #f5f3f0;
}

.jbw-order-item-step-text {
    display: block;
    font-size: 0.5625rem;
    font-weight: 700;
    line-height: 1.25;
    color: var(--c-muted);
    padding: 0 0.15rem;
    word-break: break-word;
}

.jbw-order-item-step--done .jbw-order-item-step-text,
.jbw-order-item-step--current .jbw-order-item-step-text {
    color: var(--c-text, #1a1a1a);
}

.jbw-order-item-step--current .jbw-order-item-step-text {
    color: var(--c-primary);
}

@media (min-width: 640px) {
    .jbw-order-item-step-text { font-size: 0.625rem; }
}

.jbw-order-item-rental {
    margin-top: 0.75rem;
    padding-top: 0.65rem;
    border-top: 1px dashed #ddd6cc;
}

.jbw-order-item-rental-head {
    display: flex;
    justify-content: space-between;
    font-size: 0.7800rem;
    font-weight: 700;
    color: var(--c-muted);
    margin-bottom: 0.25rem;
}

.jbw-order-item-rental-head span:last-child {
    color: var(--c-primary);
}

.jbw-order-item-rental-dates {
    margin: 0 0 0.35rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.jbw-order-notes {
    margin-top: 0.5rem;
    padding: 1rem 1.1rem;
    border: 1px solid var(--c-border);
    border-radius: 14px;
    background: #fff;
}

.jbw-order-notes p {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.55;
    color: var(--c-text);
}

.jbw-order-refund-row {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--c-border);
    font-size: 0.875rem;
}

.jbw-order-refund-row:last-child { border-bottom: none; }
.jbw-order-refund-row p { margin: 0.25rem 0 0; font-size: 0.8125rem; color: var(--c-muted); }

.jbw-order-refund-note {
    margin: 0.75rem 0 0;
    font-size: 0.8125rem;
    color: #b45309;
}

.jbw-cart-summary {
    position: sticky;
    top: 5.5rem;
}

.jbw-cart-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: #fff;
    border-radius: var(--r-card);
    border: 1px dashed var(--c-border);
}

.jbw-cart-empty-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    color: var(--c-muted);
    opacity: 0.5;
}

/* Promo CTA band */
.jbw-promo-band {
    background: linear-gradient(135deg, var(--c-accent) 0%, #243b47 50%, #2d4a56 100%);
    border-radius: var(--r-card);
    padding: clamp(2rem, 5vw, 3rem);
    color: #fff;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    box-shadow: var(--shadow-hover);
    overflow: hidden;
    position: relative;
}

.jbw-promo-band::before {
    content: '';
    position: absolute;
    right: -10%;
    top: -40%;
    width: 50%;
    height: 180%;
    background: radial-gradient(circle, rgb(232 93 58 / 0.25) 0%, transparent 70%);
    pointer-events: none;
}

.jbw-promo-band h3 {
    font-family: var(--font-serif);
    font-size: clamp(1.35rem, 2.5vw, 1.75rem);
    margin: 0 0 0.5rem;
    position: relative;
}

.jbw-promo-band p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9375rem;
    max-width: 28rem;
    position: relative;
}

/* How it works */
.jbw-steps {
    display: grid;
    gap: 1rem;
}

@media (min-width: 768px) {
    .jbw-steps { grid-template-columns: repeat(3, 1fr); }
}

.jbw-step {
    background: #fff;
    border: 1px solid var(--c-border);
    border-radius: var(--r-card);
    padding: 1.5rem;
    box-shadow: var(--shadow-card);
    transition: transform 0.2s, box-shadow 0.2s;
}

.jbw-step:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.jbw-step-num {
    font-size: 0.75rem;
    font-weight: 800;
    color: var(--c-primary);
    letter-spacing: 0.1em;
    margin-bottom: 0.75rem;
}

/* Footer */
.jbw-footer {
    background: var(--c-accent);
    margin-top: 1.000rem;
    padding-top: 2.000rem;
}

.jbw-footer-about {
    color: rgb(255 255 255 / 0.72);
    line-height: 1.7;
}

.jbw-footer-bottom {
    border-top: 1px solid rgb(255 255 255 / 0.1);
    color: rgb(255 255 255 / 0.55);
}

/* Modal */
.jbw-modal-content {
    border-radius: var(--r-card);
    box-shadow: var(--shadow-hover);
}

.jbw-modal-circle-thumb {
    box-shadow: var(--shadow-card);
    border: 3px solid #fff;
}

/* Catalog filters */
.jbw-filters {
    background: #fff;
    border-radius: var(--r-card);
    border: 1px solid var(--c-border);
    box-shadow: var(--shadow-card);
    padding: 1.25rem;
}

.jbw-input,
.jbw-select,
.jbw-textarea {
    border-radius: 10px;
    border-color: var(--c-border);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.jbw-input:focus,
.jbw-select:focus,
.jbw-textarea:focus {
    border-color: var(--c-primary);
    box-shadow: 0 0 0 3px rgb(232 93 58 / 0.12);
    outline: none;
}

/* Designer carousel */
.jbw-designer {
    background: #fff;
    border-radius: var(--r-card);
    padding: 1rem;
    border: 1px solid var(--c-border);
    box-shadow: var(--shadow-card);
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.jbw-designer:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}

.jbw-designer-avatar {
    width: 5rem;
    height: 5rem;
    border-radius: var(--r-pill);
    object-fit: cover;
    margin: 0 auto 0.75rem;
    border: 3px solid var(--c-primary-soft);
}
</style>
