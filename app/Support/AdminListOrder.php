<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class AdminListOrder
{
    public static function newestFirst(Builder|Relation $query, string $column = 'created_at'): Builder|Relation
    {
        return $query
            ->orderByDesc($column)
            ->orderByDesc($query->getModel()->getQualifiedKeyName());
    }

    /** Most recently inserted row first (reliable when created_at may be backdated or seeded). */
    public static function latestIdFirst(Builder|Relation $query): Builder|Relation
    {
        return $query->orderByDesc($query->getModel()->getQualifiedKeyName());
    }
}
