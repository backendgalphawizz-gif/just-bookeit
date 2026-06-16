<?php

namespace App\Http\Controllers\Web;

use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends WebController
{
    public function index(): View
    {
        $faqs = Faq::query()
            ->forAudience(Faq::AUDIENCE_USER)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('web.faq.index', compact('faqs'));
    }
}
