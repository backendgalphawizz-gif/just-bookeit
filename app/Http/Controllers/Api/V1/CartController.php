<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\CartItem;
use App\Models\Customer;
use App\Services\Customer\CartService;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CartController extends ApiController
{
    public function __construct(
        protected CartService $cart
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        return $this->success($this->cart->apiPayload($customer));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate([
            'portfolio_item_id' => ['required', 'integer', 'exists:portfolio_items,id'],
            'portfolio_item_variant_id' => [
                'nullable',
                'integer',
                'exists:portfolio_item_variants,id',
            ],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $cartItem = $this->cart->add(
                $customer,
                (int) $data['portfolio_item_id'],
                (int) ($data['quantity'] ?? 1),
                isset($data['portfolio_item_variant_id']) ? (int) $data['portfolio_item_variant_id'] : null,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'item' => CustomerApiPresenter::cartItem($cartItem),
            'summary' => $this->cart->summary($customer->fresh()),
        ], 'Product added to cart.', 201);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        try {
            $this->cart->remove($customer, $cartItem);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        return $this->success([
            'summary' => $this->cart->summary($customer->fresh()),
        ], 'Item removed from cart.');
    }
}
