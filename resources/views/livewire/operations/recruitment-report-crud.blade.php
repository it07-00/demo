<div>
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5); z-index: 1060;">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fi fi-rr-chart-pie-alt me-1"></i>
                            {{ $editingId ? 'Sửa báo cáo tuyển dụng' : 'Nhập báo cáo tuyển dụng' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            {{-- Project & Date --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Dự án <span class="text-danger">*</span></label>
                                    <select class="form-select @error('formProjectId') is-invalid @enderror" wire:model.live="formProjectId">
                                        <option value="0">— Chọn dự án —</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->code }} — {{ $project->name }} ({{ $project->customer }})</option>
                                        @endforeach
                                    </select>
                                    @error('formProjectId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Ngày <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('formDate') is-invalid @enderror" wire:model="formDate">
                                    @error('formDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- Auto-filled project info --}}
                            @if ($formProjectId > 0)
                                <div class="alert alert-info py-2 mb-3">
                                    <div class="row text-sm">
                                        <div class="col-4"><strong>Chi nhánh:</strong> {{ $formBranch }}</div>
                                        <div class="col-4"><strong>KH:</strong> {{ $formCustomer }}</div>
                                        <div class="col-4"><strong>QLVH:</strong> {{ $formManager }}</div>
                                    </div>
                                </div>
                            @endif

                            {{-- Recruitment funnel numbers --}}
                            <h6 class="fw-bold mb-2"><i class="fi fi-rr-filter me-1"></i> Phễu tuyển dụng</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Đăng ký <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" wire:model="formRegistered" min="0" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Phỏng vấn</label>
                                    <input type="number" class="form-control" wire:model="formInterviewed" min="0" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Đỗ PV</label>
                                    <input type="number" class="form-control" wire:model="formPassed" min="0" placeholder="0">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-success">Đi làm</label>
                                    <input type="number" class="form-control border-success" wire:model="formStarted" min="0" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Thử việc ĐT</label>
                                    <input type="number" class="form-control" wire:model="formPartnerTrial" min="0" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Xếp hạng</label>
                                    <select class="form-select" wire:model="formRank">
                                        <option value="A">A — Tốt</option>
                                        <option value="B">B — Trung bình</option>
                                        <option value="C">C — Yếu</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Reporter & Issues --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Người báo cáo</label>
                                    <input type="text" class="form-control" wire:model="formReporter" placeholder="Tên người báo cáo">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Vấn đề phát sinh</label>
                                    <input type="text" class="form-control" wire:model="formIssues" placeholder="Nếu có...">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Hủy</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fi fi-rr-check me-1"></i> {{ $editingId ? 'Cập nhật' : 'Lưu báo cáo' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
