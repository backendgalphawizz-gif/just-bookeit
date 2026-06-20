@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
@endpush

<aside style="margin-bottom: 15px;" class="jb-banner-preview-panel" aria-label="Banner preview">
    <div class="jb-banner-preview-panel__head">
        <div>
            <h2 class="jb-banner-preview-panel__title">Live preview</h2>
            <p class="jb-banner-preview-panel__sub" x-text="audienceLabel() + ' — updates as you edit'"></p>
        </div>
        <span class="jb-banner-preview-panel__badge" x-text="isActive ? 'Published' : 'Draft'"></span>
    </div>

    <div class="jb-banner-preview-tabs" role="tablist" x-show="!isAppOnlyAudience()" x-cloak>
        <template x-for="tab in previewTabs()" :key="tab.id">
            <button
                type="button"
                class="jb-banner-preview-tab"
                :class="{ 'is-active': activeTab === tab.id }"
                @click="activeTab = tab.id"
                role="tab"
                :aria-selected="activeTab === tab.id"
                x-text="tab.label"
            ></button>
        </template>
    </div>

    <p class="jb-banner-preview-note" x-show="!isActive" x-cloak>
        <span x-text="inactiveMessage()"></span>
    </p>

    {{-- Customer website --}}
    <div class="jb-banner-preview-stage" x-show="activeTab === 'website'" x-cloak role="tabpanel">
        <p class="jb-banner-preview-stage__label">justbookit.com homepage</p>
        <div class="jb-banner-preview-web-frame">
            <div class="jb-banner-preview-web-header">
                <span class="jb-banner-preview-web-logo">Just Book IT</span>
                <span class="jb-banner-preview-web-nav">Home · Services · Categories</span>
            </div>
            <div class="jb-banner-preview-web">
                <div
                    class="jb-banner-preview-web__slide"
                    :class="{ 'jb-banner-preview-web__slide--empty': !displayImage() }"
                    :style="webHeroStyle()"
                ></div>
                <div class="jb-banner-preview-web__overlay"></div>
                <div class="jb-banner-preview-web__content">
                    <p class="jb-banner-preview-web__kicker">Just Book IT</p>
                    <h3 class="jb-banner-preview-web__title" x-html="displayTitleHtml()"></h3>
                    <p class="jb-banner-preview-web__text" x-text="displaySubtitle()"></p>
                    <span class="jb-banner-preview-web__cta">Explore collection</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer mobile app (matches live home screen) --}}
    <div class="jb-banner-preview-stage" x-show="activeTab === 'customer-app'" x-cloak role="tabpanel">
        <p class="jb-banner-preview-stage__label">Customer app · home screen</p>
        <div class="jb-mock-phone">
            <div class="jb-mock-phone__shell">
                <div class="jb-mock-phone__status" aria-hidden="true">
                    <span>9:41</span>
                    <span class="jb-mock-phone__status-icons">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M2 20h20v2H2zM4 17h2v2H4zm3-3h2v5H7zm3-3h2v8h-2zm3-3h2v11h-2zm3-3h2v14h-2z"/></svg>
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3a4.98 4.98 0 00-6.01 0zm-4.24-4.24l1.41 1.41a7 7 0 009.9 0l1.41-1.41a9 9 0 00-12.72 0z"/></svg>
                        <svg viewBox="0 0 24 24" width="14" height="12" fill="currentColor"><rect x="2" y="7" width="18" height="10" rx="2"/><rect x="20" y="10" width="2" height="4" rx="1"/></svg>
                    </span>
                </div>

                <div class="jb-mock-home__body">
                    <div class="jb-mock-home__header">
                        <div class="jb-mock-home__location">
                            <span class="jb-mock-home__loc-pin" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 21s7-4.5 7-10a7 7 0 10-14 0c0 5.5 7 10 7 10z"/><circle cx="12" cy="11" r="2.5"/></svg>
                            </span>
                            <div class="jb-mock-home__loc-text">
                                <p class="jb-mock-home__loc-title">Home <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg></p>
                                <p class="jb-mock-home__loc-addr">204, Palm Court, Malad West, Mumbai</p>
                            </div>
                        </div>
                        <button type="button" class="jb-mock-home__bell" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                        </button>
                    </div>

                    <div class="jb-mock-home__search">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
                        <span>Search by designers, categories...</span>
                    </div>

                    <div class="jb-mock-home__carousel">
                        <img
                            class="jb-mock-home__banner-img"
                            :src="displayImage() || ''"
                            alt="Banner preview"
                            x-show="displayImage()"
                        >
                        <div class="jb-mock-home__banner-empty" x-show="!displayImage()" x-cloak>
                            <p class="jb-mock-home__banner-empty-title" x-html="displayTitleHtml()"></p>
                            <p class="jb-mock-home__banner-empty-sub" x-text="displaySubtitle()"></p>
                        </div>
                    </div>

                    <div class="jb-mock-home__dots" aria-hidden="true">
                        <span class="is-active"></span><span></span><span></span>
                    </div>

                    <h3 class="jb-mock-home__section">Our Services</h3>
                    <div class="jb-mock-home__services">
                        <div class="jb-mock-home__service">
                            <img src="https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=200&q=80&fit=crop" alt="">
                            <span>Fashion Designer Booking</span>
                        </div>
                        <div class="jb-mock-home__service">
                            <img src="https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=200&q=80&fit=crop" alt="">
                            <span>Rented Dress Booking</span>
                        </div>
                        <div class="jb-mock-home__service">
                            <img src="https://images.unsplash.com/photo-1617032210317-3b0855f047a4?w=200&q=80&fit=crop" alt="">
                            <span>Rented Jewellery Booking</span>
                        </div>
                    </div>

                    <h3 class="jb-mock-home__section">Shop by Category</h3>
                    <div class="jb-mock-home__categories">
                        <div class="jb-mock-home__category">
                            <img src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=120&q=80&fit=crop" alt="">
                            <span>Women</span>
                        </div>
                        <div class="jb-mock-home__category">
                            <img src="https://images.unsplash.com/photo-1617137968427-85924c800a22?w=120&q=80&fit=crop" alt="">
                            <span>Men</span>
                        </div>
                        <div class="jb-mock-home__category">
                            <img src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=120&q=80&fit=crop" alt="">
                            <span>Kids</span>
                        </div>
                    </div>
                </div>

                <nav class="jb-mock-home__nav" aria-hidden="true">
                    <div class="jb-mock-home__nav-item is-active">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 3l9 8h-3v10h-5v-6H11v6H6V11H3l9-8z"/></svg>
                        <span>Home</span>
                    </div>
                    <div class="jb-mock-home__nav-item">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                        <span>Bookings</span>
                    </div>
                    <div class="jb-mock-home__nav-item">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        <span>Chat</span>
                    </div>
                    <div class="jb-mock-home__nav-item">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                        <span>Profile</span>
                    </div>
                </nav>
            </div>
        </div>
    </div>

    {{-- Vendor mobile app --}}
    <div class="jb-banner-preview-stage" x-show="activeTab === 'vendor-app'" x-cloak role="tabpanel">
        <p class="jb-banner-preview-stage__label">Vendor app · home</p>
        <div class="jb-banner-preview-app jb-banner-preview-app--vendor">
            <div class="jb-banner-preview-app__device">
                <div class="jb-banner-preview-app__notch" aria-hidden="true"></div>
                <div class="jb-banner-preview-app__screen">
                    <div class="jb-banner-preview-app__topbar jb-banner-preview-app__topbar--vendor">
                        <span>9:41</span>
                        <span class="jb-banner-preview-app__topbar-title">Vendor Studio</span>
                        <span>●●●</span>
                    </div>
                    <div class="jb-banner-preview-app__vendor-strip">
                        <span>Today</span><strong>12 orders</strong>
                    </div>
                    <div class="jb-banner-preview-app__banner jb-banner-preview-app__banner--vendor">
                        <img class="jb-banner-preview-app__banner-img" :src="displayImage() || ''" alt="" x-show="displayImage()">
                        <div class="jb-banner-preview-app__banner-img jb-banner-preview-app__banner-img--empty" x-show="!displayImage()"></div>
                        <div class="jb-banner-preview-app__banner-overlay"></div>
                        <div class="jb-banner-preview-app__banner-body">
                            <p class="jb-banner-preview-app__banner-title" x-text="displayTitle()"></p>
                            <p class="jb-banner-preview-app__banner-sub" x-text="displaySubtitle()"></p>
                        </div>
                    </div>
                    <div class="jb-banner-preview-app__section-title">Quick actions</div>
                    <div class="jb-banner-preview-app__tiles jb-banner-preview-app__tiles--2"><span></span><span></span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Driver mobile app --}}
    <div class="jb-banner-preview-stage" x-show="activeTab === 'driver-app'" x-cloak role="tabpanel">
        <p class="jb-banner-preview-stage__label">Driver app · deliveries</p>
        <div class="jb-banner-preview-app jb-banner-preview-app--driver">
            <div class="jb-banner-preview-app__device">
                <div class="jb-banner-preview-app__notch" aria-hidden="true"></div>
                <div class="jb-banner-preview-app__screen jb-banner-preview-app__screen--driver">
                    <div class="jb-banner-preview-app__topbar jb-banner-preview-app__topbar--driver">
                        <span>9:41</span>
                        <span class="jb-banner-preview-app__topbar-title">Driver</span>
                        <span>●●●</span>
                    </div>
                    <div class="jb-banner-preview-app__driver-status">Online · 3 tasks today</div>
                    <div class="jb-banner-preview-app__banner jb-banner-preview-app__banner--driver">
                        <img class="jb-banner-preview-app__banner-img" :src="displayImage() || ''" alt="" x-show="displayImage()">
                        <div class="jb-banner-preview-app__banner-img jb-banner-preview-app__banner-img--empty" x-show="!displayImage()"></div>
                        <div class="jb-banner-preview-app__banner-overlay"></div>
                        <div class="jb-banner-preview-app__banner-body">
                            <p class="jb-banner-preview-app__banner-title" x-text="displayTitle()"></p>
                            <p class="jb-banner-preview-app__banner-sub" x-text="displaySubtitle()"></p>
                        </div>
                    </div>
                    <div class="jb-banner-preview-app__section-title">Today's deliveries</div>
                    <div class="jb-banner-preview-app__driver-list">
                        <span></span><span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <dl class="jb-banner-preview-meta">
        <div><dt>Redirect link</dt><dd x-text="displayRedirect()"></dd></div>
        <div><dt>Audience</dt><dd x-text="audienceLabel()"></dd></div>
        <div><dt>Visibility</dt><dd x-text="isActive ? 'Will show when active & scheduled' : 'Hidden (inactive)'"></dd></div>
    </dl>
</aside>
