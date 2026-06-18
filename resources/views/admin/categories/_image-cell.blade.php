@php
    $imageUrl = $category->imageUrl();
@endphp
<td class="jb-col-image">
    @if ($imageUrl)
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="jb-category-table-img panel-lightbox-trigger"
        >
    @else
        <span class="jb-category-table-img jb-category-table-img--empty" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
        </span>
    @endif
</td>
