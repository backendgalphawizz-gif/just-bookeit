<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class RefundUpdateRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::refundUpdate();
    }
}
