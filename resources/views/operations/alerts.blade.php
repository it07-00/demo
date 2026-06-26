@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}?v=1.0.0">
@endpush

@section('content')
@php
    $fmt = static fn ($number): string => number_format((float) $number, 0, ',', '.');
    $levelQuery = request()->query();
    $preview = collect($alerts)->firstWhere('level', 'red') ?? ($alerts[0] ?? null);
@endphp

<div class="operation-page">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Cảnh báo vận hành</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cảnh báo</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('operations.projects') }}" class="btn btn-outline-primary">
            <i class="fi fi-rr-briefcase me-1"></i> Xem dự án
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Tổng cảnh báo',
                'value' => count($alerts),
                'sub' => 'đang cần theo dõi',
                'icon' => 'fi fi-rr-bell-ring',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Khẩn cấp',
                'value' => $kpi['red_alerts'],
                'sub' => 'cần xử lý ngay',
                'icon' => 'fi fi-rr-triangle-warning',
                'accent' => 'bg-danger-subtle text-danger',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Lưu ý',
                'value' => $kpi['amber_alerts'],
                'sub' => 'cần xem xét sớm',
                'icon' => 'fi fi-rr-info',
                'accent' => 'bg-warning-subtle text-warning',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Công nợ đến hạn',
                'value' => $fmt($kpi['receivable_due_soon']).' tr',
                'sub' => collect($alerts)->where('rule', 'Công nợ đến hạn')->count().' khoản ≤ 7 ngày',
                'icon' => 'fi fi-rr-money-bill-wave',
                'accent' => 'bg-primary-subtle text-primary',
            ])
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xxl-8">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('operations.alerts', array_merge($levelQuery, ['level' => 'all'])) }}" class="btn btn-outline-primary {{ $levelFilter === 'all' ? 'active' : '' }}">Tất cả</a>
                    <a href="{{ route('operations.alerts', array_merge($levelQuery, ['level' => 'red'])) }}" class="btn btn-outline-danger {{ $levelFilter === 'red' ? 'active' : '' }}">Khẩn cấp</a>
                    <a href="{{ route('operations.alerts', array_merge($levelQuery, ['level' => 'amber'])) }}" class="btn btn-outline-warning {{ $levelFilter === 'amber' ? 'active' : '' }}">Lưu ý</a>
                </div>
                <form method="GET" action="{{ route('operations.alerts') }}" class="d-flex gap-2">
                    <input type="hidden" name="level" value="{{ $levelFilter }}">
                    <select name="rule" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Mọi loại cảnh báo</option>
                        @foreach ($alertRules as $rule)
                            <option value="{{ $rule }}" @selected($ruleFilter === $rule)>{{ $rule }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="d-grid gap-2">
                @forelse ($filteredAlerts as $alert)
                    @php
                        $isRed = $alert['level'] === 'red';
                        $cardClass = $isRed ? 'border-danger bg-danger-subtle' : 'border-warning bg-warning-subtle';
                        $badgeClass = $isRed ? 'bg-danger text-white' : 'bg-warning text-dark';
                    @endphp
                    <div class="card border operation-alert-card {{ $cardClass }}">
                        <div class="card-body">
                            <div class="d-flex gap-3 align-items-start">
                                <span class="fs-5 {{ $isRed ? 'text-danger' : 'text-warning' }}">
                                    <i class="fi {{ $isRed ? 'fi-rr-triangle-warning' : 'fi-rr-info' }}"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <h6 class="fw-bold mb-0 text-dark">{{ $alert['title'] }}</h6>
                                        <span class="badge rounded-pill {{ $badgeClass }}">{{ $alert['rule'] }}</span>
                                    </div>
                                    <div class="text-muted mt-1">{{ $alert['detail'] }}</div>
                                </div>
                                @if (isset($alert['ref']['code']))
                                    <a href="{{ route('operations.projects', ['q' => $alert['ref']['code']]) }}" class="btn btn-sm btn-light text-nowrap">Xem dự án</a>
                                @elseif (isset($alert['ref']['customer']))
                                    <a href="{{ route('operations.crm') }}" class="btn btn-sm btn-light text-nowrap">Xem KH</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-5">Không có cảnh báo phù hợp bộ lọc.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-1">Quy tắc cảnh báo</h5>
                    <small class="text-muted">Ngưỡng tự động theo dữ liệu báo cáo ngày.</small>
                </div>
                <div class="card-body">
                    <div class="rounded-3 bg-danger-subtle p-3 mb-3">
                        <div class="fw-bold text-danger mb-1">Mức khẩn cấp</div>
                        <ul class="mb-0 ps-3 text-sm text-danger">
                            <li>Chưa cập nhật số liệu sau 18:00</li>
                            <li>Thiếu nhân sự nặng, lấp đầy dưới 70%</li>
                            <li>Tạm dừng trên 14 ngày không cập nhật</li>
                            <li>Công nợ đến hạn ≤ 2 ngày hoặc quá hạn</li>
                        </ul>
                    </div>
                    <div class="rounded-3 bg-warning-subtle p-3 mb-3">
                        <div class="fw-bold text-warning mb-1">Mức lưu ý</div>
                        <ul class="mb-0 ps-3 text-sm text-warning">
                            <li>Lấp đầy dưới 85%</li>
                            <li>Dự án tạm dừng trên 7 ngày</li>
                            <li>Công nợ đến hạn trong 7 ngày</li>
                            <li>Hợp đồng còn ≤ 30 ngày</li>
                            <li>Quản lý phụ trách trên 10 dự án</li>
                        </ul>
                    </div>
                    <div class="rounded-3 bg-light p-3 text-muted text-sm">
                        Mọi cảnh báo dựa trên số liệu nhân sự nhập hàng ngày và dữ liệu hợp đồng/công nợ.
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-1">Kênh gửi cảnh báo</h5>
                    <small class="text-muted">Mẫu tích hợp Zalo OA, email và thông báo trong app.</small>
                </div>
                <div class="card-body">
                    @foreach ([
                        ['icon' => 'fi fi-rr-comment-alt', 'title' => 'Zalo OA', 'desc' => 'Gửi tới nhóm quản lý vận hành'],
                        ['icon' => 'fi fi-rr-envelope', 'title' => 'Email', 'desc' => 'Báo cáo cảnh báo đỏ hằng ngày'],
                        ['icon' => 'fi fi-rr-bell-ring', 'title' => 'Thông báo trong App', 'desc' => 'Hiển thị trên dashboard'],
                    ] as $channel)
                        <label class="d-flex align-items-center gap-3 border rounded-3 p-3 mb-2">
                            <span class="operation-kpi-icon bg-light text-primary"><i class="{{ $channel['icon'] }}"></i></span>
                            <span class="flex-grow-1">
                                <span class="fw-semibold d-block">{{ $channel['title'] }}</span>
                                <span class="text-muted text-xs">{{ $channel['desc'] }}</span>
                            </span>
                            <input type="checkbox" class="form-check-input m-0" checked>
                        </label>
                    @endforeach

                    @if ($preview)
                        <div class="border border-dashed rounded-3 bg-light p-3 mt-3">
                            <div class="text-muted text-xs fw-bold mb-1">XEM TRƯỚC TIN GỬI</div>
                            <div class="text-sm">
                                <strong>[CẢNH BÁO] {{ $preview['rule'] }}</strong><br>
                                {{ $preview['title'] }}<br>
                                <span class="text-muted">{{ $preview['detail'] }}</span><br>
                                <span class="text-muted">TTVH Thành Công · {{ $today->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
