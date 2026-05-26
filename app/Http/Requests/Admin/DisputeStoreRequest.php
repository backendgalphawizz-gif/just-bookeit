<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class DisputeStoreRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::disputeStore();
    }
}
