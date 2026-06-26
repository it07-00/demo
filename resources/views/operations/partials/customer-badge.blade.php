@php
    $classes = $type === 'Trọng điểm' ? 'bg-danger-subtle text-danger' : 'bg-light text-muted';
@endphp

<span class="operation-chip {{ $classes }}">{{ $type }}</span>
