@php
    $status = $summary['status'];
    $type = $summary['type'];
@endphp

<x-layout :title="__('web.result.page_title')" robots="noindex">
    <article class="card p-5 shadow-card sm:p-8" aria-labelledby="result-title">
        <x-status-badge :status="$status" size="lg" />

        <h1 id="result-title" class="mt-4 text-[32px] leading-10 font-bold text-navy-900">{{ $summary['title'] }}</h1>

        @if ($type === 'link' && ! empty($info['host']))
            <p class="mt-2 text-ink-muted">
                {{ __('web.result.checked_link') }}:
                <span class="font-semibold break-all text-ink">{{ $info['host'] }}</span>
            </p>
        @elseif ($type === 'apk' && $fileName)
            <p class="mt-2 text-ink-muted">
                {{ __('web.result.checked_apk') }}:
                <span class="font-semibold break-all text-ink">{{ $fileName }}</span>
            </p>
        @endif

        <section class="mt-6" aria-labelledby="reasons-title">
            <h2 id="reasons-title" class="sr-only">{{ __('web.result.reasons') }}</h2>
            <ul class="space-y-2">
                @foreach ($summary['reasons'] as $reason)
                    <li class="flex gap-3">
                        <span @class([
                            'mt-[9px] size-2 shrink-0 rounded-full',
                            'bg-danger-fg' => $status->value === 'danger',
                            'bg-caution-fg' => $status->value === 'caution',
                            'bg-safe-fg' => $status->value === 'safe',
                        ]) aria-hidden="true"></span>
                        <span>{{ $reason }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="mt-6 rounded-btn bg-paper p-4 sm:p-5" aria-labelledby="todo-title">
            <h2 id="todo-title" class="text-lg leading-[26px] font-semibold text-navy-900">{{ __('web.result.what_to_do') }}</h2>
            <ol class="mt-2 list-decimal space-y-1 pl-5 marker:font-semibold marker:text-navy-900">
                @foreach ($summary['actions'] as $action)
                    <li>{{ $action }}</li>
                @endforeach
            </ol>
        </section>

        <details class="group mt-6 border-t border-line pt-4">
            <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-btn font-semibold text-navy-900 [&::-webkit-details-marker]:hidden">
                <x-icon name="chevron-down" :size="18" class="transition-transform group-open:rotate-180" />
                {{ __('web.result.technical') }}
            </summary>

            <dl class="mt-3 grid gap-x-6 gap-y-3 text-sm leading-5 sm:grid-cols-[max-content_1fr]">
                <dt class="font-medium text-ink-muted">{{ __('web.result.score') }}</dt>
                <dd class="font-semibold">{{ __('web.result.score_value', ['score' => $check->score]) }}</dd>

                @if ($type === 'link')
                    <dt class="font-medium text-ink-muted">{{ __('web.result.host') }}</dt>
                    <dd class="font-mono break-all">{{ $info['host'] ?? '—' }}</dd>

                    <dt class="font-medium text-ink-muted">{{ __('web.result.domain') }}</dt>
                    <dd class="font-mono break-all">{{ $info['domain'] ?? '—' }}</dd>

                    <dt class="font-medium text-ink-muted">{{ __('web.result.registered_at') }}</dt>
                    <dd>
                        @if (! empty($info['registered_at']))
                            {{ \Illuminate\Support\Carbon::parse($info['registered_at'])->translatedFormat('j F Y') }}
                            ({{ __('web.result.domain_age_value', ['days' => number_format($info['age_days'], 0, ',', '.')]) }})
                        @else
                            {{ __('web.result.unknown') }}
                        @endif
                    </dd>

                    <dt class="font-medium text-ink-muted">{{ __('web.result.safe_browsing') }}</dt>
                    <dd>{{ __('web.result.safe_browsing_values.'.($info['safe_browsing'] ?? 'unchecked')) }}</dd>

                    @if ($lookalike)
                        <dt class="font-medium text-ink-muted">{{ __('web.result.lookalike_of') }}</dt>
                        <dd class="font-mono break-all">{{ $lookalike['official'] }}</dd>

                        <dt class="font-medium text-ink-muted">{{ __('web.result.technique') }}</dt>
                        <dd>{{ __('signals.techniques.'.$lookalike['technique']) }}</dd>
                    @endif
                @endif

                @if ($type === 'apk')
                    <dt class="font-medium text-ink-muted">{{ __('web.result.package') }}</dt>
                    <dd class="font-mono break-all">{{ $info['package'] ?? __('web.result.unknown') }}</dd>

                    @if (! empty($info['label']))
                        <dt class="font-medium text-ink-muted">{{ __('web.result.app_label') }}</dt>
                        <dd class="break-all">{{ $info['label'] }}</dd>
                    @endif

                    <dt class="font-medium text-ink-muted">{{ __('web.result.version') }}</dt>
                    <dd>{{ $info['version'] ?? __('web.result.unknown') }}</dd>

                    @if (! empty($info['size_bytes']))
                        <dt class="font-medium text-ink-muted">{{ __('web.result.file_size') }}</dt>
                        <dd>{{ $info['size_bytes'] >= 1048576
                            ? number_format($info['size_bytes'] / 1048576, 1, ',', '.').' MB'
                            : number_format($info['size_bytes'] / 1024, 0, ',', '.').' KB' }}</dd>
                    @endif

                    @if (! empty($info['read_by']))
                        <dt class="font-medium text-ink-muted">{{ __('web.result.read_by') }}</dt>
                        <dd>{{ __('web.result.read_by_values.'.$info['read_by']) }}</dd>
                    @endif

                    <dt class="font-medium text-ink-muted">{{ __('web.result.sha256') }}</dt>
                    <dd class="font-mono text-xs leading-5 break-all">{{ $info['sha256'] ?? '—' }}</dd>

                    <dt class="font-medium text-ink-muted">{{ __('web.result.permissions') }}</dt>
                    <dd>
                        @forelse ($info['permissions'] ?? [] as $permission)
                            <span class="mr-1 mb-1 inline-block rounded bg-paper px-1.5 py-0.5 font-mono text-xs leading-5 break-all">{{ $permission }}</span>
                        @empty
                            {{ __('web.result.no_permissions') }}
                        @endforelse
                    </dd>
                @endif

                @if ($patterns !== [])
                    <dt class="font-medium text-ink-muted">{{ __('web.result.patterns') }}</dt>
                    <dd>{{ collect($patterns)->map(fn ($category) => __("modus.categories.{$category}"))->join(', ') }}</dd>
                @endif

                <dt class="font-medium text-ink-muted">{{ __('web.result.checked_at') }}</dt>
                <dd>{{ $check->created_at->timezone(config('app.timezone'))->translatedFormat('j F Y, H.i') }}</dd>
            </dl>
        </details>
    </article>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <button
            type="button"
            class="btn-secondary"
            data-share
            data-share-url="{{ route('result', $check) }}"
            data-share-text="{{ __('web.result.share_text', ['status' => $status->label(), 'title' => $summary['title']]) }}"
            data-copied="{{ __('web.result.copied') }}"
        >
            <x-icon name="share" :size="18" />
            <span data-label>{{ __('web.result.share') }}</span>
        </button>
        <a href="{{ route('home') }}#cek" class="btn-secondary">
            <x-icon name="arrow-left" :size="18" />
            {{ __('web.result.check_again') }}
        </a>
    </div>

    <p class="mt-6 text-sm leading-5 text-ink-muted">{{ __('web.result.not_guarantee') }}</p>
</x-layout>
