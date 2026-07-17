<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        'fcm_token',
        'business_mobile',
        'email',
        'business_email',
        'city',
        'gst_number',
        'aadhar_number',
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
        'bio',
        'cover_image_path',
        'password',
        'is_listing_active',
        'categories',
        'service_types',
        'rating',
        'orders_completed',
        'earnings',
        'digital_wallet_balance',
        'wallet_balance',
        'status',
        'rejection_reason',
        'approved_at',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
        'commission',
    ];

    protected $hidden = [
        'aadhar_front_path',
        'aadhar_back_path',
        'shop_logo_path',
        'pan_card_path',
        'profile_image_path',
        'cover_image_path',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'rating' => 'decimal:2',
            'earnings' => 'decimal:2',
            'digital_wallet_balance' => 'decimal:2',
            'wallet_balance' => 'decimal:2',
            'commission' => 'decimal:2',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'is_listing_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'suspended_by');
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(AccountStatusHistory::class, 'subject');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    public function portfolioImages(): HasMany
    {
        return $this->hasMany(VendorPortfolioImage::class);
    }

    public function shopImages(): HasMany
    {
        return $this->hasMany(VendorShopImage::class)->orderBy('sort_order');
    }

    /** @return list<string> */
    public function shopImageUrls(): array
    {
        return $this->shopImages
            ->map(fn (VendorShopImage $image) => $image->imageUrl())
            ->filter()
            ->values()
            ->all();
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(VendorWalletTransaction::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(VendorWithdrawalRequest::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(OrderReview::class);
    }

    public function displayName(): string
    {
        return $this->brand_name ?: $this->shop_name ?: $this->owner_name ?: 'Vendor';
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

    public function coverImageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->cover_image_path);
    }

    /** Avatar for header — profile photo, then shop logo. Cover image is not used here. */
    public function avatarUrl(): ?string
    {
        return $this->profileImageUrl() ?? $this->shopLogoUrl();
    }

    public function avatarInitial(): string
    {
        $name = $this->owner_name ?: $this->displayName();

        return strtoupper(substr($name, 0, 1) ?: 'V');
    }

    /** @return array<int, string> */
    public function selectedServiceTypes(): array
    {
        $raw = $this->service_types;
        if (is_array($raw)) {
            return array_values(array_filter(array_map('trim', $raw)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
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
