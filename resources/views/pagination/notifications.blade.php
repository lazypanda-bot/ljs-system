@if ($paginator->hasPages())
    @php
        $mobileWindow = 5;
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $mobileStart = max(1, min($currentPage - 2, $lastPage - $mobileWindow + 1));
        $mobileEnd = min($lastPage, $mobileStart + $mobileWindow - 1);
    @endphp
    <nav class="ljs-pagination notification-pagination" role="navigation" aria-label="Notification pages">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true" aria-label="Previous page">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="is-disabled pagination-desktop-only">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-current pagination-desktop-only" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination-desktop-only" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @for ($page = $mobileStart; $page <= $mobileEnd; $page++)
            @if ($page == $currentPage)
                <span class="is-current pagination-mobile-only" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="pagination-mobile-only" aria-label="Go to page {{ $page }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rsaquo;</a>
        @else
            <span class="is-disabled" aria-disabled="true" aria-label="Next page">&rsaquo;</span>
        @endif
    </nav>
@endif
