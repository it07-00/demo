<div>
    {{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Tiến độ Công việc</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tiến độ Công việc</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if ($canManage)
                <button type="button" class="btn btn-primary waves-effect waves-light" wire:click="openCreateTarget">
                    <i class="fi fi-rr-plus me-1"></i> Tạo chỉ tiêu tuần
                </button>
            @endif
        </div>
    </div>

    {{-- ── Week Navigation ──────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="previousWeek">
                        <i class="fi fi-rr-angle-left"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="currentWeek">
                        Tuần hiện tại
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="nextWeek">
                        <i class="fi fi-rr-angle-right"></i>
                    </button>
                </div>
                <div class="text-center">
                    <h5 class="mb-0 fw-bold">
                        Tuần {{ $weekStart->weekOfYear }} — {{ $weekStart->format('d/m/Y') }} → {{ $weekEnd->format('d/m/Y') }}
                    </h5>
                </div>
                <div>
                    <select class="form-select form-select-sm" wire:model.live="filterProject" style="min-width: 200px;">
                        <option value="">Tất cả dự án</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->code }} — {{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Targets List ──────────────────────────────────────────────────────── --}}
    @forelse ($targets as $target)
        <div class="card mb-3" wire:key="target-{{ $target->id }}">
            <div class="card-header bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-bold">
                        <i class="fi fi-rr-briefcase me-1 text-primary"></i>
                        {{ $target->project?->code ?? '—' }} — {{ $target->project?->name ?? '—' }}
                    </h6>
                    <small class="text-muted">KH: {{ $target->project?->customer ?? '—' }}</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-center px-3 border-end">
                        <div class="text-muted small">Nhu cầu KH</div>
                        <div class="fw-bold fs-5 text-danger">{{ number_format($target->customer_demand) }}</div>
                    </div>
                    <div class="text-center px-3 border-end">
                        <div class="text-muted small">QLVH nhận</div>
                        <div class="fw-bold fs-5 text-primary">{{ number_format($target->manager_accepted) }}</div>
                    </div>
                    <div class="text-center px-3 border-end">
                        <div class="text-muted small">Đã chia</div>
                        <div class="fw-bold fs-5 text-info">{{ number_format($target->totalAssigned()) }}</div>
                    </div>
                    <div class="text-center px-3">
                        @php
                            $totalAchieved = $target->totalAchieved();
                            $progressPct = $target->manager_accepted > 0 ? round(($totalAchieved / $target->manager_accepted) * 100, 1) : 0;
                            $progressColor = $progressPct >= 80 ? 'success' : ($progressPct >= 50 ? 'warning' : 'danger');
                        @endphp
                        <div class="text-muted small">Tiến độ</div>
                        <div class="fw-bold fs-5 text-{{ $progressColor }}">{{ $totalAchieved }}/{{ $target->manager_accepted }}</div>
                    </div>
                    @if ($canManage)
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openEditTarget({{ $target->id }})" title="Sửa">
                                <i class="fi fi-rr-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="deleteTarget({{ $target->id }})" wire:confirm="Xác nhận xóa chỉ tiêu tuần này?" title="Xóa">
                                <i class="fi fi-rr-trash"></i>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Overall progress bar --}}
            <div class="px-3 pt-2">
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-{{ $progressColor }}"
                         role="progressbar"
                         style="width: {{ min($progressPct, 100) }}%"
                         aria-valuenow="{{ $progressPct }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
                <small class="text-muted">Tổng tiến độ: {{ $progressPct }}%</small>
            </div>

            {{-- Assignments Table --}}
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="min-width: 160px;">Chuyên viên</th>
                                <th class="text-center" style="width: 80px;">Chỉ tiêu</th>
                                @foreach ($weekDays as $day)
                                    <th class="text-center {{ $day['isToday'] ? 'bg-primary-subtle' : '' }}" style="width: 90px;">
                                        {{ $day['label'] }}
                                    </th>
                                @endforeach
                                <th class="text-center" style="width: 90px;">Cộng dồn</th>
                                <th class="text-center" style="width: 90px;">Tiến độ</th>
                                <th class="text-center" style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($target->assignments as $assignment)
                                @php
                                    $aAchieved = $assignment->totalAchieved();
                                    $aPct = $assignment->progressPercent();
                                    $aColor = $aPct >= 80 ? 'success' : ($aPct >= 50 ? 'warning' : 'danger');
                                    $dailyByDate = $assignment->dailyEntries->keyBy(fn ($e) => $e->entry_date->toDateString());
                                @endphp
                                <tr wire:key="assignment-{{ $assignment->id }}">
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $assignment->user?->name ?? '—' }}</div>
                                    </td>
                                    <td class="text-center fw-bold">{{ number_format($assignment->assigned_quantity) }}</td>
                                    @foreach ($weekDays as $day)
                                        @php
                                            $entry = $dailyByDate[$day['date']] ?? null;
                                        @endphp
                                        <td class="text-center {{ $day['isToday'] ? 'bg-primary-subtle' : '' }}">
                                            @if ($entry)
                                                <span class="badge bg-{{ $entry->achieved > 0 ? 'success' : 'secondary' }}-subtle text-{{ $entry->achieved > 0 ? 'success' : 'secondary' }} px-2 py-1 cursor-pointer"
                                                      wire:click="openEditEntry({{ $entry->id }})"
                                                      title="{{ $entry->note ?? 'Click để sửa' }}"
                                                      style="cursor: pointer;">
                                                    {{ $entry->achieved }}
                                                </span>
                                            @elseif ($day['date'] <= now()->toDateString())
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                        wire:click="openEntryModal({{ $assignment->id }}, '{{ $day['date'] }}')"
                                                        title="Nhập tiến độ ngày {{ $day['label'] }}">
                                                    <i class="fi fi-rr-plus" style="font-size: 10px;"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-bold text-{{ $aColor }}">{{ $aAchieved }}</td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $aColor }}" style="width: {{ min($aPct, 100) }}%;"></div>
                                            </div>
                                            <small class="fw-bold text-{{ $aColor }}">{{ $aPct }}%</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary py-0 px-2"
                                                wire:click="openEntryModal({{ $assignment->id }})"
                                                title="Nhập tiến độ">
                                            <i class="fi fi-rr-pencil" style="font-size: 11px;"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fi fi-rr-clipboard-list-check text-muted" style="font-size: 48px;"></i>
                <h5 class="text-muted mt-3">Chưa có chỉ tiêu nào cho tuần này</h5>
                @if ($canManage)
                    <p class="text-muted">Nhấn <strong>"Tạo chỉ tiêu tuần"</strong> để bắt đầu thiết lập chỉ tiêu cho các dự án.</p>
                @endif
            </div>
        </div>
    @endforelse

    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    {{-- TARGET MODAL --}}
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    @if ($showTargetModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fi fi-rr-target me-1"></i>
                            {{ $editingTargetId ? 'Sửa chỉ tiêu tuần' : 'Tạo chỉ tiêu tuần' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showTargetModal', false)"></button>
                    </div>
                    <form wire:submit="saveTarget">
                        <div class="modal-body">
                            {{-- Week info --}}
                            <div class="alert alert-info py-2 mb-3">
                                <i class="fi fi-rr-calendar me-1"></i>
                                Tuần {{ $weekStart->weekOfYear }}: {{ $weekStart->format('d/m/Y') }} → {{ $weekEnd->format('d/m/Y') }}
                            </div>

                            {{-- Project --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="formProjectId">Dự án <span class="text-danger">*</span></label>
                                <select class="form-select" id="formProjectId" wire:model="formProjectId">
                                    <option value="0">— Chọn dự án —</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->code }} — {{ $project->name }} ({{ $project->customer }})</option>
                                    @endforeach
                                </select>
                                @error('formProjectId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="formCustomerDemand">Nhu cầu KH (tuần) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="formCustomerDemand" wire:model="formCustomerDemand" min="1" placeholder="VD: 500">
                                    @error('formCustomerDemand') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="formManagerAccepted">QLVH nhận <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="formManagerAccepted" wire:model="formManagerAccepted" min="1" placeholder="VD: 200">
                                    @error('formManagerAccepted') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- Assignments --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-semibold mb-0">Phân chia cho Chuyên viên <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info" wire:click="splitEvenly" title="Chia đều cho tất cả CVVH">
                                            <i class="fi fi-rr-arrows-h me-1"></i> Chia đều
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success" wire:click="addAssignment">
                                            <i class="fi fi-rr-plus me-1"></i> Thêm CVVH
                                        </button>
                                    </div>
                                </div>
                                @error('formAssignments') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                                @foreach ($formAssignments as $i => $fa)
                                    <div class="row g-2 mb-2 align-items-end" wire:key="fa-{{ $i }}">
                                        <div class="col-md-6">
                                            <select class="form-select form-select-sm" wire:model="formAssignments.{{ $i }}.user_id">
                                                <option value="0">— Chọn CVVH —</option>
                                                @foreach ($specialists as $spec)
                                                    <option value="{{ $spec->id }}">{{ $spec->name }} {{ $spec->operation_branch ? '('.$spec->operation_branch.')' : '' }}</option>
                                                @endforeach
                                            </select>
                                            @error("formAssignments.{$i}.user_id") <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" class="form-control form-control-sm" wire:model="formAssignments.{{ $i }}.quantity" min="1" placeholder="Số lượng">
                                            @error("formAssignments.{$i}.quantity") <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100" wire:click="removeAssignment({{ $i }})">
                                                <i class="fi fi-rr-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                @if (count($formAssignments) === 0)
                                    <div class="text-muted small text-center py-2 border rounded">
                                        Chưa có CVVH nào. Nhấn <strong>"Thêm CVVH"</strong> để phân công.
                                    </div>
                                @endif

                                @php
                                    $totalAssigned = array_sum(array_column($formAssignments, 'quantity'));
                                @endphp
                                @if (count($formAssignments) > 0)
                                    <div class="mt-2 d-flex justify-content-between">
                                        <small class="text-muted">Tổng đã chia: <strong>{{ number_format($totalAssigned) }}</strong></small>
                                        @if ($formManagerAccepted > 0 && $totalAssigned != $formManagerAccepted)
                                            <small class="text-danger fw-semibold">
                                                Chênh lệch: {{ number_format($formManagerAccepted - $totalAssigned) }}
                                            </small>
                                        @elseif ($formManagerAccepted > 0 && $totalAssigned == $formManagerAccepted)
                                            <small class="text-success fw-semibold">
                                                <i class="fi fi-rr-check me-1"></i> Khớp!
                                            </small>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="$set('showTargetModal', false)">Hủy</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fi fi-rr-check me-1"></i> Lưu chỉ tiêu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    {{-- DAILY ENTRY MODAL --}}
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    @if ($showEntryModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fi fi-rr-edit me-1"></i>
                            {{ $editingEntryId ? 'Sửa tiến độ ngày' : 'Nhập tiến độ ngày' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showEntryModal', false)"></button>
                    </div>
                    <form wire:submit="saveEntry">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="entryDate">Ngày <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="entryDate" wire:model="entryDate">
                                @error('entryDate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="entryAchieved">Số đạt được <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="entryAchieved" wire:model="entryAchieved" min="0" placeholder="VD: 25">
                                @error('entryAchieved') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="entryNote">Ghi chú</label>
                                <textarea class="form-control" id="entryNote" wire:model="entryNote" rows="2" placeholder="Ghi chú (nếu có)"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="$set('showEntryModal', false)">Hủy</button>
                            @if ($editingEntryId)
                                <button type="button" class="btn btn-outline-danger" wire:click="deleteEntry({{ $editingEntryId }})" wire:confirm="Xóa bản ghi này?">
                                    <i class="fi fi-rr-trash me-1"></i> Xóa
                                </button>
                            @endif
                            <button type="submit" class="btn btn-primary">
                                <i class="fi fi-rr-check me-1"></i> Lưu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
