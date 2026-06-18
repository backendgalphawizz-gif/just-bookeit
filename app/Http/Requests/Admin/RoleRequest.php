<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\AdminValidationRules;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->resolvedSlug() === '') {
                $validator->errors()->add('name', 'Enter a role name that can be used to generate a slug.');
            }
        });
    }

    /** @return array<string, mixed> */
    public function roleData(): array
    {
        $data = $this->safe()->except(['permissions', 'slug']);

        $data['slug'] = $this->resolvedSlug();
        $data['is_active'] = $this->boolean('is_active', true);

        return $data;
    }

    protected function resolvedSlug(): string
    {
        $role = $this->route('role');
        $slug = trim($this->string('slug')->toString());

        if ($slug === '') {
            $slug = Str::slug($this->string('name')->toString(), '_');
        }

        if ($slug === '') {
            return '';
        }

        $base = $slug;
        $counter = 2;

        while (
            Role::query()
                ->where('slug', $slug)
                ->when($role, fn ($query) => $query->whereKeyNot($role->id))
                ->exists()
        ) {
            $slug = $base.'_'.$counter;
            $counter++;
        }

        return $slug;
    }
}
