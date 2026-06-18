<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Support\AdminValidationRules;
use Illuminate\Validation\Validator;

class CategoryRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            ...AdminValidationRules::category(),
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => (int) ($this->input('sort_order') ?? 0),
            'parent_id' => $this->input('type') === Category::TYPE_SERVICE ? null : $this->input('parent_id'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');

            if ($type === Category::TYPE_SUB) {
                if (! $this->filled('parent_id')) {
                    $validator->errors()->add('parent_id', 'Select a parent category for this sub-category.');

                    return;
                }

                $parent = Category::query()->find($this->integer('parent_id'));

                if (! $parent || ! $parent->isMain()) {
                    $validator->errors()->add('parent_id', 'Sub-categories must belong to a main category (Women, Men, or Kids).');
                }
            }

            if ($validator->errors()->isNotEmpty() || ! $this->filled('name')) {
                return;
            }

            if ($this->nameAlreadyExists()) {
                $validator->errors()->add('name', $this->duplicateNameMessage($type));
            }
        });
    }

    protected function nameAlreadyExists(): bool
    {
        $name = strtolower(trim((string) $this->input('name')));
        $type = (string) $this->input('type');

        if ($name === '') {
            return false;
        }

        /** @var Category|null $current */
        $current = $this->route('category');
        $ignoreId = $current?->id;

        $query = Category::query()
            ->where('type', $type)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$name]);

        if ($type === Category::TYPE_SUB) {
            $query->where('parent_id', $this->integer('parent_id'));
        } else {
            $query->whereNull('parent_id');
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    protected function duplicateNameMessage(?string $type): string
    {
        return match ($type) {
            Category::TYPE_SUB => 'This sub-category is already present under the selected parent.',
            Category::TYPE_SERVICE => 'This service category is already present.',
            default => 'This category is already present.',
        };
    }
}
