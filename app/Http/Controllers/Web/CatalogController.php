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
            ->with(['vendor', 'category', 'subcategory.parent'])
            ->whereIn('status', ['approved', 'pending']);

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('subcategory')) {
            $query->where('subcategory_id', $request->integer('subcategory'));
        } elseif ($request->filled('category')) {
            $mainCategoryId = $request->integer('category');
            $query->whereHas('subcategory', fn ($sub) => $sub->where('parent_id', $mainCategoryId));
        }

        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->integer('vendor'));
        }

        $items = $query->latest('id')->paginate(12)->withQueryString();

        $mainCategories = Category::query()
            ->active()
            ->main()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subcategories = Category::query()
            ->active()
            ->sub()
            ->when($request->filled('category'), fn ($q) => $q->where('parent_id', $request->integer('category')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('web.catalog.index', compact('items', 'mainCategories', 'subcategories'));
    }

    public function show(PortfolioItem $item): View
    {
        $item->load(['vendor', 'category', 'subcategory.parent', 'images']);

        $related = PortfolioItem::query()
            ->where('vendor_id', $item->vendor_id)
            ->where('id', '!=', $item->id)
            ->whereIn('status', ['approved', 'pending'])
            ->limit(4)
            ->get();

        return view('web.catalog.show', compact('item', 'related'));
    }
}
