<x-layout :title="__('setup.title')" robots="noindex">
    <h1 class="text-[32px] leading-10 font-bold text-navy-900">{{ __('setup.title') }}</h1>
    <p class="mt-2 text-ink-muted">{{ __('setup.lead') }}</p>

    @if ($output)
        <section class="card mt-6 p-4 sm:p-5" aria-labelledby="output-title">
            <h2 id="output-title" class="font-semibold text-navy-900">{{ __('setup.output_title', ['action' => __("setup.actions.{$ranAction}.label")]) }}</h2>
            <pre class="mt-3 overflow-x-auto rounded-btn bg-paper p-3 font-mono text-xs leading-5 whitespace-pre-wrap">{{ $output }}</pre>
        </section>
    @endif

    <section class="mt-8" aria-labelledby="actions-title">
        <h2 id="actions-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('setup.actions_title') }}</h2>
        <div class="mt-4 grid gap-3">
            @foreach (__('setup.actions') as $action => $text)
                <form method="POST" action="{{ route('setup.run') }}" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" data-checking-form>
                    @csrf
                    <input type="hidden" name="action" value="{{ $action }}">
                    <div>
                        <p class="font-semibold text-navy-900">{{ $text['label'] }}</p>
                        <p class="text-sm leading-5 text-ink-muted">{{ $text['hint'] }}</p>
                    </div>
                    <button type="submit" class="btn-secondary shrink-0"><span data-label>{{ $text['label'] }}</span></button>
                </form>
            @endforeach
        </div>
    </section>

    <section class="mt-8" aria-labelledby="checks-title">
        <h2 id="checks-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('setup.checks_title') }}</h2>
        <ul class="card mt-4 divide-y divide-line">
            @foreach ($checks as $check)
                <li class="flex items-start gap-3 px-4 py-3">
                    @if ($check['ok'])
                        <x-icon name="circle-check" :size="20" class="mt-0.5 text-safe-fg" />
                    @else
                        <x-icon :name="$check['required'] ? 'octagon-x' : 'triangle-alert'" :size="20" @class(['mt-0.5', 'text-danger-fg' => $check['required'], 'text-caution-fg' => ! $check['required']]) />
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">
                            {{ $check['label'] }}
                            <span class="text-sm text-ink-muted">({{ $check['required'] ? __('setup.required') : __('setup.optional') }})</span>
                        </p>
                        <p class="text-sm leading-5 break-all text-ink-muted">{{ $check['value'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
    <template data-checking-text>{{ __('web.home.checking') }}</template>
</x-layout>
