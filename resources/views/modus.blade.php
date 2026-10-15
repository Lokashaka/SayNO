<x-layout :title="__('web.modus.title')">
    <h1 class="text-[32px] leading-10 font-bold text-navy-900">{{ __('web.modus.title') }}</h1>
    <p class="mt-3 text-ink-muted">{{ __('web.modus.lead') }}</p>

    <div class="mt-8 space-y-4">
        @foreach ($items as $item)
            <article id="{{ $item['slug'] }}" class="card scroll-mt-6 p-5 sm:p-6" aria-labelledby="{{ $item['slug'] }}-title">
                <h2 id="{{ $item['slug'] }}-title" class="text-2xl leading-8 font-bold text-navy-900">{{ $item['title'] }}</h2>
                <p class="mt-2 text-ink-muted">{{ $item['summary'] }}</p>

                <figure class="mt-4">
                    <figcaption class="text-sm leading-5 font-medium text-ink-muted">{{ __('web.modus.example') }}</figcaption>
                    <blockquote class="mt-2 rounded-btn border-l-4 border-line bg-paper px-4 py-3 whitespace-pre-line">{{ $item['example'] }}</blockquote>
                </figure>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <h3 class="flex items-center gap-2 text-lg leading-[26px] font-semibold text-caution-fg">
                            <x-icon name="triangle-alert" :size="18" />
                            {{ __('web.modus.signs') }}
                        </h3>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($item['signs'] as $sign)
                                <li>{{ $sign }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h3 class="flex items-center gap-2 text-lg leading-[26px] font-semibold text-safe-fg">
                            <x-icon name="shield-check" :size="18" />
                            {{ __('web.modus.avoid') }}
                        </h3>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($item['avoid'] as $tip)
                                <li>{{ $tip }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-8 flex flex-col items-start gap-3 rounded-card bg-navy-900 p-5 text-white sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <p class="font-semibold">{{ __('web.modus.cta') }}</p>
        <a href="{{ route('home') }}#cek" class="inline-flex min-h-12 items-center gap-2 rounded-btn bg-white px-5 font-semibold text-navy-900 hover:bg-paper">
            {{ __('web.home.link_submit') }}
            <x-icon name="arrow-right" :size="18" />
        </a>
    </div>
</x-layout>
