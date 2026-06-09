<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    public const OPEN_STATUSES = ['raised', 'under_review'];

    public const CHAT_CLOSED_STATUSES = ['resolved', 'closed'];

    protected $fillable = [
        'order_id',
        'category_id',
        'raised_by',
        'subject',
        'status',
        'resolution_note',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public static function createForOrder(Order $order, array $attributes): self
    {
        return self::query()->create([
            ...$attributes,
            'order_id' => $order->id,
            'category_id' => $order->category_id,
        ]);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class)->orderBy('created_at');
    }

    public function isChatOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isChatClosed(): bool
    {
        return in_array($this->status, self::CHAT_CLOSED_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }

    /** @return list<string> */
    public static function subjectOptionsForCategory(?Category $category): array
    {
        return match ($category?->slug) {
            'fashion-designer' => [
                'Measurement mismatch',
                'Late delivery',
                'Quality issue',
                'Customization problem',
                'Payment dispute',
            ],
            'rented-dress' => [
                'Wrong size or fit',
                'Dress condition issue',
                'Late pickup or return',
                'Security deposit dispute',
                'Delivery problem',
            ],
            'rented-jewellery' => [
                'Missing jewellery piece',
                'Damage or wear claim',
                'Late return',
                'Deposit refund issue',
                'Delivery problem',
            ],
            default => [
                'Delivery delay',
                'Payment dispute',
                'Service quality',
                'Other issue',
            ],
        };
    }
}
