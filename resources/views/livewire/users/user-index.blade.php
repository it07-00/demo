<div>
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Người dùng</h1>
            <p class="text-muted mb-0">Quản lý các tài khoản người dùng nội bộ và vai trò.</p>
        </div>

        @if (auth()->user()?->can('user.create'))
            <button type="button" class="btn btn-primary" wire:click="$dispatchTo('users.user-create', 'user-create:open')">
                <i class="fi fi-rr-plus me-1"></i> Thêm người dùng
            </button>
        @endif
    </div>

    <div class="card mt-3">
        <div class="card-header border-0 pb-0">
            <div class="row g-3 align-items-center">
                <div class="col-lg-5">
                    <div class="position-relative">
                        <i class="fi fi-rr-search position-absolute top-50 start-0 translate-middle-y ms-3"></i>
                        <input
                            type="search"
                            class="form-control ps-5"
                            placeholder="Tìm kiếm theo tên, username hoặc email..."
                            wire:model.live.debounce.400ms="search"
                        >
                    </div>
                </div>

                <div class="col-lg-3">
                    <select class="form-select" wire:model.live="role">
                        <option value="">Tất cả vai trò</option>
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption->name }}">{{ $roleOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <select class="form-select" wire:model.live="perPage">
                        <option value="10">10 dòng / trang</option>
                        <option value="15">15 dòng / trang</option>
                        <option value="25">25 dòng / trang</option>
                        <option value="50">50 dòng / trang</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-row-rounded">
                    <thead>
                        <tr>
                            <th scope="col">
                                <button type="button" class="btn btn-link p-0 text-body fw-semibold" wire:click="sortBy('name')">
                                    Họ và tên
                                    @if ($sortField === 'name')
                                        <i class="fi fi-rr-angle-{{ $sortDirection === 'asc' ? 'small-up' : 'small-down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th scope="col">
                                <button type="button" class="btn btn-link p-0 text-body fw-semibold" wire:click="sortBy('email')">
                                    Email
                                    @if ($sortField === 'email')
                                        <i class="fi fi-rr-angle-{{ $sortDirection === 'asc' ? 'small-up' : 'small-down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th scope="col">
                                <button type="button" class="btn btn-link p-0 text-body fw-semibold" wire:click="sortBy('username')">
                                    Username
                                    @if ($sortField === 'username')
                                        <i class="fi fi-rr-angle-{{ $sortDirection === 'asc' ? 'small-up' : 'small-down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th scope="col">Vai trò</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">
                                <button type="button" class="btn btn-link p-0 text-body fw-semibold" wire:click="sortBy('created_at')">
                                    Ngày tạo
                                    @if ($sortField === 'created_at')
                                        <i class="fi fi-rr-angle-{{ $sortDirection === 'asc' ? 'small-up' : 'small-down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr wire:key="user-row-{{ $user->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                        <span class="fw-semibold">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->username }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($user->roles as $roleItem)
                                            <span class="badge bg-primary-subtle text-primary">{{ $roleItem->name }}</span>
                                        @empty
                                            <span class="text-muted">Chưa có vai trò</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @if ($user->isLocked())
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="fi fi-rr-lock me-1"></i> Đã khóa
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fi fi-rr-check me-1"></i> Hoạt động
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                        @if (auth()->user()?->can('user.update'))
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                wire:click="$dispatchTo('users.user-edit', 'user-edit:open', { userId: {{ $user->id }} })"
                                            >
                                                <i class="fi fi-rr-edit me-1"></i> Sửa
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                wire:click="$dispatchTo('users.user-reset-password', 'user-reset-password:open', { userId: {{ $user->id }} })"
                                            >
                                                <i class="fi fi-rr-key me-1"></i> Reset
                                            </button>

                                            @if ($user->id !== auth()->id())
                                                @if ($user->isLocked())
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-success"
                                                        wire:click="unlock({{ $user->id }})"
                                                        wire:confirm="Bạn có chắc chắn muốn mở khóa tài khoản này?"
                                                    >
                                                        <i class="fi fi-rr-unlock me-1"></i> Mở khóa
                                                    </button>
                                                @else
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-warning"
                                                        wire:click="lock({{ $user->id }})"
                                                        wire:confirm="Bạn có chắc chắn muốn khóa tài khoản này?"
                                                    >
                                                        <i class="fi fi-rr-lock me-1"></i> Khóa
                                                    </button>
                                                @endif
                                            @endif
                                        @endif

                                        @if (auth()->user()?->can('user.delete'))
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="$dispatchTo('users.user-delete', 'user-delete:open', { userId: {{ $user->id }} })"
                                            >
                                                <i class="fi fi-rr-trash me-1"></i> Xóa
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    Không tìm thấy người dùng nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $users->links() }}
        </div>
    </div>

    <livewire:users.user-create />
    <livewire:users.user-edit />
    <livewire:users.user-reset-password />
    <livewire:users.user-delete />
</div>
