@props(['variant' => 'light'])

@php
    $wrapper = $variant === 'dark'
        ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/25'
        : 'bg-white/15 text-white ring-1 ring-white/25 backdrop-blur';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-2xl {$wrapper}"]) }}>
    <svg class="size-[55%]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M3 9.4 12 5l9 4.4-9 4.4L3 9.4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
        <path d="M6.4 11.3v4.2c0 .5.3 1 .8 1.2 1.5.7 3 1 4.8 1s3.3-.3 4.8-1c.5-.2.8-.7.8-1.2v-4.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
        <path d="M21 10v4.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
    </svg>
</span>
