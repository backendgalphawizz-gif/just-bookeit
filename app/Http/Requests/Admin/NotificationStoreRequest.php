<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class NotificationStoreRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::notificationStore();
    }
}
