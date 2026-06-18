<footer class="jbw-footer">
    <div class="jbw-container jbw-footer-grid">
        <div>
            <a href="{{ route('web.home') }}" class="jbw-footer-logo-link" aria-label="Just Book IT home">
                <x-web.logo variant="footer" />
            </a>
            <p class="jbw-footer-about">India's premier destination for designer dress and jewellery rentals. Look extraordinary for any occasion.</p>
        </div>
        <div>
            <p class="jbw-footer-heading">Quick Links</p>
            <ul class="jbw-footer-links">
                <li><a href="{{ route('web.home') }}#how-it-works">How It Works</a></li>
                <li><a href="{{ route('web.catalog.index') }}">Browse Catalog</a></li>
                <li><a href="{{ route('web.faq') }}">FAQs</a></li>
                @auth('customer')
                    @unless ($webCustomer->is_guest)
                        <li><a href="{{ route('web.bookings.index') }}">My Bookings</a></li>
                    @endunless
                @else
                    <li><a href="{{ route('web.login') }}">Sign in</a></li>
                @endauth
                <li><a href="{{ route('web.contact') }}">Contact Us</a></li>
            </ul>
        </div>
        <div>
            <p class="jbw-footer-heading">Services</p>
            <ul class="jbw-footer-links">
                @forelse ($webServiceCategories ?? [] as $service)
                    <li><a href="{{ route('web.services.index', ['service' => $service->id]) }}">{{ $service->name }}</a></li>
                @empty
                    <li><a href="{{ route('web.services.index') }}">Fashion Designer Booking</a></li>
                    <li><a href="{{ route('web.services.index') }}">Rental Dresses</a></li>
                    <li><a href="{{ route('web.services.index') }}">Rental Jewellery</a></li>
                @endforelse
            </ul>
        </div>
        <div>
            <p class="jbw-footer-heading">Follow Us</p>
            <div class="jbw-social">
                <a href="#" aria-label="Instagram">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.1" fill="currentColor" stroke-width="3"/></svg>
                </a>
                <a href="#" aria-label="Facebook">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a href="#" aria-label="YouTube">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="currentColor" stroke="none"/></svg>
                </a>
            </div>
        </div>
    </div>
    <div class="jbw-container jbw-footer-bottom">
        <p>© {{ date('Y') }} {{ $webBranding['name'] ?? 'Just Book IT' }}. All rights reserved.</p>
        <p>Designed & Developed by Alphawizz Technologies pvt. ltd</p>
    </div>
</footer>
