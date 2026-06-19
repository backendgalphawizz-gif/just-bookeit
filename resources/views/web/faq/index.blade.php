@extends('web.layouts.app')

@section('title', 'FAQs')

@section('content')
<div class="jbw-container">
    <div class="jbw-page-head">
        <!-- <span class="jbw-eyebrow">Help</span> -->
        <h1 class="jbw-page-title">Frequently Asked Questions</h1>
        <p class="jbw-page-subtitle">Answers to common questions about bookings, rentals, and your account.</p>
    </div>

    @if ($faqs->isNotEmpty())
        <div class="jbw-faq-list">
            @foreach ($faqs as $faq)
                <details class="jbw-card jbw-faq-item">
                    <summary class="jbw-faq-question">{{ $faq->question }}</summary>
                    <div class="jbw-faq-answer">{!! nl2br(e($faq->answer)) !!}</div>
                </details>
            @endforeach
        </div>
    @else
        <div class="jbw-card" style="text-align:center;padding:2rem">
            <p style="margin:0 0 1rem;color:var(--c-muted)">No FAQs published yet. Our team is preparing helpful answers for you.</p>
            <a href="{{ route('web.contact') }}" class="jbw-btn jbw-btn--primary">Contact support</a>
        </div>
    @endif

    <div class="jbw-card" style="margin-top:1.5rem;text-align:center">
        <p style="margin:0 0 1rem;color:var(--c-muted)">Still need help?</p>
        <a href="{{ route('web.contact') }}" class="jbw-btn jbw-btn--outline">Get in touch</a>
    </div>
</div>
@endsection
