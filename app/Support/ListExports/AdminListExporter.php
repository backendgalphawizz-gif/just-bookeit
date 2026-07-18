<?php

namespace App\Support\ListExports;

use App\Models\Admin;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Dispute;
use App\Models\Driver;
use App\Models\Faq;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Refund;
use App\Models\Role;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Services\Export\ListExportService;
use App\Support\AdminAccountStatus;
use App\Support\AdminCityScope;
use App\Support\AdminListOrder;
use App\Support\AppliesListDateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminListExporter
{
    use AppliesListDateFilter;

    public const MODULES = [
        'customers',
        'vendors',
        'drivers',
        'categories',
        'orders',
        'refunds',
        'disputes',
        'payments',
        'payouts',
        'portfolio',
        'banners',
        'faqs',
        'notifications',
        'admins',
        'roles',
    ];

    public function __construct(
        protected ListExportService $exporter,
    ) {}

    public function export(Request $request, string $module): StreamedResponse|Response
    {
        abort_unless(in_array($module, self::MODULES, true), 404);

        $admin = Auth::guard('admin')->user();
        $permissionModule = $request->filled('export_permission')
            ? $request->string('export_permission')->toString()
            : (in_array($module, ['admins', 'roles'], true) ? 'admins' : $module);
        abort_unless($admin?->hasPermission($permissionModule, 'export'), 403);

        $definition = $this->definition($module);

        if ($definition['validate_dates'] ?? true) {
            $this->validateListDateRange($request);
        }

        return $this->exporter->respond(
            $request,
            $definition['query']($request),
            $definition['headers'],
            $definition['map'],
            $definition['basename'],
            $definition['title'],
        );
    }

    protected function definition(string $module): array
    {
        return match ($module) {
            'customers' => [
                'title' => 'Customers Export',
                'basename' => 'customers',
                'headers' => ['Code', 'Name', 'Email', 'Mobile', 'City', 'Status', 'Registered', 'Total Orders'],
                'query' => fn (Request $request) => AdminCityScope::scopeCustomers(
                    $this->applyDateRange(Customer::query(), $request, 'registered_at')
                )
                    ->when($request->filled('search'), function (Builder $q) use ($request) {
                        $term = '%'.$request->string('search').'%';
                        $q->where(function (Builder $q) use ($term) {
                            $q->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('mobile', 'like', $term)
                                ->orWhere('customer_code', 'like', $term);
                        });
                    })
                    ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
                    ->when($request->filled('city'), fn (Builder $q) => $q->where('city', $request->string('city')))
                    ->when($request->filled('registered_on'), fn (Builder $q) => $q->whereDate('registered_at', $request->date('registered_on')))
                    ->newestFirst('created_at'),
                'map' => fn (Customer $customer) => [
                    $customer->customer_code,
                    $customer->name,
                    $customer->email ?? '',
                    $customer->mobile,
                    $customer->city ?? '',
                    AdminAccountStatus::labelFor($customer->status),
                    $customer->registered_at?->format('Y-m-d') ?? '',
                    $customer->total_orders,
                ],
            ],
            'vendors' => [
                'title' => 'Vendors Export',
                'basename' => 'vendors',
                'headers' => ['Code', 'Brand', 'Owner', 'Email', 'Mobile', 'Business Mobile', 'City', 'Status', 'Rating', 'Orders Completed', 'Digital Wallet', 'Actual Wallet', 'Earnings'],
                'query' => fn (Request $request) => AdminCityScope::scopeVendors(
                    $this->applyDateRange(Vendor::query(), $request)
                )
                    ->when($request->filled('search'), function (Builder $q) use ($request) {
                        $term = '%'.$request->string('search').'%';
                        $q->where(function (Builder $q) use ($term) {
                            $q->where('brand_name', 'like', $term)
                                ->orWhere('owner_name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('mobile', 'like', $term)
                                ->orWhere('business_mobile', 'like', $term)
                                ->orWhere('vendor_code', 'like', $term);
                        });
                    })
                    ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
                    ->when($request->filled('city'), fn (Builder $q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
                    ->newestFirst(),
                'map' => fn (Vendor $vendor) => [
                    $vendor->vendor_code,
                    $vendor->brand_name,
                    $vendor->owner_name,
                    $vendor->email,
                    $vendor->mobile ?? '',
                    $vendor->business_mobile ?? '',
                    $vendor->city ?? '',
                    AdminAccountStatus::labelFor($vendor->status),
                    $vendor->rating,
                    $vendor->orders_completed,
                    $vendor->digital_wallet_balance,
                    $vendor->wallet_balance,
                    $vendor->earnings,
                ],
            ],
            'drivers' => [
                'title' => 'Drivers Export',
                'basename' => 'drivers',
                'headers' => ['Code', 'Name', 'Mobile', 'Email', 'City', 'Vehicle', 'Status', 'Joined'],
                'query' => fn (Request $request) => AdminCityScope::scopeDrivers(
                    $this->applyDateRange(Driver::query(), $request)
                )
                    ->when($request->filled('search'), function (Builder $q) use ($request) {
                        $term = '%'.$request->string('search').'%';
                        $q->where(function (Builder $q) use ($term) {
                            $q->where('name', 'like', $term)
                                ->orWhere('mobile', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('driver_code', 'like', $term);
                        });
                    })
                    ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
                    ->newestFirst(),
                'map' => fn (Driver $driver) => [
                    $driver->driver_code,
                    $driver->name,
                    $driver->mobile,
                    $driver->email ?? '',
                    $driver->city ?? '',
                    $driver->vehicle_no ?? '',
                    AdminAccountStatus::labelFor($driver->status),
                    $driver->created_at?->format('Y-m-d') ?? '',
                ],
            ],
            'categories' => [
                'title' => 'Categories Export',
                'basename' => 'categories',
                'headers' => ['Name', 'Slug', 'Type', 'Parent', 'Service Type', 'Active', 'Sort Order', 'Created'],
                'query' => fn (Request $request) => AdminListOrder::newestFirst(
                    $this->applyDateRange(Category::query(), $request)
                        ->with(['parent', 'serviceCategory'])
                        ->when($request->string('type')->toString() === 'catalog', fn (Builder $q) => $q->whereIn('type', [Category::TYPE_MAIN, Category::TYPE_SUB]))
                        ->when($request->filled('type') && $request->string('type')->toString() !== 'catalog', fn (Builder $q) => $q->where('type', $request->string('type')))
                        ->when($request->filled('search'), fn (Builder $q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
                        ->when($request->filled('parent_id'), fn (Builder $q) => $q->where('parent_id', $request->integer('parent_id')))
                        ->when($request->filled('service_category_id'), fn (Builder $q) => $q->where('service_category_id', $request->integer('service_category_id')))
                        ->when($request->filled('active'), fn (Builder $q) => $q->where('is_active', $request->boolean('active')))
                ),
                'map' => fn (Category $category) => [
                    $category->name,
                    $category->slug,
                    $category->type,
                    $category->parent?->name ?? '',
                    $category->serviceCategory?->name ?? '',
                    $category->is_active ? 'Yes' : 'No',
                    $category->sort_order,
                    $category->created_at?->format('Y-m-d') ?? '',
                ],
            ],
            'orders' => [
                'title' => 'Orders Export',
                'basename' => 'orders',
                'headers' => ['Order', 'Customer', 'Vendor', 'Category', 'Type', 'Amount', 'Payment', 'Status', 'Date'],
                'query' => fn (Request $request) => AdminCityScope::scopeOrders(
                    $this->applyDateRange(Order::query(), $request)
                )
                    ->with(['customer', 'vendor', 'category'])
                    ->when($request->filled('search'), fn (Builder $q) => $q->where('order_number', 'like', '%'.$request->string('search').'%'))
                    ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
                    ->when($request->filled('payment_status'), fn (Builder $q) => $q->where('payment_status', $request->string('payment_status')))
                    ->when($request->filled('vendor_id'), fn (Builder $q) => $q->where('vendor_id', $request->integer('vendor_id')))
                    ->when($request->filled('category_id'), fn (Builder $q) => $q->where('category_id', $request->integer('category_id')))
                    ->latestIdFirst(),
                'map' => fn (Order $order) => [
                    $order->order_number,
                    $order->customer?->name ?? '',
                    $order->vendor?->brand_name ?? '',
                    $order->category?->name ?? '',
                    $order->order_type === 'rental' ? 'Rental' : 'Sale',
                    $order->amount,
                    $order->payment_status,
                    $order->statusLabel(),
                    $order->created_at?->format('Y-m-d H:i') ?? '',
                ],
            ],
            'refunds' => [
                'title' => 'Refunds Export',
                'basename' => 'refunds',
                'headers' => ['Order', 'Customer', 'Vendor', 'Amount', 'Reason', 'Status', 'Requested'],
                'query' => fn (Request $request) => $this->applyDateRange(Refund::query(), $request)
                    ->with(['customer', 'order.vendor'])
                    ->when(
                        $request->get('status') === '_open_' || $request->boolean('open_only'),
                        fn (Builder $q) => $q->whereIn('status', Refund::OPEN_STATUSES)
                    )
                    ->when(
                        $request->filled('status') && $request->get('status') !== '_open_',
                        fn (Builder $q) => $q->where('status', $request->string('status'))
                    )
                    ->when($request->filled('vendor_id'), fn (Builder $q) => $q->whereHas(
                        'order',
                        fn (Builder $order) => $order->where('vendor_id', $request->integer('vendor_id'))
                    ))
                    ->when($request->filled('customer'), function (Builder $q) use ($request) {
                        $term = '%'.$request->string('customer').'%';
                        $q->whereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $term));
                    })
                    ->when($request->filled('order_id'), function (Builder $q) use ($request) {
                        $term = '%'.$request->string('order_id').'%';
                        $q->whereHas('order', fn (Builder $order) => $order->where('order_number', 'like', $term));
                    })
                    ->newestFirst(),
                'map' => fn (Refund $refund) => [
                    $refund->order?->order_number ?? '',
                    $refund->customer?->name ?? '',
                    $refund->order?->vendor?->brand_name ?? '',
                    $refund->amount,
                    $refund->reason ?? '',
                    $refund->status,
                    $refund->created_at?->format('Y-m-d H:i') ?? '',
                ],
            ],
            'disputes' => [
                'title' => 'Disputes Export',
                'basename' => 'disputes',
                'headers' => ['Category', 'Order', 'Subject', 'Raised By', 'Status', 'Created'],
                'query' => fn (Request $request) => $this->applyDateRange(Dispute::query(), $request)
                    ->with(['order.customer', 'order.vendor', 'category'])
                    ->when($request->filled('category'), fn (Builder $q) => $q->where('category_id', $request->integer('category')))
                    ->when(
                        $request->filled('raised_by') && in_array($request->string('raised_by')->toString(), ['customer', 'vendor'], true),
                        fn (Builder $q) => $q->where('raised_by', $request->string('raised_by'))
                    )
                    ->when($request->filled('search'), function (Builder $q) use ($request) {
                        $term = '%'.$request->string('search').'%';
                        $q->whereHas('order', fn (Builder $order) => $order->where('order_number', 'like', $term));
                    })
                    ->when(
                        $request->get('status') === '_open_' || $request->boolean('open_only'),
                        fn (Builder $q) => $q->whereIn('status', Dispute::OPEN_STATUSES)
                    )
                    ->when(
                        $request->filled('status') && $request->get('status') !== '_open_',
                        fn (Builder $q) => $q->where('status', $request->string('status'))
                    )
                    ->newestFirst(),
                'map' => fn (Dispute $dispute) => [
                    $dispute->category?->name ?? $dispute->order?->category?->name ?? '',
                    $dispute->order?->order_number ?? '',
                    $dispute->subject,
                    ucfirst($dispute->raised_by),
                    $dispute->status,
                    $dispute->created_at?->format('Y-m-d H:i') ?? '',
                ],
            ],
            'payments' => [
                'title' => 'Payments Export',
                'basename' => 'payments',
                'headers' => ['Order', 'Customer', 'Vendor', 'Amount', 'Payment Status', 'Order Status', 'Date'],
                'query' => fn (Request $request) => $this->applyDateRange(Order::query(), $request)
                    ->with(['customer', 'vendor'])
                    ->when($request->filled('payment_status'), fn (Builder $q) => $q->where('payment_status', $request->string('payment_status')))
                    ->when($request->filled('search'), fn (Builder $q) => $q->where('order_number', 'like', '%'.$request->string('search').'%'))
                    ->latestIdFirst(),
                'map' => fn (Order $order) => [
                    $order->order_number,
                    $order->customer?->name ?? '',
                    $order->vendor?->brand_name ?? '',
                    $order->amount,
                    $order->payment_status,
                    $order->statusLabel(),
                    $order->created_at?->format('Y-m-d H:i') ?? '',
                ],
            ],
            'payouts' => [
                'title' => 'Payouts Export',
                'basename' => 'payouts',
                'headers' => ['Code', 'Vendor', 'Gross', 'Commission', 'Net', 'Status', 'Paid At', 'Created'],
                'query' => fn (Request $request) => $this->applyDateRange(VendorPayout::query(), $request)
                    ->with('vendor')
                    ->when(
                        $request->get('status') === '_open_' || $request->boolean('open_only'),
                        fn (Builder $q) => $q->whereIn('status', VendorPayout::OPEN_STATUSES)
                    )
                    ->when(
                        $request->filled('status') && $request->get('status') !== '_open_',
                        fn (Builder $q) => $q->where('status', $request->string('status'))
                    )
                    ->when($request->filled('vendor_id'), fn (Builder $q) => $q->where('vendor_id', $request->integer('vendor_id')))
                    ->when($request->filled('search'), function (Builder $q) use ($request) {
                        $term = '%'.$request->string('search').'%';
                        $q->where(function (Builder $q) use ($term) {
                            $q->where('payout_code', 'like', $term)
                                ->orWhere('reference', 'like', $term)
                                ->orWhereHas('vendor', fn (Builder $v) => $v->where('brand_name', 'like', $term));
                        });
                    })
                    ->newestFirst(),
                'map' => fn (VendorPayout $payout) => [
                    $payout->payout_code,
                    $payout->vendor?->brand_name ?? '',
                    $payout->gross_amount,
                    $payout->commission_amount,
                    $payout->net_amount,
                    $payout->status,
                    $payout->paid_at?->format('Y-m-d H:i') ?? '',
                    $payout->created_at?->format('Y-m-d H:i') ?? '',
                ],
            ],
            'portfolio' => [
                'title' => 'Products Export',
                'basename' => 'portfolio',
                'headers' => ['Title', 'Vendor', 'Category', 'Status', 'Submitted'],
                'query' => function (Request $request) {
                    $typeSlug = $request->string('type')->toString();
                    $typeCategoryId = $typeSlug !== ''
                        ? Category::query()
                            ->service()
                            ->where('slug', $typeSlug)
                            ->whereIn('slug', ['fashion-designer', 'rented-dress', 'rented-jewellery'])
                            ->value('id')
                        : null;

                    return $this->applyDateRange(PortfolioItem::query(), $request)
                        ->with(['vendor', 'category'])
                        ->when($typeCategoryId, fn (Builder $q) => $q->where('category_id', $typeCategoryId))
                        ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
                        ->when($request->filled('vendor_id'), fn (Builder $q) => $q->where('vendor_id', $request->integer('vendor_id')))
                        ->when($request->filled('search'), function (Builder $q) use ($request) {
                            $term = '%'.$request->string('search').'%';
                            $q->where(function (Builder $q) use ($term) {
                                $q->where('title', 'like', $term)
                                    ->orWhereHas('vendor', fn (Builder $v) => $v->where('brand_name', 'like', $term));
                            });
                        })
                        ->newestFirst();
                },
                'map' => fn (PortfolioItem $item) => [
                    $item->title,
                    $item->vendor?->brand_name ?? '',
                    $item->category?->name ?? '',
                    $item->status,
                    $item->created_at?->format('Y-m-d H:i') ?? '',
                ],
            ],
            'banners' => [
                'title' => 'Banners Export',
                'basename' => 'banners',
                'headers' => ['Audience', 'Title', 'Subtitle', 'Active', 'Starts', 'Ends', 'Created'],
                'query' => fn (Request $request) => $this->applyDateRange(Banner::query(), $request)
                    ->when($request->filled('audience'), fn (Builder $q) => $q->where('audience', $request->string('audience')))
                    ->when($request->filled('search'), fn (Builder $q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
                    ->when($request->filled('active'), fn (Builder $q) => $q->where('is_active', $request->boolean('active')))
                    ->newestFirst(),
                'map' => fn (Banner $banner) => [
                    Banner::audienceLabel($banner->audience),
                    $banner->title,
                    $banner->subtitle ?? '',
                    $banner->is_active ? 'Yes' : 'No',
                    $banner->starts_at?->format('Y-m-d') ?? '',
                    $banner->ends_at?->format('Y-m-d') ?? '',
                    $banner->created_at?->format('Y-m-d') ?? '',
                ],
            ],
            'faqs' => [
                'title' => 'FAQs Export',
                'basename' => 'faqs',
                'headers' => ['Audience', 'Question', 'Answer', 'Active', 'Sort Order'],
                'validate_dates' => false,
                'query' => function (Request $request) {
                    $audience = $request->string('audience', Faq::AUDIENCE_USER)->toString();
                    if (! in_array($audience, Faq::AUDIENCES, true)) {
                        $audience = Faq::AUDIENCE_USER;
                    }

                    return AdminListOrder::newestFirst(
                        Faq::query()
                            ->forAudience($audience)
                            ->when($request->filled('search'), function (Builder $q) use ($request) {
                                $term = '%'.$request->string('search').'%';
                                $q->where(function (Builder $q) use ($term) {
                                    $q->where('question', 'like', $term)
                                        ->orWhere('answer', 'like', $term);
                                });
                            })
                    );
                },
                'map' => fn (Faq $faq) => [
                    $faq->audience,
                    $faq->question,
                    strip_tags((string) $faq->answer),
                    $faq->is_active ? 'Yes' : 'No',
                    $faq->sort_order,
                ],
            ],
            'notifications' => [
                'title' => 'Notifications Export',
                'basename' => 'notifications',
                'headers' => ['Title', 'Channel', 'Audience', 'Status', 'Sent By', 'Sent At'],
                'query' => fn (Request $request) => $this->applyDateRange(NotificationLog::query(), $request)
                    ->with('admin')
                    ->newestFirst(),
                'map' => fn (NotificationLog $log) => [
                    $log->title,
                    $log->channel,
                    $log->audience,
                    $log->status,
                    $log->admin?->name ?? '',
                    $log->created_at?->format('Y-m-d H:i') ?? '',
                ],
            ],
            'admins' => [
                'title' => 'Admin Users Export',
                'basename' => 'admin-users',
                'headers' => ['Name', 'Email', 'Role', 'City', 'Status', 'Last Login'],
                'validate_dates' => false,
                'query' => fn (Request $request) => AdminListOrder::newestFirst(
                    Admin::query()->with(['role', 'assignedCities'])
                ),
                'map' => fn (Admin $admin) => [
                    $admin->name,
                    $admin->email,
                    $admin->role?->name ?? '',
                    $admin->assignedCities->pluck('city')->join(', ') ?: '',
                    $admin->isActive() ? 'active' : 'inactive',
                    $admin->last_login_at?->format('Y-m-d H:i') ?? '',
                ],
            ],
            'roles' => [
                'title' => 'Roles Export',
                'basename' => 'roles',
                'headers' => ['Name', 'Slug', 'Admins', 'Active', 'Description'],
                'validate_dates' => false,
                'query' => fn (Request $request) => AdminListOrder::newestFirst(
                    Role::query()->withCount('admins')
                ),
                'map' => fn (Role $role) => [
                    $role->name,
                    $role->slug,
                    $role->admins_count,
                    $role->is_active ? 'Yes' : 'No',
                    $role->description ?? '',
                ],
            ],
            default => abort(404),
        };
    }
}
