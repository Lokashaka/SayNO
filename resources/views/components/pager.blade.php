@props(['items'])

@if ($items->hasPages())
    <nav class="mt-4 flex items-center justify-between gap-3" aria-label="{{ __('web.phishing.pagination') }}">
        @if ($items->onFirstPage())
            <span></span>
        @else
            <a href="{{ $items->previousPageUrl() }}" class="btn-secondary" rel="prev">
                <x-icon name="arrow-left" :size="18" />
                {{ __('web.phishing.prev') }}
            </a>
        @endif
        <span class="text-sm text-ink-muted">{{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
        @if ($items->hasMorePages())
            <a href="{{ $items->nextPageUrl() }}" class="btn-secondary" rel="next">
                {{ __('web.phishing.next') }}
                <x-icon name="arrow-right" :size="18" />
            </a>
        @else
            <span></span>
        @endif
    </nav>
@endif
