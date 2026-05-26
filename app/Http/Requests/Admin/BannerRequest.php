<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class BannerRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $rules = AdminValidationRules::banner();
        $rules['image'] = [
            $this->isMethod('post') ? 'required' : 'nullable',
            'image',
            'mimes:jpeg,jpg,png,webp,gif',
            'max:4096',
        ];

        return [
            ...$rules,
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
