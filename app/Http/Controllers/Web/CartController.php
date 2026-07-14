<?php

namespace App\Http\Controllers\Web;

use App\Models\CartItem;
use App\Models\Customer;
use App\Services\Customer\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class CartController extends WebController
{
    public function __construct(
        protected CartService $cart
    ) {}

    public function index(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $payload = $this->cart->apiPayload($customer);

        return view('web.cart.index', [
            'items' => $this->cart->itemsFor($customer),
            'summary' => $payload['summary'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'portfolio_item_id' => ['required', 'integer', 'exists:portfolio_items,id'],
            'portfolio_item_variant_id' => ['nullable', 'integer', 'exists:portfolio_item_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->cart->add(
                $customer,
                (int) $data['portfolio_item_id'],
                (int) ($data['quantity'] ?? 1),
                isset($data['portfolio_item_variant_id']) ? (int) $data['portfolio_item_variant_id'] : null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $redirect = $request->input('redirect', route('web.cart.index'));

        return redirect()->to($redirect)->with('success', 'Added to cart.');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $this->cart->remove($customer, $cartItem);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Item removed from cart.');
    }
}
