<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withPivot(['can_view', 'can_create', 'can_edit', 'can_delete', 'can_export']);
    }

    public function hasPermission(string $slug, string $action = 'view'): bool
    {
        if ($this->slug === 'super_admin') {
            return true;
        }

        $permission = $this->permissions()->where('slug', $slug)->first();

        if (! $permission) {
            return false;
        }

        $column = match ($action) {
            'create' => 'can_create',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            'export' => 'can_export',
            default => 'can_view',
        };

        return (bool) $permission->pivot->{$column};
    }
}
