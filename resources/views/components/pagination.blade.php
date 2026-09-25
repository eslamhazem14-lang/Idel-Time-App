@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3 text-sm">
        <p class="text-xs text-muted">
            Showing <span class="text-ink-2 mono-num">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            of <span class="text-ink-2 mono-num">{{ $paginator->total() }}</span>
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn-secondary btn-sm opacity-40"><x-icon name="chevron-left" class="size-3.5" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary btn-sm" aria-label="Previous"><x-icon name="chevron-left" class="size-3.5" /></a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-muted">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="btn btn-sm bg-primary/15 text-ink ring-1 ring-primary/40 mono-num">{{ $page }}</span>
                        @elseif (abs($page - $paginator->currentPage()) <= 2 || $page === 1 || $page === $paginator->lastPage())
                            <a href="{{ $url }}" class="btn-ghost btn-sm mono-num hidden sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary btn-sm" aria-label="Next"><x-icon name="chevron-right" class="size-3.5" /></a>
            @else
                <span class="btn-secondary btn-sm opacity-40"><x-icon name="chevron-right" class="size-3.5" /></span>
            @endif
        </div>
    </nav>
@endif
