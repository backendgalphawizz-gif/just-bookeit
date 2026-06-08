<h2 class="vp-settings-panel-title">Terms &amp; Conditions</h2>
<p class="vp-legal-meta">Last updated: {{ $legalUpdatedAt }}</p>
<div class="vp-legal-content">
    @if (\App\Support\RichText::isEmpty($legal['terms_and_conditions'] ?? null))
        Terms and conditions have not been configured yet.
    @else
        {!! \App\Support\RichText::forDisplay($legal['terms_and_conditions']) !!}
    @endif
</div>
