<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'vendor_code',
        'brand_name',
        'shop_name',
        'owner_name',
        'mobile',
        'business_mobile',
        'email',
        'business_email',
        'city',
        'gst_number',
        'address',
        'country',
        'state',
        'pincode',
        'aadhar_front_path',
        'aadhar_back_path',
        'shop_logo_path',
        'pan_card_path',
        'account_name',
        'account_number',
        'ifsc_code',
        'bank_name',
        'account_type',
        'profile_image_path',
        'categories',
        'service_types',
        'rating',
        'orders_completed',
        'earnings',
        'status',
        'approved_at',
    ];

    protected $hidden = [
        'aadhar_front_path',
        'aadhar_back_path',
        'shop_logo_path',
        'pan_card_path',
        'profile_image_path',
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
        return StoresUploadedFiles::url($this->aadhar_front_path);
    }

    public function aadharBackUrl(): ?string
    {
        return StoresUploadedFiles::url($this->aadhar_back_path);
    }

    public function shopLogoUrl(): ?string
    {
        return StoresUploadedFiles::url($this->shop_logo_path);
    }

    public function panCardUrl(): ?string
    {
        return StoresUploadedFiles::url($this->pan_card_path);
    }

    public function profileImageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->profile_image_path);
    }

    public function serviceType(): ?string
    {
        $value = $this->service_types;

        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('strval', $value)));
        }

        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
