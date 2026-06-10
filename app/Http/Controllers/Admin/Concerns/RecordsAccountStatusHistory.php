<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Services\Admin\AccountStatusHistoryService;
use Illuminate\Database\Eloquent\Model;

trait RecordsAccountStatusHistory
{
    protected function recordAccountStatusHistory(
        Model $subject,
        string $action,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?string $reason = null,
    ): void {
        app(AccountStatusHistoryService::class)->record(
            $subject,
            $action,
            $previousStatus,
            $newStatus,
            $reason,
        );
    }
}
