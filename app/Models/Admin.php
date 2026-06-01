<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Admin extends Authenticatable
{
    protected $fillable = [
        'role_id',
        'name',
        'username',
        'email',
        'avatar_path',
        'password',
        'status',
        'last_login_at',
        'last_login_ip',
        'last_login_device',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedCities(): HasMany
    {
        return $this->hasMany(AdminCity::class);
    }

    public function syncAssignedCity(?string $city): void
    {
        $city = filled($city) ? trim($city) : null;

        $this->assignedCities()->delete();

        if ($city) {
            $this->assignedCities()->create(['city' => $city]);
        }
    }

    public function assignedCity(): ?string
    {
        return $this->assignedCities()->value('city');
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(AdminLoginLog::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasPermission(string $slug, string $action = 'view'): bool
    {
        if ($this->role?->slug === 'super_admin') {
            return true;
        }

        return $this->role?->hasPermission($slug, $action) ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->slug === 'super_admin';
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return Str::upper(collect($parts)->take(2)->map(fn (string $part) => Str::substr($part, 0, 1))->implode(''));
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->avatar_path || ! Storage::disk('public')->exists($this->avatar_path)) {
                return null;
            }

            return '/storage/'.ltrim(str_replace('\\', '/', $this->avatar_path), '/');
        });
    }
}
