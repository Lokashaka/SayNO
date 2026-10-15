<x-layout :title="__('web.phishing.title')" wide>
    <div class="max-w-[720px]">
        <h1 class="text-[32px] leading-10 font-bold text-navy-900">{{ __('web.phishing.title') }}</h1>
        <p class="mt-3 text-ink-muted">{{ __('web.phishing.lead') }}</p>
    </div>

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <form method="GET" action="{{ route('phishing') }}" role="search" class="w-full sm:max-w-[480px]">
            <label for="q" class="block font-semibold text-navy-900">{{ __('web.phishing.search_label') }}</label>
            <div class="mt-2 flex gap-2">
                <input
                    type="search"
                    id="q"
                    name="q"
                    value="{{ $search }}"
                    maxlength="200"
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                    placeholder="{{ __('web.phishing.search_placeholder') }}"
                    class="h-12 w-full min-w-0 rounded-btn border border-line bg-white px-4 focus:border-navy-700"
                >
                <button type="submit" class="btn-primary shrink-0">
                    <x-icon name="search" :size="18" />
                    {{ __('web.phishing.search_submit') }}
                </button>
            </div>
        </form>
        <p class="text-sm leading-5 font-medium text-ink-muted">{{ __('web.phishing.total', ['count' => number_format($total, 0, ',', '.')]) }}</p>
    </div>

    @if ($search && $items->isEmpty())
        <div class="card mt-6 p-5 sm:p-6" role="status">
            <p class="font-semibold text-navy-900">{{ __('web.phishing.not_found', ['domain' => $search]) }}</p>
            <p class="mt-1 text-ink-muted">{{ __('web.phishing.not_found_hint') }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('home') }}#cek" class="btn-secondary">{{ __('web.phishing.check_now') }}</a>
                <a href="{{ route('phishing') }}" class="inline-flex min-h-12 items-center font-semibold text-navy-900 hover:underline">{{ __('web.phishing.search_reset') }}</a>
            </div>
        </div>
    @elseif ($items->isEmpty())
        <p class="card mt-6 p-6 text-ink-muted">{{ __('web.phishing.empty') }}</p>
    @else
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm leading-5">
                <thead class="border-b border-line bg-paper text-ink-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_domain') }}</th>
                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_reason') }}</th>
                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_reports') }}</th>
                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_last_seen') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2">
                                    <x-icon name="octagon-x" :size="16" class="text-danger-fg" />
                                    <span class="font-mono text-[15px] break-all text-ink">{{ $item['defanged'] }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $item['reason'] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ __('web.phishing.reports', ['count' => $item['reports']]) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $item['last_seen']->translatedFormat('j M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

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
    @endif

    @if ($whatsappUrl)
        <div class="mt-8 flex flex-col items-start gap-3 rounded-card bg-navy-900 p-5 text-white sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <p class="font-semibold">{{ __('web.phishing.whatsapp_title') }}</p>
                <p class="text-white/80">{{ __('web.phishing.whatsapp_body') }}</p>
            </div>
            <a href="{{ $whatsappUrl }}" class="inline-flex min-h-12 items-center gap-2 rounded-btn bg-white px-5 font-semibold text-navy-900 hover:bg-paper" rel="noopener">
                <x-icon name="message-circle" :size="18" />
                {{ __('web.home.whatsapp_cta') }}
            </a>
        </div>
    @endif

    <p class="mt-6 max-w-[720px] text-sm leading-5 text-ink-muted">{{ __('web.phishing.disclaimer', ['email' => config('sayno.contact_email')]) }}</p>
</x-layout>
