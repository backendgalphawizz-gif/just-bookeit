<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;
use App\Support\LocationResolver;
use Illuminate\Validation\Rule;

class CustomerRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $rules = AdminValidationRules::customer();

        if ($customer = $this->route('customer')) {
            $rules['status'] = ['required', Rule::in([$customer->status])];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'registered_at' => AdminValidationRules::normalizeMysqlTimestampDate(
                $this->input('registered_at')
            ),
        ]);
    }

    public function customerData(): array
    {
        $data = $this->safe()->except([
            'country_id', 'country_other',
            'state_id', 'state_other',
            'city_id', 'city_other',
            'profile_image',
        ]);

        if ($this->filled('country_id') || $this->filled('state_id') || $this->filled('city_id')
            || $this->filled('country_other') || $this->filled('state_other') || $this->filled('city_other')) {
            $data = array_merge($data, LocationResolver::resolve($this->all()));
        }

        return $data;
    }
}
