@push('styles')
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}?v=1.0.0">
@endpush

<div class="operation-page">
    @php
        $fmt = static fn ($number): string => number_format((float) $number, 0, ',', '.');
        $stageColors = ['border-info', 'border-warning', 'border-success', 'border-primary'];
        $relationshipClass = static fn (string $value): string => match ($value) {
            'Rất tốt', 'Tốt' => 'bg-success-subtle text-success',
            'Bình thường' => 'bg-light text-muted',
            default => 'bg-danger-subtle text-danger',
        };
        $priorityClass = static fn (string $value): string => match ($value) {
            'Cao' => 'bg-danger-subtle text-danger',
            'Theo dõi' => 'bg-warning-subtle text-warning',
            default => 'bg-primary-subtle text-primary',
        };
    @endphp

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
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('operations.alerts', ['rule' => 'Công nợ đến hạn']) }}" class="btn btn-outline-primary">
                <i class="fi fi-rr-bell-ring me-1"></i> Cảnh báo công nợ
            </a>
            @can('crm.create')
                <button type="button" class="btn btn-primary" wire:click="create">
                    <i class="fi fi-rr-plus me-1"></i> Thêm khách hàng
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2">
            @include('operations.partials.kpi-card', [
                'label' => 'Hồ sơ CRM',
                'value' => $summary['total'],
                'sub' => $summary['active'].' đang chăm sóc',
                'icon' => 'fi fi-rr-address-book',
                'accent' => 'bg-primary-subtle text-primary',
            ])
        </div>
        <div class="col-6 col-xl-2">
            @include('operations.partials.kpi-card', [
                'label' => 'Đã ký / dài hạn',
                'value' => $summary['signed'],
                'sub' => 'khách hàng hợp đồng',
                'icon' => 'fi fi-rr-document-signed',
                'accent' => 'bg-success-subtle text-success',
            ])
        </div>
        <div class="col-6 col-xl-2">
            @include('operations.partials.kpi-card', [
                'label' => 'Lịch hẹn gần',
                'value' => $summary['due_soon'],
                'sub' => 'trong 3 ngày tới',
                'icon' => 'fi fi-rr-calendar-clock',
                'accent' => 'bg-warning-subtle text-warning',
            ])
        </div>
        <div class="col-6 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Doanh thu/tháng',
                'value' => $fmt($summary['revenue']).' tr',
                'sub' => 'theo hồ sơ CRM',
                'icon' => 'fi fi-rr-money-bill-wave',
                'accent' => 'bg-info-subtle text-info',
            ])
        </div>
        <div class="col-12 col-xl-3">
            @include('operations.partials.kpi-card', [
                'label' => 'Công nợ mở',
                'value' => $fmt($summary['receivable']).' tr',
                'sub' => 'khớp bảng công nợ',
                'icon' => 'fi fi-rr-receipt',
                'accent' => 'bg-danger-subtle text-danger',
            ])
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Tìm kiếm</label>
                    <div class="position-relative">
                        <i class="fi fi-rr-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input
                            type="search"
                            class="form-control ps-5"
                            placeholder="Tên khách hàng, liên hệ, phụ trách..."
                            wire:model.live.debounce.350ms="search"
                        >
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label">Giai đoạn</label>
                    <select class="form-select" wire:model.live="stage">
                        <option value="">Tất cả</option>
                        @foreach ($stages as $index => $stageName)
                            <option value="{{ $index }}">{{ $stageName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label">Quan hệ</label>
                    <select class="form-select" wire:model.live="relationship">
                        <option value="">Tất cả</option>
                        @foreach ($relationships as $relationshipName)
                            <option value="{{ $relationshipName }}">{{ $relationshipName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label">Ưu tiên</label>
                    <select class="form-select" wire:model.live="priority">
                        <option value="">Tất cả</option>
                        @foreach ($priorities as $priorityName)
                            <option value="{{ $priorityName }}">{{ $priorityName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" wire:model.live="active">
                        <option value="all">Tất cả</option>
                        <option value="active">Đang chăm sóc</option>
                        <option value="inactive">Tạm dừng</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Pipeline quan hệ khách hàng</h5>
        <span class="text-muted text-sm">Bấm chuyển giai đoạn để cập nhật nhanh trạng thái chăm sóc.</span>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($stages as $index => $stageName)
            <div class="col-12 col-lg-6 col-xxl-3">
                <div class="card border shadow-sm h-100 operation-pipeline-column {{ $stageColors[$index] ?? 'border-primary' }}">
                    <div class="card-header border-0 bg-transparent pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">{{ $stageName }}</h6>
                        <span class="badge bg-white text-muted border">{{ count($customersByStage[$stageName] ?? []) }}</span>
                    </div>
                    <div class="card-body">
                        @forelse (($customersByStage[$stageName] ?? []) as $customer)
                            <div class="card border-0 shadow-sm mb-3 operation-card-hover" wire:key="crm-card-{{ $customer['id'] }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between gap-2 align-items-start">
                                        <div class="min-w-0">
                                            <h6 class="fw-bold mb-1 text-truncate">{{ $customer['name'] }}</h6>
                                            <div class="text-muted text-xs">{{ $customer['contact_name'] }} · {{ $customer['contact_role'] }}</div>
                                        </div>
                                        <span class="badge rounded-pill {{ $relationshipClass($customer['relationship']) }}">{{ $customer['relationship'] }}</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-3">
                                        <span class="badge {{ $priorityClass($customer['priority']) }}">{{ $customer['priority'] }}</span>
                                        @if (! $customer['active'])
                                            <span class="badge bg-secondary-subtle text-secondary">Tạm dừng</span>
                                        @endif
                                    </div>
                                    <div class="d-flex justify-content-between text-xs mt-3">
                                        <span class="text-muted">{{ $customer['revenue_monthly'] ? $fmt($customer['revenue_monthly']).' tr/th' : 'Chưa phát sinh DT' }}</span>
                                        <span class="{{ $customer['days_to_meeting'] <= 3 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                            <i class="fi fi-rr-calendar me-1"></i>{{ $customer['next_meeting']->format('d/m') }}
                                        </span>
                                    </div>
                                    @if ($customer['next_action'])
                                        <div class="border rounded-3 bg-light p-2 text-sm mt-3">{{ $customer['next_action'] }}</div>
                                    @endif
                                    @can('crm.update')
                                        <div class="d-flex gap-2 mt-3">
                                            <button type="button" class="btn btn-light btn-sm flex-fill" wire:click="edit({{ $customer['id'] }})">
                                                <i class="fi fi-rr-edit me-1"></i> Sửa
                                            </button>
                                            <div class="dropdown">
                                                <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fi fi-rr-angle-small-down"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @foreach ($stages as $moveIndex => $moveStage)
                                                        <li>
                                                            <button type="button" class="dropdown-item" wire:click="moveStage({{ $customer['id'] }}, {{ $moveIndex }})">
                                                                {{ $moveStage }}
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item" wire:click="toggleActive({{ $customer['id'] }})">
                                                            {{ $customer['active'] ? 'Tạm dừng chăm sóc' : 'Mở lại chăm sóc' }}
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    @endcan
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

    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h5 class="mb-0">Danh sách hồ sơ CRM</h5>
            <span class="text-muted text-sm">{{ $customers->count() }} khách hàng theo bộ lọc hiện tại</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-row-rounded">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
                            <th>Phụ trách</th>
                            <th>Giai đoạn</th>
                            <th>Doanh thu</th>
                            <th>Lịch tiếp theo</th>
                            <th>Dự án / Công nợ</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr wire:key="crm-row-{{ $customer['id'] }}">
                                <td>
                                    <div class="fw-semibold">{{ $customer['name'] }}</div>
                                    <div class="text-muted text-xs">{{ $customer['type'] }}</div>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <span class="badge {{ $relationshipClass($customer['relationship']) }}">{{ $customer['relationship'] }}</span>
                                        <span class="badge {{ $priorityClass($customer['priority']) }}">{{ $customer['priority'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $customer['contact_name'] }}</div>
                                    <div class="text-muted text-xs">{{ $customer['contact_role'] }}</div>
                                    @if ($customer['contact_phone'] || $customer['contact_email'])
                                        <div class="text-muted text-xs">{{ $customer['contact_phone'] ?: $customer['contact_email'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $customer['owner_name'] ?: 'Chưa gán' }}</td>
                                <td>{{ $customer['stage'] }}</td>
                                <td>{{ $fmt($customer['revenue_monthly']) }} tr/th</td>
                                <td>
                                    <span class="{{ $customer['days_to_meeting'] <= 3 ? 'text-danger fw-semibold' : '' }}">
                                        {{ $customer['next_meeting']->format('d/m/Y') }}
                                    </span>
                                    @if ($customer['next_action'])
                                        <div class="text-muted text-xs">{{ $customer['next_action'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $customer['project_count'] }} dự án</div>
                                    <div class="text-muted text-xs">{{ $fmt($customer['receivable_total']) }} tr công nợ</div>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                        @can('crm.update')
                                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="edit({{ $customer['id'] }})">
                                                <i class="fi fi-rr-edit me-1"></i> Sửa
                                            </button>
                                        @endcan
                                        @can('crm.delete')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $customer['id'] }})"
                                                wire:confirm="Bạn có chắc chắn muốn xóa hồ sơ CRM của {{ $customer['name'] }}?"
                                            >
                                                <i class="fi fi-rr-trash me-1"></i> Xóa
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">Không có hồ sơ CRM phù hợp.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="crmCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <form wire:submit.prevent="save" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Cập nhật hồ sơ CRM' : 'Thêm khách hàng CRM' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">Tên khách hàng <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Phân loại</label>
                            <input type="text" class="form-control @error('type') is-invalid @enderror" wire:model.defer="type">
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Nguồn</label>
                            <input type="text" class="form-control @error('source') is-invalid @enderror" wire:model.defer="source">
                            @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Giai đoạn</label>
                            <select class="form-select @error('stage_idx') is-invalid @enderror" wire:model.defer="stage_idx">
                                @foreach ($stages as $index => $stageName)
                                    <option value="{{ $index }}">{{ $stageName }}</option>
                                @endforeach
                            </select>
                            @error('stage_idx') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Quan hệ</label>
                            <select class="form-select @error('relationshipField') is-invalid @enderror" wire:model.defer="relationshipField">
                                @foreach ($relationships as $relationshipName)
                                    <option value="{{ $relationshipName }}">{{ $relationshipName }}</option>
                                @endforeach
                            </select>
                            @error('relationshipField') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Ưu tiên</label>
                            <select class="form-select @error('priorityField') is-invalid @enderror" wire:model.defer="priorityField">
                                @foreach ($priorities as $priorityName)
                                    <option value="{{ $priorityName }}">{{ $priorityName }}</option>
                                @endforeach
                            </select>
                            @error('priorityField') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Phụ trách</label>
                            <input type="text" list="crmOwnerOptions" class="form-control @error('owner_name') is-invalid @enderror" wire:model.defer="owner_name">
                            <datalist id="crmOwnerOptions">
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner }}">
                                @endforeach
                            </datalist>
                            @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Người liên hệ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('contact_name') is-invalid @enderror" wire:model.defer="contact_name">
                            @error('contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Chức vụ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('contact_role') is-invalid @enderror" wire:model.defer="contact_role">
                            @error('contact_role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control @error('contact_phone') is-invalid @enderror" wire:model.defer="contact_phone">
                            @error('contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('contact_email') is-invalid @enderror" wire:model.defer="contact_email">
                            @error('contact_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Doanh thu/tháng (triệu)</label>
                            <input type="number" min="0" class="form-control @error('revenue_monthly') is-invalid @enderror" wire:model.defer="revenue_monthly">
                            @error('revenue_monthly') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Lần gặp gần nhất</label>
                            <input type="date" class="form-control @error('last_meeting') is-invalid @enderror" wire:model.defer="last_meeting">
                            @error('last_meeting') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Lịch gặp tiếp theo</label>
                            <input type="date" class="form-control @error('next_meeting') is-invalid @enderror" wire:model.defer="next_meeting">
                            @error('next_meeting') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Trạng thái</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="crmActiveSwitch" wire:model.defer="activeField">
                                <label class="form-check-label" for="crmActiveSwitch">Đang chăm sóc</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hành động tiếp theo</label>
                            <input type="text" class="form-control @error('next_action') is-invalid @enderror" wire:model.defer="next_action" placeholder="Ví dụ: Gửi báo giá, hẹn gặp CEO, nhắc công nợ...">
                            @error('next_action') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ghi chú quan hệ</label>
                            <textarea class="form-control @error('notesText') is-invalid @enderror" rows="5" wire:model.defer="notesText" placeholder="Mỗi dòng là một ghi chú"></textarea>
                            @error('notesText') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fi fi-rr-disk me-1"></i> Lưu hồ sơ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('crm-form:show', () => {
            const modalEl = document.getElementById('crmCustomerModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });

        window.addEventListener('crm-form:hide', () => {
            const modalEl = document.getElementById('crmCustomerModal');
            if (modalEl) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
        });
    });
</script>
@endpush
