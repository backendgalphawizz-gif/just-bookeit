<h2 class="vp-settings-panel-title">Privacy Policy</h2>
<p class="vp-legal-meta">Last updated: {{ $legalUpdatedAt }}</p>
<div class="vp-legal-content">
    @if (\App\Support\RichText::isEmpty($legal['privacy_policy'] ?? null))
        Privacy policy has not been configured yet.
    @else
        {!! \App\Support\RichText::forDisplay($legal['privacy_policy']) !!}
    @endif
</div>
