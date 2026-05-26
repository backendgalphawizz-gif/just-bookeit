<td class="jb-col-sn">{{ (isset($paginator) && $paginator instanceof \Illuminate\Contracts\Pagination\Paginator ? ($paginator->firstItem() ?? 1) : 1) + $loop->index }}</td>
