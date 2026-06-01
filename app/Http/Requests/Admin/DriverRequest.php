<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class DriverRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::driver($this->route('driver')?->id);
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'is_verified' => $this->boolean('is_verified'),
        ]);
    }
}
