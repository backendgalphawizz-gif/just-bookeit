<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundHistory extends Model
{
    protected $fillable = [
        'refund_id',
        'status',
        'note',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }
}
