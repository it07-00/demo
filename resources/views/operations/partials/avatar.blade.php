@php
    $parts = preg_split('/\s+/u', trim($name));
    $first = $parts[0] ?? 'U';
    $last = $parts[count($parts) - 1] ?? $first;
    $initials = mb_strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));
    $colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-danger', 'bg-warning', 'bg-secondary'];
    $color = $colors[crc32($name) % count($colors)];
@endphp

<span class="operation-avatar {{ $size ?? '' }} {{ $color }}" title="{{ $name }}">{{ $initials }}</span>
