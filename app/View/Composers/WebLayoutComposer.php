<?php

namespace App\View\Composers;

use App\Models\Banner;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebLayoutComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'webCustomer' => Auth::guard('customer')->user(),
            'webNavCategories' => Category::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(8)
                ->get(),
            'webActiveBanner' => Banner::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->latest('id')
                ->first(),
        ]);
    }
}
