<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class OrderRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::order();
    }
}
