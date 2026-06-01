@props(['title' => 'Manage status', 'actions' => []])

@if (count($actions) > 0)
    <div class="jb-card mt-6">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $title }}</p>
        </div>
        <div class="jb-card-body flex flex-wrap gap-2">
            @foreach ($actions as $action)
                <form
                    method="POST"
                    action="{{ $action['url'] }}"
                    class="inline-flex"
                    @if (! empty($action['confirm']))
                        data-jb-confirm="{{ $action['confirm'] }}"
                        data-jb-confirm-title="{{ $action['confirmTitle'] ?? 'Are you sure?' }}"
                        data-jb-confirm-variant="{{ $action['confirmVariant'] ?? 'warning' }}"
                        data-jb-confirm-label="{{ $action['confirmLabel'] ?? $action['label'] }}"
                    @endif
                >
                    @csrf
                    @if (! empty($action['status']))
                        <input type="hidden" name="status" value="{{ $action['status'] }}">
                    @endif
                    <x-admin.button :variant="$action['variant'] ?? 'secondary'" type="submit" size="sm">
                        {{ $action['label'] }}
                    </x-admin.button>
                </form>
            @endforeach
        </div>
    </div>
@endif
