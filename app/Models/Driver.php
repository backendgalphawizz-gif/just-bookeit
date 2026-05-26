<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens;

    public const PENDING_STATUS = 'pending';

    protected $fillable = [
        'driver_code',
        'name',
        'mobile',
        'email',
        'city',
        'aadhar_path',
        'status',
        'is_verified',
        'approved_at',
        'registered_at',
    ];

    protected $hidden = [
        'aadhar_path',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'approved_at' => 'datetime',
            'registered_at' => 'datetime',
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

    public function aadharUrl(): ?string
    {
        if (! $this->aadhar_path) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $this->aadhar_path), '/');
    }
}
