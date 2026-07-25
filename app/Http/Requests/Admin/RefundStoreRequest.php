<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use App\Models\Refund;
use App\Support\AdminValidationRules;
use Illuminate\Validation\Validator;

class RefundStoreRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::refundStore();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $order = Order::query()->with('refunds')->find($this->input('order_id'));
            if (! $order) {
                return;
            }

            $max = self::refundableAmount($order);
            $amount = round((float) $this->input('amount'), 2);

            if ($amount > $max + 0.001) {
                $validator->errors()->add(
                    'amount',
                    'Amount cannot exceed the refundable balance of ₹'.number_format($max, 2).'.'
                );
            }
        });
    }

    public static function refundableAmount(Order $order, ?int $ignoreRefundId = null): float
    {
        $already = (float) $order->refunds
            ->when($ignoreRefundId, fn ($rows) => $rows->where('id', '!=', $ignoreRefundId))
            ->whereNotIn('status', ['rejected'])
            ->sum('amount');

        return max(0, round($order->grandTotal() - $already, 2));
    }
}
