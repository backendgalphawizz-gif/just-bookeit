<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminValidationRules;

class VendorRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return AdminValidationRules::vendor();
    }

    public function vendorData(): array
    {
        $data = $this->safe()->except(['categories_text']);
        $data['categories'] = array_filter(array_map('trim', explode(',', $this->input('categories_text', ''))));
        $data['rating'] = $data['rating'] ?? 0;
        $data['orders_completed'] = $data['orders_completed'] ?? 0;
        $data['earnings'] = $data['earnings'] ?? 0;

        return $data;
    }
}
