<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class DisputeUpdateRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::disputeUpdate();
    }
}
