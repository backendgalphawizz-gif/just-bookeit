@if ($paginator->hasPages())
    <div class="jb-pagination">
        <p class="text-sm text-slate-600">
            Showing
            <span class="font-semibold text-slate-900">{{ $paginator->firstItem() ?? 0 }}</span>
            to
            <span class="font-semibold text-slate-900">{{ $paginator->lastItem() ?? 0 }}</span>
            of
            <span class="font-semibold text-slate-900">{{ $paginator->total() }}</span>
            results
        </p>
        <div class="flex flex-wrap gap-1">
            @if ($paginator->onFirstPage())
                <span class="jb-btn jb-btn-secondary jb-btn-sm cursor-not-allowed opacity-50">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="jb-btn jb-btn-secondary jb-btn-sm">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="jb-btn jb-btn-ghost jb-btn-sm cursor-default">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="jb-btn jb-btn-primary jb-btn-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="jb-btn jb-btn-secondary jb-btn-sm">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="jb-btn jb-btn-secondary jb-btn-sm">Next</a>
            @else
                <span class="jb-btn jb-btn-secondary jb-btn-sm cursor-not-allowed opacity-50">Next</span>
            @endif
        </div>
    </div>
@endif
