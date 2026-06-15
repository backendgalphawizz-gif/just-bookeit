@extends('admin.layouts.app')

@section('title', 'Damage deduction rules')
@section('page_title', 'Damage deduction rules')
@section('page_subtitle', 'Set maximum damage deduction limits by catalog or service category')

@section('content')
    @php
        $isCatalogTab = $tab === 'catalog';
        $isServiceTab = $tab === 'service';

        $initialCatalogRules = old('damage_deduction_rules');

        if (! is_array($initialCatalogRules)) {
            $initialCatalogRules = collect($catalogRules)->map(fn (array $rule) => [
                'category_id' => $rule['category_id'],
                'subcategory_id' => $rule['subcategory_id'] ?? '',
                'max_percent' => $rule['max_percent'],
            ])->all();
        }

        $initialServiceRules = old('service_damage_deduction_rules');

        if (! is_array($initialServiceRules)) {
            $initialServiceRules = collect($serviceRules)->map(fn (array $rule) => [
                'category_id' => $rule['category_id'] ?? '',
                'subcategory_id' => $rule['subcategory_id'] ?? '',
                'service_category_id' => $rule['service_category_id'],
                'max_percent' => $rule['max_percent'],
            ])->all();
        }

        $canEdit = auth('admin')->user()->hasPermission('settings', 'edit');
        $totalRules = count($initialCatalogRules) + count($initialServiceRules);
    @endphp

    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            <a href="{{ route('admin.settings.damage-deduction.index', ['tab' => 'catalog']) }}"
               class="jb-settings-tab {{ $isCatalogTab ? 'jb-settings-tab--active' : '' }}">
                Catalog categories
            </a>
            <a href="{{ route('admin.settings.damage-deduction.index', ['tab' => 'service']) }}"
               class="jb-settings-tab {{ $isServiceTab ? 'jb-settings-tab--active' : '' }}">
                Service categories
            </a>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('admin.settings.damage-deduction.update') }}"
        class="jb-card jb-damage-rules-card"
        data-damage-settings-form
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="jb-card-header">
            <div class="min-w-0 flex-1">
                <p class="jb-card-header-title">
                    {{ $isCatalogTab ? 'Catalog category limits' : 'Service category limits' }}
                </p>
                <p class="mt-1 max-w-3xl text-sm text-slate-500">
                    @if ($isCatalogTab)
                        Set the maximum total damage deduction vendors can apply per product in the catalog.
                    @else
                        Set the maximum total damage deduction vendors can apply per product for each service type.
                        Pick a category and optional sub-category, then choose the service type the limit applies to.
                    @endif
                </p>
                @if ($isCatalogTab)
                    <div class="jb-damage-rules-priority mt-3 max-w-3xl">
                        <p class="font-semibold text-slate-800">Which rule applies when both exist?</p>
                        <p class="mt-1 text-sm text-slate-600">
                            The <strong>specific sub-category</strong> rule always wins.
                            Example: if <strong>Women → Sarees = 10%</strong> and later you add
                            <strong>Women → All sub-categories = 20%</strong>, then Sarees stays at 10% and
                            Lehengas, Gowns, Kurtis, etc. use 20%.
                        </p>
                    </div>
                @else
                    <div class="jb-damage-rules-priority mt-3 max-w-3xl">
                        <p class="font-semibold text-slate-800">Which rule applies when both exist?</p>
                        <p class="mt-1 text-sm text-slate-600">
                            For the same service type, a <strong>specific sub-category</strong> rule overrides
                            a <strong>All sub-categories</strong> rule in that category.
                        </p>
                    </div>
                @endif
            </div>
            @if ($canEdit && $mainCategories->isNotEmpty() && $serviceCategories->isNotEmpty())
                <button type="button" class="jb-btn jb-btn-secondary jb-btn-sm shrink-0" data-damage-settings-add>
                    + Add rule
                </button>
            @endif
        </div>

        @if ($isCatalogTab)
            @if ($mainCategories->isEmpty())
                <div class="jb-card-body">
                    <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 p-5 text-sm text-slate-500">
                        Add categories and sub-categories first under Categories.
                    </p>
                </div>
            @else
                <div
                    class="jb-damage-rules-table-wrap"
                    data-damage-settings-panel
                    data-damage-field-prefix="damage_deduction_rules"
                    data-damage-panel-type="catalog"
                >
                    <table class="jb-table jb-table--balanced jb-damage-rules-table">
                        <thead>
                            <tr>
                                <th class="jb-col-sn">#</th>
                                <th class="jb-col-category">Category</th>
                                <th class="jb-col-category jb-damage-rules-table__sub-col">Sub-category</th>
                                <th class="jb-damage-rules-table__scope-col">Applies to</th>
                                <th class="text-center jb-damage-rules-table__percent-col">Max deduction (%)</th>
                                <th class="jb-table-actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody data-damage-settings-list>
                            @forelse ($initialCatalogRules as $index => $rule)
                                @include('admin.settings.partials.damage-deduction-row', [
                                    'rowIndex' => $index,
                                    'rule' => $rule,
                                ])
                            @empty
                                <tr data-damage-settings-empty>
                                    <td colspan="6" class="jb-table-empty">
                                        No rules yet. Click <strong>+ Add rule</strong> to define a limit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <template data-damage-settings-template>
                        @include('admin.settings.partials.damage-deduction-row', [
                            'rowIndex' => '__INDEX__',
                            'rule' => ['category_id' => '', 'subcategory_id' => '', 'max_percent' => ''],
                        ])
                    </template>
                </div>

                @error('damage_deduction_rules')
                    <p class="px-6 pb-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
                @error('damage_deduction_rules.*')
                    <p class="px-6 pb-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            @endif
        @else
            @if ($serviceCategories->isEmpty() || $mainCategories->isEmpty())
                <div class="jb-card-body">
                    <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 p-5 text-sm text-slate-500">
                        @if ($mainCategories->isEmpty())
                            Add catalog categories first under Categories.
                        @else
                            Add service categories first under Categories → Service categories.
                        @endif
                    </p>
                </div>
            @else
                <div
                    class="jb-damage-rules-table-wrap"
                    data-damage-settings-panel
                    data-damage-field-prefix="service_damage_deduction_rules"
                    data-damage-panel-type="service"
                >
                    <table class="jb-table jb-table--balanced jb-damage-rules-table">
                        <thead>
                            <tr>
                                <th class="jb-col-sn">#</th>
                                <th class="jb-col-category">Category</th>
                                <th class="jb-col-category jb-damage-rules-table__sub-col">Sub-category</th>
                                <th class="jb-damage-rules-table__scope-col">Applies to</th>
                                <th class="jb-col-category">Service category</th>
                                <th class="text-center jb-damage-rules-table__percent-col">Max deduction (%)</th>
                                <th class="jb-table-actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody data-damage-settings-list>
                            @forelse ($initialServiceRules as $index => $rule)
                                @include('admin.settings.partials.service-damage-deduction-row', [
                                    'rowIndex' => $index,
                                    'rule' => $rule,
                                ])
                            @empty
                                <tr data-damage-settings-empty>
                                    <td colspan="7" class="jb-table-empty">
                                        No rules yet. Click <strong>+ Add rule</strong> to define a limit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <template data-damage-settings-template>
                        @include('admin.settings.partials.service-damage-deduction-row', [
                            'rowIndex' => '__INDEX__',
                            'rule' => ['category_id' => '', 'subcategory_id' => '', 'service_category_id' => '', 'max_percent' => ''],
                        ])
                    </template>
                </div>

                @error('service_damage_deduction_rules')
                    <p class="px-6 pb-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
                @error('service_damage_deduction_rules.*')
                    <p class="px-6 pb-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            @endif
        @endif

        @if ($isCatalogTab && $mainCategories->isNotEmpty())
            <div
                hidden
                data-damage-settings-sync
                data-damage-field-prefix="service_damage_deduction_rules"
            >
                @foreach ($initialServiceRules as $index => $rule)
                    <input type="hidden" name="service_damage_deduction_rules[{{ $index }}][category_id]" value="{{ $rule['category_id'] ?? '' }}">
                    <input type="hidden" name="service_damage_deduction_rules[{{ $index }}][subcategory_id]" value="{{ $rule['subcategory_id'] ?? '' }}">
                    <input type="hidden" name="service_damage_deduction_rules[{{ $index }}][service_category_id]" value="{{ $rule['service_category_id'] ?? '' }}">
                    <input type="hidden" name="service_damage_deduction_rules[{{ $index }}][max_percent]" value="{{ $rule['max_percent'] ?? '' }}">
                @endforeach
            </div>
        @elseif ($isServiceTab && $serviceCategories->isNotEmpty() && $mainCategories->isNotEmpty())
            <div
                hidden
                data-damage-settings-sync
                data-damage-field-prefix="damage_deduction_rules"
            >
                @foreach ($initialCatalogRules as $index => $rule)
                    <input type="hidden" name="damage_deduction_rules[{{ $index }}][category_id]" value="{{ $rule['category_id'] ?? '' }}">
                    <input type="hidden" name="damage_deduction_rules[{{ $index }}][subcategory_id]" value="{{ $rule['subcategory_id'] ?? '' }}">
                    <input type="hidden" name="damage_deduction_rules[{{ $index }}][max_percent]" value="{{ $rule['max_percent'] ?? '' }}">
                @endforeach
            </div>
        @endif

        @if ($canEdit && (($isCatalogTab && $mainCategories->isNotEmpty()) || ($isServiceTab && $serviceCategories->isNotEmpty() && $mainCategories->isNotEmpty())))
            <div class="jb-damage-rules-footer">
                <p class="text-sm text-slate-500" data-damage-settings-count>
                    {{ $totalRules }} rule(s) configured across both tabs
                </p>
                <x-admin.button variant="primary" type="submit">Save rules</x-admin.button>
            </div>
        @endif
    </form>
