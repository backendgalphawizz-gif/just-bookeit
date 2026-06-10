<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class AdminFormRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(AdminValidationRules::normalizeEmailFields($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->all() as $key => $value) {
                if (! is_string($key) || ! AdminValidationRules::isEmailFieldName($key)) {
                    continue;
                }

                $email = trim($value);
                if ($email === '') {
                    continue;
                }

                if (! AdminValidationRules::isValidEmail($email)) {
                    $validator->errors()->add($key, AdminValidationRules::emailValidationMessage());
                }
            }
        });
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
