<div>
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Vai trò &amp; Quyền hạn</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Vai trò &amp; Quyền hạn
                    </li>
                </ol>
            </nav>
        </div>
        <button
            type="button"
            class="btn btn-primary waves-effect waves-light"
            data-bs-toggle="modal"
            data-bs-target="#addRoleModal"
        >
            <i class="fi fi-rr-plus me-1"></i> Thêm vai trò mới
        </button>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header border-bottom-0 pb-0">
                    <ul class="nav nav-underline card-header-tabs" role="tablist">
                        @foreach ($roles as $role)
                            <li class="nav-item" role="presentation" wire:key="role-tab-{{ $role->id }}">
                                <div class="d-flex align-items-center">
                                    <button
                                        class="nav-link {{ $activeRoleId === $role->id ? 'active' : '' }}"
                                        type="button"
                                        wire:click="selectRole({{ $role->id }})"
                                    >
                                        @if ($role->name === \App\Enums\RoleEnum::SuperAdmin->value)
                                            <i class="fi fi-rr-star scale-1x me-1"></i>
                                        @else
                                            <i class="fi fi-rr-user scale-1x me-1"></i>
                                        @endif
                                        {{ $role->name }}
                                    </button>
                                    @if (! \App\Enums\RoleEnum::isSystemRole($role->name))
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-link p-0 ms-1 text-muted"
                                            title="Chỉnh sửa vai trò"
                                            wire:click="openEditModal({{ $role->id }})"
                                        >
                                            <i class="fi fi-rr-edit" style="font-size: 0.75rem;"></i>
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body pt-4">
                    @if ($activeRole)
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h5 class="text-dark mb-1">
                                    Thiết lập quyền cho:
                                    <span class="text-primary fw-semibold">{{ $activeRole->name }}</span>
                                </h5>
                                <p class="text-muted mb-0">
                                    <i class="fi fi-rr-info text-primary me-1"></i>
                                    {{ $activeRole->description ?? 'Không có mô tả chi tiết cho vai trò này.' }}
                                </p>
                            </div>

                            <div class="d-flex gap-2">
                                @if (! \App\Enums\RoleEnum::isSystemRole($activeRole->name))
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary waves-effect"
                                        wire:click="openEditModal({{ $activeRole->id }})"
                                    >
                                        <i class="fi fi-rr-edit me-1"></i> Chỉnh sửa
                                    </button>
                                @endif

                                @if (! \App\Enums\RoleEnum::isSystemRole($activeRole->name))
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger waves-effect"
                                        wire:click="deleteRole({{ $activeRole->id }})"
                                        wire:confirm="Bạn có chắc chắn muốn xóa vai trò '{{ $activeRole->name }}' không? Thao tác này không thể hoàn tác!"
                                    >
                                        <i class="fi fi-rr-trash me-1"></i> Xóa vai trò
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="row g-4">
                            @foreach ($permissionsGrouped as $groupName => $permissions)
                                <div class="col-md-6 col-lg-4" wire:key="group-{{ Str::slug($groupName) }}">
                                    <div class="p-3 border rounded-3 h-100 bg-light bg-opacity-25 shadow-sm">
                                        <h6 class="mb-3 pb-2 border-bottom text-dark fw-bold">
                                            <i class="fi fi-rr-shield-check text-primary me-2"></i>{{ $groupName }}
                                        </h6>

                                        <div class="d-flex flex-column gap-2 mt-2">
                                            @foreach ($permissions as $permValue => $permLabel)
                                                <div class="form-check form-switch mb-1" wire:key="perm-{{ $permValue }}">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        id="perm_{{ Str::slug($permValue) }}"
                                                        wire:click="togglePermission('{{ $permValue }}')"
                                                        @if (in_array($permValue, $activeRolePermissions, true) || $activeRole->name === \App\Enums\RoleEnum::SuperAdmin->value) checked @endif
                                                        @if ($activeRole->name === \App\Enums\RoleEnum::SuperAdmin->value) disabled @endif
                                                    />
                                                    <label class="form-check-label text-body text-sm cursor-pointer" for="perm_{{ Str::slug($permValue) }}">
                                                        {{ $permLabel }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fi fi-rr-info scale-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">Không tìm thấy vai trò nào. Hãy thêm một vai trò mới để bắt đầu.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form wire:submit.prevent="createRole" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm vai trò mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="newRoleName">Tên vai trò <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="newRoleName"
                            wire:model.defer="newRoleName"
                            class="form-control @error('newRoleName') is-invalid @enderror"
                            placeholder="Ví dụ: Editor, Accountant,..."
                            required
                        />
                        @error('newRoleName')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="newRoleDescription">Mô tả chi tiết</label>
                        <textarea
                            id="newRoleDescription"
                            wire:model.defer="newRoleDescription"
                            class="form-control @error('newRoleDescription') is-invalid @enderror"
                            rows="3"
                            placeholder="Mô tả nhiệm vụ/quyền hạn của vai trò này"
                        ></textarea>
                        @error('newRoleDescription')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm vai trò</button>
                </div>
            </form>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form wire:submit.prevent="updateRole" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fi fi-rr-edit me-2 text-primary"></i>Chỉnh sửa vai trò</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="editRoleName">Tên vai trò <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="editRoleName"
                            wire:model.defer="editRoleName"
                            class="form-control @error('editRoleName') is-invalid @enderror"
                            placeholder="Tên vai trò"
                            required
                        />
                        @error('editRoleName')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editRoleDescription">Mô tả chi tiết</label>
                        <textarea
                            id="editRoleDescription"
                            wire:model.defer="editRoleDescription"
                            class="form-control @error('editRoleDescription') is-invalid @enderror"
                            rows="3"
                            placeholder="Mô tả nhiệm vụ/quyền hạn của vai trò này"
                        ></textarea>
                        @error('editRoleDescription')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fi fi-rr-check me-1"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('role-create:hide', () => {
            const modalEl = document.getElementById('addRoleModal');
            if (modalEl) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
        });

        window.addEventListener('role-edit:show', () => {
            const modalEl = document.getElementById('editRoleModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });

        window.addEventListener('role-edit:hide', () => {
            const modalEl = document.getElementById('editRoleModal');
            if (modalEl) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
        });
    });
</script>
@endpush
