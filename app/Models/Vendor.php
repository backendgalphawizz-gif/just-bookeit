<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'vendor_code',
        'brand_name',
        'owner_name',
        'mobile',
        'email',
        'city',
        'aadhar_front_path',
        'aadhar_back_path',
        'categories',
        'rating',
        'orders_completed',
        'earnings',
        'status',
        'approved_at',
    ];

    protected $hidden = [
        'aadhar_front_path',
        'aadhar_back_path',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'rating' => 'decimal:2',
            'earnings' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function aadharFrontUrl(): ?string
    {
        return $this->storageUrl($this->aadhar_front_path);
    }

    public function aadharBackUrl(): ?string
    {
        return $this->storageUrl($this->aadhar_back_path);
    }

    protected function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
