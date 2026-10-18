@props(['title' => null, 'wide' => false, 'robots' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · SayNO' : 'SayNO · '.__('web.home.title') }}</title>
    <meta name="description" content="{{ __('web.meta_description') }}">
    @if ($robots)
        <meta name="robots" content="{{ $robots }}">
    @endif
    <meta name="theme-color" content="#0F1E3D">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#isi" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-btn focus:bg-white focus:px-4 focus:py-3 focus:shadow-card">{{ __('web.skip_to_content') }}</a>

    <header class="border-b border-line bg-white">
        <div class="mx-auto flex max-w-[1120px] flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center rounded-btn" aria-label="SayNO, ke beranda">
                <x-logo :size="36" />
            </a>
            <nav aria-label="Utama" class="-mx-2 flex flex-wrap items-center text-[15px] font-semibold text-navy-900">
                <a href="{{ route('home') }}#cek" class="inline-flex min-h-11 items-center rounded-btn px-2 hover:text-navy-700 hover:underline">{{ __('web.nav.check') }}</a>
                <a href="{{ route('phishing') }}" @class(['inline-flex min-h-11 items-center rounded-btn px-2 hover:underline', 'underline decoration-2 underline-offset-8' => request()->routeIs('phishing')]) @if (request()->routeIs('phishing')) aria-current="page" @endif>{{ __('web.nav.phishing') }}</a>
                <a href="{{ route('modus') }}"@class(['inline-flex min-h-11 items-center rounded-btn px-2 hover:underline', 'underline decoration-2 underline-offset-8' => request()->routeIs('modus')]) @if (request()->routeIs('modus')) aria-current="page" @endif>{{ __('web.nav.modus') }}</a>
                <a href="{{ route('privacy') }}" @class(['inline-flex min-h-11 items-center rounded-btn px-2 hover:underline', 'underline decoration-2 underline-offset-8' => request()->routeIs('privacy')]) @if (request()->routeIs('privacy')) aria-current="page" @endif>{{ __('web.nav.privacy') }}</a>
            </nav>
        </div>
    </header>

    <main id="isi" @class(['mx-auto w-full flex-1 px-4 py-8 sm:px-6 sm:py-12', 'max-w-[1120px]' => $wide, 'max-w-[720px]' => ! $wide])>
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-white">
        <div class="mx-auto flex max-w-[1120px] flex-col gap-4 px-4 py-8 text-sm leading-5 text-ink-muted sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <span class="inline-flex items-center gap-3">
                    <x-logo :size="24" />
                    <span>{{ __('web.footer.tagline') }}</span>
                </span>
                <span class="flex flex-wrap gap-x-4">
                    <a href="{{ route('privacy') }}" class="inline-flex min-h-11 items-center font-semibold text-navy-900 hover:underline">{{ __('web.nav.privacy_policy') }}</a>
                    <a href="{{ route('terms') }}" class="inline-flex min-h-11 items-center font-semibold text-navy-900 hover:underline">{{ __('web.nav.terms') }}</a>
                </span>
            </div>
            <p class="max-w-[640px]">{{ __('web.footer.disclaimer') }}</p>
        </div>
    </footer>
</body>
</html>
