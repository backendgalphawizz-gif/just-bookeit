<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return AdminValidationRules::adminLogin();
    }

    public function messages(): array
    {
        return AdminValidationRules::messages();
    }

    public function attributes(): array
    {
        return AdminValidationRules::attributes();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(AdminValidationRules::normalizeEmailFields($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $login = (string) $this->input('login', '');

            if ($login === '' || ! AdminValidationRules::looksLikeEmail($login)) {
                return;
            }

            if (! AdminValidationRules::isValidEmail($login)) {
                $validator->errors()->add('login', 'Enter a valid email ID (e.g. name@example.com).');
            }
        });
    }
}
