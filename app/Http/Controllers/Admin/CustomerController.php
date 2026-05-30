<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Http\Requests\Admin\CustomerRequest;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'customers';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $customers = $this->applyDateRange(Customer::query(), $request, 'registered_at')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('mobile', 'like', $term)
                        ->orWhere('customer_code', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->string('city')))
            ->orderByDesc('registered_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $customer = Customer::query()->create([
            ...collect($data)->except(['profile_image'])->all(),
            'customer_code' => CodeGenerator::customerCode(),
            'is_verified' => $request->boolean('is_verified'),
            'registered_at' => $data['registered_at'] ?? now(),
        ]);

        $this->applyProfileImage($customer, $request);
        $customer->save();

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders' => fn ($q) => $q->latest()->limit(10), 'orders.vendor', 'orders.category']);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();

        $customer->fill(collect($data)->except(['profile_image'])->all());
        $customer->is_verified = $request->boolean('is_verified');
        $this->applyProfileImage($customer, $request);
        $customer->save();

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Cannot delete customer with existing orders.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function activate(Customer $customer): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $customer->update(['status' => 'active']);

        return back()->with('success', "Customer {$customer->name} activated.");
    }

    public function suspend(Customer $customer): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $customer->update(['status' => 'suspended']);

        return back()->with('success', "Customer {$customer->name} suspended.");
    }

    private function applyProfileImage(Customer $customer, CustomerRequest $request): void
    {
        if (! $request->hasFile('profile_image')) {
            return;
        }

        $customer->profile_image_path = StoresUploadedFiles::replace(
            $request->file('profile_image'),
            $customer->profile_image_path,
            'customers/profile-images'
        );
    }
}
