@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}?v=1.0.0">
@endpush

@section('content')
@php
    $fmt = static fn ($number): string => number_format((float) $number, 0, ',', '.');
    $pct = static fn ($number): string => (int) round(((float) $number) * 100).'%';
@endphp

<div class="operation-page">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Báo cáo vận hành</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Báo cáo vận hành</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('report.create')
                <button type="button" class="btn btn-success" onclick="Livewire.dispatch('report:open-create')">
                    <i class="fi fi-rr-plus me-1"></i> Nhập số liệu hôm nay
                </button>
            @endcan
            @can('report.create')
                <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-primary">
                    <i class="fi fi-rr-document-signed me-1"></i> Báo cáo công việc
                </a>
            @endcan
            <a href="{{ route('operations.alerts', ['rule' => 'Chưa cập nhật số liệu']) }}" class="btn btn-primary">
                <i class="fi fi-rr-bell-ring me-1"></i> Chưa cập nhật
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Đã cập nhật hôm nay',
                'value' => $dailySummary['reported'].'/'.$dailySummary['need'],
                'sub' => 'dự án đang vận hành',
                'icon' => 'fi fi-rr-document-signed',
                'accent' => 'bg-primary-subtle text-primary',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Tổng đi làm',
                'value' => $fmt($dailySummary['started']),
                'sub' => 'LĐ vào ca hôm nay',
                'icon' => 'fi fi-rr-check-circle',
                'accent' => 'bg-success-subtle text-success',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Tổng đăng ký',
                'value' => $fmt($dailySummary['registered']),
                'sub' => 'ứng viên đăng ký',
                'icon' => 'fi fi-rr-clipboard-list',
                'accent' => 'bg-info-subtle text-info',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Tỷ lệ chuyển đổi',
                'value' => $pct($dailySummary['conversion']),
                'sub' => 'đi làm / đăng ký',
                'icon' => 'fi fi-rr-chart-pie-alt',
                'accent' => 'bg-warning-subtle text-warning',
            ])
        </div>
    </div>

    @if (count($missingProjects) > 0)
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-bold mb-2">
                <i class="fi fi-rr-triangle-warning me-1"></i>
                Chưa báo cáo hôm nay: {{ count($missingProjects) }} dự án
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($missingProjects as $project)
                    <a href="{{ route('operations.projects', ['q' => $project['code']]) }}" class="badge rounded-pill bg-white text-danger border border-danger-subtle px-3 py-2">
                        {{ $project['code'] }} · {{ $project['customer'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <div class="alert alert-success border-0 shadow-sm">
            <i class="fi fi-rr-check-circle me-1"></i>
            Tất cả dự án đang vận hành đã cập nhật báo cáo hôm nay.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xxl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-1">Bộ lọc báo cáo</h5>
                    <small class="text-muted">Lọc theo ngày, dự án hoặc chi nhánh.</small>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('operations.daily') }}" class="d-grid gap-3">
                        <div>
                            <label class="form-label">Ngày</label>
                            <select name="date" class="form-select">
                                <option value="">Tất cả ngày</option>
                                @foreach ($reportDates as $date)
                                    <option value="{{ $date }}" @selected($filters['date'] === $date)>
                                        {{ \Carbon\CarbonImmutable::parse($date)->format('d/m/Y') }}
                                        @if ($date === $today->toDateString())
                                            (hôm nay)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Dự án</label>
                            <select name="project" class="form-select">
                                <option value="">Tất cả dự án</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project['id'] }}" @selected($filters['project'] === $project['id'])>
                                        {{ $project['code'] }} · {{ $project['customer'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Chi nhánh</label>
                            <select name="branch" class="form-select">
                                <option value="">Tất cả chi nhánh</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch }}" @selected($filters['branch'] === $branch)>{{ $branch }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fi fi-rr-search me-1"></i> Lọc
                            </button>
                            <a href="{{ route('operations.daily') }}" class="btn btn-light">Xóa</a>
                        </div>
                    </form>

                    <hr>

                    <h6 class="fw-bold mb-3">Nhập báo cáo nhanh</h6>
                    <button type="button" class="btn btn-success w-100 mb-2" onclick="Livewire.dispatch('report:open-create')">
                        <i class="fi fi-rr-plus me-1"></i> Nhập số liệu tuyển dụng
                    </button>
                    <div class="text-muted text-xs mt-2">
                        Chọn dự án → hệ thống tự động điền chi nhánh, khách hàng, QLVH.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pb-0 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">Lịch sử báo cáo tuyển dụng</h5>
                        <small class="text-muted">{{ count($filteredHistory) }} bản ghi theo bộ lọc hiện tại.</small>
                    </div>
                    <a href="{{ route('operations.projects', ['view' => 'table']) }}" class="btn btn-sm btn-light">
                        <i class="fi fi-rr-table-list me-1"></i> Bảng nhu cầu
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Ngày</th>
                                    <th>Dự án</th>
                                    <th>Chi nhánh</th>
                                    <th class="text-end">Nhu cầu</th>
                                    <th>Phương thức</th>
                                    <th class="text-end">ĐK</th>
                                    <th class="text-end">PV</th>
                                    <th class="text-end">Đỗ</th>
                                    <th class="text-end">Đi làm</th>
                                    <th class="text-end">Thử</th>
                                    <th class="text-center">Hạng</th>
                                    <th class="text-center" style="width: 100px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($filteredHistory as $record)
                                    <tr>
                                        <td class="text-muted text-nowrap">{{ $record['date']->format('d/m') }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $record['customer'] }}</div>
                                            <div class="text-muted text-xs">
                                                {{ $record['code'] }}
                                                @if ($record['approved'])
                                                    <span class="badge bg-success-subtle text-success ms-1">duyệt</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning ms-1">chờ</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-muted">{{ $record['branch'] }}</td>
                                        <td class="text-end">{{ $fmt($record['demand']) }}</td>
                                        <td>{{ $record['method'] }}</td>
                                        <td class="text-end">{{ $record['registered'] }}</td>
                                        <td class="text-end">{{ $record['interviewed'] }}</td>
                                        <td class="text-end">{{ $record['passed'] }}</td>
                                        <td class="text-end fw-semibold text-success">{{ $record['started'] }}</td>
                                        <td class="text-end">{{ $record['partner_trial'] }}</td>
                                        <td class="text-center fw-bold">{{ $record['rank'] }}</td>
                                    <td class="text-center">
                                        @if (isset($record['db_id']))
                                            <div class="d-flex gap-1 justify-content-center">
                                                @if (!$record['approved'])
                                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-1" onclick="Livewire.dispatch('report:approve', { id: {{ $record['db_id'] }} })" title="Duyệt">
                                                        <i class="fi fi-rr-check" style="font-size: 11px;"></i>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" onclick="Livewire.dispatch('report:open-edit', { id: {{ $record['db_id'] }} })" title="Sửa">
                                                    <i class="fi fi-rr-pencil" style="font-size: 11px;"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="if(confirm('Xóa báo cáo này?')) Livewire.dispatch('report:delete', { id: {{ $record['db_id'] }} })" title="Xóa">
                                                    <i class="fi fi-rr-trash" style="font-size: 11px;"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-5">Không có bản ghi phù hợp bộ lọc.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<livewire:operations.recruitment-report-crud />

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('report:saved', () => {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
        });
    </script>
@endpush

@endsection
