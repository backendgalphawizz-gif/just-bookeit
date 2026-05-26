<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $role = $this->route('role');
        $rules = AdminValidationRules::role();

        if ($role) {
            $rules['slug'] = [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'slug')->ignore($role->id),
            ];
        } else {
            $rules['slug'] = ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', 'unique:roles,slug'];
        }

        return $rules;
    }

    protected function passedValidation(): void
    {
        $slug = $this->string('slug')->toString();

        if ($slug === '') {
            $slug = Str::slug($this->string('name')->toString(), '_');
        }

        $this->merge([
            'slug' => $slug,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
