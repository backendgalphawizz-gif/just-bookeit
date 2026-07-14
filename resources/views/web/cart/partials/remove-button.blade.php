<form method="POST" action="{{ route('web.cart.destroy', $cartItem) }}" class="jbw-line-item-remove-form">
    @csrf
    @method('DELETE')
    <button type="submit" class="jbw-btn jbw-btn--outline jbw-btn--sm">Remove</button>
</form>
