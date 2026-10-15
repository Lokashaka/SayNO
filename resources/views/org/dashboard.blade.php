<x-layout :title="__('web.org.title').' · '.$brand->name" wide robots="noindex">
    <div class="rounded-card bg-navy-900 p-5 text-white sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm leading-5 font-medium text-white/70">{{ __('web.org.title') }}</p>
                <h1 class="mt-1 text-[32px] leading-10 font-bold">{{ $brand->name }}</h1>
                <p class="mt-1 text-white/80">{{ __('web.org.subtitle', ['brand' => $brand->name]) }}</p>
            </div>
            <form method="POST" action="{{ route('org.logout', $brand) }}">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-btn border border-white/30 px-4 text-sm font-semibold hover:bg-white/10">
                    <x-icon name="log-out" :size="16" />
                    {{ __('web.org.logout') }}
                </button>
            </form>
        </div>

        <dl class="mt-6 grid gap-3 sm:grid-cols-3">
            @foreach (['lookalikes' => 'summary_lookalikes', 'fake_apks' => 'summary_fake_apks', 'reports_week' => 'summary_reports'] as $key => $label)
                <div class="rounded-btn bg-white/10 p-4">
                    <dt class="text-sm leading-5 font-medium text-white/80">{{ __("web.org.{$label}") }}</dt>
                    <dd class="mt-1 text-[32px] leading-10 font-bold">{{ number_format($summary[$key], 0, ',', '.') }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <section class="mt-8" aria-labelledby="lookalikes-title">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="lookalikes-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('web.org.lookalikes_title') }}</h2>
            <p class="text-sm leading-5 text-ink-muted">{{ __('web.org.last_scan') }}</p>
        </div>

        <div class="card mt-4 overflow-x-auto">
            @if ($lookalikes->isEmpty())
                <p class="p-6 text-ink-muted">{{ __('web.org.empty_lookalikes') }}</p>
            @else
                <table class="w-full min-w-[720px] text-left text-sm leading-5">
                    <thead class="border-b border-line bg-paper text-ink-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_domain') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_technique') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_registered') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_first_seen') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_source') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($lookalikes as $lookalike)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 font-mono break-all">
                                        @if ($lookalike->isNew())
                                            <span class="size-2 shrink-0 rounded-full bg-tolak" aria-hidden="true"></span>
                                            <span class="sr-only">{{ __('web.org.new') }}:</span>
                                        @endif
                                        {{ $lookalike->domain }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ __('signals.techniques.'.$lookalike->technique) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $lookalike->registered_at?->translatedFormat('j M Y') ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $lookalike->first_seen_at->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3">
                                    {{ __('web.org.source_'.$lookalike->source) }}
                                    @if ($lookalike->times_reported > 0)
                                        <span class="text-ink-muted">({{ __('web.org.times', ['count' => $lookalike->times_reported]) }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($lookalike->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-danger-bg px-2.5 py-1 text-danger-fg">
                                            <x-icon name="octagon-x" :size="16" />
                                            <span class="status-label">{{ __('web.org.status_active') }}</span>
                                        </span>
                                    @else
                                        <span class="text-ink-muted">{{ __('web.org.status_gone') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <section class="mt-8" aria-labelledby="apks-title">
        <h2 id="apks-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('web.org.fake_apks_title') }}</h2>

        <div class="card mt-4 overflow-x-auto">
            @if ($fakeApks->isEmpty())
                <p class="p-6 text-ink-muted">{{ __('web.org.empty_fake_apks') }}</p>
            @else
                <table class="w-full min-w-[720px] text-left text-sm leading-5">
                    <thead class="border-b border-line bg-paper text-ink-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_package') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.result.sha256') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_permissions') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_first_seen') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('web.org.col_times_seen') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($fakeApks as $apk)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 font-mono break-all">
                                        @if ($apk->isNew())
                                            <span class="size-2 shrink-0 rounded-full bg-tolak" aria-hidden="true"></span>
                                            <span class="sr-only">{{ __('web.org.new') }}:</span>
                                        @endif
                                        {{ $apk->package_name ?? '—' }}
                                    </span>
                                    @if ($apk->app_label)
                                        <span class="block text-ink-muted">{{ $apk->app_label }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs" title="{{ $apk->file_sha256 }}">{{ substr($apk->file_sha256, 0, 12) }}…</td>
                                <td class="px-4 py-3">
                                    @foreach (array_intersect($apk->permissions ?? [], $dangerousPermissions) as $permission)
                                        <span class="mr-1 mb-1 inline-block rounded bg-danger-bg px-1.5 py-0.5 font-mono text-xs text-danger-fg">{{ \Illuminate\Support\Str::after($permission, 'android.permission.') }}</span>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $apk->first_seen_at->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3">{{ __('web.org.times', ['count' => $apk->times_seen]) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</x-layout>
