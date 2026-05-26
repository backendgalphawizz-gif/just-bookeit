<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class CustomerRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::customer();
    }
}
