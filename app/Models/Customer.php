<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'customer_code',
        'name',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'profile_image_path',
        'status',
        'rejection_reason',
        'is_verified',
        'is_guest',
        'total_orders',
        'registered_at',
    ];

    public const ACTIVE_ORDER_STATUSES = [
        'new', 'pending_acceptance', 'accepted', 'in_progress', 'rework', 're_intransit',
    ];

    public function hasActiveOrders(): bool
    {
        return $this->orders()->whereIn('status', self::ACTIVE_ORDER_STATUSES)->exists();
    }

    protected $hidden = [
        'profile_image_path',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_guest' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(AccountStatusHistory::class, 'subject');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(CustomerMeasurement::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function defaultAddress(): ?CustomerAddress
    {
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->latest('id')->first();
    }

    public function profileImageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->profile_image_path);
    }

    public function registeredAtForForm(): string
    {
        foreach ([$this->registered_at, $this->created_at] as $date) {
            if ($date instanceof CarbonInterface && $date->year >= 1970) {
                return $date->format('Y-m-d');
            }
        }

        return now()->format('Y-m-d');
    }
}
