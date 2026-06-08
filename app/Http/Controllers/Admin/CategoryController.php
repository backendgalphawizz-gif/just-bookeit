<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
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

        $categories = $this->applyDateRange(Category::query(), $request)
            ->with('parent')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $parents = Category::query()->where('type', 'main')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'parents'));
    }

    public function create(): View
    {
        $parents = Category::query()->where('type', 'main')->orderBy('name')->get();

        return view('admin.categories.create', compact('parents'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);
        $data['slug'] = Str::slug($data['name']);

        if ($request->hasFile('image')) {
            $data['image_path'] = StoresUploadedFiles::store($request->file('image'), 'categories');
        }

        Category::query()->create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $parents = Category::query()->where('type', 'main')->where('id', '!=', $category->id)->orderBy('name')->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);
        $data['slug'] = Str::slug($data['name']);

        $data['image_path'] = StoresUploadedFiles::replace(
            $request->file('image'),
            $category->image_path,
            'categories'
        );

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->orders()->exists()) {
            return back()->with('error', 'Cannot delete category used in orders.');
        }

        StoresUploadedFiles::delete($category->image_path);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
