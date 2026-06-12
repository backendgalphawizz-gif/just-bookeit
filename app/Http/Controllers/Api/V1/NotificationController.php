<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\NotificationLog;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $notifications = NotificationLog::query()
            ->where('status', 'sent')
            ->whereIn('audience', ['all_customers', 'customers'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success(
            CustomerApiPresenter::paginator($notifications, fn (NotificationLog $log) => CustomerApiPresenter::notification($log))
        );
    }
}
