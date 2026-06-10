@props([
    'histories',
    'title' => 'Account history',
])

<div x-data="{ open: false }">
    <button type="button" class="jb-btn jb-btn-secondary" @click="open = true">
        History
    </button>

    <template x-teleport="body">
        <div
            class="jb-modal-alert"
            x-cloak
            x-show="open"
            @keydown.escape.window="open = false"
            role="dialog"
            aria-modal="true"
            aria-labelledby="jb-account-history-title"
        >
            <div class="jb-modal-alert-backdrop" @click="open = false"></div>

            <div class="jb-history-modal-card" @click.stop>
                <div class="jb-history-modal-head">
                    <div>
                        <h2 id="jb-account-history-title" class="jb-history-modal-title">{{ $title }}</h2>
                        <p class="jb-history-modal-subtitle">Approve, reject, suspend, block, and status changes recorded by admins.</p>
                    </div>
                    <button type="button" class="jb-history-modal-close" @click="open = false" aria-label="Close history">
                        &times;
                    </button>
                </div>

                <div class="jb-history-modal-body">
                    @if ($histories->isEmpty())
                        <p class="jb-history-empty">No account actions recorded yet.</p>
                    @else
                        <div class="jb-history-table-wrap">
                            <table class="jb-table jb-history-table">
                                <colgroup>
                                    <col class="jb-history-col-date">
                                    <col class="jb-history-col-action">
                                    <col class="jb-history-col-status">
                                    <col class="jb-history-col-status">
                                    <col class="jb-history-col-reason">
                                    <col class="jb-history-col-admin">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Reason</th>
                                        <th>Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($histories as $entry)
                                        <tr>
                                            <td>{{ $entry->created_at?->format('M d, Y · h:i A') ?? '—' }}</td>
                                            <td>
                                                <span class="jb-history-action jb-history-action--{{ $entry->actionVariant() }}">
                                                    {{ $entry->actionLabel() }}
                                                </span>
                                            </td>
                                            <td>{{ $entry->previous_status ? ucfirst($entry->previous_status) : '—' }}</td>
                                            <td>{{ $entry->new_status ? ucfirst($entry->new_status) : '—' }}</td>
                                            <td class="jb-history-reason">{{ $entry->reason ?: '—' }}</td>
                                            <td class="jb-history-admin">{{ $entry->admin?->name ?? 'System' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="jb-history-modal-foot">
                    <button type="button" class="jb-btn jb-btn-secondary" @click="open = false">Close</button>
                </div>
            </div>
        </div>
    </template>
</div>
