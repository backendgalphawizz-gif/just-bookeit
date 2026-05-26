<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class AdminUserRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::adminUser($this->route('admin')?->id);
    }
}
