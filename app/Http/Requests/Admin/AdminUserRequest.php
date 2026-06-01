<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\AdminValidationRules;
use Illuminate\Validation\Validator;

class AdminUserRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::adminUser($this->route('admin')?->id);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $role = Role::query()->find($this->input('role_id'));

            if (! $role || $role->slug === 'super_admin') {
                return;
            }

            if (! filled(trim((string) $this->input('city')))) {
                $validator->errors()->add('city', 'Select a city for this sub-admin.');
            }
        });
    }

    public function cityName(): ?string
    {
        $role = Role::query()->find($this->input('role_id'));

        if ($role?->slug === 'super_admin') {
            return null;
        }

        $city = trim((string) $this->input('city'));

        return filled($city) ? $city : null;
    }
}
