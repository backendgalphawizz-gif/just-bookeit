@include('vendor.partials.filter-actions', ['resetUrl' => $resetUrl])
@if (trim($__env->yieldPushContent('filter_actions')) !== '')
    <div class="vp-filters-page-actions">
        <label class="vp-label vp-label--spacer" aria-hidden="true">&nbsp;</label>
        <div class="vp-filters-page-actions-btns">
            @stack('filter_actions')
        </div>
    </div>
@endif
