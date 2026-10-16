<x-layout>
    <section aria-labelledby="hero-title">
        <h1 id="hero-title" class="text-[32px] leading-10 font-extrabold text-navy-900 sm:text-[40px] sm:leading-[48px]">
            {{ __('web.home.hero') }}
        </h1>
        <p class="mt-4 text-ink-muted">{{ __('web.home.lead') }}</p>

        <div class="mt-6">
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" class="btn-secondary" rel="noopener">
                    <x-icon name="message-circle" />
                    {{ __('web.home.whatsapp_cta') }}
                </a>
                <p class="mt-3 text-sm leading-5 text-ink-muted">{{ __('web.home.or_web') }}</p>
            @else
                <p class="inline-flex items-start gap-2 text-sm leading-5 text-ink-muted">
                    <x-icon name="message-circle" :size="18" class="mt-px" />
                    {{ __('web.home.whatsapp_soon') }}
                </p>
            @endif
        </div>
    </section>

    <section id="cek" class="card mt-8 scroll-mt-6 p-4 shadow-card sm:p-6" aria-label="{{ __('web.nav.check') }}">
        @error('throttle')
            <p class="mb-4 rounded-btn bg-caution-bg px-4 py-3 text-caution-fg" role="alert">{{ $message }}</p>
        @enderror

        @php($apkTab = $errors->has('apk'))

        <div class="grid grid-cols-2 gap-1 rounded-btn bg-paper p-1" role="tablist" data-tabs>
            <button type="button" role="tab" id="tab-link" aria-controls="panel-link" aria-selected="{{ $apkTab ? 'false' : 'true' }}" class="tab">
                <x-icon name="link" :size="18" />
                {{ __('web.home.tab_link') }}
            </button>
            <button type="button" role="tab" id="tab-apk" aria-controls="panel-apk" aria-selected="{{ $apkTab ? 'true' : 'false' }}" class="tab">
                <x-icon name="file" :size="18" />
                {{ __('web.home.tab_apk') }}
            </button>
        </div>

        <div id="panel-link" role="tabpanel" aria-labelledby="tab-link" class="mt-5" @if ($apkTab) data-hidden @endif>
            <form method="POST" action="{{ route('check.link') }}" data-checking-form>
                @csrf
                <label for="input" class="block font-semibold text-navy-900">{{ __('web.home.link_label') }}</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:gap-0">
                    <input
                        type="text"
                        id="input"
                        name="input"
                        value="{{ old('input') }}"
                        required
                        maxlength="4000"
                        inputmode="url"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        placeholder="{{ __('web.home.link_placeholder') }}"
                        aria-describedby="input-hint @error('input') input-error @enderror"
                        @error('input') aria-invalid="true" @enderror
                        class="h-14 w-full min-w-0 rounded-btn border border-line bg-white px-4 text-base text-ink placeholder:text-ink-muted focus:border-navy-700 sm:rounded-r-none sm:border-r-0"
                    >
                    <button type="submit" class="btn-primary h-14 shrink-0 sm:rounded-l-none sm:px-6">
                        <x-icon name="search" :size="18" />
                        <span data-label>{{ __('web.home.link_submit') }}</span>
                    </button>
                </div>
                @error('input')
                    <p id="input-error" class="mt-2 text-danger-fg" role="alert">{{ $message }}</p>
                @enderror
                <p id="input-hint" class="mt-2 text-sm leading-5 text-ink-muted">{{ __('web.home.link_hint') }}</p>
            </form>
        </div>

        <div id="panel-apk" role="tabpanel" aria-labelledby="tab-apk" class="mt-5" @unless ($apkTab) data-hidden @endunless>
            <form
                method="POST"
                action="{{ route('check.apk') }}"
                enctype="multipart/form-data"
                data-apk-form
                data-server-max-bytes="{{ \App\Support\UploadLimit::apkServerBytes() }}"
                data-browser-max-bytes="{{ config('sayno.apk.browser_max_bytes') }}"
                data-unreadable="{{ __('result.apk_unreadable') }}"
                data-unsupported="{{ __('web.home.apk_unsupported') }}"
            >
                @csrf
                <span id="apk-label" class="block font-semibold text-navy-900">{{ __('web.home.apk_label') }}</span>
                <label for="apk" data-dropzone class="mt-2 flex min-h-36 cursor-pointer flex-col items-center justify-center gap-2 rounded-card border-2 border-dashed border-line bg-paper px-4 py-6 text-center transition-colors hover:border-navy-700">
                    <x-icon name="upload" :size="24" class="text-navy-700" />
                    <span class="font-semibold text-navy-900">{{ __('web.home.apk_drop') }}</span>
                    <span class="text-sm leading-5 text-ink-muted" id="apk-hint">{{ __('web.home.apk_hint') }}</span>
                    <span class="hidden text-sm leading-5 font-semibold text-ink" data-file-name aria-live="polite"></span>
                </label>
                <input
                    type="file"
                    id="apk"
                    name="apk"
                    accept=".apk,application/vnd.android.package-archive"
                    required
                    class="sr-only"
                    aria-labelledby="apk-label"
                    aria-describedby="apk-hint @error('apk') apk-error @enderror"
                    @error('apk') aria-invalid="true" @enderror
                >
                <p id="apk-error" class="mt-2 text-danger-fg {{ $errors->has('apk') ? '' : 'hidden' }}" role="alert" data-apk-error>@error('apk'){{ $message }}@enderror</p>
                <button type="submit" class="btn-primary mt-4 w-full sm:w-auto" data-too-large="{{ __('web.home.apk_too_large') }}" data-chosen="{{ __('web.home.apk_chosen') }}">
                    <x-icon name="shield-check" :size="18" />
                    <span data-label>{{ __('web.home.apk_submit') }}</span>
                </button>
            </form>

            {{-- APK di atas batas unggah: browser mengisi form ini dengan manifest dan SHA-256 saja. --}}
            <form method="POST" action="{{ route('check.apk-manifest') }}" enctype="multipart/form-data" hidden data-apk-manifest-form>
                @csrf
                <input type="file" name="manifest" tabindex="-1">
                <input type="hidden" name="sha256">
                <input type="hidden" name="file_name">
                <input type="hidden" name="file_size">
            </form>
        </div>

        <template data-checking-text>{{ __('web.home.checking') }}</template>
    </section>

    <section class="mt-12" aria-labelledby="how-title">
        <h2 id="how-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('web.home.how_title') }}</h2>
        <ol class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach (__('web.home.how') as $step)
                <li class="card p-4">
                    <span class="flex size-8 items-center justify-center rounded-full bg-navy-900 text-sm font-bold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                    <h3 class="mt-3 text-lg leading-[26px] font-semibold text-navy-900">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-ink-muted">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="mt-12" aria-labelledby="modus-title">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="modus-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('web.home.modus_title') }}</h2>
            <a href="{{ route('modus') }}" class="inline-flex min-h-11 items-center gap-1 font-semibold text-navy-900 hover:underline">
                {{ __('web.home.modus_all') }}
                <x-icon name="arrow-right" :size="18" />
            </a>
        </div>
        <ul class="mt-4 grid gap-3">
            @foreach ($modus as $item)
                <li>
                    <a href="{{ route('modus') }}#{{ $item['slug'] }}" class="card block p-4 transition-colors hover:border-navy-700">
                        <h3 class="text-lg leading-[26px] font-semibold text-navy-900">{{ $item['title'] }}</h3>
                        <p class="mt-1 text-ink-muted">{{ $item['summary'] }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mt-12" aria-labelledby="stats-title">
        <h2 id="stats-title" class="text-2xl leading-8 font-bold text-navy-900">{{ __('web.home.stats_title') }}</h2>
        <dl class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach (['total' => 'stats_total', 'danger_week' => 'stats_danger_week', 'lookalikes' => 'stats_lookalikes'] as $key => $label)
                <div class="card p-4">
                    <dt class="text-sm leading-5 font-medium text-ink-muted">{{ __("web.home.{$label}") }}</dt>
                    <dd class="mt-1 text-[32px] leading-10 font-bold text-navy-900">{{ number_format($stats[$key], 0, ',', '.') }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
</x-layout>
