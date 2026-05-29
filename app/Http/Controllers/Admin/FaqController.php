<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends AdminController
{
    protected string $permissionModule = 'faqs';

    public function index(Request $request): View
    {
        $audience = $this->resolveAudience($request->string('audience', Faq::AUDIENCE_USER)->toString());

        $faqs = Faq::query()
            ->forAudience($audience)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(function ($query) use ($term) {
                    $query->where('question', 'like', $term)
                        ->orWhere('answer', 'like', $term);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.faqs.index', compact('faqs', 'audience'));
    }

    public function create(Request $request): View
    {
        $audience = $this->resolveAudience($request->string('audience', Faq::AUDIENCE_USER)->toString());

        return view('admin.faqs.create', compact('audience'));
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        Faq::query()->create($request->validated());

        return redirect()
            ->route('admin.faqs.index', ['audience' => $request->string('audience')])
            ->with('success', 'FAQ created successfully.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return redirect()
            ->route('admin.faqs.index', ['audience' => $faq->audience])
            ->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $audience = $faq->audience;
        $faq->delete();

        return redirect()
            ->route('admin.faqs.index', ['audience' => $audience])
            ->with('success', 'FAQ deleted successfully.');
    }

    protected function resolveAudience(string $audience): string
    {
        return in_array($audience, Faq::AUDIENCES, true) ? $audience : Faq::AUDIENCE_USER;
    }
}
