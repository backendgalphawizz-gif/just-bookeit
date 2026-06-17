<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountStatusHistory extends Model
{
    public const ACTION_APPROVE = 'approve';

    public const ACTION_REJECT = 'reject';

    public const ACTION_SUSPEND = 'suspend';

    public const ACTION_ACTIVATE = 'activate';

    public const ACTION_INACTIVATE = 'inactivate';

    public const ACTION_STATUS_UPDATE = 'status_update';

    public const ACTION_BULK_APPROVE = 'bulk_approve';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'action',
        'previous_status',
        'new_status',
        'reason',
        'admin_id',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_APPROVE => 'Approved',
            self::ACTION_REJECT => 'Rejected',
            self::ACTION_SUSPEND => 'Suspended',
            self::ACTION_ACTIVATE => 'Activated',
            self::ACTION_INACTIVATE => 'Inactivated',
            self::ACTION_STATUS_UPDATE => 'Status updated',
            self::ACTION_BULK_APPROVE => 'Bulk approved',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function actionVariant(): string
    {
        return match ($this->action) {
            self::ACTION_APPROVE, self::ACTION_ACTIVATE, self::ACTION_BULK_APPROVE => 'success',
            self::ACTION_REJECT, self::ACTION_INACTIVATE => 'error',
            self::ACTION_SUSPEND => 'warning',
            default => 'neutral',
        };
    }
}
