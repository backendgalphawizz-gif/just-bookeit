<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
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

    protected function applyDateRange(Builder $query, Request $request, string $column = 'created_at'): Builder
    {
        return $query
            ->when($request->filled('from'), fn (Builder $q) => $q->whereDate($column, '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereDate($column, '<=', $request->date('to')));
    }
}
