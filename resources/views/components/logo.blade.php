@props(['variant' => 'warna', 'size' => 36])

@php
    // Proporsi dari artboard Figma: ikon 104 px, wordmark 72 px, jarak 20 px.
    $textSize = round($size * 72 / 104, 1);
    $gap = round($size * 20 / 104, 1);
    $sayColor = $variant === 'gelap' ? 'text-white' : 'text-navy-900';
    $noColor = $variant === 'satu-warna' ? 'text-navy-900' : 'text-tolak';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center']) }} style="gap: {{ $gap }}px">
    <img src="{{ asset("brand/ikon-{$variant}.svg") }}" width="{{ $size }}" height="{{ $size }}" alt="" class="block shrink-0">
    <span class="font-extrabold leading-none whitespace-nowrap tracking-[-0.02em]" style="font-size: {{ $textSize }}px"><span class="{{ $sayColor }}">Say</span><span class="{{ $noColor }}">NO</span></span>
</span>
