@php
    $value = max(0, min(100, (int) round($value)));
@endphp

<div class="operation-progress">
    <div class="operation-progress-bar {{ $class ?? 'bg-primary' }}" style="width: {{ $value }}%"></div>
</div>
