<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $fillable = [
        'role_id',
        'name',
        'username',
        'email',
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
}
