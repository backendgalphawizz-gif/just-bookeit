<?php

namespace App\Http\Controllers\Web;

use App\Models\Category;
use App\Models\PortfolioItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends WebController
{
    public function index(Request $request): View
    {
        $query = PortfolioItem::query()
            ->with(['vendor', 'category'])
            ->whereIn('status', ['approved', 'pending']);

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->integer('vendor'));
        }

        $items = $query->latest('id')->paginate(12)->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('web.catalog.index', compact('items', 'categories'));
    }

    public function show(PortfolioItem $item): View
    {
        $item->load(['vendor', 'category']);

        $related = PortfolioItem::query()
            ->where('vendor_id', $item->vendor_id)
            ->where('id', '!=', $item->id)
            ->whereIn('status', ['approved', 'pending'])
            ->limit(4)
            ->get();

        return view('web.catalog.show', compact('item', 'related'));
    }
}
