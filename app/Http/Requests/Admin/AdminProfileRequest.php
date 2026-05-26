<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class AdminProfileRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $adminId = auth('admin')->id();

        return [
            'name' => ['required', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email,'.$adminId],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'max:128'],
        ];
    }
}
