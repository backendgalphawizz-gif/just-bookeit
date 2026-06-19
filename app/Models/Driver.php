<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        'fcm_token',
        'email',
        'city',
        'vehicle_no',
        'account_name',
        'account_number',
        'ifsc_code',
        'bank_name',
        'account_type',
        'aadhar_path',
        'aadhar_front_path',
        'aadhar_back_path',
        'driving_licence_path',
        'profile_image_path',
        'status',
        'rejection_reason',
        'is_verified',
        'approved_at',
        'registered_at',
        'wallet_balance',
        'total_earnings',
    ];

    protected $hidden = [
        'aadhar_path',
        'aadhar_front_path',
        'aadhar_back_path',
        'driving_licence_path',
        'profile_image_path',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'approved_at' => 'datetime',
            'registered_at' => 'datetime',
            'wallet_balance' => 'decimal:2',
            'total_earnings' => 'decimal:2',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(DriverWalletTransaction::class);
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(AccountStatusHistory::class, 'subject');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function aadharUrl(): ?string
    {
        return StoresUploadedFiles::url($this->aadhar_path);
    }

    public function aadharFrontUrl(): ?string
    {
        return StoresUploadedFiles::url($this->aadhar_front_path);
    }

    public function aadharBackUrl(): ?string
    {
        return StoresUploadedFiles::url($this->aadhar_back_path);
    }

    public function drivingLicenceUrl(): ?string
    {
        return StoresUploadedFiles::url($this->driving_licence_path);
    }

    public function profileImageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->profile_image_path);
    }
}
