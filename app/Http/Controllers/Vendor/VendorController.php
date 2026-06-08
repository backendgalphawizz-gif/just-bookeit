<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Support\VendorValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class VendorController extends Controller
{
    protected function vendor()
    {
        return Auth::guard('vendor')->user();
    }

    protected function refreshVendorSession($vendor): void
    {
        $fresh = $vendor->fresh();
        if ($fresh) {
            Auth::guard('vendor')->setUser($fresh);
        }
    }

    /** @return array<string, mixed> */
    protected function validateVendor(Request $request, array $rules): array
    {
        return $request->validate($rules, VendorValidationRules::messages(), VendorValidationRules::attributes());
    }
}
