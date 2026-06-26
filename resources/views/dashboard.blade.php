@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=1.0.1">
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}?v=1.0.0">
@endpush

@section('content')
    <div class="dashboard-page">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Dashboard</h1>
            <span class="text-muted">
                <i class="fi fi-rr-calendar me-1"></i>{{ now()->format('d/m/Y') }}
            </span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @can('schedule.create')
                <a href="{{ route('duty-schedules.index') }}" class="btn btn-outline-primary waves-effect">
                    <i class="fi fi-rr-calendar-plus me-1"></i> Tạo lịch công tác
                </a>
            @endcan
            @can('report.create')
                <a href="{{ route('daily-reports.index') }}" class="btn btn-primary waves-effect waves-light">
                    <i class="fi fi-rr-document-signed me-1"></i> Viết báo cáo
                </a>
            @endcan
        </div>
    </div>

    @can('project.view')
        @php
            $op = $operations;
            $fmt = static fn ($number): string => number_format((float) $number, 0, ',', '.');
            $pct = static fn ($number): string => (int) round(((float) $number) * 100).'%';
            $opAlerts = collect($op['alerts'])->take(5);
            $opDebts = collect($op['receivables'])->sortBy('days_left')->take(4);
            $opManagers = collect($op['managers'])->filter(fn ($manager) => empty($manager['unassigned']))->sortByDesc('count')->take(4);
        @endphp

        <div class="operation-page mb-4">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="mb-1">Tổng quan vận hành</h5>
                    <small class="text-muted">Dự án, nhân sự, công nợ và cảnh báo theo prototype TTVH Thành Công.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('operations.projects') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fi fi-rr-briefcase me-1"></i> Dự án
                    </a>
                    @can('alert.view')
                        <a href="{{ route('operations.alerts') }}" class="btn btn-sm btn-primary">
                            <i class="fi fi-rr-bell-ring me-1"></i> Cảnh báo
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6 col-xl-3">
                    @include('operations.partials.kpi-card', [
                        'label' => 'Tổng dự án',
                        'value' => $op['kpi']['total'],
                        'sub' => count($op['branches']).' chi nhánh',
                        'icon' => 'fi fi-rr-briefcase',
                    ])
                </div>
                <div class="col-6 col-xl-3">
                    @include('operations.partials.kpi-card', [
                        'label' => 'Đang vận hành',
                        'value' => $op['kpi']['operating'],
                        'sub' => 'Tạm dừng '.$op['kpi']['paused'],
                        'icon' => 'fi fi-rr-check-circle',
                        'accent' => 'bg-success-subtle text-success',
                    ])
                </div>
                <div class="col-6 col-xl-3">
                    @include('operations.partials.kpi-card', [
                        'label' => 'Lấp đầy LĐ',
                        'value' => $pct($op['kpi']['fill_rate']),
                        'sub' => $fmt($op['kpi']['sum_actual']).'/'.$fmt($op['kpi']['sum_demand']).' LĐ',
                        'icon' => 'fi fi-rr-users',
                        'accent' => 'bg-info-subtle text-info',
                    ])
                </div>
                <div class="col-6 col-xl-3">
                    @include('operations.partials.kpi-card', [
                        'label' => 'Cảnh báo',
                        'value' => $op['kpi']['red_alerts'] + $op['kpi']['amber_alerts'],
                        'sub' => $op['kpi']['red_alerts'].' đỏ · '.$op['kpi']['amber_alerts'].' vàng',
                        'icon' => 'fi fi-rr-triangle-warning',
                        'accent' => 'bg-danger-subtle text-danger',
                    ])
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header border-0 pb-0">
                            <h6 class="card-title mb-1">Tải công việc quản lý</h6>
                            <small class="text-muted">Số dự án đang phụ trách.</small>
                        </div>
                        <div class="card-body">
                            @foreach ($opManagers as $manager)
                                @php
                                    $load = min(100, (int) round(($manager['count'] / 13) * 100));
                                    $bar = $manager['count'] > 10 ? 'bg-danger' : ($manager['count'] > 7 ? 'bg-warning' : 'bg-success');
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between text-sm mb-1">
                                        <span class="fw-semibold">{{ $manager['name'] }}</span>
                                        <span class="text-muted">{{ $manager['count'] }} DA</span>
                                    </div>
                                    @include('operations.partials.progress', ['value' => $load, 'class' => $bar])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header border-0 pb-0 d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">Công nợ sắp đến hạn</h6>
                                <small class="text-muted">Tổng {{ $fmt($op['kpi']['receivable_due_soon']) }} triệu trong 7 ngày.</small>
                            </div>
                        </div>
                        <div class="card-body">
                            @foreach ($opDebts as $debt)
                                <div class="d-flex justify-content-between gap-3 border rounded-3 p-2 mb-2">
                                    <span class="min-w-0">
                                        <span class="fw-semibold d-block text-truncate">{{ $debt['customer'] }} · {{ $fmt($debt['amount']) }} tr</span>
                                        <span class="text-muted text-xs">{{ $debt['note'] }}</span>
                                    </span>
                                    <span class="{{ $debt['days_left'] <= 7 ? 'text-danger fw-semibold' : 'text-muted' }} text-nowrap">
                                        {{ $debt['days_left'] < 0 ? 'Quá hạn '.abs($debt['days_left']).'n' : 'Còn '.$debt['days_left'].'n' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header border-0 pb-0">
                            <h6 class="card-title mb-1">Cảnh báo cần chú ý</h6>
                            <small class="text-muted">Ưu tiên cảnh báo đỏ trước.</small>
                        </div>
                        <div class="card-body">
                            @foreach ($opAlerts as $alert)
                                <div class="d-flex gap-2 rounded-3 p-2 mb-2 {{ $alert['level'] === 'red' ? 'bg-danger-subtle' : 'bg-warning-subtle' }}">
                                    <i class="fi {{ $alert['level'] === 'red' ? 'fi-rr-triangle-warning text-danger' : 'fi-rr-info text-warning' }} mt-1"></i>
                                    <span class="min-w-0">
                                        <span class="fw-semibold d-block text-truncate">{{ $alert['title'] }}</span>
                                        <span class="text-muted text-xs d-block text-truncate">{{ $alert['detail'] }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="row g-3 dashboard-stat-row">
        @can('user.view')
            <div class="col-6 col-md-4 col-xl">
                <div class="card dashboard-stat-card bg-secondary bg-opacity-05 shadow-none border-0 h-100 transition-hover">
                    <div class="card-body">
                        <div class="avatar bg-secondary shadow-secondary rounded-circle text-white mb-3">
                            <i class="fi fi-sr-users"></i>
                        </div>
                        <h3 class="mb-1">{{ $stats['users'] }}</h3>
                        <h6 class="mb-0">Thành viên</h6>
                        <small class="fw-medium text-success">
                            <i class="fi fi-rr-arrow-small-up scale-3x"></i> +{{ $stats['new_users_month'] }} trong tháng
                        </small>
                    </div>
                </div>
            </div>
        @endcan

        @can('role.manage')
            <div class="col-6 col-md-4 col-xl">
                <div class="card dashboard-stat-card bg-primary bg-opacity-05 shadow-none border-0 h-100 transition-hover">
                    <div class="card-body">
                        <div class="avatar bg-primary shadow-primary rounded-circle text-white mb-3">
                            <i class="fi fi-rr-shield-check"></i>
                        </div>
                        <h3 class="mb-1">{{ $stats['roles'] }}</h3>
                        <h6 class="mb-0">Vai trò</h6>
                        <small class="fw-medium text-primary">{{ $stats['permissions'] }} quyền hệ thống</small>
                    </div>
                </div>
            </div>
        @endcan

        @can('schedule.view')
            <div class="col-6 col-md-4 col-xl">
                <div class="card dashboard-stat-card bg-warning bg-opacity-05 shadow-none border-0 h-100 transition-hover">
                    <div class="card-body">
                        <div class="avatar bg-warning shadow-warning rounded-circle text-white mb-3">
                            <i class="fi fi-rr-calendar-clock"></i>
                        </div>
                        <h3 class="mb-1">{{ $stats['schedules_today'] }}</h3>
                        <h6 class="mb-0">Lịch hôm nay</h6>
                        <small class="fw-medium text-warning">{{ $stats['schedules_week'] }} lịch trong tuần</small>
                    </div>
                </div>
            </div>
        @endcan

        @can('report.view')
            <div class="col-6 col-md-4 col-xl">
                <div class="card dashboard-stat-card bg-info bg-opacity-05 shadow-none border-0 h-100 transition-hover">
                    <div class="card-body">
                        <div class="avatar bg-info shadow-info rounded-circle text-white mb-3">
                            <i class="fi fi-rr-document-signed"></i>
                        </div>
                        <h3 class="mb-1">{{ $stats['reports_today'] }}</h3>
                        <h6 class="mb-0">Báo cáo hôm nay</h6>
                        <small class="fw-medium text-info">{{ $stats['reports_month'] }} báo cáo trong tháng</small>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    <div class="row g-4 mt-1 dashboard-main-row">
        <div class="col-xxl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0 d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">Tổng quan vận hành</h5>
                        <small class="text-muted">Theo dõi lịch công tác và báo cáo nội bộ trong ngày.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary">TTVH-TC Office</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @can('schedule.view')
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light bg-opacity-50 h-100">
                                    <div class="avatar bg-warning rounded-circle text-white">
                                        <i class="fi fi-rr-calendar-day"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <h6 class="mb-0">Lịch tuần này</h6>
                                            <strong>{{ $stats['schedules_week'] }}</strong>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-warning" style="width: {{ min($stats['schedules_week'] * 10, 100) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endcan

                        @can('report.view')
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light bg-opacity-50 h-100">
                                    <div class="avatar bg-success rounded-circle text-white">
                                        <i class="fi fi-rr-chart-histogram"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <h6 class="mb-0">Tỷ lệ báo cáo hôm nay</h6>
                                            <strong>{{ $reportStatus['percent'] }}%</strong>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-success" style="width: {{ $reportStatus['percent'] }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $reportStatus['submitted'] }} đã nộp, {{ $reportStatus['missing'] }} chưa nộp</small>
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>

                    <div class="row g-4 mt-1">
                        @can('schedule.view')
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0">Lịch công tác sắp tới</h6>
                                    <a href="{{ route('duty-schedules.index') }}" class="small fw-semibold">Xem tất cả</a>
                                </div>

                                @forelse ($recentSchedules as $schedule)
                                    <a href="{{ route('duty-schedules.index') }}" class="d-flex align-items-start gap-3 p-3 rounded-3 bg-light bg-opacity-50 mb-2 text-body transition-hover">
                                        <span class="avatar avatar-sm bg-{{ $schedule['color'] }}-subtle text-{{ $schedule['color'] }} rounded-circle">
                                            <i class="fi fi-rr-calendar"></i>
                                        </span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-flex align-items-center justify-content-between gap-2">
                                                <strong class="text-truncate min-w-0">
                                                    @if ($schedule['is_private'])
                                                        <i class="fi fi-rr-lock me-1"></i>
                                                    @endif
                                                    {{ $schedule['title'] }}
                                                </strong>
                                                <small class="text-muted text-nowrap">{{ $schedule['time'] }}</small>
                                            </span>
                                            <small class="text-muted d-block text-truncate">
                                                {{ $schedule['creator'] }}{{ $schedule['location'] ? ' · '.$schedule['location'] : '' }}
                                            </small>
                                        </span>
                                    </a>
                                @empty
                                    <div class="text-center text-muted py-4">
                                        <i class="fi fi-rr-calendar-slash display-6 d-block mb-2"></i>
                                        Chưa có lịch công tác sắp tới.
                                    </div>
                                @endforelse
                            </div>
                        @endcan

                        @can('report.view')
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0">Báo cáo mới nhất</h6>
                                    <a href="{{ route('daily-reports.index') }}" class="small fw-semibold text-success">Xem tất cả</a>
                                </div>

                                @forelse ($recentReports as $report)
                                    <a href="{{ route('daily-reports.index') }}" class="d-flex align-items-start gap-3 p-3 rounded-3 bg-light bg-opacity-50 mb-2 text-body transition-hover">
                                        <span class="avatar avatar-sm bg-success-subtle text-success rounded-circle">
                                            <i class="fi fi-rr-document-signed"></i>
                                        </span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-flex align-items-center justify-content-between gap-2">
                                                <strong class="text-truncate min-w-0">{{ $report['user'] }}</strong>
                                                <small class="text-muted text-nowrap">{{ $report['date'] }}</small>
                                            </span>
                                            <small class="text-muted d-block text-truncate">{{ $report['summary'] }}</small>
                                        </span>
                                    </a>
                                @empty
                                    <div class="text-center text-muted py-4">
                                        <i class="fi fi-rr-document display-6 d-block mb-2"></i>
                                        Chưa có báo cáo nào.
                                    </div>
                                @endforelse
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="card dashboard-side-card border-0 shadow-sm mb-4">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-0">Thao tác nhanh</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @can('user.view')
                            <div class="col-6">
                                <a href="{{ route('users.index') }}" class="btn btn-light w-100 text-start waves-effect">
                                    <i class="fi fi-rr-users text-primary me-2"></i> Nhân sự
                                </a>
                            </div>
                        @endcan
                        @can('schedule.view')
                            <div class="col-6">
                                <a href="{{ route('duty-schedules.index') }}" class="btn btn-light w-100 text-start waves-effect">
                                    <i class="fi fi-rr-calendar text-warning me-2"></i> Lịch
                                </a>
                            </div>
                        @endcan
                        @can('report.view')
                            <div class="col-6">
                                <a href="{{ route('daily-reports.index') }}" class="btn btn-light w-100 text-start waves-effect">
                                    <i class="fi fi-rr-document text-success me-2"></i> Báo cáo
                                </a>
                            </div>
                        @endcan
                        @can('document.view')
                            <div class="col-6">
                                <a href="{{ route('document-regulations.index') }}" class="btn btn-light w-100 text-start waves-effect">
                                    <i class="fi fi-rr-document-signed text-info me-2"></i> Tài liệu
                                </a>
                            </div>
                        @endcan
                        @can('role.manage')
                            <div class="col-6">
                                <a href="{{ route('roles-permissions.index') }}" class="btn btn-light w-100 text-start waves-effect">
                                    <i class="fi fi-rr-shield-check text-danger me-2"></i> Quyền
                                </a>
                            </div>
                        @endcan
                        @can('setting.view')
                            <div class="col-12">
                                <a href="{{ route('settings.index') }}" class="btn btn-light w-100 text-start waves-effect">
                                    <i class="fi fi-rr-settings text-info me-2"></i> Thiết lập hệ thống
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>

            @can('role.manage')
                <div class="card dashboard-side-card border-0 shadow-sm">
                    <div class="card-header border-0 pb-0 d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Phân bố vai trò</h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $stats['users'] }} users</span>
                    </div>
                    <div class="card-body">
                        @forelse ($roleDistribution as $role)
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="fw-semibold">{{ $role['name'] }}</span>
                                    <small class="text-muted">{{ $role['users_count'] }} người</small>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar" style="width: {{ $role['percent'] }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                Chưa có vai trò nào.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endcan
        </div>
    </div>
    </div>
@endsection
