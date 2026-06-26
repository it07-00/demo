@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}?v=1.0.0">
@endpush

@section('content')
@php
    $fmt = static fn ($number): string => number_format((float) $number, 0, ',', '.');
    $pct = static fn ($number): string => (int) round(((float) $number) * 100).'%';
    $shortName = static fn (string $name): string => collect(explode(' ', $name))->slice(-2)->implode(' ');
    $lowFillChart = array_map(static fn ($project): array => [
        'label' => $project['code'].' · '.$project['customer'],
        'fill' => (int) round($project['fill_rate'] * 100),
    ], $lowFillProjects);
@endphp

<div class="operation-page">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">KPI & Hiệu suất</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">KPI & Hiệu suất</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('operations.projects', ['view' => 'table']) }}" class="btn btn-outline-primary">
            <i class="fi fi-rr-table-list me-1"></i> Bảng nhu cầu
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Tăng trưởng dự án',
                'value' => '+'.$growthPct.'%',
                'sub' => $monthly_growth[0]['projects'].' → '.$monthly_growth[count($monthly_growth) - 1]['projects'].' DA/12 tháng',
                'icon' => 'fi fi-rr-chart-line-up',
                'accent' => 'bg-success-subtle text-success',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Lấp đầy LĐ TB',
                'value' => $pct($kpi['fill_rate']),
                'sub' => 'dự án đang vận hành',
                'icon' => 'fi fi-rr-users',
                'accent' => 'bg-info-subtle text-info',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Chi nhánh dẫn đầu',
                'value' => $bestBranch['branch'] ?? '—',
                'sub' => isset($bestBranch['fill']) ? 'lấp đầy '.$pct($bestBranch['fill']) : 'chưa có dữ liệu',
                'icon' => 'fi fi-rr-trophy',
                'accent' => 'bg-warning-subtle text-warning',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'QL hiệu quả nhất',
                'value' => isset($bestManager['name']) ? $shortName($bestManager['name']) : '—',
                'sub' => isset($bestManager['fill']) ? 'lấp đầy TB '.$pct($bestManager['fill']) : 'chưa có dữ liệu',
                'icon' => 'fi fi-rr-star',
                'accent' => 'bg-primary-subtle text-primary',
            ])
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xxl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-1">Tăng trưởng số dự án</h5>
                    <small class="text-muted">Tổng dự án và dự án đang vận hành trong 12 tháng.</small>
                </div>
                <div class="card-body">
                    <div class="operation-chart"><canvas id="chartGrowth"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-1">Tải quản lý</h5>
                    <small class="text-muted">Số dự án phụ trách vs tỷ lệ lấp đầy.</small>
                </div>
                <div class="card-body">
                    <div class="operation-chart-sm"><canvas id="chartManager"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-1">Lấp đầy thấp nhất</h5>
                    <small class="text-muted">10 dự án cần chú ý.</small>
                </div>
                <div class="card-body">
                    <div class="operation-chart"><canvas id="chartFill"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-1">So sánh chi nhánh</h5>
                    <small class="text-muted">Số dự án và tỷ lệ lấp đầy theo chi nhánh.</small>
                </div>
                <div class="card-body">
                    <div class="operation-chart"><canvas id="chartBranch"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pb-0 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">Xếp hạng chuyên viên tuần</h5>
                        <small class="text-muted">7 ngày gần nhất · số lượng đi làm · xếp hạng · tỷ lệ chuyển đổi.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary">Tự động</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Chuyên viên</th>
                                    <th class="text-center">Dự án</th>
                                    <th class="text-end">SL đi làm</th>
                                    <th class="text-center">Hạng</th>
                                    <th class="text-end">Chuyển đổi</th>
                                    <th>Hiệu suất</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($specialistRank as $index => $person)
                                    @php
                                        $efficiency = (int) round($person['conversion'] * 100);
                                        $barClass = $efficiency >= 70 ? 'bg-success' : ($efficiency >= 50 ? 'bg-warning' : 'bg-danger');
                                        $gradeClass = $person['grade'] === 'A' ? 'bg-success-subtle text-success' : ($person['grade'] === 'B' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger');
                                    @endphp
                                    <tr>
                                        <td class="fw-bold text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @include('operations.partials.avatar', ['name' => $person['name']])
                                                <span>
                                                    <span class="fw-semibold d-block">{{ $person['name'] }}</span>
                                                    <span class="text-muted text-xs">{{ $person['branch'] }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $person['projects'] }}</td>
                                        <td class="text-end fw-semibold text-success">{{ $fmt($person['started']) }}</td>
                                        <td class="text-center"><span class="badge rounded-pill {{ $gradeClass }}">{{ $person['grade'] }}</span></td>
                                        <td class="text-end">{{ $efficiency }}%</td>
                                        <td style="min-width: 150px;">@include('operations.partials.progress', ['value' => $efficiency, 'class' => $barClass])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Chart === 'undefined') {
            return;
        }

        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = '#64748b';

        const growth = @json($monthly_growth);
        const managers = @json($managerStats);
        const branches = @json($branchStats);
        const lowFill = @json($lowFillChart);

        new Chart(document.getElementById('chartGrowth'), {
            type: 'line',
            data: {
                labels: growth.map((item) => item.month),
                datasets: [
                    { label: 'Tổng dự án', data: growth.map((item) => item.projects), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.12)', fill: true, tension: .35, borderWidth: 2.5 },
                    { label: 'Đang vận hành', data: growth.map((item) => item.operating), borderColor: '#10b981', tension: .35, borderWidth: 2.2 },
                ],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } },
        });

        new Chart(document.getElementById('chartManager'), {
            type: 'bar',
            data: {
                labels: managers.map((item) => item.name.split(' ').slice(-2).join(' ')),
                datasets: [
                    { label: 'Số DA', data: managers.map((item) => item.total), backgroundColor: '#6366f1', borderRadius: 6, yAxisID: 'y' },
                    { label: 'Lấp đầy (%)', data: managers.map((item) => Math.round(item.fill * 100)), type: 'line', borderColor: '#10b981', backgroundColor: '#10b981', yAxisID: 'y1' },
                ],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true }, y1: { beginAtZero: true, max: 100, position: 'right', grid: { display: false }, ticks: { callback: (v) => v + '%' } }, x: { grid: { display: false } } } },
        });

        new Chart(document.getElementById('chartFill'), {
            type: 'bar',
            data: {
                labels: lowFill.map((item) => item.label),
                datasets: [{ data: lowFill.map((item) => item.fill), backgroundColor: lowFill.map((item) => item.fill >= 85 ? '#10b981' : item.fill >= 70 ? '#f59e0b' : '#f43f5e'), borderRadius: 5 }],
            },
            options: { maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } }, y: { grid: { display: false } } } },
        });

        new Chart(document.getElementById('chartBranch'), {
            type: 'bar',
            data: {
                labels: branches.map((item) => item.branch),
                datasets: [
                    { label: 'Số dự án', data: branches.map((item) => item.count), backgroundColor: '#6366f1', borderRadius: 6, yAxisID: 'y' },
                    { label: 'Lấp đầy (%)', data: branches.map((item) => Math.round(item.fill * 100)), type: 'line', borderColor: '#10b981', backgroundColor: '#10b981', yAxisID: 'y1' },
                ],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true }, y1: { beginAtZero: true, max: 100, position: 'right', grid: { display: false }, ticks: { callback: (v) => v + '%' } }, x: { grid: { display: false } } } },
        });
    });
</script>
@endpush
