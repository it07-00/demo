@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}?v=1.0.0">
@endpush

@section('content')
@php
    $fmt = static fn ($number): string => number_format((float) $number, 0, ',', '.');
    $pct = static fn ($number): string => (int) round(((float) $number) * 100).'%';
    $managerCount = collect($staff)->where('role', 'Quản lý vận hành')->count();
    $specialistCount = collect($staff)->where('role', 'Chuyên viên vận hành')->count();
    $overloadedCount = collect($staff)->where('overloaded', true)->count();
    $roleQuery = request()->query();
@endphp

<div class="operation-page">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Nhân sự & Phân công</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nhân sự & Phân công</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-success" onclick="Livewire.dispatch('staff:open-create')">
                <i class="fi fi-rr-plus me-1"></i> Thêm nhân sự
            </button>
            <a href="{{ route('operations.projects') }}" class="btn btn-outline-primary">
                <i class="fi fi-rr-briefcase me-1"></i> Xem dự án
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Tổng nhân sự',
                'value' => count($staff),
                'sub' => 'theo Google Sheet',
                'icon' => 'fi fi-rr-users',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Quản lý vận hành',
                'value' => $managerCount,
                'sub' => 'phụ trách cụm dự án',
                'icon' => 'fi fi-rr-user-crown',
                'accent' => 'bg-primary-subtle text-primary',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Chuyên viên',
                'value' => $specialistCount,
                'sub' => 'triển khai 21 chức trách',
                'icon' => 'fi fi-rr-user-gear',
                'accent' => 'bg-info-subtle text-info',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Đang quá tải',
                'value' => $overloadedCount,
                'sub' => 'cần cân bằng phân công',
                'icon' => 'fi fi-rr-triangle-warning',
                'accent' => 'bg-danger-subtle text-danger',
            ])
        </div>
    </div>

    @if ($suggestion)
        <div class="alert alert-warning border-0 shadow-sm d-flex gap-3 align-items-start">
            <i class="fi fi-rr-lightbulb-on fs-4"></i>
            <div>
                <div class="fw-bold">Gợi ý cân bằng tải</div>
                {{ $suggestion['from']['name'] }} đang gánh <strong>{{ $suggestion['from']['count'] }} dự án</strong>,
                trong khi {{ $suggestion['to']['name'] }} có <strong>{{ $suggestion['to']['count'] }} dự án</strong>.
                Cân nhắc chuyển <strong>{{ $suggestion['move'] }} dự án</strong> để phân bổ công bằng hơn.
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xxl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">Danh sách nhân sự vận hành</h5>
                        <small class="text-muted">Bấm vào một người để xem dự án đang phụ trách.</small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('operations.staff', array_merge($roleQuery, ['role' => 'all'])) }}" class="btn btn-outline-primary {{ $roleFilter === 'all' ? 'active' : '' }}">Tất cả</a>
                        <a href="{{ route('operations.staff', array_merge($roleQuery, ['role' => 'Quản lý vận hành'])) }}" class="btn btn-outline-primary {{ $roleFilter === 'Quản lý vận hành' ? 'active' : '' }}">Quản lý</a>
                        <a href="{{ route('operations.staff', array_merge($roleQuery, ['role' => 'Chuyên viên vận hành'])) }}" class="btn btn-outline-primary {{ $roleFilter === 'Chuyên viên vận hành' ? 'active' : '' }}">Chuyên viên</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nhân sự</th>
                                    <th>Vai trò</th>
                                    <th class="text-center">Dự án</th>
                                    <th>Tải công việc</th>
                                    <th class="text-center">Tiến độ TB</th>
                                    <th class="text-center" style="width: 80px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($filteredStaff as $person)
                                    @php
                                        $isSelected = $selectedStaff && $selectedStaff['name'] === $person['name'];
                                        $maxLoad = $person['role'] === 'Quản lý vận hành' ? 13 : 8;
                                        $loadPct = $maxLoad > 0 ? min(100, ($person['project_count'] / $maxLoad) * 100) : 0;
                                        $loadClass = $person['overloaded'] ? 'bg-danger' : ($loadPct >= 70 ? 'bg-warning' : 'bg-success');
                                    @endphp
                                    <tr class="{{ $isSelected ? 'table-primary' : '' }}">
                                        <td>
                                            <a class="d-flex align-items-center gap-2 text-body" href="{{ route('operations.staff', array_merge($roleQuery, ['role' => $roleFilter, 'person' => $person['name']])) }}">
                                                @include('operations.partials.avatar', ['name' => $person['name']])
                                                <span class="min-w-0">
                                                    <span class="fw-semibold d-block">
                                                        {{ $person['name'] }}
                                                        @if ($person['overloaded'])
                                                            <span class="badge bg-danger-subtle text-danger ms-1">quá tải</span>
                                                        @endif
                                                    </span>
                                                    <span class="text-muted text-xs">{{ $person['branch'] }} · {{ $person['employment_status'] }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td class="text-muted">{{ $person['role'] }}</td>
                                        <td class="text-center fw-semibold">{{ $person['project_count'] }}</td>
                                        <td style="min-width: 150px;">
                                            @include('operations.partials.progress', ['value' => $loadPct, 'class' => $loadClass])
                                        </td>
                                        <td class="text-center">{{ $person['avg_progress'] }}%</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" onclick="Livewire.dispatch('staff:open-edit', { name: '{{ $person['name'] }}' })" title="Thiết lập / Phân công">
                                                <i class="fi fi-rr-pencil" style="font-size: 11px;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">Không có nhân sự phù hợp.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="card border-0 shadow-sm position-sticky" style="top: 96px;">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-1">Dự án phụ trách</h5>
                    <small class="text-muted">Theo vai trò quản lý hoặc chuyên viên.</small>
                </div>
                <div class="card-body">
                    @if ($selectedStaff)
                        <div class="d-flex align-items-center justify-content-between rounded-3 bg-light p-3 mb-3">
                            <div class="d-flex align-items-center gap-3 min-w-0">
                                @include('operations.partials.avatar', ['name' => $selectedStaff['name']])
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate">{{ $selectedStaff['name'] }}</div>
                                    <div class="text-muted text-xs">{{ $selectedStaff['role'] }} · {{ $selectedStaff['project_count'] }} dự án · {{ $selectedStaff['branch'] }}</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="Livewire.dispatch('staff:open-edit', { name: '{{ $selectedStaff['name'] }}' })" title="Thiết lập / Phân công">
                                <i class="fi fi-rr-pencil me-1"></i> Phân công
                            </button>
                        </div>

                        <div class="operation-scroll pe-1">
                            @forelse ($selectedStaff['projects'] as $project)
                                <div class="border rounded-3 p-3 mb-2">
                                    <div class="d-flex gap-2 justify-content-between align-items-start mb-2">
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-dark text-truncate">{{ $project['name'] }}</div>
                                            <div class="text-muted text-xs">{{ $project['code'] }} · {{ $project['branch'] }}</div>
                                        </div>
                                        @include('operations.partials.status-badge', ['status' => $project['status']])
                                    </div>
                                    @if ($selectedStaff['role'] !== 'Quản lý vận hành')
                                        <div class="text-muted text-xs mb-2">QL: {{ $project['manager_name'] }}</div>
                                    @endif
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted text-xs text-nowrap">21 chức trách</span>
                                        <div class="flex-grow-1">
                                            @include('operations.partials.progress', ['value' => $project['progress_pct'], 'class' => 'bg-primary'])
                                        </div>
                                        <span class="text-xs fw-semibold">{{ $project['progress'] }}/21</span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">Chưa phụ trách dự án nào.</div>
                            @endforelse
                        </div>
                    @else
                        <div class="text-center text-muted py-4">Chọn một nhân sự để xem chi tiết.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<livewire:operations.staff-assignment-crud />

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('staff:saved', () => {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
        });
    </script>
@endpush

@endsection
