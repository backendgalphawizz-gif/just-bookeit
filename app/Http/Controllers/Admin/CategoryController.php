<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Vendor;
use App\Http\Requests\Admin\CategoryRequest;
use App\Support\AppliesListDateFilter;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'categories';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);
        $type = $this->resolveType($request->string('type', Category::TYPE_MAIN)->toString());

        $categories = $this->applyDateRange(
            Category::query()->where('type', $type),
            $request
        )
            ->with('parent')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'type'));
    }

    public function create(Request $request): View
    {
        $type = $this->resolveType($request->string('type', Category::TYPE_MAIN)->toString());
        $parents = Category::query()->where('type', Category::TYPE_MAIN)->orderBy('name')->get();

        return view('admin.categories.create', compact('parents', 'type'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);
        $data['slug'] = Str::slug($data['name']);
        $data['parent_id'] = $data['type'] === Category::TYPE_MAIN ? null : ($data['parent_id'] ?? null);

        if ($request->hasFile('image')) {
            $data['image_path'] = StoresUploadedFiles::store($request->file('image'), 'categories');
        }

        Category::query()->create($data);

        return redirect()
            ->route('admin.categories.index', ['type' => $data['type']])
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $type = $category->type;
        $parents = Category::query()
            ->where('type', Category::TYPE_MAIN)
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parents', 'type'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);
        $data['slug'] = Str::slug($data['name']);
        $data['parent_id'] = $data['type'] === Category::TYPE_MAIN ? null : ($data['parent_id'] ?? null);

        $data['image_path'] = StoresUploadedFiles::replace(
            $request->file('image'),
            $category->image_path,
            'categories'
        );

        $category->update($data);

        return redirect()
            ->route('admin.categories.index', ['type' => $category->type])
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->orders()->exists()) {
            return back()->with('error', 'Cannot delete category used in orders.');
        }

        if (Vendor::query()->whereJsonContains('categories', $category->name)->exists()) {
            return back()->with('error', 'Cannot delete category assigned to vendors.');
        }

        StoresUploadedFiles::delete($category->image_path);
        $type = $category->type;
        $category->delete();

        return redirect()
            ->route('admin.categories.index', ['type' => $type])
            ->with('success', 'Category deleted successfully.');
    }

    protected function resolveType(string $type): string
    {
        return in_array($type, Category::TYPES, true) ? $type : Category::TYPE_MAIN;
    }
}
