<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class CustomerRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::customer();
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'registered_at' => AdminValidationRules::normalizeMysqlTimestampDate(
                $this->input('registered_at')
            ),
        ]);
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'is_verified' => $this->boolean('is_verified'),
        ]);
    }
}
