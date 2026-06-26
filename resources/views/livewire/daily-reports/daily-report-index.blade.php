<div x-data="{ viewType: $wire.entangle('viewType') }" x-init="$watch('viewType', value => {
    if (value === 'calendar') {
        $nextTick(() => {
            if (window.calendarReportsInstance) {
                window.calendarReportsInstance.updateSize();
                window.calendarReportsInstance.refetchEvents();
            }
        });
    }
})">
    {{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Báo cáo Ngày</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Báo cáo Ngày</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm me-2">
                <button
                    type="button"
                    class="btn btn-outline-primary"
                    :class="viewType === 'table' ? 'active' : ''"
                    @click="viewType = 'table'"
                >
                    <i class="fi fi-rr-list-check me-1"></i> Danh sách
                </button>
                <button
                    type="button"
                    class="btn btn-outline-primary"
                    :class="viewType === 'calendar' ? 'active' : ''"
                    @click="viewType = 'calendar'"
                >
                    <i class="fi fi-rr-calendar me-1"></i> Lịch
                </button>
            </div>
            @can('create', \App\Models\DailyReport::class)
                <button
                    type="button"
                    class="btn btn-primary waves-effect waves-light"
                    wire:click="openCreateModal"
                >
                    <i class="fi fi-rr-plus me-1"></i> Tạo báo cáo hôm nay
                </button>
            @endcan
        </div>
    </div>

    {{-- ── View Mode Tabs (mine / all) ─────────────────────────────────────── --}}
    @if ($canViewAll && auth()->user()?->can(\App\Enums\PermissionEnum::ReportCreate->value))
        <ul class="nav nav-pills mb-3 gap-1">
            <li class="nav-item">
                <button
                    type="button"
                    class="nav-link {{ $viewMode === 'mine' ? 'active' : '' }}"
                    wire:click="$set('viewMode', 'mine')"
                >
                    <i class="fi fi-rr-user me-1"></i> Của tôi
                </button>
            </li>
            <li class="nav-item">
                <button
                    type="button"
                    class="nav-link {{ $viewMode === 'all' ? 'active' : '' }}"
                    wire:click="$set('viewMode', 'all')"
                >
                    <i class="fi fi-rr-users me-1"></i> Tất cả
                </button>
            </li>
        </ul>
    @endif

    {{-- ── Filter Bar ───────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-sm-auto">
                    <label class="form-label mb-0 text-muted small">Ngày</label>
                    <input
                        type="date"
                        class="form-control form-control-sm"
                        wire:model.live="filterDate"
                        id="filterDate"
                        style="min-width: 150px;"
                    />
                </div>

                @if ($canViewAll && $viewMode === 'all')
                    <div class="col-sm-auto">
                        <label class="form-label mb-0 text-muted small">Nhân viên</label>
                        <select class="form-select form-select-sm" wire:model.live="filterUserId" id="filterUser" style="min-width: 180px;">
                            <option value="0">Tất cả nhân viên</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-sm">
                    <label class="form-label mb-0 text-muted small">Tìm kiếm</label>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Tìm trong nội dung..."
                        id="searchReport"
                    />
                </div>

                @if ($filterDate || $filterUserId || $search)
                    <div class="col-sm-auto">
                        <label class="form-label mb-0 invisible d-block">Xóa</label>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            wire:click="$set('filterDate', ''); $set('filterUserId', 0); $set('search', '')"
                        >
                            <i class="fi fi-rr-cross-small me-1"></i> Xóa bộ lọc
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div :class="viewType === 'table' ? '' : 'd-none'">
        {{-- ── Reports Table ────────────────────────────────────────────────────── --}}
        <div class="card">
        <div class="card-body p-0">
            @if ($reports->isEmpty())
                <div class="text-center py-5">
                    <i class="fi fi-rr-document scale-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted mb-1">Chưa có báo cáo nào.</p>
                    @can('create', \App\Models\DailyReport::class)
                        <button type="button" class="btn btn-sm btn-primary mt-2" wire:click="openCreateModal">
                            <i class="fi fi-rr-plus me-1"></i> Tạo báo cáo ngay
                        </button>
                    @endcan
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Ngày</th>
                                @if ($canViewAll && $viewMode === 'all')
                                    <th>Nhân viên</th>
                                @endif
                                <th>Công việc đã thực hiện</th>
                                <th>Kế hoạch ngày mai</th>
                                <th>Vấn đề phát sinh</th>
                                <th class="text-end pe-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr wire:key="report-{{ $report->id }}">
                                    <td class="ps-3 text-nowrap">
                                        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">
                                            {{ $report->report_date->format('d/m/Y') }}
                                        </span>
                                        <div class="text-muted text-xs mt-1">
                                            {{ $report->report_date->diffForHumans() }}
                                        </div>
                                    </td>

                                    @if ($canViewAll && $viewMode === 'all')
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-weight:600;font-size:0.8rem;">
                                                    {{ strtoupper(substr($report->user->name ?? 'U', 0, 1)) }}
                                                </div>
                                                <span class="text-body fw-medium">{{ $report->user->name ?? '—' }}</span>
                                            </div>
                                        </td>
                                    @endif

                                    <td style="max-width: 280px;">
                                        <div class="text-body" style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                            {{ $report->work_done }}
                                        </div>
                                    </td>

                                    <td style="max-width: 200px;">
                                        @if ($report->plan_tomorrow)
                                            <div class="text-muted text-sm" style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                                {{ $report->plan_tomorrow }}
                                            </div>
                                        @else
                                            <span class="text-muted text-xs">—</span>
                                        @endif
                                    </td>

                                    <td style="max-width: 180px;">
                                        @if ($report->issues)
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="fi fi-rr-exclamation me-1"></i>
                                                {{ \Illuminate\Support\Str::limit($report->issues, 40) }}
                                            </span>
                                        @else
                                            <span class="text-muted text-xs">Không có</span>
                                        @endif
                                    </td>

                                    <td class="text-end pe-3 text-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary me-1"
                                            title="Xem chi tiết"
                                            wire:click="openDetailModal({{ $report->id }})"
                                        >
                                            <i class="fi fi-rr-eye"></i>
                                        </button>

                                        @can('update', $report)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary me-1"
                                                title="Chỉnh sửa"
                                                wire:click="openEditModal({{ $report->id }})"
                                            >
                                                <i class="fi fi-rr-edit"></i>
                                            </button>
                                        @endcan

                                        @can('delete', $report)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Xóa"
                                                wire:click="delete({{ $report->id }})"
                                                wire:confirm="Bạn có chắc chắn muốn xóa báo cáo ngày {{ $report->report_date->format('d/m/Y') }} không?"
                                            >
                                                <i class="fi fi-rr-trash"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($reports->hasPages())
                    <div class="p-3 border-top">
                        {{ $reports->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
    </div>

    <div :class="viewType === 'calendar' ? '' : 'd-none'" wire:ignore>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 calendar-scroll-wrapper">
                <div class="d-flex flex-wrap gap-3 mb-3 small">
                    <span class="d-flex align-items-center">
                        <span class="d-inline-block rounded-circle bg-success-subtle border border-success me-2" style="width: 12px; height: 12px; flex-shrink: 0;"></span> Báo cáo hoàn thành tốt
                    </span>
                    <span class="d-flex align-items-center">
                        <span class="d-inline-block rounded-circle bg-warning-subtle border border-warning me-2" style="width: 12px; height: 12px; flex-shrink: 0;"></span> Báo cáo có vấn đề phát sinh
                    </span>
                </div>
                <div id="calendar-reports"></div>
            </div>
        </div>
    </div>

    {{-- ── Create / Edit Modal ─────────────────────────────────────────────── --}}
    <div wire:ignore.self class="modal fade" id="reportCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form wire:submit.prevent="save" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fi fi-rr-document me-2 text-primary"></i>
                        {{ $editingId > 0 ? 'Chỉnh sửa Báo cáo' : 'Tạo Báo cáo Ngày' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="formDate">
                            Ngày báo cáo <span class="text-danger">*</span>
                        </label>
                        <input
                            type="date"
                            id="formDate"
                            wire:model.defer="formDate"
                            class="form-control @error('formDate') is-invalid @enderror"
                        />
                        @error('formDate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="formWorkDone">
                            Công việc đã thực hiện <span class="text-danger">*</span>
                        </label>
                        <textarea
                            id="formWorkDone"
                            wire:model.defer="formWorkDone"
                            class="form-control @error('formWorkDone') is-invalid @enderror"
                            rows="5"
                            placeholder="Mô tả chi tiết những công việc bạn đã thực hiện trong ngày hôm nay..."
                        ></textarea>
                        @error('formWorkDone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="formPlanTomorrow">
                            Kế hoạch ngày mai
                        </label>
                        <textarea
                            id="formPlanTomorrow"
                            wire:model.defer="formPlanTomorrow"
                            class="form-control @error('formPlanTomorrow') is-invalid @enderror"
                            rows="3"
                            placeholder="Những việc bạn sẽ làm vào ngày mai..."
                        ></textarea>
                        @error('formPlanTomorrow')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-1">
                        <label class="form-label" for="formIssues">
                            Vấn đề phát sinh <span class="text-muted text-sm">(nếu có)</span>
                        </label>
                        <textarea
                            id="formIssues"
                            wire:model.defer="formIssues"
                            class="form-control @error('formIssues') is-invalid @enderror"
                            rows="2"
                            placeholder="Khó khăn, vướng mắc cần hỗ trợ..."
                        ></textarea>
                        @error('formIssues')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fi fi-rr-check me-1"></i>
                        {{ $editingId > 0 ? 'Lưu thay đổi' : 'Tạo báo cáo' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Detail View Modal ───────────────────────────────────────────────── --}}
    <div wire:ignore.self class="modal fade" id="reportDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fi fi-rr-document me-2 text-primary"></i>
                        Chi tiết Báo cáo Ngày
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($viewingReport)
                        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3">
                            <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:42px;height:42px;font-size:1rem;">
                                {{ strtoupper(substr($viewingReport->user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">{{ $viewingReport->user->name ?? '—' }}</div>
                                <div class="text-primary fw-bold">
                                    <i class="fi fi-rr-calendar me-1"></i>
                                    {{ $viewingReport->report_date->format('d/m/Y') }} ({{ $viewingReport->report_date->isoFormat('dddd') }})
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-dark fw-bold mb-2">
                                <i class="fi fi-rr-check-circle text-success me-2"></i>
                                Công việc đã thực hiện
                            </h6>
                            <div class="p-3 border rounded-3 bg-light text-body" style="white-space: pre-wrap;">{{ $viewingReport->work_done }}</div>
                        </div>

                        @if ($viewingReport->plan_tomorrow)
                            <div class="mb-4">
                                <h6 class="text-dark fw-bold mb-2">
                                    <i class="fi fi-rr-arrow-right text-primary me-2"></i>
                                    Kế hoạch ngày mai
                                </h6>
                                <div class="p-3 border rounded-3 bg-light text-body" style="white-space: pre-wrap;">{{ $viewingReport->plan_tomorrow }}</div>
                            </div>
                        @endif

                        @if ($viewingReport->issues)
                            <div class="mb-2">
                                <h6 class="text-dark fw-bold mb-2">
                                    <i class="fi fi-rr-exclamation text-warning me-2"></i>
                                    Vấn đề phát sinh
                                </h6>
                                <div class="p-3 border border-warning rounded-3 bg-warning-subtle text-body" style="white-space: pre-wrap;">{{ $viewingReport->issues }}</div>
                            </div>
                        @endif

                        <div class="text-muted text-xs mt-3">
                            <i class="fi fi-rr-clock me-1"></i>
                            Tạo lúc: {{ $viewingReport->created_at->format('H:i d/m/Y') }}
                            @if ($viewingReport->updated_at->ne($viewingReport->created_at))
                                · Cập nhật: {{ $viewingReport->updated_at->format('H:i d/m/Y') }}
                            @endif
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    @if ($viewingReport && auth()->user()?->can('update', $viewingReport))
                        <button
                            type="button"
                            class="btn btn-primary"
                            wire:click="openEditModal({{ $viewingReport->id }})"
                        >
                            <i class="fi fi-rr-edit me-1"></i> Chỉnh sửa
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Day Reports List (for Director / users who cannot create) -->
    <div
        class="modal fade"
        id="dayReportsModal"
        tabindex="-1"
        aria-labelledby="dayReportsModalLabel"
        aria-hidden="true"
        wire:ignore.self
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark" id="dayReportsModalLabel">
                        <i class="fi fi-rr-document text-primary me-2"></i> Báo cáo ngày {{ $selectedDateStr }}
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                    @if (empty($dayReports))
                        <div class="text-center py-5">
                            <i class="fi fi-rr-document-signed scale-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">Không có báo cáo nào trong ngày này.</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach ($dayReports as $report)
                                <div class="card border border-light-subtle shadow-sm mb-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-3 bg-light p-2 rounded-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;font-size:0.8rem;">
                                                    {{ strtoupper(substr($report['user_name'], 0, 1)) }}
                                                </div>
                                                <div>
                                                    <span class="text-body fw-bold d-block">{{ $report['user_name'] }}</span>
                                                    <span class="text-muted text-xs"><i class="fi fi-rr-clock me-1"></i>Tạo lúc: {{ $report['created_at_formatted'] }}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-1">
                                                @if ($report['can_edit'])
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-warning p-1 lh-1"
                                                        title="Chỉnh sửa"
                                                        wire:click="openEditFromList({{ $report['id'] }})"
                                                    >
                                                        <i class="fi fi-rr-edit"></i>
                                                    </button>
                                                @endif
                                                @if ($report['can_delete'])
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger p-1 lh-1"
                                                        title="Xóa"
                                                        wire:click="deleteFromList({{ $report['id'] }})"
                                                        wire:confirm="Bạn có chắc chắn muốn xóa báo cáo ngày này không?"
                                                    >
                                                        <i class="fi fi-rr-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <h6 class="text-dark fw-bold mb-1 text-sm">
                                                <i class="fi fi-rr-check-circle text-success me-1"></i>
                                                Công việc đã thực hiện
                                            </h6>
                                            <div class="p-2 border rounded bg-light text-body text-sm" style="white-space: pre-wrap;">{{ $report['work_done'] }}</div>
                                        </div>

                                        @if ($report['plan_tomorrow'])
                                            <div class="mb-3">
                                                <h6 class="text-dark fw-bold mb-1 text-sm">
                                                    <i class="fi fi-rr-arrow-right text-primary me-1"></i>
                                                    Kế hoạch ngày mai
                                                </h6>
                                                <div class="p-2 border rounded bg-light text-body text-sm" style="white-space: pre-wrap;">{{ $report['plan_tomorrow'] }}</div>
                                            </div>
                                        @endif

                                        @if ($report['issues'])
                                            <div class="mb-0">
                                                <h6 class="text-dark fw-bold mb-1 text-sm">
                                                    <i class="fi fi-rr-exclamation text-warning me-1"></i>
                                                    Vấn đề phát sinh
                                                </h6>
                                                <div class="p-2 border border-warning rounded bg-warning-subtle text-body text-sm" style="white-space: pre-wrap;">{{ $report['issues'] }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light waves-effect"
                        data-bs-dismiss="modal"
                    >
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/fullcalendar.global.min.js') }}"></script>
<script>
    let calendarReportsInstance = null;
    let calendarReportsWireId = null;

    function initReportsCalendar() {
        const calendarEl = document.getElementById('calendar-reports');
        if (!calendarEl) return;

        if (typeof Livewire === 'undefined') {
            document.addEventListener('livewire:init', initReportsCalendar, { once: true });
            return;
        }

        const componentEl = calendarEl.closest('[wire\\:id]');
        if (!componentEl) return;
        const currentWireId = componentEl.getAttribute('wire:id');
        const wire = Livewire.find(currentWireId);
        if (!wire) return;

        if (calendarReportsInstance && calendarReportsWireId === currentWireId) {
            calendarReportsInstance.refetchEvents();
            return;
        }

        if (calendarReportsInstance) {
            calendarReportsInstance.destroy();
        }

        calendarReportsWireId = currentWireId;

        calendarReportsInstance = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'vi',
            firstDay: 1, // Start week on Monday
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },
            buttonText: {
                today: 'Hôm nay',
                month: 'Tháng'
            },
            buttonHints: {
                prev: '$one trước',
                next: '$one sau'
            },
            editable: false,
            selectable: true,
            events: function(info, successCallback, failureCallback) {
                wire.getEvents(info.startStr, info.endStr)
                    .then(events => successCallback(events))
                    .catch(err => {
                        console.error('Error fetching reports calendar events:', err);
                        failureCallback(err);
                    });
            },
            dateClick: function(info) {
                @can('create', \App\Models\DailyReport::class)
                    wire.openCreateModalForDate(info.dateStr);
                @else
                    wire.showDayReports(info.dateStr);
                @endcan
            },
            eventClick: function(info) {
                const event = info.event;
                wire.openDetailModal(event.id);
            }
        });

        calendarReportsInstance.render();
        window.calendarReportsInstance = calendarReportsInstance;
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('report-create:show', () => AppModal.show('reportCreateModal'));
        window.addEventListener('report-create:hide', () => {
            AppModal.hide('reportCreateModal');
            if (calendarReportsInstance) {
                calendarReportsInstance.refetchEvents();
            }
        });
        window.addEventListener('report-detail:show', () => AppModal.show('reportDetailModal'));
        window.addEventListener('report-detail:hide', () => AppModal.hide('reportDetailModal'));
        window.addEventListener('report:open-day-reports', () => AppModal.show('dayReportsModal'));
        window.addEventListener('report:close-day-reports', () => {
            AppModal.hide('dayReportsModal');
            if (calendarReportsInstance) {
                calendarReportsInstance.refetchEvents();
            }
        });

        initReportsCalendar();
    });

    document.addEventListener('livewire:navigated', function () {
        initReportsCalendar();
    });

    window.addEventListener('reports:filter-changed', () => {
        if (calendarReportsInstance) {
            calendarReportsInstance.refetchEvents();
        }
    });

    document.addEventListener('livewire:update', () => {
        if (calendarReportsInstance) {
            setTimeout(() => {
                calendarReportsInstance.updateSize();
            }, 100);
        }
    });
</script>
@endpush
