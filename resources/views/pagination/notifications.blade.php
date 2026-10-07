@if ($paginator->hasPages())
    @php
        $lastPage = $paginator->lastPage();
        $currentPage = $paginator->currentPage();
        $relativeUrl = static function (?string $url): string {
            $parts = parse_url($url ?? '');

            return ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        };
        $pageWindow = static function (int $maximum) use ($lastPage, $currentPage): array {
            $count = min($maximum, $lastPage);
            $start = max(1, min($currentPage - intdiv($count - 1, 2), $lastPage - $count + 1));

            return range($start, $start + $count - 1);
        };
    @endphp
    <nav class="ljs-pagination notification-pagination" role="navigation" aria-label="Notification pages">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true" aria-label="Previous page">&lsaquo;</span>
        @else
            <a href="{{ $relativeUrl($paginator->previousPageUrl()) }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
        @endif

        @foreach ([['is-desktop', 8], ['is-mobile', 3]] as [$windowClass, $maximumPages])
            <span class="notification-pagination-pages {{ $windowClass }}">
                @foreach ($pageWindow($maximumPages) as $page)
                    @if ($page === $currentPage)
                        <span class="is-current notification-page-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $relativeUrl($paginator->url($page)) }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            </span>
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $relativeUrl($paginator->nextPageUrl()) }}" rel="next" aria-label="Next page">&rsaquo;</a>
        @else
            <span class="is-disabled" aria-disabled="true" aria-label="Next page">&rsaquo;</span>
        @endif
    </nav>
@endif