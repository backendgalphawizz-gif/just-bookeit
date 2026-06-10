<?php

namespace App\Services\Admin;

use App\Models\AccountStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AccountStatusHistoryService
{
    public function record(
        Model $subject,
        string $action,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?string $reason = null,
    ): AccountStatusHistory {
        return AccountStatusHistory::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'reason' => filled($reason) ? trim($reason) : null,
            'admin_id' => Auth::guard('admin')->id(),
        ]);
    }
}
