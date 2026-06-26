@php
    $isOperating = $status === 'Đang vận hành';
    $classes = $isOperating ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
    $dot = $isOperating ? 'bg-success' : 'bg-warning';
@endphp

<span class="operation-status {{ $classes }}">
    <span class="operation-status-dot {{ $dot }}"></span>
    {{ $status }}
</span>
