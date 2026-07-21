<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;
use Illuminate\Validation\Rule;

class ProductColorRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $colorId = $this->route('color')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:'.AdminValidationRules::REGEX_TITLE,
                Rule::unique('product_colors', 'name')->ignore($colorId),
            ],
            'hex_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $hex = trim((string) $this->input('hex_code', ''));
        if ($hex !== '' && ! str_starts_with($hex, '#')) {
            $hex = '#'.$hex;
        }

        $this->merge([
            'hex_code' => $hex !== '' ? strtoupper($hex) : null,
        ]);
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
