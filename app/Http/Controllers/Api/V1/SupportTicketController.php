<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Models\SupportTicket;
use App\Support\AdminValidationRules;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $tickets = $customer->supportTickets()
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            CustomerApiPresenter::paginator($tickets, fn (SupportTicket $ticket) => CustomerApiPresenter::supportTicket($ticket))
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'email' => AdminValidationRules::emailRules(true),
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = $customer->supportTickets()->create([
            ...$data,
            'status' => 'pending',
        ]);

        return $this->success([
            'ticket' => CustomerApiPresenter::supportTicket($ticket),
        ], 'Support request submitted.', 201);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($ticket->customer_id === $customer->id, 403);

        return $this->success(CustomerApiPresenter::supportTicket($ticket));
    }
}
