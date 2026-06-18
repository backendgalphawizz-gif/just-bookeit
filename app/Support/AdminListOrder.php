<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class AdminListOrder
{
    public static function newestFirst(Builder $query, string $column = 'created_at'): Builder
    {
        return $query
            ->orderByDesc($column)
            ->orderByDesc($query->getModel()->getQualifiedKeyName());
    }

    /** Most recently inserted row first (reliable when created_at may be backdated or seeded). */
    public static function latestIdFirst(Builder $query): Builder
    {
        return $query->orderByDesc($query->getModel()->getQualifiedKeyName());
    }
}
