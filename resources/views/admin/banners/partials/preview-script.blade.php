@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bannerPreviewForm', (config = {}) => ({
        activeTab: 'website',
        audience: config.audience || 'customer',
        title: '',
        subtitle: '',
        redirectUrl: '',
        isActive: true,
        imageUrl: config.imageUrl || null,
        previewImage: null,

        init() {
            if (config.static) {
                this.title = config.title || '';
                this.subtitle = config.subtitle || '';
                this.redirectUrl = config.redirectUrl || '';
                this.isActive = config.isActive ?? true;
                this.audience = config.audience || 'customer';
            } else {
                const sync = () => {
                    this.title = this.$el.querySelector('[name=title]')?.value || '';
                    this.subtitle = this.$el.querySelector('[name=subtitle]')?.value || '';
                    this.redirectUrl = this.$el.querySelector('[name=redirect_url]')?.value || '';
                    this.isActive = !!this.$el.querySelector('[name=is_active]')?.checked;
                    this.audience = this.$el.querySelector('[name=audience]')?.value || this.audience;
                };

                sync();
                this.$el.addEventListener('input', sync);
                this.$el.addEventListener('change', sync);

                this.$el.querySelector('[name=image]')?.addEventListener('change', (event) => {
                    const file = event.target.files?.[0];
                    if (this.previewImage) {
                        URL.revokeObjectURL(this.previewImage);
                    }
                    this.previewImage = file ? URL.createObjectURL(file) : null;
                });
            }

            this.activeTab = this.previewTabs()[0]?.id || 'website';
        },

        previewTabs() {
            if (this.audience === 'vendor') {
                return [{ id: 'vendor-app', label: 'Vendor app' }];
            }

            if (this.audience === 'driver') {
                return [{ id: 'driver-app', label: 'Driver app' }];
            }

            return [
                { id: 'website', label: 'Website' },
                { id: 'customer-app', label: 'Customer app' },
            ];
        },

        isAppOnlyAudience() {
            return this.audience === 'vendor' || this.audience === 'driver';
        },

        audienceLabel() {
            return {
                customer: 'Customer',
                vendor: 'Vendor',
                driver: 'Driver',
            }[this.audience] || 'Customer';
        },

        inactiveMessage() {
            if (this.audience === 'vendor') {
                return 'Inactive — this banner will not appear on the vendor app.';
            }
            if (this.audience === 'driver') {
                return 'Inactive — this banner will not appear on the driver app.';
            }

            return 'Inactive — this banner will not appear on the website or customer app.';
        },

        displayImage() {
            return this.previewImage || this.imageUrl || null;
        },

        displayTitle() {
            return this.title.trim() || 'Your banner title';
        },

        displayTitleHtml() {
            return this.displayTitle().replace(/\n/g, '<br>');
        },

        displaySubtitle() {
            return this.subtitle.trim() || 'Add a subtitle to describe this promotion';
        },

        displayRedirect() {
            const url = this.redirectUrl.trim();
            return url || 'No redirect URL set';
        },

        webHeroStyle() {
            const image = this.displayImage();
            if (! image) {
                return {};
            }

            return { backgroundImage: 'url("'+image.replace(/"/g, '\\"')+'")' };
        },
    }));
});
</script>
@endpush
