<div>
    <div
        wire:ignore.self
        class="modal fade"
        id="userCreateModal"
        tabindex="-1"
        aria-labelledby="userCreateModalLabel"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit="save">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userCreateModalLabel">Tạo người dùng mới</h5>
                        <button type="button" class="btn-close" wire:click="close" aria-label="Đóng"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="create-name">Họ và tên</label>
                                <input id="create-name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="create-username">Username</label>
                                <input id="create-username" type="text" class="form-control @error('username') is-invalid @enderror" wire:model.defer="username" autocomplete="username">
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="create-email">Địa chỉ Email</label>
                                <input id="create-email" type="email" class="form-control @error('email') is-invalid @enderror" wire:model.defer="email">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="create-password">Mật khẩu</label>
                                <div class="input-group">
                                    <input id="create-password" type="password" class="form-control @error('password') is-invalid @enderror" wire:model.defer="password">
                                    <button type="button" class="btn btn-outline-secondary" data-password-toggle="#create-password" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                                        <i class="fi fi-rr-eye" data-password-toggle-icon></i>
                                    </button>
                                </div>
                                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="create-password-confirmation">Xác nhận mật khẩu</label>
                                <div class="input-group">
                                    <input id="create-password-confirmation" type="password" class="form-control" wire:model.defer="password_confirmation">
                                    <button type="button" class="btn btn-outline-secondary" data-password-toggle="#create-password-confirmation" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                                        <i class="fi fi-rr-eye" data-password-toggle-icon></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Vai trò</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($roleOptions as $roleOption)
                                        <div class="form-check" wire:key="create-role-{{ $roleOption->id }}">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="create-role-{{ $roleOption->id }}"
                                                value="{{ $roleOption->name }}"
                                                wire:model.defer="roles"
                                            >
                                            <label class="form-check-label" for="create-role-{{ $roleOption->id }}">
                                                {{ $roleOption->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('roles') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @error('roles.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="close">Hủy</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            Lưu lại
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
