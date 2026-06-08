<h2 class="vp-settings-panel-title">FAQ's</h2>
<p class="vp-legal-meta">Last updated: {{ $legalUpdatedAt }}</p>

@forelse ($legal['faq'] as $faq)
    <details class="vp-faq-item" @if($loop->first) open @endif>
        <summary>{{ $faq['question'] }}</summary>
        <div class="vp-faq-answer">{{ $faq['answer'] }}</div>
    </details>
@empty
    <p style="color:var(--vp-muted);font-size:.9rem;">No FAQs available yet.</p>
@endforelse
