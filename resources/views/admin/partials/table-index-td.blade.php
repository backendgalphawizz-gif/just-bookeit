@php
    $rowNumber = isset($paginator) && method_exists($paginator, 'firstItem')
        ? (int) ($paginator->firstItem() + $loop->index)
        : $loop->iteration;
@endphp
<td class="jb-table-col-index">{{ $rowNumber }}</td>
