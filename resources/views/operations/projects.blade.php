@push('styles')
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}?v=1.0.0">
@endpush

@php
    $fmt = static fn ($number): string => number_format((float) $number, 0, ',', '.');
    $pct = static fn ($number): string => (int) round(((float) $number) * 100).'%';
    $logsByProject = collect($today_logs)->keyBy('project_id');
    $receivablesByCustomer = collect($receivables)->groupBy('customer');
    $phases = collect($responsibilities)->groupBy('phase');
@endphp

<div class="operation-page">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Dự án & Khách hàng</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dự án & Khách hàng</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('project.view')
                <button type="button" class="btn btn-success" wire:click="$dispatchTo('operations.project-crud', 'project:open-create')">
                    <i class="fi fi-rr-plus me-1"></i> Tạo dự án
                </button>
            @endcan
            @can('analytics.view')
                <a href="{{ route('operations.analytics') }}" class="btn btn-outline-primary">
                    <i class="fi fi-rr-chart-histogram me-1"></i> KPI vận hành
                </a>
            @endcan
            @can('alert.view')
                <a href="{{ route('operations.alerts') }}" class="btn btn-primary">
                    <i class="fi fi-rr-bell-ring me-1"></i> Cảnh báo
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Tổng dự án',
                'value' => $kpi['total'],
                'sub' => count($branches).' chi nhánh vận hành',
                'icon' => 'fi fi-rr-briefcase',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Đang vận hành',
                'value' => $kpi['operating'],
                'sub' => 'Tạm dừng '.$kpi['paused'].' dự án',
                'icon' => 'fi fi-rr-check-circle',
                'accent' => 'bg-success-subtle text-success',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Lấp đầy lao động',
                'value' => $pct($kpi['fill_rate']),
                'sub' => $fmt($kpi['sum_actual']).'/'.$fmt($kpi['sum_demand']).' LĐ',
                'icon' => 'fi fi-rr-users',
                'accent' => 'bg-info-subtle text-info',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Khách trọng điểm',
                'value' => $kpi['key'],
                'sub' => 'Thông thường '.$kpi['normal'].' dự án',
                'icon' => 'fi fi-rr-star',
                'accent' => 'bg-warning-subtle text-warning',
            ])
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-xl">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="search" class="form-control" placeholder="Tên dự án, khách hàng, mã DA..." wire:model.live.debounce.350ms="search">
                </div>
                <div class="col-6 col-lg-3 col-xl-2">
                    <label class="form-label">Chi nhánh</label>
                    <select class="form-select" wire:model.live="branch">
                        <option value="">Tất cả</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch }}">{{ $branch }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3 col-xl-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">Mọi trạng thái</option>
                        @foreach ($statusOptions as $state)
                            <option value="{{ $state }}">{{ $state }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3 col-xl-2">
                    <label class="form-label">Loại khách</label>
                    <select class="form-select" wire:model.live="typeFilter">
                        <option value="">Mọi loại</option>
                        @foreach (array_keys($kpi['by_type'] ?? []) as $customerType)
                            <option value="{{ $customerType }}">{{ $customerType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3 col-xl-auto d-flex gap-2">
                    <button type="button" class="btn btn-light" wire:click="resetFilters">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <div class="btn-group btn-group-sm">
            <button type="button" wire:click="setView('cards')" class="btn btn-outline-primary {{ $view === 'cards' ? 'active' : '' }}">
                <i class="fi fi-rr-grid me-1"></i> Thẻ dự án
            </button>
            <button type="button" wire:click="setView('table')" class="btn btn-outline-primary {{ $view === 'table' ? 'active' : '' }}">
                <i class="fi fi-rr-table-list me-1"></i> Bảng nhu cầu
            </button>
        </div>
        <div class="text-muted text-sm">Hiển thị {{ count($filteredProjects) }} / {{ count($projects) }} dự án</div>
    </div>

    @if ($view === 'table')
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Khách hàng</th>
                            <th class="text-end">Nhu cầu</th>
                            <th class="text-end">Hiện có</th>
                            <th class="text-end">Đơn giá</th>
                            <th>Phương thức</th>
                            <th>Chính sách</th>
                            <th>Tuyển</th>
                            <th>Quản lý</th>
                            <th class="text-center" style="width: 80px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($filteredProjects as $project)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $project['customer'] }}</div>
                                    <div class="text-muted text-xs">{{ $project['code'] }} · {{ $project['branch'] }}</div>
                                </td>
                                <td class="text-end fw-semibold">{{ $fmt($project['demand']) }}</td>
                                <td class="text-end">{{ $fmt($project['actual']) }}</td>
                                <td class="text-end">{{ $project['unit_price'] }}k</td>
                                <td>{{ $project['method'] }}</td>
                                <td>{{ $project['policy'] }}</td>
                                <td>
                                    <span class="badge {{ $project['recruit_status'] === 'Đang tuyển' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $project['recruit_status'] }}
                                    </span>
                                </td>
                                <td>{{ $project['manager_name'] }}</td>
                                <td class="text-center">
                                    @if (isset($project['db_id']))
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" wire:click="$dispatchTo('operations.project-crud', 'project:open-edit', { id: {{ (int) $project['db_id'] }} })" title="Sửa">
                                                <i class="fi fi-rr-pencil" style="font-size: 11px;"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" wire:confirm="Xóa dự án {{ $project['code'] }}?" wire:click="$dispatchTo('operations.project-crud', 'project:delete', { id: {{ (int) $project['db_id'] }} })" title="Xóa">
                                                <i class="fi fi-rr-trash" style="font-size: 11px;"></i>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">Không có dự án phù hợp bộ lọc.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="row g-3">
            @forelse ($filteredProjects as $project)
                @php
                    $fillPct = (int) round($project['fill_rate'] * 100);
                    $fillClass = $fillPct >= 90 ? 'bg-success' : ($fillPct >= 75 ? 'bg-warning' : 'bg-danger');
                    $log = $logsByProject->get($project['id']);
                    $customerReceivables = $receivablesByCustomer->get($project['customer'], collect());
                @endphp
                <div class="col-12 col-xl-6 col-xxl-4">
                    <div class="card border-0 shadow-sm h-100 operation-card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                                <div class="min-w-0">
                                    <div class="text-muted text-xs fw-bold">{{ $project['code'] }}</div>
                                    <h5 class="mb-1 text-dark">{{ $project['name'] }}</h5>
                                </div>
                                @include('operations.partials.status-badge', ['status' => $project['status']])
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3 text-sm text-muted">
                                <span class="fw-semibold text-dark">{{ $project['customer'] }}</span>
                                @include('operations.partials.customer-badge', ['type' => $project['customer_type']])
                                <span>{{ $project['branch'] }}</span>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between text-xs mb-1">
                                    <span class="text-muted">Nhu cầu / Hiện có</span>
                                    <span class="fw-semibold">{{ $fmt($project['actual']) }}/{{ $fmt($project['demand']) }} · {{ $fillPct }}%</span>
                                </div>
                                @include('operations.partials.progress', ['value' => $fillPct, 'class' => $fillClass])
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between text-xs mb-1">
                                    <span class="text-muted">Tiến độ 21 chức trách</span>
                                    <span class="fw-semibold">{{ $project['progress'] }}/21</span>
                                </div>
                                @include('operations.partials.progress', ['value' => $project['progress_pct'], 'class' => 'bg-primary'])
                            </div>

                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    @include('operations.partials.avatar', ['name' => $project['manager_name'], 'size' => 'operation-avatar-sm'])
                                    <span class="text-sm text-truncate">{{ $project['manager_name'] }}</span>
                                </div>
                                <div class="d-flex">
                                    @foreach ($project['team'] as $member)
                                        <span class="ms-n1">
                                            @include('operations.partials.avatar', ['name' => $member, 'size' => 'operation-avatar-sm'])
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <button class="btn btn-light btn-sm w-100 mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#project-{{ $project['id'] }}">
                                <i class="fi fi-rr-eye me-1"></i> Chi tiết dự án
                            </button>
                            @if (isset($project['db_id']))
                                <div class="d-flex gap-1 mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" wire:click="$dispatchTo('operations.project-crud', 'project:open-edit', { id: {{ (int) $project['db_id'] }} })">
                                        <i class="fi fi-rr-pencil me-1"></i> Sửa
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" wire:confirm="Xóa dự án {{ $project['code'] }}?" wire:click="$dispatchTo('operations.project-crud', 'project:delete', { id: {{ (int) $project['db_id'] }} })">
                                        <i class="fi fi-rr-trash"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="collapse border-top" id="project-{{ $project['id'] }}">
                            <div class="card-body bg-light bg-opacity-50">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card border-0 mb-0">
                                            <div class="card-body">
                                                <h6 class="fw-bold mb-3">Thông tin dự án</h6>
                                                <div class="row g-2 text-sm">
                                                    <div class="col-6"><span class="text-muted">Sản phẩm:</span> <span class="fw-semibold">{{ $project['product'] }}</span></div>
                                                    <div class="col-6"><span class="text-muted">Đơn giá:</span> <span class="fw-semibold">{{ $project['unit_price'] }}k/giờ</span></div>
                                                    <div class="col-6"><span class="text-muted">Phương thức:</span> <span class="fw-semibold">{{ $project['method'] }}</span></div>
                                                    <div class="col-6"><span class="text-muted">Chính sách:</span> <span class="fw-semibold">{{ $project['policy'] }}</span></div>
                                                    <div class="col-12">
                                                        <span class="text-muted">Hợp đồng:</span>
                                                        <span class="fw-semibold">
                                                            {{ $project['contract_start']->format('d/m/Y') }} - {{ $project['contract_end']->format('d/m/Y') }}
                                                            @if ($project['contract_days_left'] <= 30)
                                                                <span class="text-danger">còn {{ $project['contract_days_left'] }} ngày</span>
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card border-0 mb-0">
                                            <div class="card-body">
                                                <h6 class="fw-bold mb-3">Phễu tuyển dụng hôm nay</h6>
                                                @if ($log)
                                                    <div class="row g-2 text-center">
                                                        <div class="col-3"><div class="fw-bold h5 mb-0">{{ $log['registered'] }}</div><div class="text-muted text-xs">Đăng ký</div></div>
                                                        <div class="col-3"><div class="fw-bold h5 mb-0 text-info">{{ $log['interviewed'] }}</div><div class="text-muted text-xs">Phỏng vấn</div></div>
                                                        <div class="col-3"><div class="fw-bold h5 mb-0 text-primary">{{ $log['passed'] }}</div><div class="text-muted text-xs">Đỗ PV</div></div>
                                                        <div class="col-3"><div class="fw-bold h5 mb-0 text-success">{{ $log['started'] }}</div><div class="text-muted text-xs">Đi làm</div></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between text-xs text-muted mt-3">
                                                        <span>Chuyển đổi: <strong>{{ $pct($log['conversion']) }}</strong></span>
                                                        <span>Xếp hạng: <strong>{{ $log['rank'] }}</strong></span>
                                                    </div>
                                                    @if ($log['issues'])
                                                        <div class="alert alert-warning py-2 px-3 mt-3 mb-0 text-sm">{{ $log['issues'] }}</div>
                                                    @endif
                                                @else
                                                    <div class="alert alert-danger mb-0">Chưa cập nhật số liệu hôm nay.</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card border-0 mb-0">
                                            <div class="card-body">
                                                <h6 class="fw-bold mb-3">Công nợ khách hàng</h6>
                                                @forelse ($customerReceivables as $debt)
                                                    <div class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2 mb-2 bg-white">
                                                        <span>{{ $fmt($debt['amount']) }} tr · {{ $debt['note'] }}</span>
                                                        <span class="{{ $debt['days_left'] <= 7 ? 'text-danger fw-semibold' : 'text-muted' }}">Hạn {{ $debt['due_date']->format('d/m') }}</span>
                                                    </div>
                                                @empty
                                                    <div class="text-muted text-sm">Không có công nợ ghi nhận.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card border-0 mb-0">
                                            <div class="card-body">
                                                <h6 class="fw-bold mb-3">Tiến độ 21 chức trách</h6>
                                                @foreach ($phases as $phase => $items)
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span class="text-primary text-xs fw-bold text-uppercase">{{ $phase }}</span>
                                                            <span class="text-muted text-xs">{{ $items->where('no', '<=', $project['progress'])->count() }}/{{ $items->count() }}</span>
                                                        </div>
                                                        <div class="operation-timeline">
                                                            @foreach ($items as $item)
                                                                @php($done = $item['no'] <= $project['progress'])
                                                                <div class="operation-step">
                                                                    <span class="operation-step-index {{ $done ? 'bg-success text-white' : 'bg-light text-muted' }}">{{ $done ? '✓' : $item['no'] }}</span>
                                                                    <span class="{{ $done ? 'text-dark' : 'text-muted' }}">{{ $item['name'] }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card border-0 mb-0">
                                            <div class="card-body">
                                                <h6 class="fw-bold mb-3">Tài liệu</h6>
                                                <div class="row g-2">
                                                    @foreach ($project['docs'] as $doc)
                                                        <div class="col-12 col-md-4">
                                                            <div class="border rounded-3 p-3 bg-white h-100">
                                                                <div class="fw-semibold text-truncate">
                                                                    <i class="fi fi-rr-document me-1 text-primary"></i>
                                                                    @if (isset($doc['path']))
                                                                        <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank" title="{{ $doc['name'] }}">{{ $doc['name'] }}</a>
                                                                    @else
                                                                        {{ $doc['name'] }}
                                                                    @endif
                                                                </div>
                                                                <div class="text-muted text-xs">{{ $doc['type'] }} · {{ isset($doc['path']) ? 'Hệ thống' : 'Google Drive' }}</div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-5">Không có dự án phù hợp bộ lọc.</div>
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    <livewire:operations.project-crud />
</div>
