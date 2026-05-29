@extends('admin.layouts.app')
@section('title', 'FAQs')
@section('page_title', 'FAQs')
@section('page_subtitle', 'Manage frequently asked questions by app')
@section('content')
    @php
        $tabs = [
            'user' => 'Customer / User app',
            'vendor' => 'Vendor app',
            'driver' => 'Driver app',
        ];
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div class="flex flex-wrap gap-2">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.faqs.index', ['audience' => $key, 'search' => request('search')]) }}"
                   class="jb-settings-tab {{ $audience === $key ? 'jb-settings-tab--active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        @if (auth('admin')->user()->hasPermission('faqs', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.faqs.create', ['audience' => $audience])">+ Add FAQ</x-admin.button>
        @endif
    </div>

    <form method="GET" class="jb-filters">
        <input type="hidden" name="audience" value="{{ $audience }}">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Question or answer">
            </div>
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.faqs.index', ['audience' => $audience])])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $faqs->total() }} FAQs · {{ \App\Models\Faq::audienceLabel($audience) }}</p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="w-20 text-center">Order</th>
                        <th class="jb-col-name">Question</th>
                        <th>Answer preview</th>
                        <th class="text-center">Active</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faqs as $faq)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $faqs])
                            <td class="text-center">{{ $faq->sort_order }}</td>
                            <td class="jb-col-name font-semibold">{{ $faq->question }}</td>
                            <td class="max-w-md truncate text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 120) }}</td>
                            <td class="text-center">{{ $faq->is_active ? 'Yes' : 'No' }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    @if (auth('admin')->user()->hasPermission('faqs', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.faqs.edit', $faq)" />
                                    @endif
                                    @if (auth('admin')->user()->hasPermission('faqs', 'delete'))
                                        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="jb-action-form">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.action-btn variant="delete" type="submit" confirm="Delete this FAQ?" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="jb-table-empty">No FAQs for this app yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($faqs->hasPages())
            {{ $faqs->links() }}
        @endif
    </div>
@endsection
