<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Http\Requests\Admin\CategoryRequest;
use App\Support\AppliesListDateFilter;
use App\Support\CategorySlugResolver;
use App\Support\StoresUploadedFiles;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'categories';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);
        $type = $this->resolveIndexType($request->string('type', 'catalog')->toString());

        if ($type === Category::TYPE_SERVICE) {
            return view('admin.categories.index', $this->serviceCategoriesViewData($request));
        }

        return view('admin.categories.index', $this->catalogCategoriesViewData($request));
    }

    public function create(Request $request): View
    {
        $type = $this->resolveType($request->string('type', Category::TYPE_MAIN)->toString());
        $parents = Category::query()->main()->orderBy('name')->get();

        return view('admin.categories.create', compact('parents', 'type'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);
        $data['parent_id'] = $this->resolveParentId($data['type'], $data['parent_id'] ?? null);
        $data['slug'] = CategorySlugResolver::forCategory(
            $data['name'],
            $data['type'],
            $data['parent_id'],
        );

        if ($request->hasFile('image')) {
            $data['image_path'] = StoresUploadedFiles::store($request->file('image'), 'categories');
        }

        try {
            Category::query()->create($data);
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withInput()
                ->with('error', 'A category with a similar name already exists. Try a different name.');
        }

        return redirect()
            ->route('admin.categories.index', ['type' => $this->indexTypeForCategory($data['type'])])
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $type = $category->type;
        $parents = Category::query()
            ->main()
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parents', 'type'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);
        $data['parent_id'] = $this->resolveParentId($data['type'], $data['parent_id'] ?? null);
        $data['slug'] = CategorySlugResolver::forCategory(
            $data['name'],
            $data['type'],
            $data['parent_id'],
            $category->id,
        );

        $data['image_path'] = StoresUploadedFiles::replace(
            $request->file('image'),
            $category->image_path,
            'categories'
        );

        try {
            $category->update($data);
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withInput()
                ->with('error', 'A category with a similar name already exists. Try a different name.');
        }

        return redirect()
            ->route('admin.categories.index', ['type' => $this->indexTypeForCategory($category->type)])
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->orders()->exists()) {
            return back()->with('error', 'This category is linked to orders and cannot be deleted.');
        }

        if ($category->portfolioItems()->exists()) {
            return back()->with('error', 'This sub-category is linked to products and cannot be deleted.');
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'Remove child categories before deleting this category.');
        }

        if (PortfolioItem::query()->where('category_id', $category->id)->exists()) {
            return back()->with('error', 'This category is linked to products and cannot be deleted.');
        }

        if (Vendor::query()->whereJsonContains('categories', $category->name)->exists()) {
            return back()->with('error', 'This category is assigned to vendors and cannot be deleted.');
        }

        StoresUploadedFiles::delete($category->image_path);
        $indexType = $this->indexTypeForCategory($category->type);
        $category->delete();

        return redirect()
            ->route('admin.categories.index', ['type' => $indexType])
            ->with('success', 'Category deleted successfully.');
    }

    protected function resolveType(string $type): string
    {
        return in_array($type, Category::TYPES, true) ? $type : Category::TYPE_MAIN;
    }

    protected function resolveIndexType(string $type): string
    {
        if ($type === Category::TYPE_SERVICE) {
            return Category::TYPE_SERVICE;
        }

        if (in_array($type, ['catalog', Category::TYPE_MAIN, Category::TYPE_SUB], true)) {
            return 'catalog';
        }

        return 'catalog';
    }

    protected function indexTypeForCategory(string $type): string
    {
        return $type === Category::TYPE_SERVICE ? Category::TYPE_SERVICE : 'catalog';
    }

    /** @return array<string, mixed> */
    protected function serviceCategoriesViewData(Request $request): array
    {
        $categories = $this->applyDateRange(
            Category::query()->where('type', Category::TYPE_SERVICE),
            $request
        )
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return [
            'type' => Category::TYPE_SERVICE,
            'categories' => $categories,
            'mainCategories' => collect(),
            'subcategoryTotal' => 0,
        ];
    }

    /** @return array<string, mixed> */
    protected function catalogCategoriesViewData(Request $request): array
    {
        $search = $request->filled('search') ? $request->string('search')->toString() : null;
        $activeFilter = $request->filled('active') ? $request->boolean('active') : null;

        $categories = $this->applyDateRange(Category::query()->main(), $request)
            ->when($request->filled('parent_id'), fn ($q) => $q->where('id', $request->integer('parent_id')))
            ->when($search, function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhereHas('subcategories', fn ($sub) => $sub->where('name', 'like', $term));
                });
            })
            ->when($activeFilter !== null, fn ($q) => $q->where('is_active', $activeFilter))
            ->with(['subcategories' => function ($q) use ($activeFilter) {
                $q->when($activeFilter !== null, fn ($sub) => $sub->where('is_active', $activeFilter))
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $subcategoryTotal = Category::query()
            ->sub()
            ->when($request->filled('parent_id'), fn ($q) => $q->where('parent_id', $request->integer('parent_id')))
            ->when($search, fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($activeFilter !== null, fn ($q) => $q->where('is_active', $activeFilter))
            ->count();

        return [
            'type' => 'catalog',
            'categories' => $categories,
            'mainCategories' => Category::query()->main()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'subcategoryTotal' => $subcategoryTotal,
        ];
    }

    protected function resolveParentId(string $type, mixed $parentId): ?int
    {
        if ($type === Category::TYPE_MAIN) {
            return null;
        }

        if ($type === Category::TYPE_SUB) {
            return $parentId ? (int) $parentId : null;
        }

        return null;
    }
}
