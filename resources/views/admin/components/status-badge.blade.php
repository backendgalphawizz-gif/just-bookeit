@php
    $styles = [
        'active' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/10',
        'success' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/10',
        'delivered' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/10',
        'processed' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/10',
        'resolved' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/10',
        'closed' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10',
        'pending' => 'bg-amber-100 text-amber-900 ring-1 ring-amber-600/10',
        'pending_acceptance' => 'bg-amber-100 text-amber-900 ring-1 ring-amber-600/10',
        'requested' => 'bg-amber-100 text-amber-900 ring-1 ring-amber-600/10',
        'under_review' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-600/10',
        'new' => 'bg-blue-100 text-blue-800 ring-1 ring-blue-600/10',
        'accepted' => 'bg-indigo-100 text-indigo-800 ring-1 ring-indigo-600/10',
        'in_progress' => 'bg-indigo-100 text-indigo-800 ring-1 ring-indigo-600/10',
        'returned' => 'bg-orange-100 text-orange-900 ring-1 ring-orange-600/10',
        'rework' => 'bg-violet-100 text-violet-800 ring-1 ring-violet-600/10',
        're_intransit' => 'bg-indigo-100 text-indigo-800 ring-1 ring-indigo-600/10',
        're_delivered' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/10',
        'suspended' => 'bg-orange-100 text-orange-900 ring-1 ring-orange-600/10',
        'blocked' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-600/10',
        'cancelled' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-600/10',
        'rejected' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-600/10',
        'failed' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-600/10',
        'refunded' => 'bg-purple-100 text-purple-800 ring-1 ring-purple-600/10',
        'approved' => 'bg-teal-100 text-teal-800 ring-1 ring-teal-600/10',
        'raised' => 'bg-orange-100 text-orange-900 ring-1 ring-orange-600/10',
        'scheduled' => 'bg-violet-100 text-violet-800 ring-1 ring-violet-600/10',
        'processing' => 'bg-indigo-100 text-indigo-800 ring-1 ring-indigo-600/10',
        'paid' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/10',
    ];
    $class = $styles[$status] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10';
    $label = $label ?? \App\Models\Order::statusLabelFor($status);
@endphp
<span class="jb-badge {{ $class }}">{{ $label }}</span>
