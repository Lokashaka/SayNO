{{-- Halaman Kebijakan Privasi dan Syarat & Ketentuan. Teks di lang/id/{privacy,terms}.php. --}}
@php
    $email = config('sayno.contact_email');
    $emailLink = '<a href="mailto:'.e($email).'" class="font-semibold text-navy-900 underline">'.e($email).'</a>';
@endphp

<x-layout :title="$title">
    <h1 class="text-[32px] leading-10 font-bold text-navy-900">{{ $title }}</h1>
    <p class="mt-2 text-sm leading-5 text-ink-muted">{{ __('web.legal.updated') }}</p>
    <p class="mt-4">{{ __("{$key}.intro") }}</p>

    @foreach (__("{$key}.sections", ['email' => $email]) as $section)
        <section @isset($section['id']) id="{{ $section['id'] }}" @endisset class="mt-8 scroll-mt-6">
            <h2 class="text-2xl leading-8 font-bold text-navy-900">{{ $section['title'] }}</h2>
            <ul class="mt-3 list-disc space-y-2 pl-5">
                @foreach ($section['items'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>
    @endforeach

    @if (\Illuminate\Support\Facades\Lang::has("{$key}.law"))
        <p class="mt-8">{{ __("{$key}.law") }}</p>
    @endif

    <section class="mt-8">
        <h2 class="text-2xl leading-8 font-bold text-navy-900">{{ __("{$key}.contact_title") }}</h2>
        <p class="mt-3">{!! __("{$key}.contact", ['email' => $emailLink]) !!}</p>
    </section>

    <p class="mt-8 text-sm leading-5 text-ink-muted">
        @if ($key === 'privacy')
            <a href="{{ route('terms') }}" class="font-semibold text-navy-900 hover:underline">{{ __('web.nav.terms') }}</a>
        @else
            <a href="{{ route('privacy') }}" class="font-semibold text-navy-900 hover:underline">{{ __('web.nav.privacy_policy') }}</a>
        @endif
    </p>
</x-layout>
