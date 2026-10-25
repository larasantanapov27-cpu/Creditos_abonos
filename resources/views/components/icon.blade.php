@props(['name'=>'home'])
@php
    $paths = [
        'home' => 'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M20 21v-2a4 4 0 0 0-3-3.87 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M17 3a4 4 0 0 1 0 8',
        'wallet' => 'M20 8V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h15v12H5a3 3 0 0 1-3-3V6 M20 12h-5v5h5 M17 14.5h.01',
        'receipt' => 'M5 3h14v18l-3-2-4 2-4-2-3 2Z M8 7h8 M8 11h8 M8 15h4',
        'clock' => 'M12 8v4l3 2 M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
        'settings' => 'M4 6h16 M4 12h16 M4 18h16 M8 3v6 M16 9v6 M10 15v6',
        'logout' => 'M9 3H3v18h6 M10 12h12l-5-5 M22 12l-5 5',
        'menu' => 'M3 6h18 M3 12h18 M3 18h18',
        'chevron' => 'm6 9 6 6 6-6',
        'plus' => 'M12 5v14 M5 12h14',
        'arrow' => 'M5 12h14 M13 6l6 6-6 6',
        'search' => 'M21 21l-5-5 M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
        'print' => 'M6 9V3h12v6 M6 17H3V9h18v8h-3 M6 14h12v7H6Z',
        'check' => 'm5 12 4 4L19 6',
    ];
@endphp
<svg
    {{ $attributes->class(['icon']) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.65"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    <path d="{{ $paths[$name] ?? $paths['home'] }}"/>
</svg>
