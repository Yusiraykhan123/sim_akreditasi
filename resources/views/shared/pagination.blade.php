@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);
@endphp

<div class="pagination-wrap documents-pagination">
    <p class="pagination-summary">
        Menampilkan <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
        dari <strong>{{ $paginator->total() }}</strong> {{ $label }}
    </p>
    <nav aria-label="{{ $ariaLabel }}">
        @if ($paginator->onFirstPage())
            <span class="page-control is-disabled" aria-disabled="true">← <span>Sebelumnya</span></span>
        @else
            <a class="page-control" href="{{ $paginator->previousPageUrl() }}">← <span>Sebelumnya</span></a>
        @endif
        <div class="page-numbers">
            @if ($startPage > 1)
                <a class="page-number" href="{{ $paginator->url(1) }}">1</a>
                @if ($startPage > 2)<span class="page-ellipsis" aria-hidden="true">…</span>@endif
            @endif
            @foreach ($paginator->getUrlRange($startPage, $endPage) as $page => $url)
                @if ($page === $currentPage)
                    <span class="page-number is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="page-number" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
            @if ($endPage < $lastPage)
                @if ($endPage < $lastPage - 1)<span class="page-ellipsis" aria-hidden="true">…</span>@endif
                <a class="page-number" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
            @endif
        </div>
        @if ($paginator->hasMorePages())
            <a class="page-control" href="{{ $paginator->nextPageUrl() }}"><span>Selanjutnya</span> →</a>
        @else
            <span class="page-control is-disabled" aria-disabled="true"><span>Selanjutnya</span> →</span>
        @endif
    </nav>
</div>
