<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Http\Requests\Admin\CustomerRequest;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
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

        Customer::query()->create([
            ...$data,
            'customer_code' => CodeGenerator::customerCode(),
            'is_verified' => $request->boolean('is_verified'),
            'registered_at' => $data['registered_at'] ?? now(),
        ]);

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

        $customer->update([
            ...$data,
            'is_verified' => $request->boolean('is_verified'),
        ]);

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
}