@endsection

@push('styles')
<style>
    .jb-damage-rules-card .jb-card-header {
        align-items: flex-start;
    }

    .jb-damage-rules-table-wrap {
        overflow-x: auto;
    }

    .jb-damage-rules-table {
        min-width: 56rem;
    }

    [data-damage-settings-row]:hover td {
        background: #fafafa;
    }

    .jb-damage-rules-table__sub-col {
        min-width: 13rem;
    }

    .jb-damage-rules-table__scope-col {
        min-width: 11rem;
    }

    .jb-damage-rules-table__percent-col {
        width: 9rem;
    }

    .jb-damage-rules-table__select {
        min-height: 2.625rem;
        width: 100%;
        min-width: 10rem;
    }

    .jb-damage-rules-table__select:disabled {
        cursor: not-allowed;
        background: #f8fafc;
        color: #94a3b8;
    }

    .jb-damage-rules-table__percent {
        width: 100%;
        max-width: 7.5rem;
        min-height: 2.625rem;
        margin-inline: auto;
        text-align: center;
    }

    .jb-damage-rules-table td {
        vertical-align: middle;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }

    .jb-damage-rules-scope {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.2rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .jb-damage-rules-scope--all {
        background: #ede9fe;
        color: #6d28d9;
    }

    .jb-damage-rules-scope--sub {
        background: #e0f2fe;
        color: #0369a1;
    }

    .jb-damage-rules-scope--global {
        background: #f1f5f9;
        color: #475569;
    }

    .jb-damage-rules-priority {
        border-radius: 0.75rem;
        border: 1px solid #e0e7ff;
        background: #f8fafc;
        padding: 0.85rem 1rem;
    }

    .jb-damage-rules-scope-hint:not([hidden]) {
        max-width: 14rem;
    }

    .jb-damage-rules-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border-top: 1px solid #f1f5f9;
        padding: 1rem 1.5rem;
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const form = document.querySelector('[data-damage-settings-form]');
        if (!form) return;

        const panel = form.querySelector('[data-damage-settings-panel]');
        if (!panel) return;

        const fieldPrefix = panel.dataset.damageFieldPrefix;
        const list = panel.querySelector('[data-damage-settings-list]');
        const template = panel.querySelector('[data-damage-settings-template]');
        const addBtn = form.querySelector('[data-damage-settings-add]');
        const countEl = form.querySelector('[data-damage-settings-count]');
        const emptyRow = list.querySelector('[data-damage-settings-empty]');
        const syncBlock = form.querySelector('[data-damage-settings-sync]');
        const syncPrefix = syncBlock?.dataset.damageFieldPrefix;
        const syncFieldsPerRule = syncPrefix === 'damage_deduction_rules' ? 3 : 4;
        const syncRuleCount = syncBlock
            ? syncBlock.querySelectorAll(`input[name^="${syncPrefix}["]`).length / syncFieldsPerRule
            : 0;

        const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

        const syncSubcategoryOptions = (row) => {
            const categorySelect = row.querySelector('[data-damage-category]');
            const subSelect = row.querySelector('[data-damage-subcategory]');
            const scope = row.querySelector('[data-damage-scope]');

            if (!categorySelect || !subSelect) return;

            const parentId = categorySelect.value;
            const isServiceRow = row.querySelector('[name*="service_category_id"]') !== null;
            let selectedIsValid = subSelect.value === '';

            subSelect.querySelectorAll('option[data-parent-id]').forEach((option) => {
                const matches = parentId !== '' && option.dataset.parentId === parentId;
                option.hidden = !matches;
                option.disabled = !matches;

                if (option.selected && matches) {
                    selectedIsValid = true;
                }
            });

            subSelect.disabled = parentId === '';

            if (!selectedIsValid) {
                subSelect.value = '';
            }

            if (!scope) return;

            if (parentId === '') {
                if (isServiceRow) {
                    scope.textContent = 'All categories';
                    scope.className = 'jb-damage-rules-scope jb-damage-rules-scope--global';
                }

                updateScopeHints();
                return;
            }

            if (subSelect.value === '') {
                scope.textContent = 'All sub-categories';
                scope.className = 'jb-damage-rules-scope jb-damage-rules-scope--all';
            } else {
                scope.textContent = subSelect.options[subSelect.selectedIndex]?.textContent?.trim() || 'Sub-category';
                scope.className = 'jb-damage-rules-scope jb-damage-rules-scope--sub';
            }

            updateScopeHints();
        };

        const updateScopeHints = () => {
            const rows = Array.from(list.querySelectorAll('[data-damage-settings-row]')).map((row) => {
                const subSelect = row.querySelector('[data-damage-subcategory]');
                const serviceSelect = row.querySelector('[name*="service_category_id"]');

                return {
                    row,
                    categoryId: row.querySelector('[data-damage-category]')?.value || '',
                    subcategoryId: subSelect?.value || '',
                    serviceCategoryId: serviceSelect?.value || '',
                    subcategoryName: subSelect?.value
                        ? subSelect.options[subSelect.selectedIndex]?.textContent?.trim() || ''
                        : '',
                };
            });

            rows.forEach(({ row, categoryId, subcategoryId, serviceCategoryId, subcategoryName }) => {
                const hintEl = row.querySelector('[data-damage-scope-hint]');
                if (!hintEl) {
                    return;
                }

                let hint = '';

                if (categoryId !== '') {
                    const peers = rows.filter(({ row: peerRow, categoryId: peerCategoryId, serviceCategoryId: peerServiceCategoryId }) => {
                        if (peerRow === row || peerCategoryId !== categoryId) {
                            return false;
                        }

                        if (serviceCategoryId !== '' || peerServiceCategoryId !== '') {
                            return peerServiceCategoryId === serviceCategoryId;
                        }

                        return true;
                    });

                    if (subcategoryId !== '') {
                        if (peers.some(({ subcategoryId: peerSubcategoryId }) => peerSubcategoryId === '')) {
                            hint = 'This limit is used for this sub-category (overrides the category-wide rule).';
                        }
                    } else {
                        const exceptions = peers
                            .filter(({ subcategoryId: peerSubcategoryId }) => peerSubcategoryId !== '')
                            .map(({ subcategoryName: peerSubcategoryName }) => peerSubcategoryName)
                            .filter(Boolean);

                        if (exceptions.length > 0) {
                            hint = `Default for other sub-categories. Does not apply to: ${exceptions.join(', ')}.`;
                        } else {
                            hint = 'Applies to every sub-category in this category.';
                        }
                    }
                }

                hintEl.textContent = hint;
                hintEl.hidden = hint === '';
            });
        };

        const reindexRows = () => {
            list.querySelectorAll('[data-damage-settings-row]').forEach((row, index) => {
                const numberCell = row.querySelector('[data-damage-row-number]');
                if (numberCell) {
                    numberCell.textContent = String(index + 1);
                }

                row.querySelectorAll(`[name^="${fieldPrefix}["]`).forEach((field) => {
                    field.name = field.name.replace(
                        new RegExp(`${escapeRegExp(fieldPrefix)}\\[\\d+\\]`),
                        `${fieldPrefix}[${index}]`
                    );
                });
            });

            if (countEl) {
                const activeTotal = list.querySelectorAll('[data-damage-settings-row]').length;
                countEl.textContent = `${activeTotal + syncRuleCount} rule(s) configured across both tabs`;
            }

            updateScopeHints();
        };

        const bindRow = (row) => {
            const categorySelect = row.querySelector('[data-damage-category]');
            const subSelect = row.querySelector('[data-damage-subcategory]');
            const serviceSelect = row.querySelector('[name*="service_category_id"]');

            if (categorySelect && subSelect) {
                syncSubcategoryOptions(row);
                categorySelect.addEventListener('change', () => syncSubcategoryOptions(row));
                subSelect.addEventListener('change', () => syncSubcategoryOptions(row));
            }

            serviceSelect?.addEventListener('change', () => updateScopeHints());

            row.querySelector('[data-damage-settings-remove]')?.addEventListener('click', () => {
                row.remove();
                reindexRows();

                if (!list.querySelector('[data-damage-settings-row]') && emptyRow) {
                    emptyRow.hidden = false;
                }
            });
        };

        list.querySelectorAll('[data-damage-settings-row]').forEach(bindRow);
        updateScopeHints();

        form.addEventListener('submit', () => {
            list.querySelectorAll('[data-damage-settings-row]').forEach((row) => {
                const categorySelect = row.querySelector('[data-damage-category]');
                const subSelect = row.querySelector('[data-damage-subcategory]');

                if (categorySelect?.value && subSelect) {
                    subSelect.disabled = false;
                }
            });
        });

        addBtn?.addEventListener('click', () => {
            if (!template) return;

            if (emptyRow) {
                emptyRow.hidden = true;
            }

            const index = list.querySelectorAll('[data-damage-settings-row]').length;
            const html = template.innerHTML.replaceAll('__INDEX__', String(index));
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;

            list.appendChild(row);
            bindRow(row);
            reindexRows();
            row.querySelector('select, input')?.focus();
        });
    })();
</script>
@endpush
