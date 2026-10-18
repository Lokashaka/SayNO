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
        <p class="text-sm leading-5 font-medium text-ink-muted">{{ __('web.phishing.total', ['count' => number_format($total + $lookalikeTotal, 0, ',', '.')]) }}</p>
    </div>

    @if ($search && $items->isEmpty() && $lookalikes->isEmpty())
        <div class="card mt-6 p-5 sm:p-6" role="status">
            <p class="font-semibold text-navy-900">{{ __('web.phishing.not_found', ['domain' => $search]) }}</p>
            <p class="mt-1 text-ink-muted">{{ __('web.phishing.not_found_hint') }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('home') }}#cek" class="btn-secondary">{{ __('web.phishing.check_now') }}</a>
                <a href="{{ route('phishing') }}" class="inline-flex min-h-12 items-center font-semibold text-navy-900 hover:underline">{{ __('web.phishing.search_reset') }}</a>
            </div>
        </div>
    @else
        @unless ($search && $items->isEmpty())
            <section class="mt-8" aria-labelledby="bahaya-title">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="bahaya-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('web.phishing.danger_title') }}</h2>
                    <p class="text-sm leading-5 font-medium text-ink-muted">{{ __('web.phishing.total', ['count' => number_format($total, 0, ',', '.')]) }}</p>
                </div>
                <p class="mt-1 text-ink-muted">{{ __('web.phishing.danger_lead') }}</p>

                @if ($items->isEmpty())
                    <p class="card mt-4 p-6 text-ink-muted">{{ __('web.phishing.danger_empty') }}</p>
                @else
                    <div class="card mt-4 overflow-x-auto">
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

                    <x-pager :items="$items" />
                @endif
            </section>
        @endunless

        @unless ($search && $lookalikes->isEmpty())
            <section id="tiruan" class="mt-10" aria-labelledby="tiruan-title">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="tiruan-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('web.phishing.lookalike_title') }}</h2>
                    <p class="text-sm leading-5 font-medium text-ink-muted">{{ __('web.phishing.total', ['count' => number_format($lookalikeTotal, 0, ',', '.')]) }}</p>
                </div>
                <p class="mt-1 max-w-[720px] text-ink-muted">{{ __('web.phishing.lookalike_lead') }}</p>

                @if ($lookalikes->isEmpty())
                    <p class="card mt-4 p-6 text-ink-muted">{{ __('web.phishing.lookalike_empty') }}</p>
                @else
                    <div class="card mt-4 overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-sm leading-5">
                            <thead class="border-b border-line bg-paper text-ink-muted">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_domain') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_brand') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_technique') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_registered') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.phishing.col_first_seen') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($lookalikes as $lookalike)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <span class="font-mono text-[15px] break-all text-ink">{{ $lookalike['defanged'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($lookalike['suspicious'])
                                                <span class="inline-flex items-center gap-1.5 text-danger-fg">
                                                    <x-icon name="octagon-x" :size="16" />
                                                    <span class="font-semibold">{{ __('web.phishing.status_suspicious') }}</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 text-caution-fg">
                                                    <x-icon name="triangle-alert" :size="16" />
                                                    <span>{{ __('web.phishing.status_new') }}</span>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $lookalike['brand'] }}</td>
                                        <td class="px-4 py-3">{{ $lookalike['technique'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $lookalike['registered_at']?->translatedFormat('j M Y') ?? __('web.phishing.unknown_date') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $lookalike['first_seen']->translatedFormat('j M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <x-pager :items="$lookalikes" />
                @endif
            </section>
        @endunless
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
