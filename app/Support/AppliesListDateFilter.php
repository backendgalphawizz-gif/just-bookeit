<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait AppliesListDateFilter
{
    protected function validateListDateRange(Request $request): void
    {
        try {
            $request->validate(
                AdminValidationRules::listDateRange(),
                AdminValidationRules::messages(),
                AdminValidationRules::attributes()
            );
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(url()->current());
        }
    }

    protected function applyDateRange(Builder|Relation $query, Request $request, string $column = 'created_at'): Builder|Relation
    {
        return $query
            ->when($request->filled('from'), fn ($q) => $q->whereDate($column, '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate($column, '<=', $request->date('to')));
    }
}
