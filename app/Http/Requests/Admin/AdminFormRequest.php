<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class AdminFormRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(AdminValidationRules::normalizeEmailFields($this->all()));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return AdminValidationRules::messages();
    }

    public function attributes(): array
    {
        return AdminValidationRules::attributes();
    }
}
