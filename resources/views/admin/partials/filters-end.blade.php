@include('admin.partials.filter-actions', ['resetUrl' => $resetUrl])
@if (trim($__env->yieldPushContent('filter_actions')) !== '')
    <div class="jb-filters-page-actions">
        <label class="jb-label jb-label--spacer" aria-hidden="true">&nbsp;</label>
        <div class="jb-filters-page-actions-btns">
            @stack('filter_actions')
        </div>
    </div>
@endif
