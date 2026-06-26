<div>
    <div class="app-page-head d-flex align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Lịch công tác</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Lịch công tác
                    </li>
                </ol>
            </nav>
        </div>
        @can('create', App\Models\DutySchedule::class)
            <button
                type="button"
                class="btn btn-primary waves-effect waves-light"
                wire:click="openCreate('{{ date('Y-m-d') }}')"
                wire:loading.attr="disabled"
            >
                <i class="fi fi-rr-plus me-1"></i> Thêm lịch công tác
            </button>
        @endcan
    </div>

    @if ($successMessage)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fi fi-rr-check me-2"></i> {{ $successMessage }}
            <button type="button" class="btn-close" wire:click="$set('successMessage', null)" aria-label="Close"></button>
        </div>
    @endif

    {{-- ── Filter Bar ───────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-sm-auto">
                    <label class="form-label mb-0 text-muted small">Người tạo / Người tham gia</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUserId" id="filterParticipant" style="min-width: 220px;">
                        <option value="0">Tất cả thành viên</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="p-4 calendar-scroll-wrapper" wire:ignore>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form (Add / Edit Event) -->
    <div
        class="modal fade"
        id="modalAddEvent"
        tabindex="-1"
        aria-labelledby="modalAddEventLabel"
        aria-hidden="true"
        wire:ignore.self
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark" id="modalAddEventLabel">
                        {{ $scheduleId ? 'Cập nhật lịch công tác' : 'Thêm lịch công tác' }}
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        wire:click="resetForm"
                    ></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    wire:model="title"
                                    class="form-control @error('title') is-invalid @enderror"
                                    placeholder="Nhập tiêu đề công việc..."
                                />
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Nhãn màu sắc <span class="text-danger">*</span></label>
                                <select wire:model="label_color" class="form-select @error('label_color') is-invalid @enderror">
                                    <option value="primary">Primary (Mặc định)</option>
                                    <option value="success">Success (Hoàn thành)</option>
                                    <option value="info">Info (Cuộc họp)</option>
                                    <option value="purple">Purple (Họp công tác)</option>
                                    <option value="warning">Warning (Cá nhân/Nháp)</option>
                                    <option value="danger">Danger (Khẩn cấp)</option>
                                </select>
                                @error('label_color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch mt-1">
                                    <input
                                        type="checkbox"
                                        class="form-check-input @error('is_private') is-invalid @enderror"
                                        id="is_private"
                                        wire:model="is_private"
                                    />
                                    <label class="form-check-label fw-semibold" for="is_private">
                                        Lịch riêng tư (Chỉ bạn và quản trị viên nhìn thấy chi tiết)
                                    </label>
                                    @error('is_private')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-semibold">Thời gian bắt đầu <span class="text-danger">*</span></label>
                                <input
                                    type="datetime-local"
                                    wire:model="start_at"
                                    class="form-control @error('start_at') is-invalid @enderror"
                                />
                                @error('start_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-semibold">Thời gian kết thúc</label>
                                <input
                                    type="datetime-local"
                                    wire:model="end_at"
                                    class="form-control @error('end_at') is-invalid @enderror"
                                />
                                @error('end_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Địa điểm</label>
                                <input
                                    type="text"
                                    wire:model="location"
                                    class="form-control @error('location') is-invalid @enderror"
                                    placeholder="Nhập địa điểm họp, làm việc..."
                                />
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Nội dung chi tiết</label>
                                <textarea
                                    wire:model="description"
                                    class="form-control @error('description') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Mô tả nội dung công việc chi tiết..."
                                ></textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Thành viên tham gia</label>
                                <div class="border rounded p-3 user-select-list @error('user_ids') is-invalid @enderror">
                                    @foreach ($users as $u)
                                        <div class="form-check mb-1">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                id="user_check_{{ $u->id }}"
                                                value="{{ $u->id }}"
                                                wire:model="user_ids"
                                            />
                                            <label class="form-check-label text-dark fw-medium" for="user_check_{{ $u->id }}">
                                                {{ $u->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('user_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 text-end">
                                <button
                                    type="button"
                                    class="btn btn-light waves-effect me-2"
                                    data-bs-dismiss="modal"
                                    wire:click="resetForm"
                                >
                                    Hủy
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary waves-effect waves-light"
                                >
                                    Lưu lại
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal View Details -->
    <div
        class="modal fade"
        id="eventDetailsModal"
        tabindex="-1"
        aria-hidden="true"
        wire:ignore
    >
        <div class="modal-dialog modal-dialog-centered duty-schedule-detail-dialog">
            <div class="modal-content duty-schedule-detail-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark" id="detailTitle">Chi tiết lịch công tác</h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body duty-schedule-detail-body">
                    <div class="schedule-detail-list">
                        <div class="schedule-detail-item">
                            <strong class="schedule-detail-label"><i class="fi fi-rr-clock"></i>Bắt đầu:</strong>
                            <span id="detailStart" class="schedule-detail-value"></span>
                        </div>
                        <div class="schedule-detail-item">
                            <strong class="schedule-detail-label"><i class="fi fi-rr-calendar"></i>Kết thúc:</strong>
                            <span id="detailEnd" class="schedule-detail-value"></span>
                        </div>
                        <div class="schedule-detail-item">
                            <strong class="schedule-detail-label"><i class="fi fi-rr-marker"></i>Địa điểm:</strong>
                            <span id="detailLocation" class="schedule-detail-value"></span>
                        </div>
                        <div class="schedule-detail-item">
                            <strong class="schedule-detail-label"><i class="fi fi-rr-user"></i>Người tạo:</strong>
                            <div class="schedule-detail-value">
                                <span id="detailCreator" class="schedule-detail-chip schedule-detail-chip-info"></span>
                            </div>
                        </div>
                        <div class="schedule-detail-item">
                            <strong class="schedule-detail-label"><i class="fi fi-rr-users"></i>Thành viên tham gia:</strong>
                            <div id="detailParticipants" class="schedule-detail-value schedule-detail-chips"></div>
                        </div>
                        <div class="schedule-detail-item">
                            <strong class="schedule-detail-label"><i class="fi fi-rr-document-signed"></i>Nội dung:</strong>
                            <div id="detailDescription" class="schedule-detail-value schedule-detail-description" style="white-space: pre-wrap;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light waves-effect"
                        data-bs-dismiss="modal"
                    >
                        Đóng
                    </button>
                    <button
                        type="button"
                        id="detailBtnEdit"
                        class="btn btn-warning waves-effect waves-light"
                    >
                        <i class="fi fi-rr-edit me-1"></i> Chỉnh sửa
                    </button>
                    <button
                        type="button"
                        id="detailBtnDelete"
                        class="btn btn-danger waves-effect waves-light"
                    >
                        <i class="fi fi-rr-trash me-1"></i> Xóa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Day Schedules List (for Director / users who cannot create) -->
    <div
        class="modal fade"
        id="daySchedulesModal"
        tabindex="-1"
        aria-labelledby="daySchedulesModalLabel"
        aria-hidden="true"
        wire:ignore.self
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark" id="daySchedulesModalLabel">
                        <i class="fi fi-rr-calendar text-primary me-2"></i> Lịch công tác ngày {{ $selectedDateStr }}
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                    @if (empty($daySchedules))
                        <div class="text-center py-5">
                            <i class="fi fi-rr-calendar-slash scale-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">Không có lịch công tác nào trong ngày này.</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach ($daySchedules as $schedule)
                                <div class="card border border-light-subtle shadow-sm mb-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-start justify-content-between mb-2">
                                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-1 gap-sm-2">
                                                <span class="badge 
                                                    @if($schedule['label_color'] === 'success') bg-success-subtle text-success border border-success
                                                    @elseif($schedule['label_color'] === 'warning') bg-warning-subtle text-warning border border-warning
                                                    @elseif($schedule['label_color'] === 'danger') bg-danger-subtle text-danger border border-danger
                                                    @elseif($schedule['label_color'] === 'info') bg-info-subtle text-info border border-info
                                                    @elseif($schedule['label_color'] === 'purple') bg-purple-subtle text-purple border border-purple
                                                    @elseif($schedule['label_color'] === 'private') bg-secondary-subtle text-secondary border border-secondary opacity-75
                                                    @else bg-primary-subtle text-primary border border-primary
                                                    @endif
                                                    fw-semibold px-2 py-1"
                                                >
                                                    @if($schedule['label_color'] === 'success') Hoàn thành
                                                    @elseif($schedule['label_color'] === 'warning') Cá nhân / Nháp
                                                    @elseif($schedule['label_color'] === 'danger') Khẩn cấp
                                                    @elseif($schedule['label_color'] === 'info') Cuộc họp
                                                    @elseif($schedule['label_color'] === 'purple') Họp công tác
                                                    @elseif($schedule['label_color'] === 'private') Lịch riêng tư
                                                    @else Mặc định
                                                    @endif
                                                </span>
                                                <span class="text-muted text-sm">
                                                    <i class="fi fi-rr-clock me-1"></i>
                                                    {{ $schedule['start_formatted'] }}
                                                    @if ($schedule['end_formatted'])
                                                        - {{ $schedule['end_formatted'] }}
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="d-flex gap-1">
                                                @if ($schedule['can_edit'])
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-warning p-1 lh-1"
                                                        title="Chỉnh sửa"
                                                        wire:click="openEditFromList({{ $schedule['id'] }})"
                                                    >
                                                        <i class="fi fi-rr-edit"></i>
                                                    </button>
                                                @endif
                                                @if ($schedule['can_delete'])
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger p-1 lh-1"
                                                        title="Xóa"
                                                        wire:click="deleteFromList({{ $schedule['id'] }})"
                                                        wire:confirm="Bạn có chắc chắn muốn xóa lịch công tác này không?"
                                                    >
                                                        <i class="fi fi-rr-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        <h6 class="text-dark fw-bold mb-2">{{ $schedule['title'] }}</h6>
                                        <div class="row g-2 mb-2 text-sm text-muted">
                                            <div class="col-sm-6">
                                                <strong><i class="fi fi-rr-marker me-1"></i> Địa điểm:</strong> {{ $schedule['location'] ?: 'Không có' }}
                                            </div>
                                            <div class="col-sm-6">
                                                <strong><i class="fi fi-rr-user me-1"></i> Người tạo:</strong>
                                                <span class="badge bg-info-subtle text-info border border-info text-xs">{{ $schedule['creator_name'] }}</span>
                                            </div>
                                        </div>
                                        <div class="mb-2 text-sm text-muted">
                                            <strong><i class="fi fi-rr-users me-1"></i> Thành viên:</strong>
                                            @if (empty($schedule['participants']))
                                                <span class="text-muted">Không có</span>
                                            @else
                                                <div class="d-inline-flex flex-wrap gap-1 align-items-center">
                                                    @foreach ($schedule['participants'] as $p)
                                                        <span class="badge bg-primary-subtle text-primary border border-primary text-xs">{{ $p['name'] }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        @if ($schedule['description'])
                                            <div class="p-2 bg-light rounded text-body text-sm mb-0" style="white-space: pre-wrap;">{{ $schedule['description'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        @can('create', App\Models\DutySchedule::class)
                            @if($selectedDateStr && \Carbon\Carbon::parse($selectedDateStr)->startOfDay()->gte(\Carbon\Carbon::today()))
                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm waves-effect waves-light"
                                    wire:click="openCreateFromList"
                                >
                                    <i class="fi fi-rr-plus me-1"></i> Thêm lịch ngày này
                                </button>
                            @endif
                        @endcan
                    </div>
                    <button
                        type="button"
                        class="btn btn-light btn-sm waves-effect mb-0"
                        data-bs-dismiss="modal"
                    >
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/duty-schedule.css') }}?v=1.0.2">
@endpush


@push('scripts')
<script src="{{ asset('js/fullcalendar.global.min.js') }}"></script>
<script>
    let calendarInstance = null;
    let calendarWireId = null;

    function formatDateTime(date) {
        const d = new Date(date);
        return d.toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showBootstrapModal(modalId) {
        if (window.AppModal) {
            window.AppModal.show(modalId);
            return;
        }

        const modalEl = document.getElementById(modalId);

        if (!modalEl || typeof bootstrap === 'undefined' || modalEl.classList.contains('show')) {
            return;
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function hideBootstrapModal(modalId) {
        if (window.AppModal) {
            window.AppModal.hide(modalId);
            return;
        }

        const modalEl = document.getElementById(modalId);

        if (!modalEl || typeof bootstrap === 'undefined') {
            return;
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    }

    function initCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        if (typeof Livewire === 'undefined') {
            document.addEventListener('livewire:init', initCalendar, { once: true });
            return;
        }

        const componentEl = calendarEl.closest('[wire\\:id]');
        if (!componentEl) return;
        const currentWireId = componentEl.getAttribute('wire:id');
        const wire = Livewire.find(currentWireId);
        if (!wire) return;

        if (calendarInstance && calendarWireId === currentWireId) {
            calendarInstance.refetchEvents();
            return;
        }

        if (calendarInstance) {
            calendarInstance.destroy();
        }

        calendarWireId = currentWireId;

        calendarInstance = new FullCalendar.Calendar(calendarEl, {
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
            dayCellContent: function(arg) {
                // Day number element
                const numberEl = document.createElement('span');
                const cleanNum = arg.dayNumberText.replace('thg', '').replace('tháng', '').replace(/[a-zA-Z]/g, '').trim();
                
                if (arg.isToday) {
                    numberEl.className = 'day-cell-today-number';
                } else {
                    numberEl.className = 'day-cell-number';
                }
                numberEl.innerText = cleanNum;
                
                // Actions container
                const rightContainer = document.createElement('div');
                rightContainer.className = 'day-cell-actions';
                
                // Check if date is in the past
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const cellDate = new Date(arg.date);
                cellDate.setHours(0, 0, 0, 0);
                const isPast = cellDate < today;

                // Plus button (only if not in the past)
                if (!isPast) {
                    @can('create', App\Models\DutySchedule::class)
                        const plusBtn = document.createElement('button');
                        plusBtn.type = 'button';
                        plusBtn.className = 'btn-plus-day';
                        plusBtn.innerHTML = '<i class="fi fi-rr-plus" style="font-size: 8px; line-height: 1;"></i>';
                        plusBtn.onclick = function(e) {
                            e.stopPropagation();
                            // Format date to YYYY-MM-DD
                            const year = arg.date.getFullYear();
                            const month = String(arg.date.getMonth() + 1).padStart(2, '0');
                            const day = String(arg.date.getDate()).padStart(2, '0');
                            const dateStr = `${year}-${month}-${day}`;
                            wire.openCreate(dateStr);
                        };
                        rightContainer.appendChild(plusBtn);
                    @endcan
                }
                
                return { domNodes: [numberEl, rightContainer] };
            },
            eventContent: function(arg) {
                const event = arg.event;
                const props = event.extendedProps;
                
                // 1. Get event time string
                let timeStr = '';
                if (event.allDay) {
                    timeStr = 'Cả ngày';
                } else if (event.start) {
                    // Check if start time is 00:00:00 and end is null or 23:59:59 (all day)
                    const isAllDayLike = event.start.getHours() === 0 && event.start.getMinutes() === 0;
                    if (isAllDayLike && (!event.end || (event.end.getHours() === 0 && event.end.getMinutes() === 0))) {
                        timeStr = 'Cả ngày';
                    } else {
                        const startHours = String(event.start.getHours()).padStart(2, '0');
                        const startMinutes = String(event.start.getMinutes()).padStart(2, '0');
                        const startStr = `${startHours}:${startMinutes}`;
                        
                        if (event.end) {
                            const endHours = String(event.end.getHours()).padStart(2, '0');
                            const endMinutes = String(event.end.getMinutes()).padStart(2, '0');
                            const endStr = `${endHours}:${endMinutes}`;
                            timeStr = `${startStr} - ${endStr}`;
                        } else {
                            timeStr = startStr;
                        }
                    }
                }
                
                // 2. Format creator & participants names list
                let namesList = [];
                if (props.creator_name) {
                    namesList.push(props.creator_name);
                }
                if (props.participants && props.participants.length > 0) {
                    props.participants.forEach(p => {
                        if (p.name !== props.creator_name) {
                            namesList.push(p.name);
                        }
                    });
                }
                const namesStr = namesList.filter(Boolean).join(', ');
                
                // 3. Create DOM structure
                const card = document.createElement('div');
                const themeClass = 'event-theme-' + (props.label_color || 'primary');
                card.className = `app-event-card ${themeClass}`;
                
                // Title element
                const titleEl = document.createElement('span');
                titleEl.className = 'app-event-title';
                titleEl.innerText = props.raw_title || event.title;
                card.appendChild(titleEl);
                
                // Meta subtitle (time • names)
                const metaEl = document.createElement('span');
                metaEl.className = 'app-event-meta';
                
                let metaText = '';
                if (timeStr && namesStr) {
                    metaText = `${timeStr} • ${namesStr}`;
                } else {
                    metaText = timeStr || namesStr || '';
                }
                metaEl.innerText = metaText;
                card.appendChild(metaEl);
                
                return { domNodes: [card] };
            },
            events: function(info, successCallback, failureCallback) {
                wire.getEvents(info.startStr, info.endStr)
                    .then(events => {
                        // Cache event counts per date
                        const counts = {};
                        events.forEach(e => {
                            if (e.start) {
                                const dateKey = e.start.substring(0, 10);
                                counts[dateKey] = (counts[dateKey] || 0) + 1;
                            }
                        });
                        
                        successCallback(events);
                    })
                    .catch(err => {
                        console.error('Error fetching calendar events:', err);
                        failureCallback(err);
                    });
            },
            dateClick: function(info) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const cellDate = new Date(info.date);
                cellDate.setHours(0, 0, 0, 0);

                if (cellDate < today) {
                    wire.showDaySchedules(info.dateStr);
                    return;
                }

                @if(auth()->user()?->hasRole(\App\Enums\RoleEnum::Director->value))
                    wire.showDaySchedules(info.dateStr);
                @else
                    @can('create', App\Models\DutySchedule::class)
                        wire.openCreate(info.dateStr);
                    @else
                        wire.showDaySchedules(info.dateStr);
                    @endcan
                @endif
            },
            eventClick: function(info) {
                const event = info.event;
                const props = event.extendedProps;

                document.getElementById('detailTitle').innerText = props.raw_title || event.title;
                document.getElementById('detailStart').innerText = event.start ? formatDateTime(event.start) : '';
                document.getElementById('detailEnd').innerText = event.end ? formatDateTime(event.end) : 'Không có';
                document.getElementById('detailLocation').innerText = props.location || 'Không có';
                document.getElementById('detailDescription').innerText = props.description || 'Không có';
                document.getElementById('detailCreator').innerText = props.creator_name || 'N/A';

                const participantsContainer = document.getElementById('detailParticipants');
                participantsContainer.innerHTML = '';
                if (props.participants && props.participants.length > 0) {
                    props.participants.forEach(p => {
                        const span = document.createElement('span');
                        span.className = 'schedule-detail-chip schedule-detail-chip-primary';
                        span.innerText = p.name;
                        participantsContainer.appendChild(span);
                    });
                } else {
                    participantsContainer.innerHTML = '<span class="schedule-detail-empty">Không có</span>';
                }
            }
        });

        calendarInstance.render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCalendar();
    });

    document.addEventListener('livewire:navigated', function () {
        initCalendar();
    });

    window.addEventListener('schedule:open-create', () => {
        showBootstrapModal('modalAddEvent');
    });

    window.addEventListener('schedule:open-edit', () => {
        showBootstrapModal('modalAddEvent');
    });

    window.addEventListener('schedule:open-day-schedules', () => {
        showBootstrapModal('daySchedulesModal');
    });

    window.addEventListener('schedule:close-day-schedules', () => {
        hideBootstrapModal('daySchedulesModal');
    });

    window.addEventListener('schedule:saved', () => {
        hideBootstrapModal('modalAddEvent');
        if (calendarInstance) {
            calendarInstance.refetchEvents();
        }
    });

    window.addEventListener('schedule:deleted', () => {
        if (calendarInstance) {
            calendarInstance.refetchEvents();
        }
    });

    window.addEventListener('schedule:filter-changed', () => {
        if (calendarInstance) {
            calendarInstance.refetchEvents();
        }
    });
</script>
@endpush

