<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class CategoryRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            ...AdminValidationRules::category(),
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
