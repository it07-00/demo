@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}?v=1.0.0">
@endpush

@section('content')
@php
    $fmt = static fn ($number): string => number_format((float) $number, 0, ',', '.');
    $totalRevenue = collect($crm_customers)->sum('revenue_monthly');
    $negotiating = collect($crm_customers)->whereIn('stage_idx', [0, 1])->count();
    $signed = collect($crm_customers)->where('stage_idx', '>=', 2)->count();
    $stageColors = ['border-info', 'border-warning', 'border-success', 'border-primary'];
@endphp

<div class="operation-page">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">CRM khách hàng</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">CRM khách hàng</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('operations.alerts', ['rule' => 'Công nợ đến hạn']) }}" class="btn btn-outline-primary">
            <i class="fi fi-rr-bell-ring me-1"></i> Cảnh báo công nợ
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'KH trọng điểm',
                'value' => count($crm_customers),
                'sub' => 'đang theo dõi quan hệ',
                'icon' => 'fi fi-rr-star',
                'accent' => 'bg-warning-subtle text-warning',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Doanh thu/tháng',
                'value' => $fmt($totalRevenue).' tr',
                'sub' => 'từ KH đã ký hợp đồng',
                'icon' => 'fi fi-rr-money-bill-wave',
                'accent' => 'bg-success-subtle text-success',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Đang đàm phán',
                'value' => $negotiating,
                'sub' => 'đang trao đổi / ăn cafe',
                'icon' => 'fi fi-rr-handshake',
                'accent' => 'bg-info-subtle text-info',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Đã ký / dài hạn',
                'value' => $signed,
                'sub' => 'đối tác hợp đồng dài hạn',
                'icon' => 'fi fi-rr-document-signed',
                'accent' => 'bg-primary-subtle text-primary',
            ])
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Pipeline quan hệ khách hàng</h5>
        <span class="text-muted text-sm">Mở từng thẻ để xem hồ sơ chi tiết.</span>
    </div>

    <div class="row g-3">
        @foreach ($crm_stages as $index => $stage)
            <div class="col-12 col-lg-6 col-xxl-3">
                <div class="card border shadow-sm h-100 operation-pipeline-column {{ $stageColors[$index] ?? 'border-primary' }}">
                    <div class="card-header border-0 bg-transparent pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">{{ $stage }}</h6>
                        <span class="badge bg-white text-muted border">{{ count($customersByStage[$stage] ?? []) }}</span>
                    </div>
                    <div class="card-body">
                        @forelse (($customersByStage[$stage] ?? []) as $customer)
                            @php
                                $collapseId = 'crm-'.md5($customer['name']);
                                $nextSoon = $today->diffInDays($customer['next_meeting'], false) <= 3;
                                $relationClass = match ($customer['relationship']) {
                                    'Rất tốt', 'Tốt' => 'bg-success-subtle text-success',
                                    'Bình thường' => 'bg-light text-muted',
                                    default => 'bg-danger-subtle text-danger',
                                };
                            @endphp
                            <div class="card border-0 shadow-sm mb-3 operation-card-hover">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between gap-2 align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1">{{ $customer['name'] }}</h6>
                                            <div class="text-muted text-xs">{{ $customer['contact_name'] }} · {{ $customer['contact_role'] }}</div>
                                        </div>
                                        <span class="badge rounded-pill {{ $relationClass }}">{{ $customer['relationship'] }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-xs mt-3">
                                        <span class="text-muted">{{ $customer['revenue_monthly'] ? $fmt($customer['revenue_monthly']).' tr/th' : 'Chưa phát sinh DT' }}</span>
                                        <span class="{{ $nextSoon ? 'text-danger fw-semibold' : 'text-muted' }}">
                                            <i class="fi fi-rr-calendar me-1"></i>{{ $customer['next_meeting']->format('d/m') }}
                                        </span>
                                    </div>
                                    <button class="btn btn-light btn-sm w-100 mt-3" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                                        <i class="fi fi-rr-eye me-1"></i> Hồ sơ KH
                                    </button>
                                </div>
                                <div class="collapse border-top" id="{{ $collapseId }}">
                                    <div class="card-body bg-light bg-opacity-50">
                                        <div class="mb-3">
                                            <div class="d-flex gap-1 mb-2">
                                                @foreach ($crm_stages as $stepIndex => $step)
                                                    <div class="flex-grow-1">
                                                        <div class="rounded-pill {{ $stepIndex <= $customer['stage_idx'] ? 'bg-primary' : 'bg-secondary-subtle' }}" style="height: 7px;"></div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="text-muted text-xs">Giai đoạn hiện tại: <strong>{{ $customer['stage'] }}</strong></div>
                                        </div>

                                        <div class="list-group list-group-flush mb-3">
                                            <div class="list-group-item px-0 bg-transparent d-flex justify-content-between gap-3">
                                                <span class="text-muted">Người liên hệ</span><strong class="text-end">{{ $customer['contact_name'] }} · {{ $customer['contact_role'] }}</strong>
                                            </div>
                                            <div class="list-group-item px-0 bg-transparent d-flex justify-content-between gap-3">
                                                <span class="text-muted">Số dự án</span><strong>{{ $customer['project_count'] }}</strong>
                                            </div>
                                            <div class="list-group-item px-0 bg-transparent d-flex justify-content-between gap-3">
                                                <span class="text-muted">Lần gặp gần nhất</span><strong>{{ $customer['last_meeting']->format('d/m/Y') }}</strong>
                                            </div>
                                            <div class="list-group-item px-0 bg-transparent d-flex justify-content-between gap-3">
                                                <span class="text-muted">Lịch gặp tiếp theo</span><strong>{{ $customer['next_meeting']->format('d/m/Y') }}</strong>
                                            </div>
                                        </div>

                                        <h6 class="fw-bold text-sm">Ghi chú quan hệ</h6>
                                        @foreach ($customer['notes'] as $note)
                                            <div class="border rounded-3 p-2 bg-white mb-2 text-sm">{{ $note }}</div>
                                        @endforeach

                                        <h6 class="fw-bold text-sm mt-3">Dự án đang chạy</h6>
                                        @forelse ($customer['projects'] as $project)
                                            <div class="d-flex align-items-center justify-content-between border rounded-3 p-2 bg-white mb-2">
                                                <span class="text-sm">{{ $project['code'] }} · {{ $project['branch'] }}</span>
                                                @include('operations.partials.status-badge', ['status' => $project['status']])
                                            </div>
                                        @empty
                                            <div class="text-muted text-sm">Chưa có dự án đang chạy.</div>
                                        @endforelse

                                        <h6 class="fw-bold text-sm mt-3">Công nợ</h6>
                                        @forelse ($customer['receivables'] as $debt)
                                            <div class="d-flex align-items-center justify-content-between border rounded-3 p-2 bg-white mb-2">
                                                <span class="text-sm">{{ $fmt($debt['amount']) }} tr · {{ $debt['note'] }}</span>
                                                <span class="{{ $debt['days_left'] <= 7 ? 'text-danger fw-semibold' : 'text-muted' }} text-xs">Hạn {{ $debt['due_date']->format('d/m') }}</span>
                                            </div>
                                        @empty
                                            <div class="text-muted text-sm">Không có công nợ.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="border border-dashed rounded-3 p-4 text-center text-muted">Trống</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
