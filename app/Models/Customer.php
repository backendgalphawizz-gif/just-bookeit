<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'city',
        'profile_image_path',
        'status',
        'is_verified',
        'is_guest',
        'total_orders',
        'registered_at',
    ];

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

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function profileImageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->profile_image_path);
    }
}
