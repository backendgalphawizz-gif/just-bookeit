<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;
use Illuminate\Validation\Rule;

class CustomerRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $rules = AdminValidationRules::customer();

        if ($customer = $this->route('customer')) {
            $rules['status'] = ['required', Rule::in([$customer->status])];
        }

        return $rules;
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
