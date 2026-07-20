<form
    method="POST"
    action="{{ route('vendor.products.listing-active', $item) }}"
    class="vp-avail-switch @unless($isApproved) is-disabled @endunless"
    @unless($isApproved) title="Only approved products can change availability" @endunless
>
    @csrf
    @method('PATCH')
    <button
        type="submit"
        name="is_listing_active"
        value="1"
        class="vp-avail-switch-btn {{ $isActive ? 'is-on is-available' : '' }}"
        @disabled(! $isApproved)
    >Available</button>
    <button
        type="submit"
        name="is_listing_active"
        value="0"
        class="vp-avail-switch-btn {{ ! $isActive ? 'is-on is-unavailable' : '' }}"
        @disabled(! $isApproved)
    >Unavailable</button>
</form>
