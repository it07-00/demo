<div class="card border-0 shadow-sm operation-kpi-card h-100">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div class="min-w-0">
                <div class="text-muted text-sm fw-semibold">{{ $label }}</div>
                <div class="h3 mb-1 text-dark">{{ $value }}</div>
                @isset($sub)
                    <div class="text-muted text-xs">{{ $sub }}</div>
                @endisset
            </div>
            <span class="operation-kpi-icon {{ $accent ?? 'bg-primary-subtle text-primary' }}">
                <i class="{{ $icon ?? 'fi fi-rr-apps' }}"></i>
            </span>
        </div>
    </div>
</div>
