<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class RefundStoreRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::refundStore();
    }
}
