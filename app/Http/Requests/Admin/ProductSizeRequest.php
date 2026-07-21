<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;
use Illuminate\Validation\Rule;

class ProductSizeRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $sizeId = $this->route('size')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:'.AdminValidationRules::REGEX_TITLE,
                Rule::unique('product_sizes', 'name')->ignore($sizeId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => (int) ($this->input('sort_order') ?? 0),
            'name' => trim((string) $this->input('name')),
        ]);
    }
}
