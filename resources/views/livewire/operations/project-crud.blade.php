<div>
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5); z-index: 1060;">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fi fi-rr-briefcase me-1"></i>
                            {{ $editingId ? 'Sửa dự án' : 'Tạo dự án mới' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            {{-- Row 1: Code, Name --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Mã dự án <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('formCode') is-invalid @enderror" wire:model="formCode" placeholder="DA-039">
                                    @error('formCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label fw-semibold">Tên dự án <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('formName') is-invalid @enderror" wire:model="formName" placeholder="Goertek - Bắc Ninh (Đợt 1)">
                                    @error('formName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- Row 2: Customer, Type, Branch --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Khách hàng <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('formCustomer') is-invalid @enderror" wire:model="formCustomer" placeholder="Goertek">
                                    @error('formCustomer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Loại khách hàng</label>
                                    <select class="form-select" wire:model="formCustomerType">
                                        <option value="Trọng điểm">Trọng điểm</option>
                                        <option value="Thông thường">Thông thường</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Chi nhánh <span class="text-danger">*</span></label>
                                    <select class="form-select @error('formBranch') is-invalid @enderror" wire:model="formBranch">
                                        <option value="">— Chọn —</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch }}">{{ $branch }}</option>
                                        @endforeach
                                    </select>
                                    @error('formBranch') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- Row 3: Product, Method, Policy, Price --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Sản phẩm <span class="text-danger">*</span></label>
                                    <select class="form-select" wire:model="formProduct">
                                        <option value="">— Chọn —</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product }}">{{ $product }}</option>
                                        @endforeach
                                    </select>
                                    @error('formProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Phương thức <span class="text-danger">*</span></label>
                                    <select class="form-select" wire:model="formMethod">
                                        @foreach ($methods as $method)
                                            <option value="{{ $method }}">{{ $method }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Chính sách <span class="text-danger">*</span></label>
                                    <select class="form-select" wire:model="formPolicy">
                                        @foreach ($policies as $policy)
                                            <option value="{{ $policy }}">{{ $policy }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Đơn giá (k/giờ)</label>
                                    <input type="number" class="form-control" wire:model="formUnitPrice" min="1">
                                </div>
                            </div>

                            {{-- Row 4: Status, Demand, Actual, Progress --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Trạng thái</label>
                                    <select class="form-select" wire:model="formStatus">
                                        <option value="Đang vận hành">Đang vận hành</option>
                                        <option value="Tạm dừng">Tạm dừng</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Nhu cầu LĐ <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" wire:model="formDemand" min="0">
                                    @error('formDemand') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Hiện có</label>
                                    <input type="number" class="form-control" wire:model="formActual" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Tiến độ (0-21)</label>
                                    <input type="number" class="form-control" wire:model="formProgress" min="0" max="21">
                                </div>
                            </div>

                            {{-- Row 5: Contract dates, Recruit, Manager --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">HĐ bắt đầu <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" wire:model="formContractStart">
                                    @error('formContractStart') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">HĐ kết thúc <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" wire:model="formContractEnd">
                                    @error('formContractEnd') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Tuyển dụng</label>
                                    <select class="form-select" wire:model="formRecruitStatus">
                                        <option value="Đang tuyển">Đang tuyển</option>
                                        <option value="Dừng tuyển">Dừng tuyển</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">QLVH phụ trách</label>
                                    <select class="form-select" wire:model="formManagerName">
                                        <option value="">— Chưa phân công —</option>
                                        @foreach ($managers as $mgr)
                                            <option value="{{ $mgr->name }}">{{ $mgr->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Team members --}}
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0">Team chuyên viên</label>
                                    <button type="button" class="btn btn-sm btn-outline-success" wire:click="addTeamMember">
                                        <i class="fi fi-rr-plus me-1"></i> Thêm CVVH
                                    </button>
                                </div>
                                @foreach ($formTeam as $i => $member)
                                    <div class="row g-2 mb-2" wire:key="team-{{ $i }}">
                                        <div class="col">
                                            <select class="form-select form-select-sm" wire:model="formTeam.{{ $i }}">
                                                <option value="">— Chọn CVVH —</option>
                                                @foreach ($specialists as $spec)
                                                    <option value="{{ $spec->name }}">{{ $spec->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeTeamMember({{ $i }})">
                                                <i class="fi fi-rr-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Project documents upload section --}}
                            <div class="border-top pt-3 mt-3">
                                <h6 class="fw-bold mb-3"><i class="fi fi-rr-document me-1"></i> Hồ sơ tài liệu dự án</h6>
                                
                                <div class="row g-2 mb-3 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label text-xs fw-semibold">Chọn file tài liệu</label>
                                        <input type="file" class="form-control form-control-sm" wire:model="formDocFile">
                                        <div wire:loading wire:target="formDocFile" class="text-xs text-muted mt-1">Đang tải lên...</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-xs fw-semibold">Loại tài liệu</label>
                                        <select class="form-select form-select-sm" wire:model="formDocType">
                                            <option value="Hợp đồng">Hợp đồng</option>
                                            <option value="Bảng lương">Bảng lương</option>
                                            <option value="Tuyển dụng">Tuyển dụng</option>
                                            <option value="Khác">Khác</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary w-100"
                                            wire:click="addDocument"
                                            wire:loading.attr="disabled"
                                            wire:target="formDocFile,addDocument"
                                        >
                                            Tải lên
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tên file</th>
                                                <th>Loại</th>
                                                <th class="text-center" style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($formDocs as $index => $doc)
                                                <tr>
                                                     <td class="text-sm">
                                                         @if (isset($doc['path']))
                                                             <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank">{{ $doc['name'] }}</a>
                                                         @else
                                                             {{ $doc['name'] }}
                                                         @endif
                                                     </td>
                                                    <td class="text-sm"><span class="badge bg-secondary-subtle text-secondary">{{ $doc['type'] }}</span></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" wire:click="removeDocument({{ $index }})">
                                                            <i class="fi fi-rr-trash" style="font-size: 10px;"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-2 text-xs">Chưa có tài liệu nào được tải lên.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Hủy</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fi fi-rr-check me-1"></i> {{ $editingId ? 'Cập nhật' : 'Tạo dự án' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
