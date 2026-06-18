<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RecordsAccountStatusHistory;
use App\Models\AccountStatusHistory;
use App\Models\Customer;
use App\Http\Requests\Admin\CustomerRequest;
use App\Support\AdminCityScope;
use App\Support\AdminValidationRules;
use App\Support\AppliesListDateFilter;
use App\Support\CodeGenerator;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends AdminController
{
    use AppliesListDateFilter;
    use RecordsAccountStatusHistory;

    protected string $permissionModule = 'customers';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $customers = AdminCityScope::scopeCustomers(
            $this->applyDateRange(Customer::query(), $request, 'registered_at')
        )
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
            ->when($request->filled('registered_on'), fn ($q) => $q->whereDate('registered_at', $request->date('registered_on')))
            ->newestFirst('registered_at')
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
        $data = $request->customerData();

        $customer = Customer::query()->create([
            ...$data,
            'customer_code' => CodeGenerator::customerCode(),
            'is_verified' => $request->boolean('is_verified'),
            'registered_at' => $data['registered_at'] ?? now(),
        ]);

        $this->applyProfileImage($customer, $request);
        $customer->save();

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Request $request, Customer $customer): View
    {
        $customer->load([
            'statusHistories' => fn ($q) => $q->with('admin')->orderByDesc('created_at'),
        ]);

        $orders = $customer->orders()
            ->with(['vendor', 'category'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.show', compact('customer', 'orders'));
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->customerData();

        $previousStatus = $customer->status;

        $attributes = collect($data);

        if (! filled($attributes->get('registered_at'))) {
            $attributes = $attributes->except(['registered_at']);
        }

        $customer->fill($attributes->all());
        $customer->is_verified = $request->boolean('is_verified');

        if (! $customer->registered_at || $customer->registered_at->year < 1970) {
            $customer->registered_at = $customer->created_at ?? now();
        }

        if ($customer->status === 'active') {
            $customer->rejection_reason = null;
        }

        $this->applyProfileImage($customer, $request);
        $customer->save();

        if ($previousStatus !== $customer->status) {
            $this->recordAccountStatusHistory(
                $customer,
                AccountStatusHistory::ACTION_STATUS_UPDATE,
                $previousStatus,
                $customer->status,
            );
        }

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->orders()->exists()) {
            return back()->with('error', 'This customer has orders on record and cannot be deleted.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function activate(Customer $customer): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $previousStatus = $customer->status;

        $customer->update([
            'status' => 'active',
            'rejection_reason' => null,
        ]);

        $this->recordAccountStatusHistory(
            $customer,
            AccountStatusHistory::ACTION_ACTIVATE,
            $previousStatus,
            'active',
        );

        return back()->with('success', "Customer {$customer->name} activated.");
    }

    public function inactivate(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        if ($customer->hasActiveOrders()) {
            return back()->with('error', 'This customer has active orders and cannot be inactivated right now.');
        }

        $data = $request->validate(
            AdminValidationRules::accountRejection(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        $previousStatus = $customer->status;

        $customer->update([
            'status' => 'inactive',
            'rejection_reason' => $data['rejection_reason'],
        ]);

        $this->recordAccountStatusHistory(
            $customer,
            AccountStatusHistory::ACTION_INACTIVATE,
            $previousStatus,
            'inactive',
            $data['rejection_reason'],
        );

        return back()->with('success', "Customer {$customer->name} inactivated.");
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
