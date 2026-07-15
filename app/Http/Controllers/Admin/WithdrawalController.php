<?php

namespace App\Http\Controllers\Admin;

use App\Models\Vendor;
use App\Models\VendorWithdrawalRequest;
use App\Services\Vendor\VendorWalletService;
use App\Support\AppliesListDateFilter;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class WithdrawalController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'payouts';

    public function __construct(
        protected VendorWalletService $wallet
    ) {}

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $withdrawals = $this->applyDateRange(VendorWithdrawalRequest::query(), $request)
            ->with(['vendor', 'reviewedByAdmin'])
            ->when(
                $request->get('status') === '_open_' || $request->boolean('open_only'),
                fn ($q) => $q->whereIn('status', VendorWithdrawalRequest::OPEN_STATUSES)
            )
            ->when(
                $request->filled('status') && $request->get('status') !== '_open_',
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('request_code', 'like', $term)
                        ->orWhere('payment_reference', 'like', $term)
                        ->orWhereHas('vendor', fn ($v) => $v->where('brand_name', 'like', $term));
                });
            })
            ->newestFirst()
            ->paginate(15)
            ->withQueryString();

        $totals = [
            'pending' => VendorWithdrawalRequest::query()
                ->where('status', VendorWithdrawalRequest::STATUS_PENDING)
                ->sum('amount'),
            'approved' => VendorWithdrawalRequest::query()
                ->where('status', VendorWithdrawalRequest::STATUS_APPROVED)
                ->sum('amount'),
        ];

        $vendors = Vendor::query()->active()->orderBy('brand_name')->get(['id', 'brand_name']);

        return view('admin.withdrawals.index', compact('withdrawals', 'totals', 'vendors'));
    }

    public function show(VendorWithdrawalRequest $withdrawal): View
    {
        $withdrawal->load(['vendor', 'reviewedByAdmin']);

        return view('admin.withdrawals.show', [
            'withdrawal' => $withdrawal,
            'available' => $this->wallet->availableForWithdrawal($withdrawal->vendor),
        ]);
    }

    public function approve(Request $request, VendorWithdrawalRequest $withdrawal): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'min:5', 'max:1000'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ], [
            'admin_note.required' => 'Please enter an admin note before approving.',
            'admin_note.min' => 'Admin note must be at least 5 characters.',
            'admin_note.max' => 'Admin note may not be longer than 1000 characters.',
        ]);

        try {
            $this->wallet->approveWithdrawal(
                $withdrawal,
                (int) auth('admin')->id(),
                $data['admin_note'],
                $data['payment_reference'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (QueryException $exception) {
            report($exception);

            return back()->withInput()->with('error', $this->friendlyDatabaseMessage($exception));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Unable to approve this withdrawal right now. Please try again.');
        }

        return back()->with('success', 'Withdrawal '.$withdrawal->request_code.' approved and deducted from actual wallet.');
    }

    public function reject(Request $request, VendorWithdrawalRequest $withdrawal): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'admin_note.required' => 'Please enter a rejection note.',
            'admin_note.min' => 'Rejection note must be at least 5 characters.',
            'admin_note.max' => 'Rejection note may not be longer than 1000 characters.',
        ]);

        try {
            $this->wallet->rejectWithdrawal(
                $withdrawal,
                (int) auth('admin')->id(),
                $data['admin_note'],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (QueryException $exception) {
            report($exception);

            return back()->withInput()->with('error', $this->friendlyDatabaseMessage($exception));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Unable to reject this withdrawal right now. Please try again.');
        }

        return back()->with('success', 'Withdrawal '.$withdrawal->request_code.' rejected.');
    }

    protected function friendlyDatabaseMessage(QueryException $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Data too long') || str_contains($message, '22001')) {
            return 'The note is too long for the current database setup. Please shorten it and try again, or ask support to run the latest migrations.';
        }

        return 'Unable to save this withdrawal update. Please check the details and try again.';
    }
}
