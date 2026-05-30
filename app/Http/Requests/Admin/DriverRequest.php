<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class DriverRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $driverId = $this->route('driver')?->id;

        return [
            'name' => ['required', 'string', 'max:255', 'regex:'.AdminValidationRules::REGEX_TITLE],
            'mobile' => ['required', 'string', 'regex:'.AdminValidationRules::REGEX_PHONE, 'unique:drivers,mobile,'.$driverId],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100', 'regex:'.AdminValidationRules::REGEX_CITY],
            'status' => ['required', 'in:pending,active,suspended,rejected'],
            'is_verified' => ['nullable', 'boolean'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'aadhar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'aadhar_front' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'aadhar_back' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'driving_licence' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'is_verified' => $this->boolean('is_verified'),
        ]);
    }
}
