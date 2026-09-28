@props(['name' => 'grid'])

@php
    $paths = [
        'grid'        => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'box'         => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>',
        'truck'       => '<path d="M3 6h11v9H3z"/><path d="M14 9h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'inbox'       => '<path d="M3 13h5l1 3h6l1-3h5"/><path d="M4 5h16l1 8v6H3v-6z"/>',
        'alert'       => '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'clipboard'   => '<rect x="8" y="3" width="8" height="4" rx="1"/><path d="M16 5h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h4"/>',
        'check'       => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle'=> '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
        'trash'       => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/>',
        'cart'        => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 12h11L21 7H6"/>',
        'stack'       => '<path d="M12 2 2 7l10 5 10-5z"/><path d="M2 12l10 5 10-5"/><path d="M2 17l10 5 10-5"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'wallet'      => '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6H7a2 2 0 0 1 0-4"/><path d="M17 13h.01"/>',
        'users'       => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 11a3 3 0 1 0-2-5.2"/><path d="M17.5 14a5.5 5.5 0 0 1 4 5.3"/>',
        'calendar'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M8 3v4"/><path d="M16 3v4"/>',
        'file-text'   => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/>',
        'chef'        => '<path d="M7 21h10"/><path d="M7 17h10v4H7z"/><path d="M6.5 17a5.5 5.5 0 0 1-1.2-9.4 4 4 0 0 1 7.2-2.2 4 4 0 0 1 6.6 3.2 5.5 5.5 0 0 1-1.6 8.4"/>',
        'receipt'     => '<path d="M5 3v18l2.5-1.6L10 21l2.5-1.6L15 21l2.5-1.6L20 21V3z"/><path d="M9 8h7"/><path d="M9 12h7"/><path d="M9 16h4"/>',
        'chart'       => '<path d="M3 3v18h18"/><path d="M7 15l4-5 3.5 3L21 6"/>',
        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 7 19.4a1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 15a1.7 1.7 0 0 0-1.6-1H1a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 3 9a1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 9 3.4V3a2 2 0 1 1 4 0v.1A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.7 1.7 0 0 0 21 9h.1a2 2 0 1 1 0 4H21a1.7 1.7 0 0 0-1.6 1z"/>',
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'plus'        => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'arrow-left'  => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
        'download'    => '<path d="M12 3v12"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/>',
        'key'         => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 9.2-9.2"/><path d="m17 6 3 3"/><path d="m15 8 2 2"/>',
        'bell'        => '<path d="M18 8a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7"/><path d="M10.5 20a1.8 1.8 0 0 0 3 0"/>',
        'sun'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/>',
        'moon'        => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8"/>',
        'x'           => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'edit'        => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'wrench'      => '<path d="M14.7 6.3a4 4 0 0 0 5 5l-9 9a2.8 2.8 0 1 1-4-4z"/>',
        'flame'       => '<path d="M12 22a7 7 0 0 0 7-7c0-5-4-6-4-10 0 0-3 1-3 5 0-2-2-3-2-3s-5 3-5 8a7 7 0 0 0 7 7z"/>',
        'activity'    => '<path d="M22 12h-4l-3 8-6-16-3 8H2"/>',
        'pound'       => '<path d="M17 19H7c1.8-1.2 2.5-2.8 2.5-5V9a4 4 0 0 1 7-2.6"/><path d="M6.5 13H13"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-5']) }} viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? $paths['grid'] !!}
</svg>
