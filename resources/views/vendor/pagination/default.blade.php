@if ($paginator->hasPages())
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
        <p style="margin:0;font-size:.82rem;color:var(--vp-muted);">
            Showing <strong style="color:var(--vp-text);">{{ $paginator->firstItem() ?? 0 }}</strong>
            to <strong style="color:var(--vp-text);">{{ $paginator->lastItem() ?? 0 }}</strong>
            of <strong style="color:var(--vp-text);">{{ $paginator->total() }}</strong> results
        </p>
        <ul class="pagination">
            @if ($paginator->onFirstPage())
                <li><span style="opacity:.45;">Previous</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}">Previous</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}">Next</a></li>
            @else
                <li><span style="opacity:.45;">Next</span></li>
            @endif
        </ul>
    </div>
@endif
