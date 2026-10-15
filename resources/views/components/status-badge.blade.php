@props(['status', 'size' => 'md'])

@php
    $styles = [
        'safe' => ['bg-safe-bg text-safe-fg', 'circle-check'],
        'caution' => ['bg-caution-bg text-caution-fg', 'triangle-alert'],
        'danger' => ['bg-danger-bg text-danger-fg', 'octagon-x'],
    ][$status->value];
@endphp

{{-- Status selalu warna + ikon + kata, tidak hanya warna. --}}
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full {$styles[0]} ".($size === 'lg' ? 'px-3.5 py-2' : 'px-2.5 py-1')]) }}>
    <x-icon :name="$styles[1]" :size="$size === 'lg' ? 20 : 16" />
    <span class="status-label">{{ $status->label() }}</span>
</span>
