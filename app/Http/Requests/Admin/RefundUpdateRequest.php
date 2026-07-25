<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use App\Support\AdminValidationRules;
use Illuminate\Validation\Validator;

class RefundUpdateRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::refundUpdate();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var \App\Models\Refund $refund */
            $refund = $this->route('refund');
            if (! $refund) {
                return;
            }

            $order = Order::query()->with('refunds')->find($refund->order_id);
            if (! $order) {
                return;
            }

            $max = RefundStoreRequest::refundableAmount($order, $refund->id);
            $amount = round((float) $this->input('amount'), 2);

            if ($amount > $max + 0.001) {
                $validator->errors()->add(
                    'amount',
                    'Amount cannot exceed the refundable balance of ₹'.number_format($max, 2).'.'
                );
            }
        });
    }
}
