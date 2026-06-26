<div>
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5); z-index: 1060;">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <!-- Header -->
                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title fw-bold">
                            <i class="fi fi-rr-user-gear me-2"></i>
                            @if ($userId)
                                Thiết lập nhân sự & Phân công: {{ $selectedName }}
                            @else
                                Thêm nhân sự & Phân công vận hành
                            @endif
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="$set('showModal', false)"></button>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="bg-light border-bottom px-3 pt-2">
                        <ul class="nav nav-tabs border-bottom-0">
                            <li class="nav-item">
                                <button type="button" class="nav-link {{ $activeTab === 'info' ? 'active fw-bold' : '' }}" wire:click="switchTab('info')">
                                    <i class="fi fi-rr-id-badge me-1"></i> Thông tin chung
                                </button>
                            </li>
                            @if ($formRole !== 'None' && $formRole !== '')
                                <li class="nav-item">
                                    <button type="button" class="nav-link {{ $activeTab === 'projects' ? 'active fw-bold' : '' }}" wire:click="switchTab('projects')">
                                        <i class="fi fi-rr-briefcase me-1"></i>
                                        @if ($formRole === 'Quản lý vận hành')
                                            Cụm dự án quản lý
                                        @else
                                            Dự án triển khai
                                        @endif
                                        <span class="badge bg-primary ms-1">{{ count($assignedProjects) }}</span>
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <!-- Body -->
                    <div class="modal-body py-3" style="max-height: calc(100vh - 280px);">
                        @if ($activeTab === 'info')
                            <!-- Info Tab -->
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Nhân viên</label>
                                    @if ($userId)
                                        <input type="text" class="form-control bg-light" value="{{ $selectedName }}" readonly>
                                    @else
                                        <select wire:model.live="selectedName" class="form-select @error('selectedName') is-invalid @enderror">
                                            <option value="">-- Chọn nhân viên --</option>
                                            @foreach ($users as $u)
                                                @if (!$u->operation_role)
                                                    <option value="{{ $u->name }}">{{ $u->name }} ({{ $u->email }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('selectedName')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Vai trò vận hành</label>
                                    <select wire:model.live="formRole" class="form-select @error('formRole') is-invalid @enderror">
                                        <option value="None">Không tham gia vận hành (None)</option>
                                        <option value="Quản lý vận hành">Quản lý vận hành (QLVH)</option>
                                        <option value="Chuyên viên vận hành">Chuyên viên vận hành (CVVH)</option>
                                    </select>
                                    @error('formRole')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Chi nhánh phụ trách</label>
                                    <select wire:model="formBranch" class="form-select @error('formBranch') is-invalid @enderror" {{ $formRole === 'None' ? 'disabled' : '' }}>
                                        <option value="">-- Chọn chi nhánh --</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch }}">{{ $branch }}</option>
                                        @endforeach
                                    </select>
                                    @error('formBranch')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Trạng thái nhân sự</label>
                                    <select wire:model="formStatus" class="form-select @error('formStatus') is-invalid @enderror">
                                        <option value="Chính thức">Chính thức</option>
                                        <option value="Thử việc">Thử việc</option>
                                        <option value="Cộng tác viên">Cộng tác viên</option>
                                    </select>
                                    @error('formStatus')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @elseif ($activeTab === 'projects')
                            <!-- Projects Tab -->
                            <div class="mb-3">
                                <div class="alert alert-info py-2 px-3 text-sm mb-3">
                                    <i class="fi fi-rr-info me-1"></i>
                                    @if ($formRole === 'Quản lý vận hành')
                                        Chọn các dự án mà <strong>{{ $selectedName }}</strong> sẽ trực tiếp chịu trách nhiệm quản lý chính.
                                    @else
                                        Chọn các dự án mà <strong>{{ $selectedName }}</strong> được phân công tham gia triển khai/tuyển dụng.
                                    @endif
                                </div>

                                <div class="row g-2">
                                    @forelse ($projects as $project)
                                        <div class="col-md-6">
                                            <div class="border rounded-3 p-3 h-100 bg-white shadow-xs hover-bg-light transition-all">
                                                <div class="form-check d-flex align-items-start gap-2 mb-0">
                                                    <input class="form-check-input mt-1" type="checkbox" 
                                                           value="{{ $project->id }}" 
                                                           id="project-{{ $project->id }}"
                                                           wire:model.live="assignedProjects">
                                                    <label class="form-check-label w-100 text-sm cursor-pointer" for="project-{{ $project->id }}">
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <strong class="text-dark">{{ $project->code }}</strong>
                                                            <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $project->branch }}</span>
                                                        </div>
                                                        <div class="text-muted text-xs mb-1 text-truncate" title="{{ $project->name }}">{{ $project->name }}</div>
                                                        <div class="text-muted text-xs">
                                                            @if ($formRole === 'Quản lý vận hành')
                                                                QL hiện tại: <span class="fw-semibold">{{ $project->manager_name }}</span>
                                                            @else
                                                                QL: {{ $project->manager_name }} | CV: {{ is_array($project->team) ? count($project->team) : 0 }}
                                                            @endif
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-center text-muted py-4">Không có dự án nào.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-light" wire:click="$set('showModal', false)">
                            Đóng
                        </button>
                        <button type="button" class="btn btn-primary px-4" wire:click="save">
                            <span wire:loading class="spinner-border spinner-border-sm me-1"></span>
                            Lưu cấu hình
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
